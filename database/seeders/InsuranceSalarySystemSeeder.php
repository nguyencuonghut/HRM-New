<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeder: Dữ liệu mẫu cho hệ thống lương BHXH
 *
 * Bao gồm:
 * 1. Lương tối thiểu vùng (4 vùng)
 * 2. Thang hệ số 7 bậc cho tất cả positions (đọc từ JSON)
 *
 * Dữ liệu được đọc từ: database/data/insurance_salary_system.json
 *
 * Cách sử dụng:
 * php artisan db:seed --class=InsuranceSalarySystemSeeder
 *
 * Hoặc thêm vào DatabaseSeeder.php:
 * $this->call([InsuranceSalarySystemSeeder::class]);
 */
class InsuranceSalarySystemSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📋 Đang seed hệ thống lương BHXH...');

        // Đọc dữ liệu từ JSON
        $jsonPath = database_path('data/insurance_salary_system.json');

        if (!file_exists($jsonPath)) {
            $this->command->error("❌ Không tìm thấy file: {$jsonPath}");
            return;
        }

        $data = json_decode(file_get_contents($jsonPath), true);

        if (!$data) {
            $this->command->error('❌ Không thể parse JSON file');
            return;
        }

        // 1. Seed Lương tối thiểu vùng
        $this->seedMinimumWages($data['minimum_wages']);

        // 2. Seed Thang hệ số cho tất cả positions
        $this->seedPositionSalaryGrades($data['salary_grade_categories']);

        $this->command->info('✅ Hoàn thành seed hệ thống lương BHXH!');
    }

    /**
     * Seed dữ liệu lương tối thiểu vùng từ JSON
     */
    private function seedMinimumWages(array $wagesData): void
    {
        $this->command->info('  → Đang seed lương tối thiểu vùng...');

        $wages = [];
        foreach ($wagesData as $wage) {
            $wages[] = [
                'id' => Str::uuid()->toString(),
                'region' => $wage['region'],
                'amount' => $wage['amount'],
                'effective_from' => $wage['effective_from'],
                'effective_to' => $wage['effective_to'],
                'is_active' => $wage['is_active'],
                'note' => $wage['note'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('minimum_wages')->insert($wages);

        $this->command->info("  ✓ Đã seed " . count($wages) . " bản ghi lương tối thiểu vùng");
    }

    /**
     * Seed thang hệ số cho tất cả positions dựa trên categories từ JSON
     */
    private function seedPositionSalaryGrades(array $categories): void
    {
        $this->command->info('  → Đang seed thang hệ số cho positions...');

        $totalGrades = 0;
        $matchedCount = 0;
        $notFoundPositions = [];

        // Tạo map từ title → category để tìm nhanh
        $categoryMap = [];
        foreach ($categories as $category) {
            $titleKey = mb_strtolower(trim($category['title']), 'UTF-8');
            $categoryMap[$titleKey] = $category;
        }

        // Lấy tất cả positions từ DB
        $positions = DB::table('positions')->get();

        if ($positions->isEmpty()) {
            $this->command->warn('  ⚠ Không có position nào trong DB. Hãy chạy PositionSeeder trước.');
            return;
        }

        foreach ($positions as $position) {
            $positionTitleKey = mb_strtolower(trim($position->title), 'UTF-8');

            // Tìm category match chính xác theo title
            if (!isset($categoryMap[$positionTitleKey])) {
                $notFoundPositions[] = $position->title;
                continue;
            }

            $category = $categoryMap[$positionTitleKey];

            // Tạo 7 bậc cho position này
            $grades = [];
            for ($grade = 1; $grade <= 7; $grade++) {
                $grades[] = [
                    'id' => Str::uuid()->toString(),
                    'position_id' => $position->id,
                    'grade' => $grade,
                    'coefficient' => $category['coefficient'][$grade - 1],
                    'effective_from' => $category['effective_from'] ?? '2024-01-01',
                    'effective_to' => null,
                    'is_active' => true,
                    'note' => "Thang hệ số cho {$category['title']}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('position_salary_grades')->insert($grades);
            $totalGrades += count($grades);
            $matchedCount++;
        }

        $this->command->info("  ✓ Đã seed " . $totalGrades . " bậc lương cho " . $matchedCount . "/" . $positions->count() . " positions");

        // Hiển thị positions không tìm thấy trong JSON
        if (!empty($notFoundPositions)) {
            $this->command->warn("  ⚠ Không tìm thấy trong JSON (" . count($notFoundPositions) . " positions):");
            foreach (array_slice($notFoundPositions, 0, 10) as $title) {
                $this->command->line("    - {$title}");
            }
            if (count($notFoundPositions) > 10) {
                $this->command->line("    ... và " . (count($notFoundPositions) - 10) . " positions khác");
            }
        }
    }
}
