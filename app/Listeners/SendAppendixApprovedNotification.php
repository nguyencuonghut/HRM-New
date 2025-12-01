<?php

namespace App\Listeners;

use App\Events\AppendixApproved;
use App\Notifications\AppendixApprovedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Events\Attributes\ListensTo;

#[ListensTo(AppendixApproved::class)]
class SendAppendixApprovedNotification implements ShouldQueue, ShouldBeUnique
{
    use InteractsWithQueue;

    /**
     * The unique ID of the listener.
     */
    public function uniqueId(): string
    {
        return static::class . '-' . ($this->event->appendix->id ?? 'unknown');
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public int $uniqueFor = 60;

    /**
     * Handle the event.
     */
    public function handle(AppendixApproved $event): void
    {
        $appendix = $event->appendix;
        $approver = $event->approver;
        $comments = $event->comments;
        $contract = $appendix->contract;

        // Gửi notification cho người tạo contract (employee owner)
        if ($contract->employee && $contract->employee->user) {
            $contract->employee->user->notify(
                new AppendixApprovedNotification($appendix, $approver, $comments)
            );
        }

        // Gửi notification cho HR hoặc người tạo contract nếu khác với employee
        // (Có thể có trường hợp HR tạo contract cho employee)
        if ($contract->created_by_user_id) {
            $creator = \App\Models\User::find($contract->created_by_user_id);
            if ($creator && (!$contract->employee->user || $creator->id !== $contract->employee->user->id)) {
                $creator->notify(
                    new AppendixApprovedNotification($appendix, $approver, $comments)
                );
            }
        }
    }
}
