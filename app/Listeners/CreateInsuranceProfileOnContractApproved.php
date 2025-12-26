<?php

namespace App\Listeners;

use App\Events\ContractApproved;
use App\Services\EmployeeInsuranceProfileService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Events\Attributes\ListensTo;

/**
 * Listener: Tạo insurance profile khi Contract được duyệt (status → ACTIVE)
 *
 * Event: ContractApproved
 * Action: Call EmployeeInsuranceProfileService::createProfileFromContract()
 */
#[ListensTo(ContractApproved::class)]
class CreateInsuranceProfileOnContractApproved implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * @var EmployeeInsuranceProfileService
     */
    protected $insuranceProfileService;

    /**
     * Create the event listener.
     */
    public function __construct(EmployeeInsuranceProfileService $insuranceProfileService)
    {
        $this->insuranceProfileService = $insuranceProfileService;
    }

    /**
     * Handle the event.
     */
    public function handle(ContractApproved $event): void
    {
        try {
            Log::info("CreateInsuranceProfileOnContractApproved listener triggered", [
                'contract_id' => $event->contract->id,
                'employee_id' => $event->contract->employee_id,
            ]);

            // Chỉ tạo profile nếu contract có status ACTIVE
            if ($event->contract->status !== 'ACTIVE') {
                Log::info("Contract is not ACTIVE, skipping insurance profile creation", [
                    'contract_id' => $event->contract->id,
                    'status' => $event->contract->status,
                ]);
                return;
            }

            // Tạo insurance profile
            $profile = $this->insuranceProfileService->createProfileFromContract($event->contract);

            if ($profile) {
                Log::info("Insurance profile created successfully", [
                    'contract_id' => $event->contract->id,
                    'profile_id' => $profile->id,
                ]);
            } else {
                Log::warning("Insurance profile not created (may already exist or missing data)", [
                    'contract_id' => $event->contract->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to create insurance profile on contract approval", [
                'contract_id' => $event->contract->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw để queue retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ContractApproved $event, \Throwable $exception): void
    {
        Log::error("CreateInsuranceProfileOnContractApproved listener failed permanently", [
            'contract_id' => $event->contract->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
