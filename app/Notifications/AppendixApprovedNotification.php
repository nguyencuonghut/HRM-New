<?php

namespace App\Notifications;

use App\Models\ContractAppendix;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppendixApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected ContractAppendix $appendix;
    protected User $approver;
    protected ?string $comments;

    /**
     * Create a new notification instance.
     */
    public function __construct(ContractAppendix $appendix, User $approver, ?string $comments = null)
    {
        $this->appendix = $appendix;
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
        $contract = $this->appendix->contract;
        $appendixType = $this->appendix->appendix_type->label();

        $mail = (new MailMessage)
            ->subject('Phụ lục hợp đồng đã được phê duyệt')
            ->greeting('Xin chào ' . $notifiable->name . ',')
            ->line('Phụ lục hợp đồng của bạn đã được phê duyệt.')
            ->line('**Phụ lục:** ' . $this->appendix->appendix_no)
            ->line('**Loại:** ' . $appendixType)
            ->line('**Hợp đồng:** ' . $contract->contract_number)
            ->line('**Người phê duyệt:** ' . $this->approver->name);

        if ($this->comments) {
            $mail->line('**Ý kiến:** ' . $this->comments);
        }

        return $mail
            ->action('Xem chi tiết', url('/contracts/' . $contract->id . '?tab=appendixes'))
            ->line('Cảm ơn bạn!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $contract = $this->appendix->contract;
        $appendixType = $this->appendix->appendix_type->label();

        return [
            'type' => 'appendix_approved',
            'appendix_id' => $this->appendix->id,
            'appendix_no' => $this->appendix->appendix_no,
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'approver_name' => $this->approver->name,
            'comments' => $this->comments,
            'appendix_type' => $appendixType,
            'status' => $this->appendix->status,
            'message' => 'Phụ lục ' . $this->appendix->appendix_no . ' (' . $appendixType . ') đã được ' . $this->approver->name . ' phê duyệt',
            'action_url' => '/contracts/' . $contract->id . '?tab=appendixes',
        ];
    }
}
