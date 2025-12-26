<?php

namespace App\Console\Commands;

use App\Models\InsuranceGradeSuggestion;
use Illuminate\Console\Command;

/**
 * Command: Đánh dấu suggestions quá hạn
 *
 * Chạy hàng ngày (cron: 0 0 * * *)
 *
 * Đánh dấu EXPIRED cho suggestions:
 * - status = PENDING
 * - expires_at < today
 *
 * Usage:
 * php artisan insurance:expire-suggestions
 */
class ExpireInsuranceSuggestionsCommand extends Command
{
    protected $signature = 'insurance:expire-suggestions';

    protected $description = 'Đánh dấu đề xuất tăng bậc BHXH đã quá hạn (chạy hàng ngày)';

    public function handle()
    {
        $this->info('🔍 Đang kiểm tra suggestions quá hạn...');

        $expiredSuggestions = InsuranceGradeSuggestion::expired()->get();

        if ($expiredSuggestions->isEmpty()) {
            $this->info('✓ Không có suggestion nào quá hạn');
            return Command::SUCCESS;
        }

        $this->info("⚠ Tìm thấy {$expiredSuggestions->count()} suggestion quá hạn");

        foreach ($expiredSuggestions as $suggestion) {
            $suggestion->markExpired();
            $this->line("  - {$suggestion->employee->name}: Bậc {$suggestion->current_grade} → {$suggestion->suggested_grade}");
        }

        $this->info("✅ Đã đánh dấu EXPIRED cho {$expiredSuggestions->count()} suggestion");

        return Command::SUCCESS;
    }
}
