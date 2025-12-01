<?php

namespace App\Notifications;

use App\Models\ContractAppendix;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AppendixApprovalRequested extends Notification implements ShouldQueue
{
    use Queueable;

    protected ContractAppendix $appendix;

    /**
     * Create a new notification instance.
     */
    public function __construct(ContractAppendix $appendix)
    {
        $this->appendix = $appendix;
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

        return (new MailMessage)
            ->subject('Yêu cầu phê duyệt phụ lục hợp đồng')
            ->greeting('Xin chào ' . $notifiable->name . ',')
            ->line('Bạn có một yêu cầu phê duyệt phụ lục hợp đồng mới.')
            ->line('**Phụ lục:** ' . $this->appendix->appendix_no)
            ->line('**Hợp đồng:** ' . $contract->contract_number)
            ->line('**Nhân viên:** ' . $contract->employee->full_name)
            ->line('**Loại phụ lục:** ' . $appendixType)
            ->line('**Ngày hiệu lực:** ' . $this->appendix->effective_date->format('d/m/Y'))
            ->action('Xem chi tiết', url('/contracts/' . $contract->id . '?tab=appendixes'))
            ->line('Vui lòng xem xét và phê duyệt/từ chối yêu cầu này.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $contract = $this->appendix->contract;
        $appendixType = $this->appendix->appendix_type->label();

        return [
            'type' => 'appendix_approval_requested',
            'appendix_id' => $this->appendix->id,
            'appendix_no' => $this->appendix->appendix_no,
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'employee_name' => $contract->employee->full_name,
            'employee_code' => $contract->employee->employee_code,
            'appendix_type' => $appendixType,
            'message' => 'Yêu cầu phê duyệt phụ lục ' . $this->appendix->appendix_no . ' (' . $appendixType . ') cho hợp đồng ' . $contract->contract_number,
            'action_url' => '/contracts/' . $contract->id . '?tab=appendixes',
        ];
    }
}
