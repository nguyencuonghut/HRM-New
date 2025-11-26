<?php

namespace App\Listeners;

use App\Events\ContractSubmitted;
use App\Models\ContractApproval;
use App\Notifications\ContractApprovalRequested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Events\Attributes\ListensTo;

#[ListensTo(ContractSubmitted::class)]
class SendContractApprovalRequestNotification implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue;

    /**
     * The unique ID of the listener.
     */
    public function uniqueId(): string
    {
        return static::class . '-' . ($this->event->contract->id ?? 'unknown');
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public int $uniqueFor = 60;

    /**
     * Handle the event.
     */
    public function handle(ContractSubmitted $event): void
    {
        $contract = $event->contract;

        \Log::info('SendContractApprovalRequestNotification::handle called', [
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
        ]);

        // Lấy danh sách người phê duyệt (Director/HR Head) từ contract_approvals
        $approvers = ContractApproval::where('contract_id', $contract->id)
            ->where('status', 'PENDING')
            ->whereNotNull('approver_id')
            ->with('approver')
            ->get()
            ->pluck('approver')
            ->filter()
            ->unique('id'); // Loại bỏ duplicate approvers

        // Gửi notification cho từng người phê duyệt
        foreach ($approvers as $approver) {
            // Check xem đã gửi notification cho contract này chưa (trong vòng 1 phút)
            $recentNotification = $approver->notifications()
                ->where('type', 'App\Notifications\ContractApprovalRequested')
                ->where('created_at', '>=', now()->subMinute())
                ->whereRaw("JSON_EXTRACT(data, '$.contract_id') = ?", [$contract->id])
                ->exists();

            if (!$recentNotification) {
                $approver->notify(new ContractApprovalRequested($contract));
            }
        }
    }
}
