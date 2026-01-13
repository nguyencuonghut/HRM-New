<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\InsuranceChangeRecord;
use App\Models\InsuranceComponent;
use App\Models\InsuranceMonthlyContribution;
use App\Models\InsuranceMonthlyContributionItem;
use App\Models\InsuranceMonthlyReport;
use App\Models\InsuranceParticipation;
use App\Models\InsuranceParticipationComponent;
use App\Models\User;
use App\Services\InsuranceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InsuranceSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected InsuranceReportService $reportService;
    protected User $admin;
    protected array $components;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportService = app(InsuranceReportService::class);
        $this->admin = User::where('email', 'admin@example.com')->first() ?? User::create([
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
                'default_rate_total' => 0.22,
                'is_active' => true,
            ]),
            'BHYT' => InsuranceComponent::create([
                'code' => 'BHYT',
                'name_vi' => 'Bảo hiểm Y tế',
                'name_en' => 'Health Insurance',
                'default_rate_total' => 0.045,
                'is_active' => true,
            ]),
        ];
    }

    protected function createEmployee(string $name = 'Test Employee', float $insuranceSalary = 10000000): Employee
    {
        $employee = Employee::create([
            'id' => Str::uuid(),
            'employee_code' => 'EMP' . rand(1000, 9999),
            'full_name' => $name,
            'dob' => '1990-01-01',
            'gender' => 'MALE',
            'phone' => '0123456789',
            'personal_email' => 'test' . rand(1000, 9999) . '@example.com',
        ]);

        // Create an ACTIVE contract with insurance_salary
        \App\Models\Contract::create([
            'id' => Str::uuid(),
            'employee_id' => $employee->id,
            'contract_type' => 'INDEFINITE',
            'contract_number' => 'HD' . rand(1000, 9999),
            'start_date' => '2025-01-01',
            'end_date' => null,
            'status' => 'ACTIVE',
            'insurance_salary' => $insuranceSalary,
            'basic_salary' => $insuranceSalary,
        ]);

        return $employee;
    }

    protected function createParticipationForEmployee(Employee $employee, float $insuranceSalary = 10000000): InsuranceParticipation
    {
        $participation = InsuranceParticipation::create([
            'id' => Str::uuid(),
            'employee_id' => $employee->id,
            'participation_start_date' => '2026-01-01',
            'status' => 'ACTIVE',
            'insurance_salary' => $insuranceSalary,
            'has_social_insurance' => true,
            'has_health_insurance' => true,
            'has_unemployment_insurance' => false,
        ]);

        // Add components
        foreach ($this->components as $component) {
            InsuranceParticipationComponent::create([
                'insurance_participation_id' => $participation->id,
                'component_id' => $component->id,
                'is_enabled' => true,
                'base_type' => 'INSURANCE_SALARY',
                'rate_total' => $component->default_rate_total,
            ]);
        }

        return $participation;
    }

    public function test_it_generates_snapshot_when_finalizing_report(): void
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
            'total_decrease' => 0,
            'total_adjust' => 0,
        ]);

        $employee = $this->createEmployee('John Doe', 10000000);
        $this->createParticipationForEmployee($employee, 10000000);

        $record = InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-15',
            'approval_status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
            'suggested_declaration_month' => '2026-02',
            'declaration_month' => '2026-01',
        ]);

        // Before finalize: no snapshots
        $this->assertEquals(0, InsuranceMonthlyContribution::count());

        // Finalize report
        $this->reportService->finalizeReport($report, $this->admin);

        // After finalize: snapshot created
        $this->assertEquals(1, InsuranceMonthlyContribution::count());

        $contribution = InsuranceMonthlyContribution::first();
        $this->assertEquals($report->id, $contribution->report_id);
        $this->assertEquals($employee->id, $contribution->employee_id);
        $this->assertEquals($record->id, $contribution->change_record_id);
        $this->assertEquals(10000000, $contribution->base_insurance_salary);

        // Check contribution items (2 components)
        $items = $contribution->items;
        $this->assertCount(2, $items);

        // BHXH: 10M × 22% = 2.2M
        $bhxhItem = $items->firstWhere('component_code', 'BHXH_HUU_TU');
        $this->assertNotNull($bhxhItem);
        $this->assertEquals(2200000, $bhxhItem->amount);

        // BHYT: 10M × 4.5% = 450K
        $bhytItem = $items->firstWhere('component_code', 'BHYT');
        $this->assertNotNull($bhytItem);
        $this->assertEquals(450000, $bhytItem->amount);

        // Total: 2.65M
        $this->assertEquals(2650000, $contribution->total_amount);
    }

    public function test_it_regenerates_snapshot_when_finalizing_again(): void
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
        ]);

        $employee = $this->createEmployee();
        $this->createParticipationForEmployee($employee, 10000000);

        $record = InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-10',
            'approval_status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'suggested_declaration_month' => '2026-01',
            'declaration_month' => '2026-01',
        ]);

        // First finalize
        $report->update(['status' => 'DRAFT']);
        $this->reportService->finalizeReport($report, $this->admin);

        $firstSnapshotId = InsuranceMonthlyContribution::first()->id;
        $firstTotal = InsuranceMonthlyContribution::first()->total_amount;

        // Simulate some change (un-finalize for test)
        $report->update(['status' => 'DRAFT']);

        // Second finalize - should regenerate snapshot
        $this->reportService->finalizeReport($report, $this->admin);

        // Should still have 1 contribution (old deleted, new created)
        $this->assertEquals(1, InsuranceMonthlyContribution::count());

        $newSnapshot = InsuranceMonthlyContribution::first();
        $this->assertNotEquals($firstSnapshotId, $newSnapshot->id); // Different ID = regenerated
        $this->assertEquals($firstTotal, $newSnapshot->total_amount); // Same calculation
    }

    public function test_export_uses_snapshot_data(): void
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 2,
        ]);

        $employee1 = $this->createEmployee('Employee 1', 10000000);
        $employee2 = $this->createEmployee('Employee 2', 15000000);

        foreach ([$employee1, $employee2] as $i => $employee) {
            $salary = ($i === 0) ? 10000000 : 15000000;
            $this->createParticipationForEmployee($employee, $salary);

            InsuranceChangeRecord::create([
                'report_id' => $report->id,
                'employee_id' => $employee->id,
                'change_type' => 'INCREASE',
                'insurance_salary' => $employee->id === $employee1->id ? 10000000 : 15000000,
                'effective_date' => '2026-01-10',
                'approval_status' => 'APPROVED',
                'approved_by' => $this->admin->id,
                'suggested_declaration_month' => '2026-01',
                'declaration_month' => '2026-01',
            ]);
        }

        // Finalize to generate snapshot
        $this->reportService->finalizeReport($report, $this->admin);

        // Export report
        $exportData = $this->reportService->exportReport($report);

        // Verify export structure
        $this->assertArrayHasKey('report_info', $exportData);
        $this->assertArrayHasKey('employees', $exportData);
        $this->assertArrayHasKey('summary', $exportData);

        // Verify employee count
        $this->assertCount(2, $exportData['employees']);

        // Verify summary
        $this->assertEquals(2, $exportData['summary']['total_employees']);
        $this->assertEquals(25000000, $exportData['summary']['total_base_salary']); // 10M + 15M

        // Employee 1: (10M × 22%) + (10M × 4.5%) = 2.2M + 450K = 2.65M
        // Employee 2: (15M × 22%) + (15M × 4.5%) = 3.3M + 675K = 3.975M
        // Total: 6.625M
        $this->assertEquals(6625000, $exportData['summary']['total_contribution']);
    }

    public function test_export_fails_for_non_finalized_report(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Chỉ có thể export báo cáo đã hoàn tất');

        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
        ]);

        $this->reportService->exportReport($report);
    }

    public function test_export_fails_when_no_snapshot_exists(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Báo cáo chưa có dữ liệu snapshot đóng BHXH');

        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'FINALIZED',
            'finalized_by' => $this->admin->id,
            'finalized_at' => now(),
        ]);

        $this->reportService->exportReport($report);
    }

    public function test_editing_contract_after_finalize_does_not_affect_export(): void
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
        ]);

        $employee = $this->createEmployee('Test Employee', 10000000);
        $participation = $this->createParticipationForEmployee($employee, 10000000);

        $record = InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000, // Original: 10M
            'effective_date' => '2026-01-10',
            'approval_status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'suggested_declaration_month' => '2026-01',
            'declaration_month' => '2026-01',
        ]);

        // Finalize report (creates snapshot with 10M base)
        $this->reportService->finalizeReport($report, $this->admin);

        $exportBefore = $this->reportService->exportReport($report);
        $baseSalaryBefore = $exportBefore['employees'][0]['base_insurance_salary'];
        $totalBefore = $exportBefore['employees'][0]['total_contribution'];

        // "Edit contract" - simulate changing insurance salary
        $record->update(['insurance_salary' => 20000000]); // Change to 20M

        // Export again - should still show old snapshot data
        $exportAfter = $this->reportService->exportReport($report);
        $baseSalaryAfter = $exportAfter['employees'][0]['base_insurance_salary'];
        $totalAfter = $exportAfter['employees'][0]['total_contribution'];

        // Snapshot is immutable - export unchanged
        $this->assertEquals($baseSalaryBefore, $baseSalaryAfter); // Still 10M
        $this->assertEquals($totalBefore, $totalAfter); // Still same total
        $this->assertEquals(10000000, $baseSalaryAfter); // Confirm it's still 10M
    }

    public function test_snapshot_includes_all_approved_records_only(): void
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 3,
        ]);

        $employees = [];
        for ($i = 1; $i <= 3; $i++) {
            $employee = $this->createEmployee("Employee $i");
            $this->createParticipationForEmployee($employee, 10000000);
            $employees[] = $employee;
        }

        // Record 1: APPROVED
        InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employees[0]->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-10',
            'approval_status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'suggested_declaration_month' => '2026-01',
            'declaration_month' => '2026-01',
        ]);

        // Record 2: PENDING (should NOT be in snapshot)
        InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employees[1]->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-10',
            'approval_status' => 'PENDING',
            'suggested_declaration_month' => '2026-01',
            'declaration_month' => '2026-01',
        ]);

        // Record 3: REJECTED (should NOT be in snapshot)
        InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employees[2]->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-10',
            'approval_status' => 'REJECTED',
            'approved_by' => $this->admin->id,
            'suggested_declaration_month' => '2026-01',
            'declaration_month' => '2026-01',
        ]);

        // Approve record 2 to make it valid
        $record2 = InsuranceChangeRecord::where('employee_id', $employees[1]->id)->first();
        $record2->update([
            'approval_status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        // Finalize
        $this->reportService->finalizeReport($report, $this->admin);

        // Only 2 approved records should have snapshots
        $this->assertEquals(2, InsuranceMonthlyContribution::count());

        $contributionEmployeeIds = InsuranceMonthlyContribution::pluck('employee_id')->toArray();
        $this->assertContains($employees[0]->id, $contributionEmployeeIds);
        $this->assertContains($employees[1]->id, $contributionEmployeeIds);
        $this->assertNotContains($employees[2]->id, $contributionEmployeeIds); // Rejected not included
    }

    public function test_snapshot_records_each_component_separately(): void
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
        ]);

        $employee = $this->createEmployee();
        $this->createParticipationForEmployee($employee, 10000000);

        InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-10',
            'approval_status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'suggested_declaration_month' => '2026-01',
            'declaration_month' => '2026-01',
        ]);

        $this->reportService->finalizeReport($report, $this->admin);

        $contribution = InsuranceMonthlyContribution::first();
        $items = $contribution->items;

        // Should have 2 separate item records (BHXH + BHYT)
        $this->assertCount(2, $items);

        // Each item should have complete data
        foreach ($items as $item) {
            $this->assertNotNull($item->component_id);
            $this->assertNotNull($item->component_code);
            $this->assertNotNull($item->component_name);
            $this->assertNotNull($item->base_type);
            $this->assertNotNull($item->base_used);
            $this->assertNotNull($item->rate_total);
            $this->assertNotNull($item->amount);
        }

        // Verify component codes
        $codes = $items->pluck('component_code')->toArray();
        $this->assertContains('BHXH_HUU_TU', $codes);
        $this->assertContains('BHYT', $codes);
    }

    public function test_deleting_old_snapshots_before_regenerating(): void
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
        ]);

        $employee = $this->createEmployee();
        $this->createParticipationForEmployee($employee, 10000000);

        InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-10',
            'approval_status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'suggested_declaration_month' => '2026-01',
            'declaration_month' => '2026-01',
        ]);

        // First finalize - creates snapshots
        $report->update(['status' => 'DRAFT']);
        $this->reportService->finalizeReport($report, $this->admin);
        $this->assertEquals(1, InsuranceMonthlyContribution::count());
        $this->assertEquals(2, InsuranceMonthlyContributionItem::count()); // 2 components

        // Get IDs of first snapshot
        $firstContributionId = InsuranceMonthlyContribution::first()->id;
        $firstItemIds = InsuranceMonthlyContributionItem::pluck('id')->toArray();

        // Re-finalize (after un-finalizing)
        $report->update(['status' => 'DRAFT']);
        $this->reportService->finalizeReport($report, $this->admin);

        // Should still have same counts
        $this->assertEquals(1, InsuranceMonthlyContribution::count());
        $this->assertEquals(2, InsuranceMonthlyContributionItem::count());

        // But with different IDs (old deleted, new created)
        $newContributionId = InsuranceMonthlyContribution::first()->id;
        $newItemIds = InsuranceMonthlyContributionItem::pluck('id')->toArray();

        $this->assertNotEquals($firstContributionId, $newContributionId);
        $this->assertNotEquals($firstItemIds, $newItemIds);
    }
}
