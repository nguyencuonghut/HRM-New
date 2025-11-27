<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Contract $contract;
    protected int $daysUntilExpiry;
    protected string $recipientType; // 'employee', 'manager', 'head'
    protected ?string $recipientName;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract, int $daysUntilExpiry, string $recipientType = 'employee', ?string $recipientName = null)
    {
        $this->contract = $contract;
        $this->daysUntilExpiry = $daysUntilExpiry;
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
        $employee = $this->contract->employee;

        // Xác định tên người nhận
        if ($notifiable instanceof \Illuminate\Notifications\AnonymousNotifiable) {
            $recipientName = $this->recipientName ?? $employee->full_name;
        } else {
            $recipientName = $notifiable->name ?? 'Bạn';
        }

        $urgencyLevel = $this->daysUntilExpiry <= 7 ? 'KHẨN CẤP' : ($this->daysUntilExpiry <= 15 ? 'QUAN TRỌNG' : 'THÔNG BÁO');

        // Message khác nhau tùy loại người nhận
        if ($this->recipientType === 'employee') {
            $messageText = "Hợp đồng lao động của bạn sẽ hết hạn trong **{$this->daysUntilExpiry} ngày**.";
            $actionText = 'Vui lòng liên hệ phòng nhân sự để chuẩn bị thủ tục gia hạn.';
        } else {
            $messageText = "Hợp đồng lao động dưới đây sẽ hết hạn trong **{$this->daysUntilExpiry} ngày**.";
            $actionText = 'Vui lòng chuẩn bị thủ tục gia hạn hoặc chấm dứt hợp đồng.';
        }

        $mail = (new MailMessage)
            ->subject("[{$urgencyLevel}] Hợp đồng lao động sắp hết hạn")
            ->greeting('Xin chào ' . $recipientName . ',')
            ->line($messageText)
            ->line('**Số hợp đồng:** ' . $this->contract->contract_number)
            ->line('**Nhân viên:** ' . $employee->full_name)
            ->line('**Ngày hết hạn:** ' . $this->contract->end_date->format('d/m/Y'))
            ->line($actionText)
            ->action('Xem chi tiết', url('/contracts/' . $this->contract->id));

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
            'contract_id' => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'employee_name' => $this->contract->employee->full_name,
            'end_date' => $this->contract->end_date->format('Y-m-d'),
            'days_until_expiry' => $this->daysUntilExpiry,
        ];
    }
}
