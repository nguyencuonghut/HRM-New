<?php

namespace App\Services;

use App\Models\InsuranceChangeRecord;
use App\Models\InsuranceMonthlyReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service: Insurance Declaration Month Management
 *
 * Purpose: Handle declaration month logic for insurance change records
 * Rules:
 * - Day 1-14: Declare in same month (e.g., change on Jan 10 → declare in Jan)
 * - Day 15-31: Declare in next month (e.g., change on Jan 20 → declare in Feb)
 * - Reviewer can override with reason
 */
class InsuranceDeclarationService
{
    /**
     * Suggest declaration month based on change effective date
     *
     * Business rule:
     * - If day of month <= 14: suggest same month
     * - If day of month >= 15: suggest next month
     *
     * @param \Carbon\Carbon $effectiveDate
     * @return string YYYY-MM format
     */
    public function suggestDeclarationMonth(Carbon $effectiveDate): string
    {
        $day = $effectiveDate->day;

        if ($day <= 14) {
            // Declare in same month
            return $effectiveDate->format('Y-m');
        } else {
            // Declare in next month (use addMonthNoOverflow to handle end-of-month edge cases)
            return $effectiveDate->copy()->addMonthNoOverflow()->format('Y-m');
        }
    }

    /**
     * Validate all change records in a report have declaration_month matching report month
     *
     * @param InsuranceMonthlyReport $report
     * @return array ['valid' => bool, 'errors' => array, 'mismatchedRecords' => Collection]
     */
    public function validateDeclarationMonth(InsuranceMonthlyReport $report): array
    {
        $reportMonth = $report->year . '-' . str_pad($report->month, 2, '0', STR_PAD_LEFT);

        $mismatchedRecords = InsuranceChangeRecord::where('report_id', $report->id)
            ->where(function ($query) use ($reportMonth) {
                $query->whereNull('declaration_month')
                    ->orWhere('declaration_month', '!=', $reportMonth);
            })
            ->with('employee')
            ->get();

        if ($mismatchedRecords->isEmpty()) {
            return [
                'valid' => true,
                'errors' => [],
                'mismatchedRecords' => collect(),
            ];
        }

        $errors = $mismatchedRecords->map(function ($record) use ($reportMonth) {
            return sprintf(
                'Employee %s (ID: %s): declaration_month "%s" does not match report month "%s"',
                $record->employee->full_name ?? 'Unknown',
                $record->employee_id,
                $record->declaration_month ?? 'NULL',
                $reportMonth
            );
        })->toArray();

        return [
            'valid' => false,
            'errors' => $errors,
            'mismatchedRecords' => $mismatchedRecords,
        ];
    }

    /**
     * Validate a single record can be added to a specific report
     *
     * @param InsuranceChangeRecord $record
     * @param InsuranceMonthlyReport $report
     * @return bool
     */
    public function canRecordBelongToReport(InsuranceChangeRecord $record, InsuranceMonthlyReport $report): bool
    {
        if (!$record->declaration_month) {
            return false;
        }

        $reportMonth = $report->year . '-' . str_pad($report->month, 2, '0', STR_PAD_LEFT);

        return $record->declaration_month === $reportMonth;
    }

    /**
     * Get appropriate report for a change record based on its declaration month
     *
     * @param InsuranceChangeRecord $record
     * @return InsuranceMonthlyReport|null
     */
    public function findReportForRecord(InsuranceChangeRecord $record): ?InsuranceMonthlyReport
    {
        if (!$record->declaration_month) {
            return null;
        }

        [$year, $month] = explode('-', $record->declaration_month);

        return InsuranceMonthlyReport::where('year', (int) $year)
            ->where('month', (int) $month)
            ->first();
    }

    /**
     * Get or create report for a specific month
     *
     * @param string $declarationMonth YYYY-MM format
     * @return InsuranceMonthlyReport
     */
    public function getOrCreateReportForMonth(string $declarationMonth): InsuranceMonthlyReport
    {
        [$year, $month] = explode('-', $declarationMonth);

        return InsuranceMonthlyReport::firstOrCreate(
            [
                'year' => (int) $year,
                'month' => (int) $month,
            ],
            [
                'status' => 'DRAFT',
                'total_increase' => 0,
                'total_decrease' => 0,
                'total_adjust' => 0,
            ]
        );
    }

    /**
     * Count records by declaration month
     *
     * @param Collection|InsuranceChangeRecord[] $records
     * @return array ['YYYY-MM' => count]
     */
    public function groupRecordsByDeclarationMonth(Collection $records): array
    {
        return $records
            ->whereNotNull('declaration_month')
            ->groupBy('declaration_month')
            ->map->count()
            ->toArray();
    }

