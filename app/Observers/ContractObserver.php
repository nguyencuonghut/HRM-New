<?php

namespace App\Observers;

use App\Models\Contract;
use App\Services\EmploymentResolver;
use App\Services\EmployeeStatusService;
use Illuminate\Support\Facades\Log;

class ContractObserver
{
    protected EmploymentResolver $resolver;
    protected EmployeeStatusService $statusService;

    public function __construct(
        EmploymentResolver $resolver,
        EmployeeStatusService $statusService
    ) {
        $this->resolver = $resolver;
        $this->statusService = $statusService;
    }

    /**
     * Handle the Contract "saved" event.
     *
     * Backfill-on-write: Automatically create/update employment when contract is saved
     *
     * Rules:
     * - LEGACY contracts: Create employment if status != DRAFT
     * - RECRUITMENT contracts: Create employment when status = ACTIVE/APPROVED
     */
    public function saved(Contract $contract): void
    {
        try {
            Log::info("ContractObserver: saved() triggered", [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'status' => $contract->status,
                'source' => $contract->source,
                'employee_id' => $contract->employee_id,
                'start_date' => $contract->start_date,
                'employment_id' => $contract->employment_id,
                'was_recently_created' => $contract->wasRecentlyCreated,
            ]);

            // Skip if contract doesn't have required fields yet
            if (!$contract->employee_id || !$contract->start_date) {
                Log::warning("ContractObserver: Skipping - missing employee_id or start_date", [
                    'contract_id' => $contract->id,
                ]);
                return;
            }

            // Handle employment end when contract status becomes EXPIRED or CANCELLED
            if (in_array($contract->status, ['EXPIRED', 'CANCELLED']) && $contract->isDirty('status')) {
                $this->handleEmploymentEndOnContractEnd($contract);
            }

            // Handle employee status sync when contract status changes
            // This handles LEGACY contracts (backfill) which are created directly with status ACTIVE/TERMINATED
            // and also handles status updates (e.g., ACTIVE → TERMINATED)
            if ($contract->isDirty('status') || $contract->wasRecentlyCreated) {
                $this->syncEmployeeStatusOnContractChange($contract);
            }

            // Skip if employment_id is already set (prevent infinite loop)
            // This happens when resolver calls $contract->save() to update employment_id
            if ($contract->employment_id) {
                Log::info("ContractObserver: Skipping - employment_id already set", [
                    'contract_id' => $contract->id,
                    'employment_id' => $contract->employment_id,
                ]);
                return;
            }

            // Only process if conditions are met (check inside resolver)
            $employment = $this->resolver->attachEmploymentForContract($contract);

            if ($employment) {
                Log::info("EmploymentResolver: Attached employment for contract", [
                    'contract_id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'employment_id' => $employment->id,
                    'source' => $contract->source,
                    'status' => $contract->status,
                ]);
            } else {
                Log::warning("EmploymentResolver: No employment created (returned null)", [
                    'contract_id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'status' => $contract->status,
                    'source' => $contract->source,
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't block contract save
            Log::error("EmploymentResolver: Failed to attach employment for contract", [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Sync employee status when contract status changes
     * Handles both LEGACY (backfill) and status updates
     */
    protected function syncEmployeeStatusOnContractChange(Contract $contract): void
    {
        try {
            $employee = $contract->employee;
            if (!$employee) {
                return;
            }

            // Only sync for meaningful status changes
            $statusesThatAffectEmployee = ['ACTIVE', 'TERMINATED', 'EXPIRED', 'CANCELLED', 'SUSPENDED'];

            if (!in_array($contract->status, $statusesThatAffectEmployee)) {
                Log::debug("ContractObserver: Skipping employee status sync - status not relevant", [
                    'contract_id' => $contract->id,
                    'status' => $contract->status,
                ]);
                return;
            }

            // For LEGACY contracts created with ACTIVE status (backfill)
            if ($contract->source === 'LEGACY' && $contract->wasRecentlyCreated && $contract->status === 'ACTIVE') {
                Log::info("ContractObserver: LEGACY contract created with ACTIVE status, syncing employee", [
                    'contract_id' => $contract->id,
                    'employee_id' => $employee->id,
                ]);
                $this->statusService->syncFromContracts($employee);
                return;
            }

            // For LEGACY contracts created with TERMINATED status (backfill for past employees)
            if ($contract->source === 'LEGACY' && $contract->wasRecentlyCreated && $contract->status === 'TERMINATED') {
                Log::info("ContractObserver: LEGACY contract created with TERMINATED status, syncing employee", [
                    'contract_id' => $contract->id,
                    'employee_id' => $employee->id,
                ]);
                $this->statusService->syncFromContracts($employee);
                return;
            }

            // For status changes (any source)
            if ($contract->isDirty('status')) {
                $oldStatus = $contract->getOriginal('status');
                $newStatus = $contract->status;

                Log::info("ContractObserver: Contract status changed, syncing employee", [
                    'contract_id' => $contract->id,
                    'employee_id' => $employee->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'source' => $contract->source,
                ]);

                $this->statusService->syncFromContracts($employee);
            }
        } catch (\Exception $e) {
            Log::error("ContractObserver: Failed to sync employee status on contract change", [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle employment end when contract expires or is cancelled
     */
    protected function handleEmploymentEndOnContractEnd(Contract $contract): void
    {
        \Log::info("ContractObserver: Handling employment end on contract end", [
            'contract_id' => $contract->id,
            'status' => $contract->status,
        ]);
        try {
            // Check if employee has other active contracts
            $hasOtherActiveContracts = Contract::where('employee_id', $contract->employee_id)
                ->where('id', '!=', $contract->id)
                ->whereIn('status', ['ACTIVE', 'SUSPENDED'])
                ->exists();

            // If no other active contracts, end current employment
            if (!$hasOtherActiveContracts) {
                $endDate = $contract->end_date ?? now();
                $reason = $contract->status === 'EXPIRED' ? 'CONTRACT_END' : 'OTHER';
                $note = $contract->status === 'EXPIRED'
                    ? "Hợp đồng {$contract->contract_number} hết hạn"
                    : "Hợp đồng {$contract->contract_number} bị hủy";

                $this->resolver->endCurrentEmployment(
                    employeeId: $contract->employee_id,
                    endDate: $endDate->toDateString(),
                    reason: $reason,
                    note: $note,
                    employmentId: $contract->employment_id  // Pass employment_id from contract
                );

                Log::info("ContractObserver: Ended employment due to contract end", [
                    'contract_id' => $contract->id,
                    'employee_id' => $contract->employee_id,
                    'employment_id' => $contract->employment_id,
                    'status' => $contract->status,
                    'end_date' => $endDate->toDateString(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("ContractObserver: Failed to end employment on contract end", [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Contract "deleted" event.
     *
     * When a contract is deleted, check if its employment still has other contracts.
     * If not, consider deleting the employment too.
     */
    public function deleted(Contract $contract): void
    {
        try {
            if (!$contract->employment_id) {
                return;
            }

            $employment = $contract->employment;
            if (!$employment) {
                return;
            }

            // If this was the only contract in this employment, optionally delete employment
            $remainingContracts = $employment->contracts()->count();

            if ($remainingContracts === 0) {
                Log::info("EmploymentResolver: No contracts remaining, deleting employment", [
                    'employment_id' => $employment->id,
                    'employee_id' => $employment->employee_id,
                ]);

                $employment->delete();
            }
        } catch (\Exception $e) {
            Log::error("EmploymentResolver: Failed to clean up employment after contract deletion", [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
