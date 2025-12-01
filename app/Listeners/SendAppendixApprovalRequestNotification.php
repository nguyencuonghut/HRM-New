<?php

namespace App\Listeners;

use App\Events\AppendixSubmitted;
use App\Notifications\AppendixApprovalRequested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Events\Attributes\ListensTo;

#[ListensTo(AppendixSubmitted::class)]
class SendAppendixApprovalRequestNotification implements ShouldQueue, ShouldBeUnique
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
    public function handle(AppendixSubmitted $event): void
    {
        $appendix = $event->appendix;
        $contract = $appendix->contract;

        \Log::info('SendAppendixApprovalRequestNotification::handle called', [
            'appendix_id' => $appendix->id,
            'appendix_no' => $appendix->appendix_no,
            'contract_id' => $contract->id,
        ]);

        // Lấy danh sách người có quyền phê duyệt contract (Director/HR Head)
        // Loại trừ người submit (nếu họ cũng có quyền approve)
        $currentUserId = auth()->id();
        $approvers = \App\Models\User::permission('approve contracts')
            ->where('id', '!=', $currentUserId)
            ->get();

        // Gửi notification cho từng người phê duyệt
        foreach ($approvers as $approver) {
            // Check xem đã gửi notification cho appendix này chưa (trong vòng 1 phút)
            $recentNotification = $approver->notifications()
                ->where('type', 'App\Notifications\AppendixApprovalRequested')
                ->where('created_at', '>=', now()->subMinute())
                ->whereRaw("JSON_EXTRACT(data, '$.appendix_id') = ?", [$appendix->id])
                ->exists();

            if (!$recentNotification) {
                $approver->notify(new AppendixApprovalRequested($appendix));
            }
        }
    }
}
