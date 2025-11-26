<?php

namespace App\Notifications;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Contract $contract;
    protected User $approver;
    protected ?string $comments;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract, User $approver, ?string $comments = null)
    {
        $this->contract = $contract;
        $this->approver = $approver;
        $this->comments = $comments;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Hợp đồng đã được phê duyệt')
            ->greeting('Xin chào ' . $notifiable->name . ',')
            ->line('Hợp đồng của bạn đã được phê duyệt.')
            ->line('**Hợp đồng:** ' . $this->contract->contract_number)
            ->line('**Người phê duyệt:** ' . $this->approver->name)
            ->line('**Trạng thái:** ' . $this->contract->status_label);

        if ($this->comments) {
            $mail->line('**Ý kiến:** ' . $this->comments);
        }

        return $mail
            ->action('Xem chi tiết', url('/contracts/' . $this->contract->id))
            ->line('Cảm ơn bạn!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contract_approved',
            'contract_id' => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'approver_name' => $this->approver->name,
            'comments' => $this->comments,
            'status' => $this->contract->status,
            'message' => 'Hợp đồng ' . $this->contract->contract_number . ' đã được ' . $this->approver->name . ' phê duyệt',
            'action_url' => '/contracts/' . $this->contract->id,
        ];
    }
}
