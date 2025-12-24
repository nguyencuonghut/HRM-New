<?php

namespace App\Observers;

use App\Models\EmployeeAbsence;
use App\Services\EmployeeStatusService;
use Illuminate\Support\Facades\Log;

class EmployeeAbsenceObserver
{
    protected EmployeeStatusService $statusService;

    public function __construct(EmployeeStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Handle the EmployeeAbsence "updated" event.
     * When absence status changes to ENDED, check if employee should return to ACTIVE
     */
    public function updated(EmployeeAbsence $absence): void
    {
        // Only handle when status changes to ENDED
        if ($absence->isDirty('status') && $absence->status === EmployeeAbsence::STATUS_ENDED) {
            try {
                $employee = $absence->employee;

                if ($employee) {
                    Log::info("Employee absence ended, syncing employee status", [
                        'absence_id' => $absence->id,
                        'employee_id' => $employee->id,
                        'absence_type' => $absence->absence_type,
                    ]);

                    // Sync status from leaves (will check if there are other active long leaves)
                    $this->statusService->syncFromLeaves($employee);
                }
            } catch (\Exception $e) {
                Log::error("Failed to update employee status when absence ended", [
                    'absence_id' => $absence->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the EmployeeAbsence "created" event.
     * When long-term absence is created (from approved leave), update status to ON_LEAVE
     */
    public function created(EmployeeAbsence $absence): void
    {
        // Only handle ACTIVE absences (affects insurance, >= 30 days)
        if ($absence->status === EmployeeAbsence::STATUS_ACTIVE && $absence->affects_insurance) {
            try {
                $employee = $absence->employee;

                if ($employee) {
                    Log::info("Employee absence created, syncing employee status", [
                        'absence_id' => $absence->id,
                        'employee_id' => $employee->id,
                        'absence_type' => $absence->absence_type,
                        'duration_days' => $absence->duration_days,
                    ]);

                    $this->statusService->syncFromLeaves($employee);
                }
            } catch (\Exception $e) {
                Log::error("Failed to update employee status when absence created", [
                    'absence_id' => $absence->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
