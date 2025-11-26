<?php

namespace App\Notifications;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Contract $contract;
    protected User $rejector;
    protected ?string $comments;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract, User $rejector, ?string $comments = null)
    {
        $this->contract = $contract;
        $this->rejector = $rejector;
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
            ->subject('Hợp đồng đã bị từ chối')
            ->greeting('Xin chào ' . $notifiable->name . ',')
            ->line('Hợp đồng của bạn đã bị từ chối.')
            ->line('**Hợp đồng:** ' . $this->contract->contract_number)
            ->line('**Người từ chối:** ' . $this->rejector->name)
            ->line('**Trạng thái:** ' . $this->contract->status_label);

        if ($this->comments) {
            $mail->line('**Lý do:** ' . $this->comments);
        }

        return $mail
            ->action('Xem chi tiết', url('/contracts/' . $this->contract->id))
            ->line('Vui lòng kiểm tra và chỉnh sửa hợp đồng nếu cần.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'contract_rejected',
            'contract_id' => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'rejector_name' => $this->rejector->name,
            'comments' => $this->comments,
            'status' => $this->contract->status,
            'message' => 'Hợp đồng ' . $this->contract->contract_number . ' đã bị ' . $this->rejector->name . ' từ chối',
            'action_url' => '/contracts/' . $this->contract->id,
        ];
    }
}
