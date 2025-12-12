<?php

namespace App\Listeners;

use App\Events\ContractApproved;
use App\Models\EmployeeEmployment;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Events\Attributes\ListensTo;
use Illuminate\Support\Facades\Log;

#[ListensTo(ContractApproved::class)]
class CreateEmploymentPeriod implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the event: When Contract is APPROVED, create/update employment period
     *
     * Logic:
     * - Nếu chưa có employment nào → tạo mới (lần đầu tuyển dụng)
     * - Nếu đã có employment TERMINATED → tạo mới (tái tuyển dụng)
     * - Nếu đã có employment ACTIVE → không làm gì (gia hạn hợp đồng)
     */
    public function handle(ContractApproved $event): void
    {
        $contract = $event->contract;
        $employee = $contract->employee;

        // Check current employment status
        $currentEmployment = $employee->employments()
            ->where('is_current', true)
            ->first();

        // Case 1: Already has active employment → just link contract
        if ($currentEmployment) {
            // Link contract to existing employment
            $contract->update(['employment_id' => $currentEmployment->id]);

            Log::info("Linked contract {$contract->id} to existing employment {$currentEmployment->id}");
            return;
        }

        // Case 2: No active employment → create new (first hire OR rehire)
        try {
            $employment = EmployeeEmployment::create([
                'employee_id' => $employee->id,
                'start_date' => $contract->start_date,
                'end_date' => null,
                'end_reason' => null,
                'is_current' => true,
                'note' => 'Created from approved contract #' . $contract->id,
            ]);

            // Link contract to new employment
            $contract->update(['employment_id' => $employment->id]);

            // Update employee hire_date to reflect current employment start
            $employee->update(['hire_date' => $contract->start_date]);

            Log::info("Created new employment {$employment->id} for employee {$employee->id} from contract {$contract->id}");

            // Activity log
            activity()
                ->useLog('employment')
                ->performedOn($employee)
                ->causedBy($event->approver ?? auth()->user())
                ->withProperties([
                    'employment_id' => $employment->id,
                    'start_date' => $contract->start_date,
                    'contract_id' => $contract->id,
                ])
                ->log('Tạo chu kỳ làm việc mới từ hợp đồng được duyệt');

        } catch (\Exception $e) {
            Log::error("Error creating employment period: {$e->getMessage()}");
        }
    }
}
