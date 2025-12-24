<?php

namespace App\Listeners;

use App\Events\LeaveRequestApproved;
use App\Services\EmployeeStatusService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Events\Attributes\ListensTo;
use Illuminate\Support\Facades\Log;

#[ListensTo(LeaveRequestApproved::class)]
class UpdateEmployeeStatusOnLeaveApproved implements ShouldHandleEventsAfterCommit
{
    protected EmployeeStatusService $statusService;

    public function __construct(EmployeeStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Handle the event: When long-term leave is APPROVED, update employee status to ON_LEAVE
     *
     * This works for both:
     * - Admin/HR khai báo (auto-approved via LeaveApprovalService::autoApprove)
     * - Normal approval workflow (manual approval by Manager/Director)
     */
    public function handle(LeaveRequestApproved $event): void
    {
        $leaveRequest = $event->leaveRequest;
        $employee = $leaveRequest->employee;

        if (!$employee) {
            Log::warning("Leave request {$leaveRequest->id} has no associated employee");
            return;
        }

        // Only update status for long-term leaves (>= 30 days or MATERNITY)
        $leaveTypeCode = $leaveRequest->leaveType?->code;
        $isLongLeave = $leaveRequest->days >= 30
            || in_array($leaveTypeCode, ['MATERNITY']);

        if (!$isLongLeave) {
            Log::debug("Leave request {$leaveRequest->id} is not long-term, skipping status update");
            return;
        }

        // Only update if leave is currently active (start_date <= today <= end_date)
        $isActive = $leaveRequest->start_date <= now()
            && $leaveRequest->end_date >= now();

        if (!$isActive) {
            Log::debug("Leave request {$leaveRequest->id} is not currently active, skipping status update");
            return;
        }

        try {
            $this->statusService->syncFromLeaves($employee);

            Log::info("Updated employee status after leave approval", [
                'leave_request_id' => $leaveRequest->id,
                'employee_id' => $employee->id,
                'leave_type' => $leaveTypeCode,
                'days' => $leaveRequest->days,
                'employee_status' => $employee->fresh()->status,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update employee status on leave approval", [
                'leave_request_id' => $leaveRequest->id,
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
