<?php

namespace App\Listeners;

use App\Events\LeaveRequestEnded;
use App\Services\EmployeeStatusService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Events\Attributes\ListensTo;
use Illuminate\Support\Facades\Log;

#[ListensTo(LeaveRequestEnded::class)]
class UpdateEmployeeStatusOnLeaveEnded implements ShouldHandleEventsAfterCommit
{
    protected EmployeeStatusService $statusService;

    public function __construct(EmployeeStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Handle the event: When long-term leave ends, check if employee should return to ACTIVE
     *
     * This is triggered by:
     * - Console command checking expired leaves (daily cron)
     * - Manual leave cancellation
     */
    public function handle(LeaveRequestEnded $event): void
    {
        $leaveRequest = $event->leaveRequest;
        $employee = $leaveRequest->employee;

        if (!$employee) {
            Log::warning("Leave request {$leaveRequest->id} has no associated employee");
            return;
        }

        try {
            Log::info("Leave request ended, syncing employee status", [
                'leave_request_id' => $leaveRequest->id,
                'employee_id' => $employee->id,
                'leave_type' => $leaveRequest->leaveType?->code,
                'end_date' => $leaveRequest->end_date,
            ]);

            // Sync from leaves (will check if there are other active long leaves)
            $this->statusService->syncFromLeaves($employee);

            Log::info("Employee status synced after leave ended", [
                'leave_request_id' => $leaveRequest->id,
                'employee_id' => $employee->id,
                'new_status' => $employee->fresh()->status,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update employee status on leave end", [
                'leave_request_id' => $leaveRequest->id,
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
