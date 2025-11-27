<?php

namespace App\Notifications;

use App\Models\Contract;
use App\Models\User;
use App\Enums\ContractTerminationReason;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractTerminatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Contract $contract;
    protected User $terminator;
    protected string $reason;
    protected ?string $note;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract, User $terminator, string $reason, ?string $note = null)
    {
        $this->contract = $contract;
        $this->terminator = $terminator;
        $this->reason = $reason;
        $this->note = $note;
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
        $reasonEnum = ContractTerminationReason::from($this->reason);
        $reasonLabel = $reasonEnum->label();

        $mail = (new MailMessage)
            ->subject('Thông báo chấm dứt hợp đồng lao động')
            ->greeting('Xin chào ' . $notifiable->name . ',')
            ->line('Hợp đồng lao động của bạn đã bị chấm dứt.')
            ->line('**Số hợp đồng:** ' . $this->contract->contract_number)
            ->line('**Nhân viên:** ' . $this->contract->employee->full_name)
            ->line('**Lý do:** ' . $reasonLabel)
            ->line('**Ngày chấm dứt:** ' . $this->contract->terminated_at->format('d/m/Y'));

        if ($this->note) {
            $mail->line('**Ghi chú:** ' . $this->note);
        }

        $mail->line('**Người thực hiện:** ' . $this->terminator->name)
            ->action('Xem chi tiết', url('/contracts/' . $this->contract->id))
            ->line('Cảm ơn bạn đã đóng góp cho công ty.');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $reasonEnum = ContractTerminationReason::from($this->reason);

        return [
            'type' => 'contract_terminated',
            'contract_id' => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'employee_name' => $this->contract->employee->full_name,
            'employee_code' => $this->contract->employee->employee_code,
            'reason' => $this->reason,
            'reason_label' => $reasonEnum->label(),
            'terminated_at' => $this->contract->terminated_at->format('Y-m-d'),
            'terminator_name' => $this->terminator->name,
            'note' => $this->note,
            'message' => 'Hợp đồng ' . $this->contract->contract_number . ' đã bị chấm dứt: ' . $reasonEnum->label(),
            'action_url' => '/contracts/' . $this->contract->id,
        ];
    }
}
