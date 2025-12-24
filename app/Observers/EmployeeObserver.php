<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\EmployeeStatusService;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    public function __construct(
        protected EmployeeStatusService $statusService
    ) {}

    /**
     * Handle the Employee "creating" event.
     * Ensure new employees have correct initial status
     */
    public function creating(Employee $employee): void
    {
        // If status is not explicitly set, default to INACTIVE
        // Employee should only be ACTIVE when they have an active contract
        if (!$employee->status) {
            $employee->status = 'INACTIVE';
        }

        // If someone tries to create an employee with ACTIVE status,
        // log a warning as this should be set via contract creation
        if ($employee->status === 'ACTIVE') {
            Log::warning('Employee being created with ACTIVE status - should be set via contract', [
                'employee_code' => $employee->employee_code,
                'full_name' => $employee->full_name,
            ]);
        }
    }

    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        // Initialize leave balances for new employee
        $this->initializeLeaveBalances($employee);
    }

    /**
     * Handle the Employee "updating" event.
     * Track status changes for audit purposes
     */
    public function updating(Employee $employee): void
    {
        // If status is being changed manually (not via our service),
        // log it for audit purposes
        if ($employee->isDirty('status')) {
            $oldStatus = $employee->getOriginal('status');
            $newStatus = $employee->status;

            Log::info('Employee status being changed', [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'full_name' => $employee->full_name,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ]);
        }
    }

    /**
     * Handle the Employee "saved" event.
     * Verify status consistency after save
     */
    public function saved(Employee $employee): void
    {
        // After save, verify that status is consistent with contracts/leaves
        try {
            $hasActiveContract = $employee->contracts()
                ->where('status', 'ACTIVE')
                ->exists();

            $hasActiveLongLeave = $this->statusService->hasActiveLongLeave($employee->id);

            // Determine what the status SHOULD be
            $expectedStatus = 'INACTIVE';
            if ($hasActiveContract) {
                $expectedStatus = $hasActiveLongLeave ? 'ON_LEAVE' : 'ACTIVE';
            }

            // If status doesn't match expected, log a warning and auto-fix
            if ($employee->status !== $expectedStatus) {
                Log::warning('Employee status inconsistent with contracts/leaves', [
                    'employee_id' => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'full_name' => $employee->full_name,
                    'current_status' => $employee->status,
                    'expected_status' => $expectedStatus,
                    'has_active_contract' => $hasActiveContract,
                    'has_active_long_leave' => $hasActiveLongLeave,
                ]);

                // Auto-fix the inconsistency
                // Use updateQuietly to avoid triggering this observer again
                $employee->updateQuietly(['status' => $expectedStatus]);

                Log::info('Auto-corrected employee status', [
                    'employee_id' => $employee->id,
                    'corrected_to' => $expectedStatus,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('EmployeeObserver: Failed to verify status consistency', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
        }
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
