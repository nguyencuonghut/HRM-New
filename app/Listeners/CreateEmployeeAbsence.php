<?php

namespace App\Listeners;

use App\Events\LeaveRequestApproved;
use App\Models\EmployeeAbsence;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Events\Attributes\ListensTo;
use Illuminate\Support\Facades\Log;

#[ListensTo(LeaveRequestApproved::class)]
class CreateEmployeeAbsence implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the event: When Leave Request is APPROVED and >=30 days, create employee absence
     */
    public function handle(LeaveRequestApproved $event): void
    {
        $leaveRequest = $event->leaveRequest;

        // Only process long leaves (>=30 days) or maternity
        if ($leaveRequest->days < 30 && $leaveRequest->leaveType?->code !== 'MATERNITY') {
            return;
        }

        // Check leave type
        $leaveTypeCode = $leaveRequest->leaveType?->code;
        if (!in_array($leaveTypeCode, ['MATERNITY', 'SICK', 'UNPAID'])) {
            return;
        }

        // Check if already exists
        $exists = EmployeeAbsence::where('leave_request_id', $leaveRequest->id)->exists();
        if ($exists) {
            Log::info("Employee absence already exists for leave request {$leaveRequest->id}");
            return;
        }

        try {
            // Map leave type to absence type
            $absenceTypeMap = [
                'MATERNITY' => 'MATERNITY',
                'SICK' => 'SICK_LONG',
                'UNPAID' => 'UNPAID_LONG',
            ];

            $absenceType = $absenceTypeMap[$leaveTypeCode] ?? 'OTHER';

            EmployeeAbsence::create([
                'employee_id' => $leaveRequest->employee_id,
                'absence_type' => $absenceType,
                'start_date' => $leaveRequest->start_date,
                'end_date' => $leaveRequest->end_date,
                'duration_days' => (int) $leaveRequest->days,
                'affects_insurance' => true,
                'reason' => $leaveRequest->reason,
                'leave_request_id' => $leaveRequest->id,
                'status' => 'ACTIVE', // Immediately active since leave is approved
            ]);

            Log::info("Created employee absence for leave request {$leaveRequest->id} (Employee: {$leaveRequest->employee_id}, Type: {$absenceType})");

            // Activity log
            activity()
                ->useLog('employee-absence')
                ->performedOn($leaveRequest->employee)
                ->causedBy($event->approver ?? auth()->user())
                ->withProperties([
                    'leave_request_id' => $leaveRequest->id,
                    'absence_type' => $absenceType,
                    'duration_days' => $leaveRequest->days,
                    'start_date' => $leaveRequest->start_date->format('Y-m-d'),
                    'end_date' => $leaveRequest->end_date->format('Y-m-d'),
                ])
                ->log('Tạo nghỉ dài hạn ảnh hưởng bảo hiểm');

        } catch (\Exception $e) {
            Log::error("Error creating employee absence: {$e->getMessage()}");
        }
    }
}
