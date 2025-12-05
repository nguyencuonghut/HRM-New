<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto Backup Scheduler
Schedule::command('backup:run-scheduled')
    ->everyMinute() // Chạy mỗi phút để check xem có backup nào cần chạy không
    ->withoutOverlapping() // Tránh chạy đồng thời nhiều backup
    ->runInBackground() // Chạy background để không block
    ->appendOutputTo(storage_path('logs/backup-scheduler.log'));

// Leave Balance Management
// Initialize leave balances for new year (January 1st at 00:00)
Schedule::command('leave:initialize-balances')
    ->yearlyOn(1, 1, '00:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/leave-initialize.log'));

// Reset carried forward leave balances (April 1st at 00:00)
Schedule::command('leave:reset-carried-forward')
    ->yearlyOn(4, 1, '00:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/leave-reset-carried-forward.log'));
