<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractApprovalRequested extends Notification implements ShouldQueue
{
    use Queueable;

    protected Contract $contract;

    /**
     * Create a new notification instance.
     */
    public function __construct(Contract $contract)
    {
        $this->contract = $contract;
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
        $contractTypeLabels = [
            'PROBATION' => 'Thử việc',
            'FIXED_TERM' => 'Xác định thời hạn',
            'INDEFINITE' => 'Không xác định thời hạn',
            'SEASONAL' => 'Theo mùa vụ',
            'PROJECT_BASED' => 'Theo dự án',
        ];

        $contractType = $contractTypeLabels[$this->contract->contract_type] ?? $this->contract->contract_type;

        return (new MailMessage)
            ->subject('Yêu cầu phê duyệt hợp đồng mới')
            ->greeting('Xin chào ' . $notifiable->name . ',')
            ->line('Bạn có một yêu cầu phê duyệt hợp đồng mới.')
            ->line('**Hợp đồng:** ' . $this->contract->contract_number)
            ->line('**Nhân viên:** ' . $this->contract->employee->full_name)
            ->line('**Loại HĐ:** ' . $contractType)
            ->line('**Ngày bắt đầu:** ' . $this->contract->start_date->format('d/m/Y'))
            ->action('Xem chi tiết', url('/contracts/' . $this->contract->id))
            ->line('Vui lòng xem xét và phê duyệt/từ chối yêu cầu này.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $contractTypeLabels = [
            'PROBATION' => 'Thử việc',
            'FIXED_TERM' => 'Xác định thời hạn',
            'INDEFINITE' => 'Không xác định thời hạn',
            'SEASONAL' => 'Theo mùa vụ',
            'PROJECT_BASED' => 'Theo dự án',
        ];

        $contractType = $contractTypeLabels[$this->contract->contract_type] ?? $this->contract->contract_type;

        return [
            'type' => 'contract_approval_requested',
            'contract_id' => $this->contract->id,
            'contract_number' => $this->contract->contract_number,
            'employee_name' => $this->contract->employee->full_name,
            'employee_code' => $this->contract->employee->employee_code,
            'contract_type' => $contractType,
            'message' => 'Yêu cầu phê duyệt hợp đồng ' . $this->contract->contract_number . ' cho nhân viên ' . $this->contract->employee->full_name,
            'action_url' => '/contracts/' . $this->contract->id,
        ];
    }
}
