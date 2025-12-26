<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\InsuranceGradeSuggestion;
use App\Services\InsuranceSalaryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Command: Quét và tạo đề xuất tăng bậc BHXH
 *
 * Chạy hàng tháng (cron: 0 0 1 * *)
 *
 * Workflow:
 * 1. Lấy tất cả nhân viên active có insurance profile
 * 2. Tính thâm niên tại vị trí hiện tại
 * 3. Kiểm tra đủ điều kiện tăng bậc (3 năm/bậc)
 * 4. Tạo suggestion nếu chưa có suggestion PENDING cho nhân viên này
 * 5. Gửi notification cho HR
 *
 * Usage:
 * php artisan insurance:suggest-grade-raise
 * php artisan insurance:suggest-grade-raise --dry-run
 * php artisan insurance:suggest-grade-raise --force (bỏ qua check pending)
 */
class SuggestInsuranceGradeRaiseCommand extends Command
{
    protected $signature = 'insurance:suggest-grade-raise
                            {--dry-run : Chạy thử không lưu DB}
                            {--force : Bỏ qua check suggestion pending}';

    protected $description = 'Quét nhân viên và tạo đề xuất tăng bậc BHXH (chạy hàng tháng)';

    protected InsuranceSalaryService $insuranceService;

    public function __construct(InsuranceSalaryService $insuranceService)
    {
        parent::__construct();
        $this->insuranceService = $insuranceService;
    }

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $isForce = $this->option('force');

        $this->info('🔍 Bắt đầu quét nhân viên đủ điều kiện tăng bậc BHXH...');
        $this->newLine();

        // Lấy nhân viên active có insurance profile
        $employees = Employee::with('currentInsuranceProfile.position')
            ->whereHas('currentInsuranceProfile')
            ->where('status', 'active')
            ->get();

        $this->info("✓ Tìm thấy {$employees->count()} nhân viên có hồ sơ BHXH");
        $this->newLine();

        $eligible = [];
        $skipped = [];
        $errors = [];

        foreach ($employees as $employee) {
            try {
                $suggestion = $this->insuranceService->suggestGradeRaise($employee);

                if (!$suggestion || !$suggestion['eligible']) {
                    continue; // Không đủ điều kiện
                }

                // Check đã có suggestion PENDING chưa
                if (!$isForce) {
                    $existingPending = InsuranceGradeSuggestion::where('employee_id', $employee->id)
                        ->pending()
                        ->exists();

                    if ($existingPending) {
                        $skipped[] = [
                            'employee' => $employee->name,
                            'reason' => 'Đã có suggestion PENDING',
                        ];
                        continue;
                    }
                }

                // Nhân viên đủ điều kiện
                $eligible[] = [
                    'employee' => $employee,
                    'suggestion' => $suggestion,
                ];

            } catch (\Exception $e) {
                $errors[] = [
                    'employee' => $employee->name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $this->newLine();
        $this->info("📊 KẾT QUẢ QUÉT:");
        $this->table(
            ['Chỉ số', 'Số lượng'],
            [
                ['Tổng nhân viên', $employees->count()],
                ['Đủ điều kiện tăng bậc', count($eligible)],
                ['Bỏ qua (đã có suggestion)', count($skipped)],
                ['Lỗi', count($errors)],
            ]
        );

        // Hiển thị danh sách đủ điều kiện
        if (count($eligible) > 0) {
            $this->newLine();
            $this->info('👥 DANH SÁCH ĐỦ ĐIỀU KIỆN TĂNG BẬC:');
            $this->table(
                ['Nhân viên', 'Vị trí', 'Thâm niên', 'Bậc hiện tại', 'Bậc đề xuất'],
                collect($eligible)->map(function ($item) {
                    $emp = $item['employee'];
                    $sug = $item['suggestion'];
                    return [
                        $emp->name,
                        $emp->currentInsuranceProfile->position->title ?? '-',
                        $sug['tenure_years'] . ' năm',
                        'Bậc ' . $sug['current_grade'],
                        'Bậc ' . $sug['suggested_grade'],
                    ];
                })->toArray()
            );

            // Tạo suggestions
            if (!$isDryRun) {
                $this->newLine();
                $this->info('💾 Đang tạo suggestions...');

                $created = 0;
                DB::transaction(function () use ($eligible, &$created) {
                    foreach ($eligible as $item) {
                        $emp = $item['employee'];
                        $sug = $item['suggestion'];

                        InsuranceGradeSuggestion::create([
                            'employee_id' => $emp->id,
                            'current_grade' => $sug['current_grade'],
                            'suggested_grade' => $sug['suggested_grade'],
                            'tenure_years' => $sug['tenure_years'],
                            'reason' => "Đủ {$sug['tenure_years']} năm thâm niên tại vị trí {$emp->currentInsuranceProfile->position->title}",
                            'status' => 'PENDING',
                            'suggested_at' => now(),
                            'expires_at' => now()->addDays(90), // Hết hạn sau 90 ngày
                        ]);

                        $created++;
                    }
                });

                $this->info("✓ Đã tạo {$created} suggestion");

                // TODO: Gửi notification cho HR
                // Notification::send($hrUsers, new NewInsuranceGradeSuggestions($created));
            } else {
                $this->warn('⚠ DRY RUN: Không lưu vào database');
            }
        } else {
            $this->info('ℹ Không có nhân viên nào đủ điều kiện tăng bậc');
        }

        // Hiển thị lỗi (nếu có)
        if (count($errors) > 0) {
            $this->newLine();
            $this->error('❌ CÓ LỖI XẢY RA:');
            $this->table(
                ['Nhân viên', 'Lỗi'],
                collect($errors)->map(fn($err) => [$err['employee'], $err['error']])->toArray()
            );
        }

        // Hiển thị bỏ qua (nếu có)
        if (count($skipped) > 0) {
            $this->newLine();
            $this->warn('⏭ BỎ QUA:');
            $this->table(
                ['Nhân viên', 'Lý do'],
                collect($skipped)->map(fn($s) => [$s['employee'], $s['reason']])->toArray()
            );
        }

        $this->newLine();
        $this->info('✅ Hoàn thành!');

        return Command::SUCCESS;
    }
}
