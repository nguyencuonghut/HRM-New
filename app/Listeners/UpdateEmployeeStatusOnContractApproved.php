<?php

namespace App\Listeners;

use App\Events\ContractApproved;
use App\Services\EmployeeStatusService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Events\Attributes\ListensTo;
use Illuminate\Support\Facades\Log;

#[ListensTo(ContractApproved::class)]
class UpdateEmployeeStatusOnContractApproved implements ShouldHandleEventsAfterCommit
{
    protected EmployeeStatusService $statusService;

    public function __construct(EmployeeStatusService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Handle the event: When Contract is APPROVED, update employee status to ACTIVE
     * (unless they have active long-term leave)
     */
    public function handle(ContractApproved $event): void
    {
        $contract = $event->contract;
        $employee = $contract->employee;

        if (!$employee) {
            Log::warning("Contract {$contract->id} has no associated employee");
            return;
        }

        try {
            $this->statusService->syncFromContracts($employee);

            Log::info("Updated employee status after contract approval", [
                'contract_id' => $contract->id,
                'employee_id' => $employee->id,
                'employee_status' => $employee->fresh()->status,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update employee status on contract approval", [
                'contract_id' => $contract->id,
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
