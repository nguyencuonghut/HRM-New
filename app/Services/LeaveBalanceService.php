<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service for calculating and managing leave balances
 *
 * This service handles the actual entitlement calculation logic
 * that should NOT be in the Observer.
 */
class LeaveBalanceService
{
    /**
     * Recalculate leave balances for an employee for a given year
     *
     * This method:
     * - Does NOT skip existing records (always recalculates)
     * - Uses the most recent ACTIVE contract
     * - Applies business rules for entitlement calculation
     *
     * @param Employee $employee
     * @param int $year
     * @return void
     */
    public function recalcForEmployeeYear(Employee $employee, int $year): void
    {
        try {
            Log::info("LeaveBalanceService: Recalculating leave balance for employee", [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'year' => $year,
            ]);

            // Get all leave types that require balance tracking
            // TODO: Consider using a dedicated flag like 'track_balance' instead of 'requires_approval'
            $leaveTypes = LeaveType::where('requires_approval', true)->get();

            // Get the most recent ACTIVE contract
            $contract = $employee->contracts()
                ->where('status', 'ACTIVE')
                ->orderByDesc('start_date')
                ->first();

            if (!$contract) {
                Log::warning("LeaveBalanceService: No active contract found for employee", [
                    'employee_id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                ]);
            }

            foreach ($leaveTypes as $leaveType) {
                // Always ensure record exists (bootstrap with zero if needed)
                $leaveBalance = LeaveBalance::firstOrCreate(
                    [
                        'employee_id'   => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'year'          => $year,
                    ],
                    [
                        'total_days'      => 0,
                        'used_days'       => 0,
                        'remaining_days'  => 0,
                        'carried_forward' => 0,
                    ]
                );

                // Calculate new entitlement
                $totalDays = $this->calculateEntitlement($employee, $contract, $leaveType, $year);

                // IMPORTANT: Always update, never skip because record exists
                $leaveBalance->total_days = $totalDays;

                // Recalculate remaining days
                // Formula: total + carried_forward - used
                $leaveBalance->remaining_days = max(
                    0,
                    $leaveBalance->total_days + ($leaveBalance->carried_forward ?? 0) - ($leaveBalance->used_days ?? 0)
                );

                $leaveBalance->save();

                Log::info("LeaveBalanceService: Updated leave balance", [
                    'employee_id' => $employee->id,
                    'leave_type_code' => $leaveType->code,
                    'year' => $year,
                    'total_days' => $totalDays,
                    'used_days' => $leaveBalance->used_days,
                    'remaining_days' => $leaveBalance->remaining_days,
                ]);
            }

            Log::info("LeaveBalanceService: Completed recalculation for employee", [
                'employee_id' => $employee->id,
                'year' => $year,
            ]);
        } catch (\Exception $e) {
            Log::error("LeaveBalanceService: Failed to recalculate leave balance", [
                'employee_id' => $employee->id,
                'year' => $year,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to let caller handle
        }
    }

    /**
     * Calculate entitlement for a specific leave type
     *
     * Business rules:
     * - PROBATION contract: 0 annual leave (company policy)
     * - ANNUAL leave: pro-rated based on start date within the year
     * - Other leave types: use default days_per_year
     *
     * @param Employee $employee
     * @param Contract|null $contract
     * @param LeaveType $leaveType
     * @param int $year
     * @return float
     */
    private function calculateEntitlement(
        Employee $employee,
        ?Contract $contract,
        LeaveType $leaveType,
        int $year
    ): float {
        // No contract = no entitlement
        if (!$contract) {
            return 0;
        }

        // PROBATION contract: no annual leave (company policy)
        // Can be adjusted based on company rules
        if ($contract->contract_type === 'PROBATION' && $leaveType->code === 'ANNUAL') {
            Log::debug("LeaveBalanceService: PROBATION contract, no annual leave", [
                'employee_id' => $employee->id,
                'contract_id' => $contract->id,
            ]);
            return 0;
        }

        // ANNUAL leave calculation with pro-rating
        if ($leaveType->code === 'ANNUAL') {
            return $this->calculateAnnualLeaveEntitlement($contract, $year);
        }

        // For other leave types (SICK, PERSONAL, etc.), use default
        return (float) $leaveType->days_per_year;
    }

    /**
     * Calculate annual leave entitlement with pro-rating
     *
     * Rules (adjust based on company policy):
     * - Full year (contract started before this year): 12 days
     * - Partial year: pro-rated by months remaining in year
     * - Contract types eligible: FIXED_TERM, INDEFINITE, SEASONAL, SERVICE, INTERNSHIP, PARTTIME
     *   (basically all non-PROBATION contracts)
     *
     * @param Contract $contract
     * @param int $year
     * @return float
     */
    private function calculateAnnualLeaveEntitlement(Contract $contract, int $year): float
    {
        $startDate = Carbon::parse($contract->start_date);

        // If contract started before this year, give full entitlement
        if ($startDate->year < $year) {
            Log::debug("LeaveBalanceService: Contract started before year, full entitlement", [
                'contract_id' => $contract->id,
                'start_date' => $startDate->toDateString(),
                'year' => $year,
            ]);
            return 12; // Full year = 12 days (adjust based on policy)
        }

        // If contract started this year, pro-rate by months
        if ($startDate->year === $year) {
            // Calculate months from start date to end of year
            $monthsRemaining = 12 - $startDate->month + 1;
            $proRatedDays = max(0, $monthsRemaining); // 1 day per month

            Log::debug("LeaveBalanceService: Pro-rating annual leave", [
                'contract_id' => $contract->id,
                'start_date' => $startDate->toDateString(),
                'year' => $year,
                'months_remaining' => $monthsRemaining,
                'pro_rated_days' => $proRatedDays,
            ]);

            return $proRatedDays;
        }

        // Contract starts in future
        Log::debug("LeaveBalanceService: Contract starts in future", [
            'contract_id' => $contract->id,
            'start_date' => $startDate->toDateString(),
            'year' => $year,
        ]);
        return 0;
    }

    /**
     * Batch recalculate for multiple employees
     * Useful for mass updates or annual rollover
     *
     * @param array $employeeIds
     * @param int $year
     * @return array ['success' => [], 'failed' => []]
     */
    public function batchRecalc(array $employeeIds, int $year): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($employeeIds as $employeeId) {
            try {
                $employee = Employee::findOrFail($employeeId);
                $this->recalcForEmployeeYear($employee, $year);
                $results['success'][] = $employeeId;
            } catch (\Exception $e) {
                Log::error("LeaveBalanceService: Batch recalc failed for employee", [
                    'employee_id' => $employeeId,
                    'error' => $e->getMessage(),
                ]);
                $results['failed'][] = [
                    'employee_id' => $employeeId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
