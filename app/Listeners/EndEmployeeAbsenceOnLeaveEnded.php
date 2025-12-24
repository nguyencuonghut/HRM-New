<?php

namespace App\Listeners;

use App\Events\LeaveRequestEnded;
use App\Models\EmployeeAbsence;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Events\Attributes\ListensTo;
use Illuminate\Support\Facades\Log;

#[ListensTo(LeaveRequestEnded::class)]
class EndEmployeeAbsenceOnLeaveEnded implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the event: When leave ends, update related EmployeeAbsence to ENDED
     */
    public function handle(LeaveRequestEnded $event): void
    {
        $leaveRequest = $event->leaveRequest;

        try {
            // Find related employee absence
            $absence = EmployeeAbsence::where('leave_request_id', $leaveRequest->id)
                ->where('status', EmployeeAbsence::STATUS_ACTIVE)
                ->first();

            if ($absence) {
                $absence->update(['status' => EmployeeAbsence::STATUS_ENDED]);

                Log::info("Employee absence ended", [
                    'absence_id' => $absence->id,
                    'leave_request_id' => $leaveRequest->id,
                    'employee_id' => $absence->employee_id,
                ]);

                // Activity log
                activity()
                    ->useLog('employee-absence')
                    ->performedOn($absence)
                    ->withProperties([
                        'leave_request_id' => $leaveRequest->id,
                        'old_status' => EmployeeAbsence::STATUS_ACTIVE,
                        'new_status' => EmployeeAbsence::STATUS_ENDED,
                    ])
                    ->log('Kết thúc nghỉ dài hạn');
            }
        } catch (\Exception $e) {
            Log::error("Failed to end employee absence on leave end", [
                'leave_request_id' => $leaveRequest->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
