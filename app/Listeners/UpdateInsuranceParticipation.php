<?php

namespace App\Listeners;

use App\Events\AppendixApproved;
use App\Models\InsuranceParticipation;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Events\Attributes\ListensTo;
use Illuminate\Support\Facades\Log;

#[ListensTo(AppendixApproved::class)]
class UpdateInsuranceParticipation implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the event: When Appendix (SALARY type) is APPROVED, update insurance participation
     */
    public function handle(AppendixApproved $event): void
    {
        $appendix = $event->appendix;

        // Only process SALARY appendixes
        if ($appendix->appendix_type !== 'SALARY') {
            return;
        }

        // Check if appendix has insurance info
        if (!$appendix->insurance_salary || $appendix->insurance_salary <= 0) {
            Log::info("Appendix {$appendix->id} has no insurance salary, skipping participation update");
            return;
        }

        $contract = $appendix->contract;
        if (!$contract) {
            Log::warning("Appendix {$appendix->id} has no contract");
            return;
        }

        try {
            // Find active participation for this employee
            $participation = InsuranceParticipation::where('employee_id', $contract->employee_id)
                ->where('contract_id', $contract->id)
                ->where('status', 'ACTIVE')
                ->latest()
                ->first();

            if (!$participation) {
                // No active participation found, create new one
                Log::info("No active participation found, creating new one for appendix {$appendix->id}");

                InsuranceParticipation::create([
                    'employee_id' => $contract->employee_id,
                    'participation_start_date' => $appendix->effective_date,
                    'participation_end_date' => null,
                    'has_social_insurance' => $appendix->has_social_insurance ?? true,
                    'has_health_insurance' => $appendix->has_health_insurance ?? true,
                    'has_unemployment_insurance' => $appendix->has_unemployment_insurance ?? true,
                    'insurance_salary' => $appendix->insurance_salary,
                    'contract_id' => $contract->id,
                    'contract_appendix_id' => $appendix->id,
                    'status' => 'ACTIVE',
                ]);
            } else {
                // Update existing participation with new salary from appendix
                $participation->update([
                    'insurance_salary' => $appendix->insurance_salary,
                    'contract_appendix_id' => $appendix->id,
                    'has_social_insurance' => $appendix->has_social_insurance ?? $participation->has_social_insurance,
                    'has_health_insurance' => $appendix->has_health_insurance ?? $participation->has_health_insurance,
                    'has_unemployment_insurance' => $appendix->has_unemployment_insurance ?? $participation->has_unemployment_insurance,
                ]);

                Log::info("Updated insurance participation {$participation->id} with appendix {$appendix->id}");
            }

            // Activity log
            activity()
                ->useLog('insurance-participation')
                ->performedOn($contract->employee)
                ->causedBy($event->approver ?? auth()->user())
                ->withProperties([
                    'appendix_id' => $appendix->id,
                    'insurance_salary' => $appendix->insurance_salary,
                    'effective_date' => $appendix->effective_date,
                ])
                ->log('Cập nhật lương bảo hiểm từ phụ lục được duyệt');

        } catch (\Exception $e) {
            Log::error("Error updating insurance participation: {$e->getMessage()}");
        }
    }
}
