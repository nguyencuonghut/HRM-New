<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InsuranceConfigResolver;
use App\Models\InsuranceConfigSet;
use App\Models\InsuranceMinimumWageConfig;
use App\Models\InsuranceSalaryGradeConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InsuranceConfigResolverTest extends TestCase
{
    use RefreshDatabase;

    protected InsuranceConfigResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(InsuranceConfigResolver::class);
    }

    /**
     * Test: Lấy config set ACTIVE theo ngày
     */
    public function test_get_active_set_returns_correct_config()
    {
        // Tạo config set ACTIVE
        $activeSet = InsuranceConfigSet::create([
            'code' => 'VN_INS_2024',
            'name' => 'Bảng lương BHXH 2024',
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'status' => 'ACTIVE',
        ]);

        // Test: Lấy config trong khoảng thời gian
        $result = $this->resolver->getActiveSet('2024-06-15');
        $this->assertNotNull($result);
        $this->assertEquals('VN_INS_2024', $result->code);
    }

    /**
     * Test: Không tìm thấy config nếu ngoài thời gian hiệu lực
     */
    public function test_get_active_set_returns_null_outside_date_range()
    {
        InsuranceConfigSet::create([
            'code' => 'VN_INS_2024',
            'name' => 'Bảng lương BHXH 2024',
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'status' => 'ACTIVE',
        ]);

        // Test: Ngày ngoài khoảng
        $result = $this->resolver->getActiveSet('2023-12-31');
        $this->assertNull($result);

        $result = $this->resolver->getActiveSet('2025-01-01');
        $this->assertNull($result);
    }

    /**
     * Test: Chỉ lấy config có status = ACTIVE
     */
    public function test_get_active_set_only_returns_active_status()
    {
        // Tạo config DRAFT
        InsuranceConfigSet::create([
            'code' => 'VN_INS_DRAFT',
            'name' => 'Bảng lương BHXH Draft',
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'status' => 'DRAFT',
        ]);

        // Tạo config ARCHIVED
        InsuranceConfigSet::create([
            'code' => 'VN_INS_ARCHIVED',
            'name' => 'Bảng lương BHXH Archived',
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'status' => 'ARCHIVED',
        ]);

        // Test: Không lấy được DRAFT/ARCHIVED
        $result = $this->resolver->getActiveSet('2024-06-15');
        $this->assertNull($result);
    }

    /**
     * Test: Lấy lương tối thiểu theo vùng
     */
    public function test_get_minimum_wage_returns_correct_amount()
    {
        // Tạo config set + minimum wages
        $configSet = InsuranceConfigSet::create([
            'code' => 'VN_INS_2024',
            'name' => 'Bảng lương BHXH 2024',
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'status' => 'ACTIVE',
        ]);

        InsuranceMinimumWageConfig::create([
            'config_set_id' => $configSet->id,
            'region' => 1,
            'amount' => 4960000,
        ]);

        InsuranceMinimumWageConfig::create([
            'config_set_id' => $configSet->id,
            'region' => 2,
            'amount' => 4410000,
        ]);

        // Test: Lấy lương tối thiểu vùng 1
        $amount = $this->resolver->getMinimumWage(1, '2024-06-15');
        $this->assertEquals(4960000.0, $amount);

        // Test: Lấy lương tối thiểu vùng 2
        $amount = $this->resolver->getMinimumWage(2, '2024-06-15');
        $this->assertEquals(4410000.0, $amount);
    }

    /**
     * Test: Validate region (1-4)
     */
    public function test_get_minimum_wage_validates_region()
    {
        $configSet = InsuranceConfigSet::create([
            'code' => 'VN_INS_2024',
            'name' => 'Bảng lương BHXH 2024',
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'status' => 'ACTIVE',
        ]);

        InsuranceMinimumWageConfig::create([
            'config_set_id' => $configSet->id,
            'region' => 1,
            'amount' => 4960000,
        ]);

        // Test: Region không hợp lệ
        $this->assertNull($this->resolver->getMinimumWage(0, '2024-06-15'));
        $this->assertNull($this->resolver->getMinimumWage(5, '2024-06-15'));
        $this->assertNull($this->resolver->getMinimumWage(-1, '2024-06-15'));
    }

    /**
     * Test: Lấy hệ số bậc lương
     */
    public function test_get_grade_coefficient_returns_correct_value()
    {
        $configSet = InsuranceConfigSet::create([
            'code' => 'VN_INS_2024',
            'name' => 'Bảng lương BHXH 2024',
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'status' => 'ACTIVE',
        ]);

        InsuranceSalaryGradeConfig::create([
            'config_set_id' => $configSet->id,
            'grade' => 1,
            'name' => 'Bậc 1',
            'coefficient' => 1.00,
        ]);

        InsuranceSalaryGradeConfig::create([
            'config_set_id' => $configSet->id,
            'grade' => 3,
            'name' => 'Bậc 3',
            'coefficient' => 1.10,
        ]);

        // Test: Lấy hệ số bậc 1
        $coefficient = $this->resolver->getGradeCoefficient(1, '2024-06-15');
        $this->assertEquals(1.00, $coefficient);

        // Test: Lấy hệ số bậc 3
        $coefficient = $this->resolver->getGradeCoefficient(3, '2024-06-15');
        $this->assertEquals(1.10, $coefficient);
    }

    /**
     * Test: Validate grade (1-7)
     */
    public function test_get_grade_coefficient_validates_grade()
    {
        $configSet = InsuranceConfigSet::create([
            'code' => 'VN_INS_2024',
            'name' => 'Bảng lương BHXH 2024',
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'status' => 'ACTIVE',
        ]);

        InsuranceSalaryGradeConfig::create([
            'config_set_id' => $configSet->id,
            'grade' => 1,
            'name' => 'Bậc 1',
            'coefficient' => 1.00,
        ]);

        // Test: Grade không hợp lệ
        $this->assertNull($this->resolver->getGradeCoefficient(0, '2024-06-15'));
        $this->assertNull($this->resolver->getGradeCoefficient(8, '2024-06-15'));
        $this->assertNull($this->resolver->getGradeCoefficient(-1, '2024-06-15'));
    }

    /**
     * Test: Tính lương BHXH (lương tối thiểu × hệ số)
     */
    public function test_calculate_insurance_salary()
    {
        $configSet = InsuranceConfigSet::create([
            'code' => 'VN_INS_2024',
            'name' => 'Bảng lương BHXH 2024',
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'status' => 'ACTIVE',
        ]);

        InsuranceMinimumWageConfig::create([
            'config_set_id' => $configSet->id,
            'region' => 2,
            'amount' => 4410000,
        ]);

        InsuranceSalaryGradeConfig::create([
            'config_set_id' => $configSet->id,
            'grade' => 3,
            'name' => 'Bậc 3',
            'coefficient' => 1.10,
        ]);

        // Test: Tính lương BHXH = 4,410,000 × 1.10 = 4,851,000
        $salary = $this->resolver->calculate(2, 3, '2024-06-15');
        $this->assertEquals(4851000.0, $salary);
    }

    /**
     * Test: calculate() trả về null nếu thiếu config
     */
    public function test_calculate_returns_null_when_missing_config()
    {
        $configSet = InsuranceConfigSet::create([
            'code' => 'VN_INS_2024',
            'name' => 'Bảng lương BHXH 2024',
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'status' => 'ACTIVE',
        ]);

        // Chỉ tạo wage, không tạo grade
        InsuranceMinimumWageConfig::create([
            'config_set_id' => $configSet->id,
            'region' => 2,
            'amount' => 4410000,
        ]);

        // Test: Thiếu grade config
        $salary = $this->resolver->calculate(2, 3, '2024-06-15');
        $this->assertNull($salary);
    }

    /**
     * Test: Lấy tất cả lương tối thiểu (4 vùng)
     */
    public function test_get_all_minimum_wages()
    {
        $configSet = InsuranceConfigSet::create([
            'code' => 'VN_INS_2024',
            'name' => 'Bảng lương BHXH 2024',
            'effective_from' => '2024-01-01',
            'effective_to' => '2024-12-31',
            'status' => 'ACTIVE',
        ]);

        InsuranceMinimumWageConfig::create([
            'config_set_id' => $configSet->id,
            'region' => 1,
            'amount' => 4960000,
        ]);

        InsuranceMinimumWageConfig::create([
            'config_set_id' => $configSet->id,
            'region' => 2,
            'amount' => 4410000,
        ]);

        InsuranceMinimumWageConfig::create([
            'config_set_id' => $configSet->id,
            'region' => 3,
            'amount' => 3860000,
        ]);

        InsuranceMinimumWageConfig::create([
            'config_set_id' => $configSet->id,
            'region' => 4,
            'amount' => 3450000,
        ]);

        // Test: Lấy tất cả 4 vùng
        $wages = $this->resolver->getAllMinimumWages('2024-06-15');
        $this->assertCount(4, $wages);
        $this->assertEquals(4960000, $wages[0]['amount']);
        $this->assertEquals(4410000, $wages[1]['amount']);
        $this->assertEquals(3860000, $wages[2]['amount']);
        $this->assertEquals(3450000, $wages[3]['amount']);
    }

    /**
     * Test: Sử dụng ngày hiện tại nếu không truyền tham số
     */
    public function test_uses_current_date_when_date_not_provided()
    {
        $today = now()->format('Y-m-d');
        $configSet = InsuranceConfigSet::create([
            'code' => 'VN_INS_CURRENT',
            'name' => 'Bảng lương BHXH hiện tại',
            'effective_from' => now()->subYear()->format('Y-m-d'),
            'effective_to' => now()->addYear()->format('Y-m-d'),
            'status' => 'ACTIVE',
        ]);

        InsuranceMinimumWageConfig::create([
            'config_set_id' => $configSet->id,
            'region' => 1,
            'amount' => 5000000,
        ]);

        // Test: Không truyền date, dùng hôm nay
        $amount = $this->resolver->getMinimumWage(1);
        $this->assertEquals(5000000.0, $amount);
    }
}
