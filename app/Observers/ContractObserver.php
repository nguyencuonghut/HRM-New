<?php

namespace App\Observers;

use App\Models\Contract;
use App\Services\EmploymentResolver;
use Illuminate\Support\Facades\Log;

class ContractObserver
{
    protected EmploymentResolver $resolver;

    public function __construct(EmploymentResolver $resolver)
    {
        $this->resolver = $resolver;
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
