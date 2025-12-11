<?php

namespace App\Listeners;

use App\Events\ContractTerminated;
use App\Models\InsuranceParticipation;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Events\Attributes\ListensTo;
use Illuminate\Support\Facades\Log;

#[ListensTo(ContractTerminated::class)]
class TerminateInsuranceParticipation implements ShouldHandleEventsAfterCommit
{
    /**
     * Handle the event: When Contract is TERMINATED, end insurance participation
     */
    public function handle(ContractTerminated $event): void
    {
        $contract = $event->contract;

        try {
            // Find all active participations for this employee
            $participations = InsuranceParticipation::where('employee_id', $contract->employee_id)
                ->where('status', 'ACTIVE')
                ->get();

            foreach ($participations as $participation) {
                $participation->update([
                    'status' => 'TERMINATED',
                    'participation_end_date' => $contract->termination_date ?? now(),
                ]);

                Log::info("Terminated insurance participation {$participation->id} for employee {$contract->employee_id}");
            }

            // Activity log
            if ($participations->isNotEmpty()) {
                activity()
                    ->useLog('insurance-participation')
                    ->performedOn($contract->employee)
                    ->causedBy($event->terminatedBy ?? auth()->user())
                    ->withProperties([
                        'contract_id' => $contract->id,
                        'termination_date' => $contract->termination_date,
                        'participations_count' => $participations->count(),
                    ])
                    ->log('Kết thúc tham gia bảo hiểm do nghỉ việc');
            }

        } catch (\Exception $e) {
            Log::error("Error terminating insurance participation: {$e->getMessage()}");
        }
    }
}
