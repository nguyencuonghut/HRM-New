<?php

namespace App\Listeners;

use App\Events\ContractTerminated;
use App\Notifications\ContractTerminatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Events\Attributes\ListensTo;

#[ListensTo(ContractTerminated::class)]
class SendContractTerminatedNotification implements ShouldQueue, ShouldBeUnique
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
    public function handle(ContractTerminated $event): void
    {
        $contract = $event->contract;
        $terminator = $event->terminator;
        $reason = $event->reason;
        $note = $event->note;

        // Gửi notification cho nhân viên (nếu có tài khoản)
        if ($contract->employee && $contract->employee->user) {
            $contract->employee->user->notify(
                new ContractTerminatedNotification($contract, $terminator, $reason, $note)
            );
        }

        // Gửi notification cho người tạo hợp đồng (nếu khác với nhân viên)
        if ($contract->created_by_user &&
            $contract->created_by_user->id !== $contract->employee?->user?->id) {
            $contract->created_by_user->notify(
                new ContractTerminatedNotification($contract, $terminator, $reason, $note)
            );
        }

        // Gửi notification cho HR department (có thể dùng role-based notification)
        // TODO: Implement HR notification based on role or department
    }
}
