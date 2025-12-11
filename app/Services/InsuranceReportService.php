<?php

namespace App\Services;

use App\Models\InsuranceChangeRecord;
use App\Models\InsuranceMonthlyReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsuranceReportService
{
    protected InsuranceCalculationService $calculationService;

    public function __construct(InsuranceCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
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
            $record->update([
                'approval_status' => InsuranceChangeRecord::APPROVAL_APPROVED,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'admin_notes' => $adminNotes,
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
    public function adjustRecord(
        InsuranceChangeRecord $record,
        User $admin,
        float $adjustedSalary,
        string $adjustmentReason,
        ?string $adminNotes = null
    ): bool {
        if (!$record->isPending()) {
            throw new \Exception('Record không ở trạng thái chờ duyệt');
        }

        if ($record->report->isFinalized()) {
            throw new \Exception('Báo cáo đã hoàn tất, không thể điều chỉnh');
        }

        DB::beginTransaction();
        try {
            $record->update([
                'approval_status' => InsuranceChangeRecord::APPROVAL_ADJUSTED,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'adjusted_salary' => $adjustedSalary,
                'adjustment_reason' => $adjustmentReason,
                'admin_notes' => $adminNotes,
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
                    'original_salary' => $record->insurance_salary,
                    'adjusted_salary' => $adjustedSalary,
                    'reason' => $adjustmentReason,
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

        DB::beginTransaction();
        try {
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
}
