<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\ContractAppendix;
use App\Models\Employee;
use App\Models\InsuranceComponent;
use App\Models\InsuranceParticipation;
use App\Models\InsuranceParticipationComponent;
use App\Services\InsuranceContributionCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceContributionCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InsuranceContributionCalculatorService $calculator;
    protected array $components;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = app(InsuranceContributionCalculatorService::class);

        // Seed insurance components
        $this->components = [
            'BHXH_HUU_TU' => InsuranceComponent::create([
                'code' => 'BHXH_HUU_TU',
                'name_vi' => 'BHXH Hưu trí - Tử tuất',
                'name_en' => 'Social Insurance - Retirement & Survivor',
                'default_rate_employee' => 0.08,
                'default_rate_employer' => 0.14,
                'default_rate_total' => 0.22,
                'is_active' => true,
            ]),
            'BHXH_BENH' => InsuranceComponent::create([
                'code' => 'BHXH_BENH',
                'name_vi' => 'BHXH Ốm đau - Thai sản',
                'name_en' => 'Social Insurance - Sickness & Maternity',
                'default_rate_employee' => 0.015,
                'default_rate_employer' => 0.015,
                'default_rate_total' => 0.03,
                'is_active' => true,
            ]),
            'BHXH_TNLD' => InsuranceComponent::create([
                'code' => 'BHXH_TNLD',
                'name_vi' => 'BHXH Tai nạn lao động - Bệnh nghề nghiệp',
                'name_en' => 'Social Insurance - Occupational Accident & Disease',
                'default_rate_employee' => 0,
                'default_rate_employer' => 0.005,
                'default_rate_total' => 0.005,
                'is_active' => true,
            ]),
            'BHTN' => InsuranceComponent::create([
                'code' => 'BHTN',
                'name_vi' => 'Bảo hiểm Thất nghiệp',
                'name_en' => 'Unemployment Insurance',
                'default_rate_employee' => 0.01,
                'default_rate_employer' => 0.01,
                'default_rate_total' => 0.02,
                'is_active' => true,
            ]),
            'BHYT' => InsuranceComponent::create([
                'code' => 'BHYT',
                'name_vi' => 'Bảo hiểm Y tế',
                'name_en' => 'Health Insurance',
                'default_rate_employee' => 0.015,
                'default_rate_employer' => 0.03,
                'default_rate_total' => 0.045,
                'is_active' => true,
            ]),
        ];
    }

    public function test_it_calculates_contributions_with_all_5_components_for_normal_employee(): void
    {
        $employee = Employee::factory()->create();

        // Create contract with insurance salary 10M
        $contract = Contract::factory()->create([
            'employee_id' => $employee->id,
            'insurance_salary' => 10000000,
            'contract_start_date' => '2026-01-01',
            'contract_end_date' => null,
            'status' => 'ACTIVE',
        ]);

        // Create participation with all 5 components
        $participation = InsuranceParticipation::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'ACTIVE',
            'participation_start_date' => '2026-01-01',
        ]);

        foreach ($this->components as $component) {
            InsuranceParticipationComponent::create([
                'participation_id' => $participation->id,
                'component_id' => $component->id,
                'is_enabled' => true,
                'base_type' => 'INSURANCE_SALARY',
                'rate_total' => $component->default_rate_total,
            ]);
        }

        // Calculate for January 2026
        $result = $this->calculator->calculateForEmployee($employee, '2026-01');

        $this->assertEquals($employee->id, $result['employee_id']);
        $this->assertEquals(10000000.0, $result['base_insurance_salary']);
        $this->assertCount(5, $result['components']);

        // Verify each component calculation
        $componentsByCode = collect($result['components'])->keyBy('component_code');

        // BHXH Hưu trí: 10M × 22% = 2.2M
        $this->assertEquals(10000000.0, $componentsByCode['BHXH_HUU_TU']['base_used']);
        $this->assertEquals(0.22, $componentsByCode['BHXH_HUU_TU']['rate_total']);
        $this->assertEquals(2200000.0, $componentsByCode['BHXH_HUU_TU']['amount']);

        // BHXH Ốm đau: 10M × 3% = 300K
        $this->assertEquals(300000.0, $componentsByCode['BHXH_BENH']['amount']);

        // BHXH TNLĐ: 10M × 0.5% = 50K
        $this->assertEquals(50000.0, $componentsByCode['BHXH_TNLD']['amount']);

        // BHTN: 10M × 2% = 200K
        $this->assertEquals(200000.0, $componentsByCode['BHTN']['amount']);

        // BHYT: 10M × 4.5% = 450K
        $this->assertEquals(450000.0, $componentsByCode['BHYT']['amount']);

        // Total: 2.2M + 300K + 50K + 200K + 450K = 3.2M
        $this->assertEquals(3200000.0, $result['total_amount']);
    }

    public function test_it_calculates_bhtn_with_fixed_amount_base_72m_cap(): void
    {
        $employee = Employee::factory()->create();

        // Employee có lương cao: 100M
        $contract = Contract::factory()->create([
            'employee_id' => $employee->id,
            'insurance_salary' => 100000000, // 100M
            'contract_start_date' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        $participation = InsuranceParticipation::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'ACTIVE',
        ]);

        // 4 components thông thường dùng INSURANCE_SALARY
        foreach (['BHXH_HUU_TU', 'BHXH_BENH', 'BHXH_TNLD', 'BHYT'] as $code) {
            InsuranceParticipationComponent::create([
                'participation_id' => $participation->id,
                'component_id' => $this->components[$code]->id,
                'is_enabled' => true,
                'base_type' => 'INSURANCE_SALARY',
                'rate_total' => $this->components[$code]->default_rate_total,
            ]);
        }

        // BHTN dùng FIXED_AMOUNT với mức trần 72M
        InsuranceParticipationComponent::create([
            'participation_id' => $participation->id,
            'component_id' => $this->components['BHTN']->id,
            'is_enabled' => true,
            'base_type' => 'FIXED_AMOUNT',
            'base_amount' => 72000000, // 72M
            'rate_total' => 0.02,
        ]);

        $result = $this->calculator->calculateForEmployee($employee, '2026-01');

        $componentsByCode = collect($result['components'])->keyBy('component_code');

        // BHXH các khoản dùng lương 100M
        $this->assertEquals(100000000.0, $componentsByCode['BHXH_HUU_TU']['base_used']);
        $this->assertEquals(22000000.0, $componentsByCode['BHXH_HUU_TU']['amount']); // 100M × 22%

        // BHTN dùng fixed amount 72M
        $this->assertEquals('FIXED_AMOUNT', $componentsByCode['BHTN']['base_type']);
        $this->assertEquals(72000000.0, $componentsByCode['BHTN']['base_used']);
        $this->assertEquals(1440000.0, $componentsByCode['BHTN']['amount']); // 72M × 2%

        // Total không phải 100M × 31.5% mà là 100M × 29.5% + 72M × 2%
        $expectedTotal = (100000000 * 0.295) + (72000000 * 0.02);
        $this->assertEquals(round($expectedTotal, 2), $result['total_amount']);
    }

    public function test_it_calculates_with_only_one_component_tnld(): void
    {
        $employee = Employee::factory()->create();

        $contract = Contract::factory()->create([
            'employee_id' => $employee->id,
            'insurance_salary' => 8000000, // 8M
            'contract_start_date' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        $participation = InsuranceParticipation::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'ACTIVE',
        ]);

        // Chỉ tham gia BHXH TNLĐ
        InsuranceParticipationComponent::create([
            'participation_id' => $participation->id,
            'component_id' => $this->components['BHXH_TNLD']->id,
            'is_enabled' => true,
            'base_type' => 'INSURANCE_SALARY',
            'rate_total' => 0.005,
        ]);

        $result = $this->calculator->calculateForEmployee($employee, '2026-01');

        $this->assertCount(1, $result['components']);
        $this->assertEquals('BHXH_TNLD', $result['components'][0]['component_code']);
        $this->assertEquals(40000.0, $result['components'][0]['amount']); // 8M × 0.5%
        $this->assertEquals(40000.0, $result['total_amount']);
    }

    public function test_it_calculates_with_mixed_base_types(): void
    {
        $employee = Employee::factory()->create();

        $contract = Contract::factory()->create([
            'employee_id' => $employee->id,
            'insurance_salary' => 15000000, // 15M
            'contract_start_date' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        $participation = InsuranceParticipation::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'ACTIVE',
        ]);

        // BHXH Hưu trí: INSURANCE_SALARY
        InsuranceParticipationComponent::create([
            'participation_id' => $participation->id,
            'component_id' => $this->components['BHXH_HUU_TU']->id,
            'is_enabled' => true,
            'base_type' => 'INSURANCE_SALARY',
            'rate_total' => 0.22,
        ]);

        // BHTN: FIXED_AMOUNT
        InsuranceParticipationComponent::create([
            'participation_id' => $participation->id,
            'component_id' => $this->components['BHTN']->id,
            'is_enabled' => true,
            'base_type' => 'FIXED_AMOUNT',
            'base_amount' => 72000000,
            'rate_total' => 0.02,
        ]);

        // BHYT: INSURANCE_SALARY
        InsuranceParticipationComponent::create([
            'participation_id' => $participation->id,
            'component_id' => $this->components['BHYT']->id,
            'is_enabled' => true,
            'base_type' => 'INSURANCE_SALARY',
            'rate_total' => 0.045,
        ]);

        $result = $this->calculator->calculateForEmployee($employee, '2026-01');

        $this->assertCount(3, $result['components']);

        $componentsByCode = collect($result['components'])->keyBy('component_code');

        // BHXH: 15M × 22% = 3.3M
        $this->assertEquals(15000000.0, $componentsByCode['BHXH_HUU_TU']['base_used']);
        $this->assertEquals(3300000.0, $componentsByCode['BHXH_HUU_TU']['amount']);

        // BHTN: 72M × 2% = 1.44M
        $this->assertEquals(72000000.0, $componentsByCode['BHTN']['base_used']);
        $this->assertEquals(1440000.0, $componentsByCode['BHTN']['amount']);

        // BHYT: 15M × 4.5% = 675K
        $this->assertEquals(15000000.0, $componentsByCode['BHYT']['base_used']);
        $this->assertEquals(675000.0, $componentsByCode['BHYT']['amount']);

        // Total: 3.3M + 1.44M + 675K = 5.415M
        $this->assertEquals(5415000.0, $result['total_amount']);
    }

    public function test_it_gets_insurance_salary_from_contract_appendix_when_exists(): void
    {
        $employee = Employee::factory()->create();

        // Original contract: 10M
        $contract = Contract::factory()->create([
            'employee_id' => $employee->id,
            'insurance_salary' => 10000000,
            'contract_start_date' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        // Appendix tăng lương từ 15/01: 12M
        $appendix = ContractAppendix::factory()->create([
            'employee_id' => $employee->id,
            'contract_id' => $contract->id,
            'insurance_salary' => 12000000,
            'appendix_start_date' => '2026-01-15',
            'appendix_end_date' => null,
        ]);

        $participation = InsuranceParticipation::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'ACTIVE',
        ]);

        InsuranceParticipationComponent::create([
            'participation_id' => $participation->id,
            'component_id' => $this->components['BHXH_HUU_TU']->id,
            'is_enabled' => true,
            'base_type' => 'INSURANCE_SALARY',
            'rate_total' => 0.22,
        ]);

        // Calculate cho January 2026 (sau ngày 15/01)
        $result = $this->calculator->calculateForEmployee($employee, '2026-01');

        // Phải lấy salary từ appendix (12M) chứ không phải contract (10M)
        $this->assertEquals(12000000.0, $result['base_insurance_salary']);
        $this->assertEquals(12000000.0, $result['components'][0]['base_used']);
        $this->assertEquals(2640000.0, $result['components'][0]['amount']); // 12M × 22%
    }

    public function test_it_uses_contract_salary_when_appendix_does_not_cover_declaration_month(): void
    {
        $employee = Employee::factory()->create();

        $contract = Contract::factory()->create([
            'employee_id' => $employee->id,
            'insurance_salary' => 10000000,
            'contract_start_date' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        // Appendix chỉ có hiệu lực từ Feb
        $appendix = ContractAppendix::factory()->create([
            'employee_id' => $employee->id,
            'contract_id' => $contract->id,
            'insurance_salary' => 12000000,
            'appendix_start_date' => '2026-02-01',
        ]);

        $participation = InsuranceParticipation::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'ACTIVE',
        ]);

        InsuranceParticipationComponent::create([
            'participation_id' => $participation->id,
            'component_id' => $this->components['BHYT']->id,
            'is_enabled' => true,
            'base_type' => 'INSURANCE_SALARY',
            'rate_total' => 0.045,
        ]);

        // Calculate cho January (appendix chưa có hiệu lực)
        $result = $this->calculator->calculateForEmployee($employee, '2026-01');

        // Phải dùng salary từ contract gốc
        $this->assertEquals(10000000.0, $result['base_insurance_salary']);
    }

    public function test_it_throws_exception_when_employee_has_no_active_participation(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('has no active insurance participation');

        $employee = Employee::factory()->create();

        Contract::factory()->create([
            'employee_id' => $employee->id,
            'insurance_salary' => 10000000,
            'status' => 'ACTIVE',
        ]);

        // Không có participation
        $this->calculator->calculateForEmployee($employee, '2026-01');
    }

    public function test_it_throws_exception_when_employee_has_no_insurance_salary(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot determine insurance salary');

        $employee = Employee::factory()->create();

        // Contract không có insurance_salary
        Contract::factory()->create([
            'employee_id' => $employee->id,
            'insurance_salary' => null,
            'status' => 'ACTIVE',
        ]);

        $participation = InsuranceParticipation::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'ACTIVE',
        ]);

        InsuranceParticipationComponent::create([
            'participation_id' => $participation->id,
            'component_id' => $this->components['BHYT']->id,
            'is_enabled' => true,
        ]);

        $this->calculator->calculateForEmployee($employee, '2026-01');
    }

    public function test_it_throws_exception_when_employee_has_no_enabled_components(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('has no enabled insurance components');

        $employee = Employee::factory()->create();

        Contract::factory()->create([
            'employee_id' => $employee->id,
            'insurance_salary' => 10000000,
            'status' => 'ACTIVE',
        ]);

        $participation = InsuranceParticipation::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'ACTIVE',
        ]);

        // Có component nhưng không enabled
        InsuranceParticipationComponent::create([
            'participation_id' => $participation->id,
            'component_id' => $this->components['BHYT']->id,
            'is_enabled' => false,
        ]);

        $this->calculator->calculateForEmployee($employee, '2026-01');
    }

    public function test_it_calculates_for_multiple_employees_correctly(): void
    {
        $employees = Employee::factory()->count(3)->create();

        foreach ($employees as $index => $employee) {
            $salary = ($index + 1) * 10000000; // 10M, 20M, 30M

            Contract::factory()->create([
                'employee_id' => $employee->id,
                'insurance_salary' => $salary,
                'status' => 'ACTIVE',
            ]);

            $participation = InsuranceParticipation::factory()->create([
                'employee_id' => $employee->id,
                'status' => 'ACTIVE',
            ]);

            InsuranceParticipationComponent::create([
                'participation_id' => $participation->id,
                'component_id' => $this->components['BHYT']->id,
                'is_enabled' => true,
                'base_type' => 'INSURANCE_SALARY',
                'rate_total' => 0.045,
            ]);
        }

        $results = $this->calculator->calculateForEmployees($employees->all(), '2026-01');

        $this->assertCount(3, $results);
        $this->assertEquals(10000000.0, $results[0]['base_insurance_salary']);
        $this->assertEquals(20000000.0, $results[1]['base_insurance_salary']);
        $this->assertEquals(30000000.0, $results[2]['base_insurance_salary']);
        $this->assertEquals(450000.0, $results[0]['total_amount']); // 10M × 4.5%
        $this->assertEquals(900000.0, $results[1]['total_amount']);  // 20M × 4.5%
        $this->assertEquals(1350000.0, $results[2]['total_amount']); // 30M × 4.5%
    }

    public function test_it_validates_employee_before_calculation(): void
    {
        $employee = Employee::factory()->create();

        Contract::factory()->create([
            'employee_id' => $employee->id,
            'insurance_salary' => 10000000,
            'status' => 'ACTIVE',
        ]);

        $participation = InsuranceParticipation::factory()->create([
            'employee_id' => $employee->id,
            'status' => 'ACTIVE',
        ]);

        InsuranceParticipationComponent::create([
            'participation_id' => $participation->id,
            'component_id' => $this->components['BHYT']->id,
            'is_enabled' => true,
        ]);

        $validation = $this->calculator->validateEmployeeForCalculation($employee, '2026-01');

        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
    }

    public function test_it_returns_validation_errors_for_invalid_employee(): void
    {
        $employee = Employee::factory()->create();

        // Không có contract, không có participation
        $validation = $this->calculator->validateEmployeeForCalculation($employee, '2026-01');

        $this->assertFalse($validation['valid']);
        $this->assertCount(2, $validation['errors']);
        $this->assertStringContainsString('No active insurance participation', $validation['errors'][0]);
    }

    public function test_it_calculates_summary_statistics_for_multiple_calculations(): void
    {
        $employee1 = Employee::factory()->create();
        $employee2 = Employee::factory()->create();

        // Setup 2 employees với different components
        foreach ([$employee1, $employee2] as $employee) {
            Contract::factory()->create([
                'employee_id' => $employee->id,
                'insurance_salary' => 10000000,
                'status' => 'ACTIVE',
            ]);

            $participation = InsuranceParticipation::factory()->create([
                'employee_id' => $employee->id,
                'status' => 'ACTIVE',
            ]);

            foreach (['BHXH_HUU_TU', 'BHYT'] as $code) {
                InsuranceParticipationComponent::create([
                    'participation_id' => $participation->id,
                    'component_id' => $this->components[$code]->id,
                    'is_enabled' => true,
                    'base_type' => 'INSURANCE_SALARY',
                    'rate_total' => $this->components[$code]->default_rate_total,
                ]);
            }
        }

        $calculations = $this->calculator->calculateForEmployees(
            [$employee1, $employee2],
            '2026-01'
        );

        $summary = $this->calculator->getSummaryStatistics($calculations);

        $this->assertEquals(2, $summary['total_employees']);
        $this->assertEquals(20000000.0, $summary['total_base_salary']); // 10M × 2
        $this->assertEquals(5300000.0, $summary['total_contribution']); // (2.2M + 450K) × 2
        $this->assertArrayHasKey('BHXH_HUU_TU', $summary['by_component']);
        $this->assertEquals(2, $summary['by_component']['BHXH_HUU_TU']['employee_count']);
        $this->assertEquals(4400000.0, $summary['by_component']['BHXH_HUU_TU']['total_amount']); // 2.2M × 2
    }
}
