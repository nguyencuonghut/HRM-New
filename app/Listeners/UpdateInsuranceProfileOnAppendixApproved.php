<?php

namespace App\Listeners;

use App\Events\AppendixApproved;
use App\Services\EmployeeInsuranceProfileService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Events\Attributes\ListensTo;

/**
 * Listener: Cập nhật insurance profile khi Appendix được duyệt (status → ACTIVE)
 *
 * Event: AppendixApproved
 * Action: Dispatch theo appendix_type:
 *   - SALARY → updateProfileFromSalaryAppendix()
 *   - POSITION → updateProfileFromPositionAppendix()
 */
#[ListensTo(AppendixApproved::class)]
class UpdateInsuranceProfileOnAppendixApproved implements ShouldQueue
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
    public function handle(AppendixApproved $event): void
    {
        try {
            Log::info("UpdateInsuranceProfileOnAppendixApproved listener triggered", [
                'appendix_id' => $event->appendix->id,
                'appendix_type' => $event->appendix->appendix_type->value,
                'contract_id' => $event->appendix->contract_id,
            ]);

            // Chỉ xử lý nếu appendix có status ACTIVE
            if ($event->appendix->status !== 'ACTIVE') {
                Log::info("Appendix is not ACTIVE, skipping insurance profile update", [
                    'appendix_id' => $event->appendix->id,
                    'status' => $event->appendix->status,
                ]);
                return;
            }

            $profile = null;

            // Dispatch theo appendix_type
            switch ($event->appendix->appendix_type->value) {
                case 'SALARY':
                    $profile = $this->insuranceProfileService->updateProfileFromSalaryAppendix($event->appendix);
                    break;

                case 'POSITION':
                    $profile = $this->insuranceProfileService->updateProfileFromPositionAppendix($event->appendix);
                    break;

                case 'EXTENSION':
                    // EXTENSION không cần update insurance profile (renewal là contract mới)
                    Log::info("Appendix is EXTENSION type, no insurance profile update needed", [
                        'appendix_id' => $event->appendix->id,
                    ]);
                    return;

                default:
                    // Các loại appendix khác (CONTENT, OTHER) không liên quan đến BHXH
                    Log::info("Appendix type does not affect insurance profile", [
                        'appendix_id' => $event->appendix->id,
                        'appendix_type' => $event->appendix->appendix_type->value,
                    ]);
                    return;
            }

            if ($profile) {
                Log::info("Insurance profile updated successfully", [
                    'appendix_id' => $event->appendix->id,
                    'profile_id' => $profile->id,
                    'appendix_type' => $event->appendix->appendix_type->value,
                ]);
            } else {
                Log::warning("Insurance profile not updated (may be missing data)", [
                    'appendix_id' => $event->appendix->id,
                    'appendix_type' => $event->appendix->appendix_type->value,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to update insurance profile on appendix approval", [
                'appendix_id' => $event->appendix->id,
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
    public function failed(AppendixApproved $event, \Throwable $exception): void
    {
        Log::error("UpdateInsuranceProfileOnAppendixApproved listener failed permanently", [
            'appendix_id' => $event->appendix->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
