<?php

namespace App\Listeners;

use App\Events\ContractApproved;
use App\Models\InsuranceParticipation;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Events\Attributes\ListensTo;
use Illuminate\Support\Facades\Log;

#[ListensTo(ContractApproved::class)]
class CreateInsuranceParticipation implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the event: When Contract is APPROVED, create insurance participation
     */
    public function handle(ContractApproved $event): void
    {
        $contract = $event->contract;

        // Only create if contract has insurance info and is not already created
        if (!$contract->insurance_salary || $contract->insurance_salary <= 0) {
            Log::info("Contract {$contract->id} has no insurance salary, skipping participation creation");
            return;
        }

        // Check if already exists
        $exists = InsuranceParticipation::where('employee_id', $contract->employee_id)
            ->where('contract_id', $contract->id)
            ->exists();

        if ($exists) {
            Log::info("Insurance participation already exists for contract {$contract->id}");
            return;
        }

        try {
            // Get employment start date (more accurate than contract start date)
            $employment = $contract->employee->employments()
                ->where('is_current', true)
                ->first();

            $startDate = $employment ? $employment->start_date : $contract->start_date;

            InsuranceParticipation::create([
                'employee_id' => $contract->employee_id,
                'participation_start_date' => $startDate, // ✅ Dùng employment start_date
                'participation_end_date' => null, // Active
                'has_social_insurance' => $contract->has_social_insurance ?? true,
                'has_health_insurance' => $contract->has_health_insurance ?? true,
                'has_unemployment_insurance' => $contract->has_unemployment_insurance ?? true,
                'insurance_salary' => $contract->insurance_salary,
                'contract_id' => $contract->id,
                'contract_appendix_id' => null,
                'status' => 'ACTIVE',
            ]);

            Log::info("Created insurance participation for employee {$contract->employee_id} from contract {$contract->id}");

            // Activity log
            activity()
                ->useLog('insurance-participation')
                ->performedOn($contract->employee)
                ->causedBy($event->approver ?? auth()->user())
                ->withProperties([
                    'contract_id' => $contract->id,
                    'insurance_salary' => $contract->insurance_salary,
                    'start_date' => $contract->start_date,
                ])
                ->log('Tạo tham gia bảo hiểm từ hợp đồng được duyệt');

        } catch (\Exception $e) {
            Log::error("Error creating insurance participation: {$e->getMessage()}");
        }
    }
}
