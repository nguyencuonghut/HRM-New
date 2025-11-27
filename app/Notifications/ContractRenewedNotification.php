<?php

namespace App\Notifications;

use App\Models\ContractAppendix;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractRenewedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected ContractAppendix $appendix;
    protected User $creator;
    protected string $recipientType; // 'employee', 'manager', 'head'
    protected ?string $recipientName;

    /**
     * Create a new notification instance.
     */
    public function __construct(ContractAppendix $appendix, User $creator, string $recipientType = 'employee', ?string $recipientName = null)
    {
        $this->appendix = $appendix;
        $this->creator = $creator;
        $this->recipientType = $recipientType;
        $this->recipientName = $recipientName;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $contract = $this->appendix->contract;
        $employee = $contract->employee;

        // Xác định tên người nhận
        if ($notifiable instanceof \Illuminate\Notifications\AnonymousNotifiable) {
            $recipientName = $this->recipientName ?? $employee->full_name;
        } else {
            $recipientName = $notifiable->name ?? 'Bạn';
        }

        // Message khác nhau tùy loại người nhận
        $messageText = $this->recipientType === 'employee'
            ? 'Hợp đồng lao động của bạn đã được yêu cầu gia hạn và đang chờ phê duyệt.'
            : 'Hợp đồng lao động dưới đây đã được yêu cầu gia hạn và đang chờ phê duyệt.';

        $mail = (new MailMessage)
            ->subject('Thông báo yêu cầu gia hạn hợp đồng lao động')
            ->greeting('Xin chào ' . $recipientName . ',')
            ->line($messageText)
            ->line('**Số hợp đồng:** ' . $contract->contract_number)
            ->line('**Nhân viên:** ' . $employee->full_name)
            ->line('**Số phụ lục:** ' . $this->appendix->appendix_no)
            ->line('**Ngày hết hạn hiện tại:** ' . ($contract->end_date ? $contract->end_date->format('d/m/Y') : 'Không xác định'))
            ->line('**Ngày hết hạn mới:** ' . $this->appendix->end_date->format('d/m/Y'))
            ->line('**Người yêu cầu:** ' . $this->creator->name);

        if ($this->appendix->note) {
            $mail->line('**Ghi chú:** ' . $this->appendix->note);
        }

        $mail->action('Xem chi tiết', url('/contracts/' . $contract->id));

        // Chỉ hiện lời cảm ơn cho employee
        if ($this->recipientType === 'employee') {
            $mail->line('Cảm ơn bạn đã đồng hành cùng công ty.');
        }

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'appendix_id' => $this->appendix->id,
            'contract_id' => $this->appendix->contract_id,
            'appendix_no' => $this->appendix->appendix_no,
            'contract_number' => $this->appendix->contract->contract_number,
            'employee_name' => $this->appendix->contract->employee->full_name,
            'old_end_date' => $this->appendix->contract->end_date?->format('Y-m-d'),
            'new_end_date' => $this->appendix->end_date->format('Y-m-d'),
            'creator_name' => $this->creator->name,
            'recipient_type' => $this->recipientType,
        ];
    }
}
