<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        // Initialize leave balances for new employee
        $this->initializeLeaveBalances($employee);
    }

    /**
     * Initialize leave balances for employee
     */
    private function initializeLeaveBalances(Employee $employee): void
    {
        try {
            $currentYear = now()->year;

            // Get all leave types that require balance tracking
            $leaveTypes = LeaveType::where('requires_approval', true)->get();

            foreach ($leaveTypes as $leaveType) {
                // Check if balance already exists
                $exists = LeaveBalance::where('employee_id', $employee->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $currentYear)
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Calculate total days based on contract type
                $totalDays = $this->calculateTotalDays($employee, $leaveType);

                LeaveBalance::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $currentYear,
                    'total_days' => $totalDays,
                    'used_days' => 0,
                    'remaining_days' => $totalDays,
                    'carried_forward' => 0,
                ]);

                Log::info("Leave balance initialized for employee {$employee->id}, leave type {$leaveType->code}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to initialize leave balances for employee {$employee->id}: {$e->getMessage()}");
        }
    }

    /**
     * Calculate total days for new employee
     */
    private function calculateTotalDays(Employee $employee, LeaveType $leaveType): float
    {
        // Get active contract
        $contract = $employee->contracts()
            ->where('status', 'ACTIVE')
            ->orderBy('start_date', 'desc')
            ->first();

        if (!$contract) {
            return 0;
        }

        // Probation = no annual leave
        if ($contract->contract_type === 'PROBATION') {
            return 0;
        }

        // Official contract: calculate pro-rata based on join date
        if ($contract->contract_type === 'OFFICIAL' && $leaveType->code === 'ANNUAL') {
            $monthsRemaining = 12 - now()->month + 1;
            return $monthsRemaining; // 1 day per month
        }

        // Other leave types use default
        return $leaveType->days_per_year;
    }
}
