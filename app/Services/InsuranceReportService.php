<?php

namespace App\Services;

use App\Models\InsuranceChangeRecord;
use App\Models\InsuranceMonthlyContribution;
use App\Models\InsuranceMonthlyContributionItem;
use App\Models\InsuranceMonthlyReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsuranceReportService
{
    protected InsuranceCalculationService $calculationService;
    protected InsuranceDeclarationService $declarationService;
    protected InsuranceContributionCalculatorService $contributionCalculator;

    public function __construct(
        InsuranceCalculationService $calculationService,
        InsuranceDeclarationService $declarationService,
        InsuranceContributionCalculatorService $contributionCalculator
    ) {
        $this->calculationService = $calculationService;
        $this->declarationService = $declarationService;
        $this->contributionCalculator = $contributionCalculator;
    }

    /**
     * Generate monthly report (DRAFT status)
     * Auto-calculate all INCREASE/DECREASE/ADJUST records
     */
    public function generateMonthlyReport(int $year, int $month): InsuranceMonthlyReport
    {
        // Check if report already exists
        $existing = InsuranceMonthlyReport::where('year', $year)
            ->where('month', $month)
            ->first();

        if ($existing) {
            throw new \Exception("Báo cáo tháng {$month}/{$year} đã tồn tại");
        }

        DB::beginTransaction();
        try {
            // Calculate changes
            $changes = $this->calculationService->calculateMonthlyChanges($year, $month);

            // Create report
            $report = InsuranceMonthlyReport::create([
                'year' => $year,
                'month' => $month,
                'total_increase' => $changes['increase']->count(),
                'total_decrease' => $changes['decrease']->count(),
                'total_adjust' => $changes['adjust']->count(),
                'approved_increase' => 0,
                'approved_decrease' => 0,
                'approved_adjust' => 0,
                'total_insurance_salary' => 0,
                'status' => InsuranceMonthlyReport::STATUS_DRAFT,
            ]);

            // Create change records (PENDING approval)
            $totalSalary = 0;

            foreach ($changes['increase'] as $change) {
                $this->createChangeRecord($report, $change);
                $totalSalary += $change['insurance_salary'];
            }

            foreach ($changes['decrease'] as $change) {
                $this->createChangeRecord($report, $change);
                $totalSalary += $change['insurance_salary'];
            }

            foreach ($changes['adjust'] as $change) {
                $this->createChangeRecord($report, $change);
                $totalSalary += $change['insurance_salary'];
            }

            $report->update(['total_insurance_salary' => $totalSalary]);

            // Log activity
            activity()
                ->useLog('insurance-report')
                ->performedOn($report)
                ->log("Tạo báo cáo BH tháng {$month}/{$year}");

            DB::commit();
            return $report;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error generating insurance report: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Create change record from calculated data
     */
    protected function createChangeRecord(InsuranceMonthlyReport $report, array $changeData): InsuranceChangeRecord
    {
        $employee = $changeData['employee'];

        // Get insurance participation info
        $participation = $employee->insuranceParticipations()
            ->where('status', 'ACTIVE')
            ->latest()
            ->first();

        // Calculate declaration month based on effective date
        $effectiveDate = Carbon::parse($changeData['effective_date']);
        $suggestedMonth = $this->declarationService->suggestDeclarationMonth($effectiveDate);

        return InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => $changeData['change_type'],
            'insurance_salary' => $changeData['insurance_salary'],
            'has_social_insurance' => $participation->has_social_insurance ?? true,
            'has_health_insurance' => $participation->has_health_insurance ?? true,
            'has_unemployment_insurance' => $participation->has_unemployment_insurance ?? true,
            'auto_reason' => $changeData['auto_reason'],
            'system_notes' => $changeData['system_notes'],
            'effective_date' => $changeData['effective_date'],
            'contract_id' => $changeData['contract_id'] ?? null,
            'contract_appendix_id' => $changeData['contract_appendix_id'] ?? null,
            'leave_request_id' => $changeData['leave_request_id'] ?? null,
            'approval_status' => InsuranceChangeRecord::APPROVAL_PENDING,
            'suggested_declaration_month' => $suggestedMonth,
            'declaration_month' => $suggestedMonth, // Default to suggested, can be overridden later
        ]);
    }

    /**
     * Approve a change record
     */
    public function approveRecord(InsuranceChangeRecord $record, User $admin, ?string $adminNotes = null): bool
    {
        if (!$record->isPending()) {
            throw new \Exception('Record không ở trạng thái chờ duyệt');
        }

        if ($record->report->isFinalized()) {
            throw new \Exception('Báo cáo đã hoàn tất, không thể duyệt');
        }

        DB::beginTransaction();
        try {
            // ✅ Require reason when declaration_month differs from effective month
            $effectiveMonth = Carbon::parse($record->effective_date)->format('Y-m');
            if ($record->declaration_month !== $effectiveMonth && empty($record->declaration_override_reason)) {
                throw new \Exception('Vui lòng nhập lý do thay đổi tháng kê khai (do khác tháng của Ngày hiệu lực).');
            }

            $oldReport = $record->report;
            $oldReportMonth = $oldReport->year . '-' . str_pad($oldReport->month, 2, '0', STR_PAD_LEFT);

            $record->update([
                'approval_status' => InsuranceChangeRecord::APPROVAL_APPROVED,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'admin_notes' => $adminNotes,
            ]);

            // ✅ Auto move after approving if declaration_month != report_month
            $recordFresh = $record->fresh();
            if (!empty($recordFresh->declaration_month) && $recordFresh->declaration_month !== $oldReportMonth) {
                $this->declarationService->moveRecordToReport(
                    $recordFresh,
                    $recordFresh->declaration_month,
                    $oldReport
                );
            }

            // Update counters for involved reports
            $this->updateReportCounters($oldReport);
            if ($recordFresh->declaration_month && $recordFresh->declaration_month !== $oldReportMonth) {
                $newReport = $this->declarationService->getOrCreateReportForMonth($recordFresh->declaration_month);
                $this->updateReportCounters($newReport);
            }

            activity()
                ->useLog('insurance-approval')
                ->performedOn($record)
                ->causedBy($admin)
                ->withProperties([
                    'employee_name' => $record->employee->full_name,
                    'change_type' => $record->change_type,
                ])
                ->log('Duyệt thay đổi BH');

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error approving record: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Reject a change record
     */
    public function rejectRecord(InsuranceChangeRecord $record, User $admin, string $reason): bool
    {
        if (!$record->isPending()) {
            throw new \Exception('Record không ở trạng thái chờ duyệt');
        }

        if ($record->report->isFinalized()) {
            throw new \Exception('Báo cáo đã hoàn tất, không thể từ chối');
        }

        DB::beginTransaction();
        try {
            $record->update([
                'approval_status' => InsuranceChangeRecord::APPROVAL_REJECTED,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'admin_notes' => $reason,
            ]);

            // Update report counters
            $this->updateReportCounters($record->report);

            // Log activity
            activity()
                ->useLog('insurance-approval')
                ->performedOn($record)
                ->causedBy($admin)
                ->withProperties([
                    'employee_name' => $record->employee->full_name,
                    'change_type' => $record->change_type,
                    'reason' => $reason,
                ])
                ->log('Từ chối thay đổi BH');

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error rejecting record: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Adjust a change record (Admin modifies salary)
     */
    public function adjustRecord(InsuranceChangeRecord $record, User $admin, array $adjustedData, ?string $adminNotes = null): bool
    {
        if (!$record->isPending()) {
            throw new \Exception('Record không ở trạng thái chờ duyệt');
        }

        if ($record->report->isFinalized()) {
            throw new \Exception('Báo cáo đã hoàn tất, không thể điều chỉnh');
        }

        DB::beginTransaction();
        try {
            $oldReport = $record->report;
            $oldReportMonth = $oldReport->year . '-' . str_pad($oldReport->month, 2, '0', STR_PAD_LEFT);

            // ✅ Enforce requirement:
            // Reason is required when official declaration month differs from effective month (NOT suggested month)
            $effectiveMonth = Carbon::parse($record->effective_date)->format('Y-m');
            $currentDeclarationMonth = $record->declaration_month;

            if (!empty($currentDeclarationMonth) && $currentDeclarationMonth !== $effectiveMonth) {
                if (empty($record->declaration_override_reason)) {
                    throw new \Exception('Vui lòng nhập lý do thay đổi tháng kê khai (do khác tháng của Ngày hiệu lực).');
                }
            }

            // --- Apply adjustments to record fields ---
            // Tuỳ code bạn đang cho phép adjust fields gì: change_type, insurance_salary, components, etc.
            // Giữ style "update theo adjustedData", không tự ý thêm field lạ.
            $record->update(array_merge($adjustedData, [
                'approval_status' => InsuranceChangeRecord::APPROVAL_ADJUSTED,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'admin_notes' => $adminNotes,
            ]));

            // ✅ Auto move after adjusting if declaration_month != report_month
            $recordFresh = $record->fresh();

            if (!empty($recordFresh->declaration_month) && $recordFresh->declaration_month !== $oldReportMonth) {
                $this->declarationService->moveRecordToReport(
                    $recordFresh,
                    $recordFresh->declaration_month,
                    $oldReport
                );
            }

            // Update counters for involved reports
            $this->updateReportCounters($oldReport);
            if (!empty($recordFresh->declaration_month) && $recordFresh->declaration_month !== $oldReportMonth) {
                $newReport = $this->declarationService->getOrCreateReportForMonth($recordFresh->declaration_month);
                $this->updateReportCounters($newReport);
            }

            activity()
                ->useLog('insurance-approval')
                ->performedOn($record)
                ->causedBy($admin)
                ->withProperties([
                    'employee_name' => $record->employee->full_name,
                    'change_type' => $record->change_type,
                    'action' => 'adjust',
                    'old_report_month' => $oldReportMonth,
                    'new_report_month' => $recordFresh->declaration_month,
                    'effective_month' => $effectiveMonth,
                    'declaration_override_reason' => $recordFresh->declaration_override_reason,
                    'adjusted_data' => $adjustedData,
                ])
                ->log('Điều chỉnh thay đổi BH');

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error adjusting record: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Update declaration month for a change record
     * Allows reviewer to override suggested declaration month with reason
     * If declaration_month changes to different report month, moves record to appropriate report
     */
    public function updateDeclarationMonth(
    InsuranceChangeRecord $record,
    User $admin,
    string $declarationMonth,
    ?string $overrideReason = null
    ): bool {
        if ($record->report->isFinalized()) {
            throw new \Exception('Báo cáo đã hoàn tất, không thể thay đổi tháng đóng BHXH');
        }

        DB::beginTransaction();
        try {
            $oldDeclarationMonth = $record->declaration_month;
            $oldReport = $record->report;

            // ✅ Enforce requirement in service too
            $effectiveMonth = Carbon::parse($record->effective_date)->format('Y-m');
            if ($declarationMonth !== $effectiveMonth && empty($overrideReason)) {
                throw new \Exception('Vui lòng nhập lý do thay đổi tháng kê khai (do khác tháng của Ngày hiệu lực).');
            }

            $record->update([
                'declaration_month' => $declarationMonth,
                'declaration_set_by' => $admin->id,
                'declaration_set_at' => now(),
                'declaration_override_reason' => $overrideReason,
            ]);

            // ✅ Move if record no longer belongs to old report month
            if (!$this->declarationService->canRecordBelongToReport($record->fresh(), $oldReport)) {
                $newReport = $this->declarationService->moveRecordToReport(
                    $record->fresh(),
                    $declarationMonth,
                    $oldReport
                );

                activity()
                    ->useLog('insurance-declaration')
                    ->performedOn($record)
                    ->causedBy($admin)
                    ->withProperties([
                        'employee_name' => $record->employee->full_name,
                        'old_report' => $oldReport->year . '-' . str_pad($oldReport->month, 2, '0', STR_PAD_LEFT),
                        'new_report' => $newReport->year . '-' . str_pad($newReport->month, 2, '0', STR_PAD_LEFT),
                    ])
                    ->log('Di chuyển record sang báo cáo khác');
            }

            activity()
                ->useLog('insurance-declaration')
                ->performedOn($record)
                ->causedBy($admin)
                ->withProperties([
                    'employee_name' => $record->employee->full_name,
                    'suggested_month' => $record->suggested_declaration_month,
                    'old_declaration_month' => $oldDeclarationMonth,
                    'new_declaration_month' => $declarationMonth,
                    'override_reason' => $overrideReason,
                ])
                ->log('Cập nhật tháng đóng BHXH');

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating declaration month: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Finalize report (lock it, no more changes allowed)
     */
    public function finalizeReport(InsuranceMonthlyReport $report, User $admin): bool
    {
        if ($report->isFinalized()) {
            throw new \Exception('Báo cáo đã được hoàn tất');
        }

        // Check if all records are approved
        $pendingCount = $report->pendingRecords()->count();
        if ($pendingCount > 0) {
            throw new \Exception("Còn {$pendingCount} record chưa được duyệt");
        }

        // Validate declaration months match report month
        $validation = $this->declarationService->validateDeclarationMonth($report);
        if (!$validation['valid']) {
            $errorMessages = implode("\n", $validation['errors']);
            throw new \Exception(
                "Không thể hoàn tất báo cáo: Có " . count($validation['errors']) .
                " record với tháng đóng BHXH không khớp với tháng báo cáo.\n\n" .
                $errorMessages
            );
        }

        DB::beginTransaction();
        try {
            // Generate contribution snapshots before finalizing
            $this->generateSnapshotContributions($report);

            $report->update([
                'status' => InsuranceMonthlyReport::STATUS_FINALIZED,
                'finalized_by' => $admin->id,
                'finalized_at' => now(),
            ]);

            // Log activity
            activity()
                ->useLog('insurance-report')
                ->performedOn($report)
                ->causedBy($admin)
                ->log("Hoàn tất báo cáo BH tháng {$report->month}/{$report->year}");

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error finalizing report: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Update report approval counters
     */
    protected function updateReportCounters(InsuranceMonthlyReport $report): void
    {
        $report->update([
            'approved_increase' => $report->increaseRecords()->whereIn('approval_status', [
                InsuranceChangeRecord::APPROVAL_APPROVED,
                InsuranceChangeRecord::APPROVAL_ADJUSTED,
            ])->count(),
            'approved_decrease' => $report->decreaseRecords()->whereIn('approval_status', [
                InsuranceChangeRecord::APPROVAL_APPROVED,
                InsuranceChangeRecord::APPROVAL_ADJUSTED,
            ])->count(),
            'approved_adjust' => $report->adjustRecords()->whereIn('approval_status', [
                InsuranceChangeRecord::APPROVAL_APPROVED,
                InsuranceChangeRecord::APPROVAL_ADJUSTED,
            ])->count(),
        ]);
    }

    /**
     * Generate contribution snapshots for all approved employees
     * Called during report finalization - creates immutable snapshot of contributions
     * Idempotent: deletes existing snapshots before regenerating
     *
     * @param InsuranceMonthlyReport $report
     * @return int Number of contributions generated
     * @throws \Exception
     */
    public function generateSnapshotContributions(InsuranceMonthlyReport $report): int
    {
        // Delete existing snapshots for this report (idempotent)
        InsuranceMonthlyContribution::where('report_id', $report->id)->delete();

        // Get declaration month for calculation
        $declarationMonth = sprintf('%04d-%02d', $report->year, $report->month);

        // Get all approved change records
        $approvedRecords = $report->changeRecords()
            ->whereIn('approval_status', [
                InsuranceChangeRecord::APPROVAL_APPROVED,
                InsuranceChangeRecord::APPROVAL_ADJUSTED,
            ])
            ->with('employee')
            ->get();

        if ($approvedRecords->isEmpty()) {
            Log::warning("No approved records found for report {$report->id}");
            return 0;
        }

        $contributionCount = 0;
        $errors = [];

        foreach ($approvedRecords as $record) {
            try {
                // Calculate contributions using the calculator service
                $calculation = $this->contributionCalculator->calculateForEmployee(
                    $record->employee,
                    $declarationMonth
                );

                // Create parent contribution record
                $contribution = InsuranceMonthlyContribution::create([
                    'report_id' => $report->id,
                    'employee_id' => $record->employee->id,
                    'change_record_id' => $record->id,
                    'base_insurance_salary' => $calculation['base_insurance_salary'],
                    'total_amount' => $calculation['total_amount'],
                ]);

                // Create contribution items for each component
                foreach ($calculation['components'] as $componentData) {
                    InsuranceMonthlyContributionItem::create([
                        'contribution_id' => $contribution->id,
                        'component_id' => $componentData['component_id'],
                        'component_code' => $componentData['component_code'],
                        'component_name' => $componentData['component_name'],
                        'base_type' => $componentData['base_type'],
                        'base_used' => $componentData['base_used'],
                        'rate_total' => $componentData['rate_total'],
                        'amount' => $componentData['amount'],
                    ]);
                }

                $contributionCount++;
            } catch (\Exception $e) {
                $errors[] = "Employee {$record->employee->full_name}: {$e->getMessage()}";
                Log::error("Failed to generate contribution snapshot for employee {$record->employee->id}: {$e->getMessage()}");
            }
        }

        // If we had errors, throw exception with details
        if (!empty($errors)) {
            $errorMessage = "Lỗi khi tạo snapshot đóng BHXH:\n" . implode("\n", $errors);
            throw new \Exception($errorMessage);
        }

        Log::info("Generated {$contributionCount} contribution snapshots for report {$report->id}");
        return $contributionCount;
    }

    /**
     * Delete report (only if DRAFT and no approved records)
     */
    public function deleteReport(InsuranceMonthlyReport $report): bool
    {
        if ($report->isFinalized()) {
            throw new \Exception('Không thể xóa báo cáo đã hoàn tất');
        }

        $approvedCount = $report->changeRecords()
            ->whereIn('approval_status', [
                InsuranceChangeRecord::APPROVAL_APPROVED,
                InsuranceChangeRecord::APPROVAL_ADJUSTED,
            ])
            ->count();

        if ($approvedCount > 0) {
            throw new \Exception('Không thể xóa báo cáo có record đã duyệt');
        }

        DB::beginTransaction();
        try {
            // Delete all records first
            $report->changeRecords()->delete();

            // Delete report
            $report->delete();

            // Log activity
            activity()
                ->useLog('insurance-report')
                ->log("Xóa báo cáo BH tháng {$report->month}/{$report->year}");

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting report: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Export report data from snapshot (only for FINALIZED reports)
     * Returns structured data ready for Excel/CSV export
     *
     * @param InsuranceMonthlyReport $report
     * @return array [
     *     'report_info' => [...],
     *     'employees' => [...],
     *     'summary' => [...]
     * ]
     * @throws \Exception if report is not finalized or has no snapshots
     */
    public function exportReport(InsuranceMonthlyReport $report): array
    {
        if (!$report->isFinalized()) {
            throw new \Exception('Chỉ có thể export báo cáo đã hoàn tất');
        }

        // Get all contribution snapshots with items
        $contributions = InsuranceMonthlyContribution::where('report_id', $report->id)
            ->with(['employee', 'items.component', 'changeRecord'])
            ->orderBy('employee_id')
            ->get();

        if ($contributions->isEmpty()) {
            throw new \Exception('Báo cáo chưa có dữ liệu snapshot đóng BHXH');
        }

        // Report metadata
        $reportInfo = [
            'year' => $report->year,
            'month' => $report->month,
            'report_month' => sprintf('%04d-%02d', $report->year, $report->month),
            'total_employees' => $contributions->count(),
            'finalized_by' => $report->finalizedBy->full_name ?? null,
            'finalized_at' => $report->finalized_at?->format('d/m/Y H:i'),
            'total_increase' => $report->total_increase,
            'total_decrease' => $report->total_decrease,
            'total_adjust' => $report->total_adjust,
            'total_insurance_salary' => $report->total_insurance_salary,
        ];

        // Get all unique components from snapshots
        $allComponents = InsuranceMonthlyContributionItem::whereIn(
            'contribution_id',
            $contributions->pluck('id')
        )->distinct('component_code')
            ->orderBy('component_id')
            ->get(['component_id', 'component_code', 'component_name'])
            ->keyBy('component_code');

        // Employee rows with contributions breakdown
        $employeeRows = [];
        $summaryByComponent = [];

        foreach ($contributions as $contribution) {
            $employee = $contribution->employee;
            $changeRecord = $contribution->changeRecord;

            $row = [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'employee_name' => $employee->full_name,
                'department' => $employee->department->name ?? '',
                'position' => $employee->position->name ?? '',
                'change_type' => $changeRecord->change_type ?? '',
                'effective_date' => $changeRecord->effective_date?->format('d/m/Y') ?? '',
                'declaration_month' => $changeRecord->declaration_month ?? '',
                'base_insurance_salary' => $contribution->base_insurance_salary,
                'components' => [],
                'total_contribution' => $contribution->total_amount,
            ];

            // Add each component amount
            foreach ($contribution->items as $item) {
                $row['components'][$item->component_code] = [
                    'code' => $item->component_code,
                    'name' => $item->component_name,
                    'base_type' => $item->base_type,
                    'base_amount' => $item->base_amount,
                    'rate' => $item->rate_total,
                    'amount' => $item->contribution_amount,
                ];

                // Accumulate summary
                if (!isset($summaryByComponent[$item->component_code])) {
                    $summaryByComponent[$item->component_code] = [
                        'component_name' => $item->component_name,
                        'total_amount' => 0,
                        'employee_count' => 0,
                    ];
                }
                $summaryByComponent[$item->component_code]['total_amount'] += $item->contribution_amount;
                $summaryByComponent[$item->component_code]['employee_count']++;
            }

            $employeeRows[] = $row;
        }

        // Summary totals
        $summary = [
            'total_employees' => $contributions->count(),
            'total_base_salary' => $contributions->sum('base_insurance_salary'),
            'total_contribution' => $contributions->sum('total_amount'),
            'by_component' => $summaryByComponent,
            'by_change_type' => [
                'INCREASE' => $contributions->filter(fn($c) => $c->changeRecord?->change_type === 'INCREASE')->count(),
                'DECREASE' => $contributions->filter(fn($c) => $c->changeRecord?->change_type === 'DECREASE')->count(),
                'ADJUST' => $contributions->filter(fn($c) => $c->changeRecord?->change_type === 'ADJUST')->count(),
            ],
        ];

        return [
            'report_info' => $reportInfo,
            'component_list' => $allComponents->values()->toArray(),
            'employees' => $employeeRows,
            'summary' => $summary,
        ];
    }
}
