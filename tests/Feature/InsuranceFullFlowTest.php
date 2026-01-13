<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Employee;
use App\Models\InsuranceChangeRecord;
use App\Models\InsuranceComponent;
use App\Models\InsuranceMonthlyReport;
use App\Models\InsuranceParticipation;
use App\Models\InsuranceParticipationComponent;
use App\Models\User;
use App\Services\InsuranceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3.6: Integration Test - Full Flow
 *
 * Purpose: End-to-end test of complete insurance workflow
 * Tests: Contract creation → Report generation → Approval → Finalize → Export
 * Scale: 100+ employees for performance validation
 */
class InsuranceFullFlowTest extends TestCase
{
    use RefreshDatabase;

    protected InsuranceReportService $reportService;
    protected User $admin;
    protected array $components;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportService = app(InsuranceReportService::class);

        // Create admin user (RefreshDatabase clears the DB)
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Seed insurance components
        $this->components = $this->seedComponents();
    }

    protected function seedComponents(): array
    {
        return [
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

    /**
     * Create batch of employees with contracts and participations
     */
    protected function createEmployees(int $count): array
    {
        $employees = [];

        for ($i = 1; $i <= $count; $i++) {
            // Create employee
            $employee = Employee::create([
                'employee_code' => 'EMP' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'full_name' => "Test Employee {$i}",
                'first_name' => "Employee{$i}",
                'last_name' => "Test",
                'gender' => $i % 2 === 0 ? 'MALE' : 'FEMALE',
                'date_of_birth' => '1990-01-01',
                'phone' => '0900000' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'email' => "emp{$i}@test.com",
                'status' => 'ACTIVE',
            ]);

            // Create contract with varying salary (8M to 20M)
            $insuranceSalary = 8000000 + (($i % 13) * 1000000); // 8M-20M

            Contract::create([
                'employee_id' => $employee->id,
                'contract_number' => 'TEST-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'contract_type' => 'INDEFINITE',
                'start_date' => '2026-01-01',
                'end_date' => null,
                'status' => 'ACTIVE',
                'insurance_salary' => $insuranceSalary,
            ]);

            // Create participation with all 5 components
            $participation = InsuranceParticipation::create([
                'employee_id' => $employee->id,
                'participation_start_date' => '2026-01-01',
                'status' => 'ACTIVE',
                'insurance_salary' => $insuranceSalary,
            ]);

            foreach ($this->components as $component) {
                InsuranceParticipationComponent::create([
                    'insurance_participation_id' => $participation->id,
                    'component_id' => $component->id,
                    'is_enabled' => true,
                    'base_type' => 'INSURANCE_SALARY',
                    'rate_total' => $component->default_rate_total,
                ]);
            }

            $employees[] = $employee;
        }

        return $employees;
    }

    public function test_full_flow_with_100_employees(): void
    {
        // Step 1: Create 100 employees with contracts
        $startTime = microtime(true);
        $employees = $this->createEmployees(100);
        $createTime = microtime(true) - $startTime;

        $this->assertCount(100, $employees);
        $this->assertLessThan(5, $createTime, 'Employee creation took too long');

        // Step 2: Generate report (should auto-detect all as INCREASE)
        $startTime = microtime(true);
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
        ]);

        // Manually create change records (simulating detectChanges)
        foreach ($employees as $employee) {
            InsuranceChangeRecord::create([
                'report_id' => $report->id,
                'employee_id' => $employee->id,
                'change_type' => 'INCREASE',
                'auto_reason' => 'NEW_HIRE',
                'effective_date' => '2026-01-01',
                'approval_status' => 'PENDING',
                'declaration_month' => '2026-01',
                'suggested_declaration_month' => '2026-01',
                'insurance_salary' => $employee->contracts->first()->insurance_salary,
            ]);
        }

        $generateTime = microtime(true) - $startTime;

        $this->assertEquals(100, $report->changeRecords()->count());
        $this->assertLessThan(5, $generateTime, 'Report generation took too long');

        // Step 3: Approve all records
        $startTime = microtime(true);
        $report->changeRecords()->update([
            'approval_status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);
        $approveTime = microtime(true) - $startTime;

        $this->assertEquals(100, $report->changeRecords()->where('approval_status', 'APPROVED')->count());
        $this->assertLessThan(3, $approveTime, 'Bulk approval took too long');

        // Step 4: Finalize report (generates snapshots)
        $startTime = microtime(true);
        $this->reportService->finalizeReport($report, $this->admin);
        $finalizeTime = microtime(true) - $startTime;

        $report->refresh();
        $this->assertEquals('FINALIZED', $report->status);
        $this->assertLessThan(5, $finalizeTime, 'Finalization took too long');

        // Step 5: Verify snapshot generated for all 100 employees
        $contributionCount = $report->contributions()->count();
        $this->assertEquals(100, $contributionCount);

        // Verify each contribution has 5 items (all components)
        $firstContribution = $report->contributions()->with('items')->first();
        $this->assertCount(5, $firstContribution->items);

        // Verify component codes
        $codes = $firstContribution->items->pluck('component_code')->toArray();
        $this->assertContains('BHXH_HUU_TU', $codes);
        $this->assertContains('BHXH_BENH', $codes);
        $this->assertContains('BHXH_TNLD', $codes);
        $this->assertContains('BHTN', $codes);
        $this->assertContains('BHYT', $codes);

        // Step 6: Export data
        $startTime = microtime(true);
        $exportData = $this->reportService->exportReport($report);
        $exportTime = microtime(true) - $startTime;

        $this->assertArrayHasKey('report_info', $exportData);
        $this->assertArrayHasKey('employees', $exportData);
        $this->assertArrayHasKey('summary', $exportData);
        $this->assertCount(100, $exportData['employees']);
        $this->assertLessThan(3, $exportTime, 'Export took too long');

        // Step 7: Verify snapshot immutability
        // Edit a contract salary
        $firstEmployee = $employees[0];
        $contract = $firstEmployee->contracts->first();
        $originalSalary = $contract->insurance_salary;
        $contract->update(['insurance_salary' => $originalSalary * 2]);

        // Re-export should still show original data
        $exportData2 = $this->reportService->exportReport($report);
        $firstEmployeeData = collect($exportData2['employees'])->firstWhere('employee_id', $firstEmployee->id);

        $this->assertEquals($originalSalary, $firstEmployeeData['base_insurance_salary']);
        $this->assertNotEquals($originalSalary * 2, $firstEmployeeData['base_insurance_salary']);

        // Step 8: Verify totals are correct
        $summary = $exportData['summary'];
        $this->assertGreaterThan(0, $summary['total_contribution']);

        // Calculate expected total manually
        $expectedTotal = $report->contributions()->sum('total_amount');
        $this->assertEquals($expectedTotal, $summary['total_contribution']);

        // Performance summary
        echo "\n=== Performance Report ===\n";
        echo "Employee Creation: " . round($createTime, 2) . "s\n";
        echo "Report Generation: " . round($generateTime, 2) . "s\n";
        echo "Bulk Approval: " . round($approveTime, 2) . "s\n";
        echo "Finalization: " . round($finalizeTime, 2) . "s\n";
        echo "Export: " . round($exportTime, 2) . "s\n";
        echo "Total: " . round($createTime + $generateTime + $approveTime + $finalizeTime + $exportTime, 2) . "s\n";
    }

    public function test_full_flow_with_mixed_change_types(): void
    {
        // Create 10 employees
        $employees = $this->createEmployees(10);

        // Create report
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
        ]);

        // Create mixed change types
        // 3 INCREASE, 3 DECREASE, 2 ADJUST, 2 END_PARTICIPATION
        $changeTypes = [
            'INCREASE', 'INCREASE', 'INCREASE',
            'DECREASE', 'DECREASE', 'DECREASE',
            'ADJUST', 'ADJUST',
            'END_PARTICIPATION', 'END_PARTICIPATION',
        ];

        foreach ($employees as $index => $employee) {
            InsuranceChangeRecord::create([
                'report_id' => $report->id,
                'employee_id' => $employee->id,
                'change_type' => $changeTypes[$index],
                'auto_reason' => 'TEST_' . $changeTypes[$index],
                'effective_date' => '2026-01-01',
                'approval_status' => 'APPROVED',
                'declaration_month' => '2026-01',
                'suggested_declaration_month' => '2026-01',
                'insurance_salary' => $employee->contracts->first()->insurance_salary,
            ]);
        }

        // Finalize
        $this->reportService->finalizeReport($report, $this->admin);

        // Verify snapshot count (only APPROVED records)
        $contributionCount = $report->contributions()->count();
        $this->assertEquals(10, $contributionCount);

        // Export and verify
        $exportData = $this->reportService->exportReport($report);
        $this->assertCount(10, $exportData['employees']);

        // Verify each change type represented
        $changeRecords = $report->changeRecords()->get();
        $this->assertEquals(3, $changeRecords->where('change_type', 'INCREASE')->count());
        $this->assertEquals(3, $changeRecords->where('change_type', 'DECREASE')->count());
        $this->assertEquals(2, $changeRecords->where('change_type', 'ADJUST')->count());
        $this->assertEquals(2, $changeRecords->where('change_type', 'END_PARTICIPATION')->count());
    }

    public function test_full_flow_with_partial_approval(): void
    {
        // Create 20 employees
        $employees = $this->createEmployees(20);

        // Create report
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
        ]);

        // Create records with different approval statuses
        foreach ($employees as $index => $employee) {
            $status = match ($index % 4) {
                0 => 'APPROVED',
                1 => 'PENDING',
                2 => 'REJECTED',
                3 => 'ADJUSTED',
            };

            InsuranceChangeRecord::create([
                'report_id' => $report->id,
                'employee_id' => $employee->id,
                'change_type' => 'INCREASE',
                'auto_reason' => 'NEW_HIRE',
                'effective_date' => '2026-01-01',
                'approval_status' => $status,
                'declaration_month' => '2026-01',
                'suggested_declaration_month' => '2026-01',
                'insurance_salary' => $employee->contracts->first()->insurance_salary,
            ]);
        }

        // Finalize
        $this->reportService->finalizeReport($report, $this->admin);

        // Should only have snapshots for APPROVED and ADJUSTED (10 records: 5 APPROVED + 5 ADJUSTED)
        $contributionCount = $report->contributions()->count();
        $this->assertEquals(10, $contributionCount);

        // Verify PENDING and REJECTED not included
        $contributionEmployeeIds = $report->contributions()->pluck('employee_id')->toArray();

        $rejectedRecords = $report->changeRecords()->where('approval_status', 'REJECTED')->get();
        foreach ($rejectedRecords as $record) {
            $this->assertNotContains($record->employee_id, $contributionEmployeeIds);
        }

        $pendingRecords = $report->changeRecords()->where('approval_status', 'PENDING')->get();
        foreach ($pendingRecords as $record) {
            $this->assertNotContains($record->employee_id, $contributionEmployeeIds);
        }
    }

    public function test_export_structure_completeness(): void
    {
        // Create 5 employees
        $employees = $this->createEmployees(5);

        // Create and finalize report
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
        ]);

        foreach ($employees as $employee) {
            InsuranceChangeRecord::create([
                'report_id' => $report->id,
                'employee_id' => $employee->id,
                'change_type' => 'INCREASE',
                'auto_reason' => 'NEW_HIRE',
                'effective_date' => '2026-01-01',
                'approval_status' => 'APPROVED',
                'declaration_month' => '2026-01',
                'insurance_salary' => $employee->contracts->first()->insurance_salary,
            ]);
        }

        $this->reportService->finalizeReport($report, $this->admin);

        // Export
        $exportData = $this->reportService->exportReport($report);

        // Verify report_info structure
        $this->assertArrayHasKey('year', $exportData['report_info']);
        $this->assertArrayHasKey('month', $exportData['report_info']);
        $this->assertEquals(2026, $exportData['report_info']['year']);
        $this->assertEquals(1, $exportData['report_info']['month']);

        // Verify employee data structure
        $firstEmployee = $exportData['employees'][0];
        $this->assertArrayHasKey('employee_id', $firstEmployee);
        $this->assertArrayHasKey('employee_code', $firstEmployee);
        $this->assertArrayHasKey('full_name', $firstEmployee);
        $this->assertArrayHasKey('base_insurance_salary', $firstEmployee);
        $this->assertArrayHasKey('total_contribution', $firstEmployee);
        $this->assertArrayHasKey('components', $firstEmployee);

        // Verify component structure
        $this->assertIsArray($firstEmployee['components']);
        $this->assertArrayHasKey('BHXH_HUU_TU', $firstEmployee['components']);

        $bhxhComponent = $firstEmployee['components']['BHXH_HUU_TU'];
        $this->assertArrayHasKey('amount', $bhxhComponent);
        $this->assertArrayHasKey('base_used', $bhxhComponent);
        $this->assertArrayHasKey('rate', $bhxhComponent);

        // Verify summary structure
        $this->assertArrayHasKey('total_employees', $exportData['summary']);
        $this->assertArrayHasKey('total_base_salary', $exportData['summary']);
        $this->assertArrayHasKey('total_contribution', $exportData['summary']);
        $this->assertEquals(5, $exportData['summary']['total_employees']);
    }
}
