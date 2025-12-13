<?php

namespace App\Listeners;

use App\Events\ContractApproved;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Events\Attributes\ListensTo;
use Illuminate\Support\Facades\Log;

#[ListensTo(ContractApproved::class)]
class InitializeLeaveBalanceForContract implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the event: When Contract is APPROVED, initialize leave balances
     */
    public function handle(ContractApproved $event): void
    {
        $contract = $event->contract;
        $employee = $contract->employee;
        $year = now()->year;

        Log::info("Initializing leave balances for employee {$employee->id} after contract {$contract->id} approval");

        // Only initialize ANNUAL leave (quota-based)
        // Other types (SICK, PERSONAL_PAID, MATERNITY) are event-based, created when requested
        $leaveTypes = LeaveType::where('requires_approval', true)
            ->where('code', 'ANNUAL')
            ->get();

        foreach ($leaveTypes as $leaveType) {
            // Check if balance already exists
            $exists = LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $year)
                ->exists();

            if ($exists) {
                Log::info("Leave balance already exists for employee {$employee->id}, leave type {$leaveType->id}, year {$year}");
                continue;
            }

            // Calculate total days based on contract type and seniority
            $totalDays = $this->calculateTotalDays($employee, $leaveType, $year);

            // Calculate carried forward from previous year (if applicable)
            $carriedForward = 0;
            if ($year > now()->year || ($year == now()->year && now()->month <= 3)) {
                $carriedForward = $this->calculateCarriedForward($employee, $leaveType, $year - 1);
            }

            try {
                LeaveBalance::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                    'total_days' => $totalDays,
                    'used_days' => 0,
                    'remaining_days' => $totalDays + $carriedForward,
                    'carried_forward' => $carriedForward,
                ]);

                Log::info("Created leave balance for employee {$employee->id}, leave type {$leaveType->name}, total: {$totalDays}, carried: {$carriedForward}");
            } catch (\Exception $e) {
                Log::error("Failed to create leave balance for employee {$employee->id}, leave type {$leaveType->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Calculate total days for a leave type
     * Pro-rata calculation: 1 day per month worked in the year
     */
    private function calculateTotalDays($employee, $leaveType, int $year): float
    {
        // Get current employment period
        $employment = $employee->employments()
            ->where('is_current', true)
            ->first();

        if (!$employment) {
            return 0; // No active employment
        }

        // For non-ANNUAL leave types, use default days
        if ($leaveType->code !== 'ANNUAL') {
            return $leaveType->days_per_year ?? 0;
        }

        // Calculate working months in this specific year
        $yearStart = \Carbon\Carbon::create($year, 1, 1);
        $yearEnd = \Carbon\Carbon::create($year, 12, 31);

        // Determine actual work period within the year
        $workStart = $employment->start_date->year == $year
            ? $employment->start_date
            : $yearStart;

        $workEnd = $employment->end_date && $employment->end_date->year == $year
            ? $employment->end_date
            : $yearEnd;

        // Calculate full working months (include partial month as full month)
        $workingMonths = $workStart->diffInMonths($workEnd) + 1;

        // Ensure not exceed 12 months
        $workingMonths = min($workingMonths, 12);

        // ANNUAL leave: 1 day per month worked
        $baseDays = $workingMonths;

        // Add seniority bonus ONLY if worked full year (12 months)
        // Seniority bonus: +1 day per 5 years of service
        if ($workingMonths >= 12) {
            $seniorityYears = $this->calculateSeniorityYears($employee, $year - 1); // Use previous years for seniority
            $seniorityBonus = floor($seniorityYears / 5);
            $baseDays += $seniorityBonus;
        }

        return $baseDays;
    }

    /**
     * Calculate seniority years based on employment periods
     */
    private function calculateSeniorityYears($employee, int $year): int
    {
        // Try to use employment periods if available
        if ($employee->employments()->exists()) {
            $totalDays = 0;
            $endDate = \Carbon\Carbon::create($year, 12, 31);

            foreach ($employee->employments as $employment) {
                $start = \Carbon\Carbon::parse($employment->start_date);
                $end = $employment->end_date
                    ? \Carbon\Carbon::parse($employment->end_date)
                    : $endDate;

                // Only count periods that ended before or within the year
                if ($start->year <= $year) {
                    $periodEnd = $end->year > $year ? $endDate : $end;
                    $totalDays += $start->diffInDays($periodEnd);
                }
            }

            return (int) floor($totalDays / 365);
        }

        // Fallback to first contract if no employment periods
        $firstContract = $employee->contracts()
            ->orderBy('start_date', 'asc')
            ->first();

        if (!$firstContract) {
            return 0;
        }

        $startDate = \Carbon\Carbon::parse($firstContract->start_date);
        $endDate = \Carbon\Carbon::create($year, 12, 31);

        return $startDate->diffInYears($endDate);
    }

    /**
     * Calculate carried forward from previous year
     */
    private function calculateCarriedForward($employee, $leaveType, int $previousYear): float
    {
        $previousBalance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $previousYear)
            ->first();

        if (!$previousBalance) {
            return 0;
        }

        // Get maximum carry forward from leave type configuration
        $maxCarryForward = $leaveType->max_carry_forward ?? 0;

        if ($maxCarryForward <= 0) {
            return 0;
        }

        // Calculate remaining days
        $remaining = $previousBalance->remaining_days;

        // Apply maximum carry forward limit
        return min($remaining, $maxCarryForward);
    }
}
