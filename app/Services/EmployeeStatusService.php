<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Contract;
use App\Models\LeaveRequest;
use App\Enums\EmployeeStatus;
use Illuminate\Support\Facades\Log;

class EmployeeStatusService
{
    /**
     * Sync employee status from contracts
     * Called when contract is APPROVED or TERMINATED
     */
    public function syncFromContracts(Employee $employee): void
    {
        // Check for ACTIVE contracts
        $hasActiveContract = Contract::where('employee_id', $employee->id)
            ->where('status', 'ACTIVE')
            ->exists();

        if ($hasActiveContract) {
            // Has ACTIVE contract → check for long-term leave
            $hasLongLeave = $this->hasActiveLongLeave($employee);

            if ($hasLongLeave) {
                $this->updateStatus($employee, EmployeeStatus::ON_LEAVE, 'Has active contract + active long leave');
            } else {
                $this->updateStatus($employee, EmployeeStatus::ACTIVE, 'Has active contract');
            }
        } else {
            // No ACTIVE contract → check if terminated
            $hasTerminatedContract = Contract::where('employee_id', $employee->id)
                ->where('status', 'TERMINATED')
                ->exists();

            if ($hasTerminatedContract) {
                $this->updateStatus($employee, EmployeeStatus::TERMINATED, 'No active contract, has terminated contract');
            } else {
                $this->updateStatus($employee, EmployeeStatus::INACTIVE, 'No active or terminated contract');
            }
        }
    }

    /**
     * Sync employee status from leaves
     * Called when long-term leave is APPROVED or ENDED
     */
    public function syncFromLeaves(Employee $employee): void
    {
        $hasLongLeave = $this->hasActiveLongLeave($employee);

        if ($hasLongLeave) {
            $this->updateStatus($employee, EmployeeStatus::ON_LEAVE, 'Has active long-term leave');
        } else {
            // No long leave → sync from contracts
            $this->syncFromContracts($employee);
        }
    }

    /**
     * Check if employee has active long leave (>= 30 days or MATERNITY)
     */
    public function hasActiveLongLeave(Employee|string $employee): bool
    {
        $employeeId = $employee instanceof Employee ? $employee->id : $employee;

        return LeaveRequest::where('employee_id', $employeeId)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereHas('leaveType', function ($query) {
                $query->whereIn('code', ['MATERNITY', 'SICK', 'UNPAID']);
            })
            ->where(function ($query) {
                $query->where('days', '>=', 30)
                    ->orWhereHas('leaveType', function ($q) {
                        $q->where('code', 'MATERNITY');
                    });
            })
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->exists();
    }

    /**
     * Update employee status with logging
     */
    protected function updateStatus(Employee $employee, EmployeeStatus $newStatus, string $reason): void
    {
        $oldStatus = $employee->status;

        if ($oldStatus !== $newStatus->value) {
            $employee->update(['status' => $newStatus->value]);

            Log::info("Employee status updated", [
                'employee_id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'old_status' => $oldStatus,
                'new_status' => $newStatus->value,
                'reason' => $reason,
            ]);

            // Activity log
            activity()
                ->useLog('employee-status')
                ->performedOn($employee)
                ->withProperties([
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus->value,
                    'reason' => $reason,
                ])
                ->log("Cập nhật trạng thái nhân viên: {$oldStatus} → {$newStatus->value}");
        }
    }
}
