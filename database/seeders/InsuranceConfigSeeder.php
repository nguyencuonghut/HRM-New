<?php

namespace Database\Seeders;

use App\Models\InsuranceConfigSet;
use App\Models\InsuranceMinimumWageConfig;
use App\Models\InsuranceSalaryGradeConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder: Insurance Config System
 *
 * Import dữ liệu từ database/data/insurance_salary_system.json
 * vào hệ thống config mới (insurance_config_sets).
 *
 * Cách chạy:
 * php artisan db:seed --class=InsuranceConfigSeeder
 *
 * Hoặc thêm vào DatabaseSeeder:
 * $this->call([InsuranceConfigSeeder::class]);
 */
class InsuranceConfigSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📋 Đang import Insurance Config từ JSON...');

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

        DB::beginTransaction();

        try {
            // Tạo config set
            $configSet = $this->createConfigSet($data);
            $this->command->info("  ✓ Đã tạo Config Set: {$configSet->code}");

            // Import minimum wages
            $this->importMinimumWages($configSet, $data['minimum_wages']);
            $this->command->info('  ✓ Đã import ' . count($data['minimum_wages']) . ' minimum wages');

            // Import salary grades
            $gradesCount = $this->importSalaryGrades($configSet, $data['salary_grade_categories']);
            $this->command->info('  ✓ Đã import ' . $gradesCount . ' salary grade configs');

            // Validate config set
            $validation = $configSet->validateForActivation();
            if (!$validation['valid']) {
                $this->command->warn('⚠️  Config set có lỗi:');
                foreach ($validation['errors'] as $error) {
                    $this->command->warn("    - {$error}");
                }
                $this->command->warn('Config set sẽ ở trạng thái DRAFT');
            } else {
                // Activate config set
                $configSet->update([
                    'status' => InsuranceConfigSet::STATUS_ACTIVE,
                    'activated_at' => now(),
                ]);
                $this->command->info('  ✓ Config set đã được ACTIVATE');
            }

            DB::commit();

            $this->command->info('');
            $this->command->info('✅ Hoàn thành import Insurance Config!');
            $this->command->info("   Code: {$configSet->code}");
            $this->command->info("   Status: {$configSet->status}");
            $this->command->info("   Minimum Wages: {$configSet->minimumWages()->count()}");
            $this->command->info("   Salary Grades: {$configSet->salaryGrades()->count()}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('❌ Lỗi khi import: ' . $e->getMessage());
            $this->command->error($e->getTraceAsString());
        }
    }

    /**
     * Tạo config set từ dữ liệu JSON
     */
    private function createConfigSet(array $data): InsuranceConfigSet
    {
        // Lấy effective_from từ minimum_wages hoặc salary_grade_categories
        $effectiveFrom = $data['minimum_wages'][0]['effective_from'] ??
                        $data['salary_grade_categories'][0]['effective_from'] ??
                        '2026-01-01';

        // Tạo code từ effective_from
        $date = \Carbon\Carbon::parse($effectiveFrom);
        $code = 'VN_INS_' . $date->format('Y_m');

        // Kiểm tra xem đã có config set này chưa
        $existingSet = InsuranceConfigSet::where('code', $code)->first();
        if ($existingSet) {
            $this->command->warn("⚠️  Config set {$code} đã tồn tại, sẽ xóa và tạo lại");
            $existingSet->delete();
        }

        return InsuranceConfigSet::create([
            'code' => $code,
            'name' => 'Hệ thống lương BHXH ' . $date->format('m/Y'),
            'description' => 'Import từ insurance_salary_system.json - Nghị định 24/2023/NĐ-CP',
            'status' => InsuranceConfigSet::STATUS_DRAFT,
            'effective_from' => $effectiveFrom,
            'effective_to' => null,
            'notes' => 'Imported from JSON on ' . now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Import minimum wages vào config set
     */
    private function importMinimumWages(InsuranceConfigSet $configSet, array $wagesData): void
    {
        foreach ($wagesData as $wage) {
            InsuranceMinimumWageConfig::create([
                'config_set_id' => $configSet->id,
                'region' => $wage['region'],
                'amount' => $wage['amount'],
                'effective_from' => $wage['effective_from'] ?? null,
                'effective_to' => $wage['effective_to'] ?? null,
                'note' => $wage['note'] ?? null,
            ]);
        }
    }

    /**
     * Import salary grades vào config set
     *
     * Lưu ý: JSON hiện tại có categories theo position title,
     * nhưng config mới chỉ lưu 7 bậc chung cho tất cả position.
     *
     * Giải pháp: Tạo 7 bậc chuẩn từ categories đầu tiên hoặc
     * tính trung bình từ tất cả categories.
     */
    private function importSalaryGrades(InsuranceConfigSet $configSet, array $categories): int
    {
        // Tìm category có hệ số phổ biến nhất
        // Hoặc lấy category đầu tiên làm chuẩn
        // Ở đây tôi sẽ lấy "Nhân viên tạp vụ" vì có hệ số thấp nhất (base level)

        $baseCategory = null;
        foreach ($categories as $category) {
            if (stripos($category['title'], 'tạp vụ') !== false) {
                $baseCategory = $category;
                break;
            }
        }

        // Nếu không tìm thấy, lấy category đầu tiên
        if (!$baseCategory) {
            $baseCategory = $categories[0];
        }

        $coefficients = $baseCategory['coefficient'];

        // Đảm bảo có đủ 7 bậc
        if (count($coefficients) !== 7) {
            $this->command->warn("⚠️  Category '{$baseCategory['title']}' không có đủ 7 hệ số");
            // Padding nếu thiếu
            while (count($coefficients) < 7) {
                $coefficients[] = end($coefficients) * 1.1; // Tăng 10% cho bậc tiếp theo
            }
        }

        // Tạo 7 bậc
        for ($grade = 1; $grade <= 7; $grade++) {
            InsuranceSalaryGradeConfig::create([
                'config_set_id' => $configSet->id,
                'grade' => $grade,
                'name' => "Bậc {$grade}",
                'coefficient' => $coefficients[$grade - 1],
                'description' => "Hệ số bậc {$grade} (từ {$baseCategory['title']})",
                'is_active' => true,
            ]);
        }

        return 7;
    }
}

