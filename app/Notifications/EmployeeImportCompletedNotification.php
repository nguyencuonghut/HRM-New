<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EmployeeImportCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $total;
    protected int $successCount;
    protected int $errorCount;

    public function __construct(int $total, int $successCount, int $errorCount)
    {
        $this->total = $total;
        $this->successCount = $successCount;
        $this->errorCount = $errorCount;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'employee_import_completed',
            'message' => "Import nhân viên hoàn thành: {$this->successCount}/{$this->total} thành công, {$this->errorCount} lỗi.",
            'total' => $this->total,
            'success_count' => $this->successCount,
            'error_count' => $this->errorCount,
        ];
    }
}