    /**
     * Get records that have no declaration month set
     *
     * @param InsuranceMonthlyReport $report
     * @return Collection|InsuranceChangeRecord[]
     */
    public function getRecordsWithoutDeclarationMonth(InsuranceMonthlyReport $report): Collection
    {
        return InsuranceChangeRecord::where('report_id', $report->id)
            ->whereNull('declaration_month')
            ->with('employee')
            ->get();
    }

    /**
     * Check if a date is in the early period (day 1-14) or late period (day 15-31)
     *
     * @param Carbon $date
     * @return string 'early' or 'late'
     */
    public function getDeclarationPeriod(Carbon $date): string
    {
        return $date->day <= 14 ? 'early' : 'late';
    }

    /**
     * Calculate suggested vs actual declaration month difference
     *
     * @param Carbon $effectiveDate
     * @param string $actualDeclarationMonth YYYY-MM format
     * @return array ['suggested' => string, 'actual' => string, 'isOverride' => bool, 'monthsDiff' => int]
     */
    public function analyzeDeclarationMonth(Carbon $effectiveDate, string $actualDeclarationMonth): array
    {
        $suggested = $this->suggestDeclarationMonth($effectiveDate);

        $suggestedCarbon = Carbon::createFromFormat('Y-m', $suggested)->startOfMonth();
        $actualCarbon = Carbon::createFromFormat('Y-m', $actualDeclarationMonth)->startOfMonth();

        // Positive if actual is after suggested, negative if before
        // diffInMonths is signed: $a->diffInMonths($b, false) returns negative if $b is before $a
        $monthsDiff = $suggestedCarbon->diffInMonths($actualCarbon, false);

        return [
            'suggested' => $suggested,
            'actual' => $actualDeclarationMonth,
            'isOverride' => $suggested !== $actualDeclarationMonth,
            'monthsDiff' => $monthsDiff,
        ];
    }

    /**
     * Move a change record to appropriate report when declaration month changes
     *
     * This method:
     * 1. Finds or creates target report for new declaration month
     * 2. Updates record's report_id to target report
     * 3. Recalculates counters for both old and new reports
     *
     * @param InsuranceChangeRecord $record
     * @param string $newDeclarationMonth YYYY-MM format
     * @param InsuranceMonthlyReport $oldReport
     * @return InsuranceMonthlyReport The new report
     * @throws \Exception if old report is finalized
     */
    public function moveRecordToReport(
        InsuranceChangeRecord $record,
        string $newDeclarationMonth,
        InsuranceMonthlyReport $oldReport
    ): InsuranceMonthlyReport {
        if ($oldReport->isFinalized()) {
            throw new \Exception('Không thể di chuyển record từ báo cáo đã hoàn tất');
        }

        // Get or create target report
        $newReport = $this->getOrCreateReportForMonth($newDeclarationMonth);

        // Check if record already in correct report
        if ($record->report_id === $newReport->id) {
            return $newReport;
        }

        // Update record's report_id
        $record->update([
            'report_id' => $newReport->id,
        ]);

        // Recalculate both reports
        $this->recalculateReportCounters($oldReport);
        $this->recalculateReportCounters($newReport);

        return $newReport;
    }

    /**
     * Recalculate total/approved counters for a report
     *
     * @param InsuranceMonthlyReport $report
     * @return void
     */
    protected function recalculateReportCounters(InsuranceMonthlyReport $report): void
    {
        $records = InsuranceChangeRecord::where('report_id', $report->id)->get();

        $totalIncrease = $records->where('change_type', 'INCREASE')->count();
        $totalDecrease = $records->where('change_type', 'DECREASE')->count();
        $totalAdjust = $records->where('change_type', 'ADJUST')->count();

        $approvedIncrease = $records
            ->where('change_type', 'INCREASE')
            ->whereIn('approval_status', ['APPROVED', 'ADJUSTED'])
            ->count();

        $approvedDecrease = $records
            ->where('change_type', 'DECREASE')
            ->whereIn('approval_status', ['APPROVED', 'ADJUSTED'])
            ->count();

        $approvedAdjust = $records
            ->where('change_type', 'ADJUST')
            ->whereIn('approval_status', ['APPROVED', 'ADJUSTED'])
            ->count();

        $totalInsuranceSalary = $records
            ->whereIn('approval_status', ['APPROVED', 'ADJUSTED'])
            ->sum(function ($record) {
                return $record->adjusted_salary ?? $record->insurance_salary;
            });

        $report->update([
            'total_increase' => $totalIncrease,
            'total_decrease' => $totalDecrease,
            'total_adjust' => $totalAdjust,
            'approved_increase' => $approvedIncrease,
            'approved_decrease' => $approvedDecrease,
            'approved_adjust' => $approvedAdjust,
            'total_insurance_salary' => $totalInsuranceSalary,
        ]);
    }
}
