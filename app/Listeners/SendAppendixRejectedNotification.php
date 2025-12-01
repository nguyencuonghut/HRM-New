<?php

namespace App\Listeners;

use App\Events\AppendixRejected;
use App\Notifications\AppendixRejectedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Events\Attributes\ListensTo;

#[ListensTo(AppendixRejected::class)]
class SendAppendixRejectedNotification implements ShouldQueue, ShouldBeUnique
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
    public function handle(AppendixRejected $event): void
    {
        $appendix = $event->appendix;
        $rejector = $event->rejector;
        $comments = $event->comments;
        $contract = $appendix->contract;

        // Gửi notification cho người tạo contract (employee owner)
        if ($contract->employee && $contract->employee->user) {
            $contract->employee->user->notify(
                new AppendixRejectedNotification($appendix, $rejector, $comments)
            );
        }

        // Gửi notification cho HR hoặc người tạo contract nếu khác với employee
        if ($contract->created_by_user_id) {
            $creator = \App\Models\User::find($contract->created_by_user_id);
            if ($creator && (!$contract->employee->user || $creator->id !== $contract->employee->user->id)) {
                $creator->notify(
                    new AppendixRejectedNotification($appendix, $rejector, $comments)
                );
            }
        }
    }
}
