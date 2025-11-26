<?php

namespace App\Listeners;

use App\Events\ContractApproved;
use Illuminate\Events\Attributes\ListensTo;
use App\Notifications\ContractApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;

#[ListensTo(ContractApproved::class)]
class SendContractApprovedNotification implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue;

    public function uniqueId(): string
    {
        return static::class . '-' . ($this->event->contract->id ?? 'unknown');
    }

    public int $uniqueFor = 60;

    /**
     * Handle the event.
     */
    public function handle(ContractApproved $event): void
    {
        $contract = $event->contract;
        $approver = $event->approver;
        $comments = $event->comments;

        // Gửi notification cho người tạo hợp đồng
        if ($contract->created_by_user) {
            $contract->created_by_user->notify(
                new ContractApprovedNotification($contract, $approver, $comments)
            );
        }

        // Gửi notification cho nhân viên (nếu có tài khoản)
        if ($contract->employee && $contract->employee->user) {
            $contract->employee->user->notify(
                new ContractApprovedNotification($contract, $approver, $comments)
            );
        }
    }
}
