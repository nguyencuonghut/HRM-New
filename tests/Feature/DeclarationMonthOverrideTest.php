<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\InsuranceChangeRecord;
use App\Models\InsuranceMonthlyReport;
use App\Models\User;
use App\Services\InsuranceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class DeclarationMonthOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected InsuranceReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
        ]);

        $this->reportService = app(InsuranceReportService::class);
    }

    /** @test */
    public function it_updates_declaration_month_without_override_reason_when_same_as_suggested()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
            'total_decrease' => 0,
            'total_adjust' => 0,
        ]);

        $employee = Employee::create([
            'full_name' => 'Test Employee',
            'employee_code' => 'EMP001',
            'dob' => '1990-01-01',
            'gender' => 'MALE',
            'personal_email' => 'test@example.com',
        ]);

        $record = InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-10',
            'suggested_declaration_month' => '2026-01',
            'declaration_month' => '2026-01',
            'approval_status' => 'PENDING',
        ]);

        $result = $this->reportService->updateDeclarationMonth(
            $record,
            $this->admin,
            '2026-01', // Same as suggested
            null // No override reason needed
        );

        $this->assertTrue($result);

        $record->refresh();
        $this->assertEquals('2026-01', $record->declaration_month);
        $this->assertEquals($this->admin->id, $record->declaration_set_by);
        $this->assertNotNull($record->declaration_set_at);
        $this->assertNull($record->declaration_override_reason);
    }

    /** @test */
    public function it_updates_declaration_month_with_override_reason_when_different_from_suggested()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
            'total_decrease' => 0,
            'total_adjust' => 0,
        ]);

        $employee = Employee::create([
            'full_name' => 'Test Employee',
            'employee_code' => 'EMP001',
            'dob' => '1990-01-01',
            'gender' => 'MALE',
            'personal_email' => 'test@example.com',
        ]);

        $record = InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-10',
            'suggested_declaration_month' => '2026-01',
            'declaration_month' => '2026-01',
            'approval_status' => 'PENDING',
        ]);

        $result = $this->reportService->updateDeclarationMonth(
            $record,
            $this->admin,
            '2026-02', // Different from suggested
            'Delayed paperwork submission'
        );

        $this->assertTrue($result);

        $record->refresh();
        $this->assertEquals('2026-02', $record->declaration_month);
        $this->assertEquals($this->admin->id, $record->declaration_set_by);
        $this->assertNotNull($record->declaration_set_at);
        $this->assertEquals('Delayed paperwork submission', $record->declaration_override_reason);
    }

    /** @test */
    public function it_moves_record_to_different_report_when_declaration_month_changes()
    {
        // Create January report
        $janReport = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
            'total_decrease' => 0,
            'total_adjust' => 0,
        ]);

        $employee = Employee::create([
            'full_name' => 'Test Employee',
            'employee_code' => 'EMP001',
            'dob' => '1990-01-01',
            'gender' => 'MALE',
            'personal_email' => 'test@example.com',
        ]);

        $record = InsuranceChangeRecord::create([
            'report_id' => $janReport->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-15',
            'suggested_declaration_month' => '2026-02',
            'declaration_month' => '2026-01', // Initially in Jan report
            'approval_status' => 'PENDING',
        ]);

        // Update declaration month to February
        $this->reportService->updateDeclarationMonth(
            $record,
            $this->admin,
            '2026-02',
            'Correcting to suggested month'
        );

        $record->refresh();

        // Record should have moved to February report
        $this->assertEquals('2026-02', $record->declaration_month);
        $this->assertNotEquals($janReport->id, $record->report_id);

        // February report should have been created
        $febReport = InsuranceMonthlyReport::where('year', 2026)->where('month', 2)->first();
        $this->assertNotNull($febReport);
        $this->assertEquals($febReport->id, $record->report_id);

        // January report counters should be recalculated
        $janReport->refresh();
        $this->assertEquals(0, $janReport->total_increase);

        // February report should have the record
        $febReport->refresh();
        $this->assertEquals(1, $febReport->total_increase);
    }

    /** @test */
    public function it_recalculates_report_counters_when_moving_approved_record()
    {
        $janReport = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
            'approved_increase' => 1,
            'total_decrease' => 0,
            'total_adjust' => 0,
        ]);

        $employee = Employee::create([
            'full_name' => 'Test Employee',
            'employee_code' => 'EMP001',
            'dob' => '1990-01-01',
            'gender' => 'MALE',
            'personal_email' => 'test@example.com',
        ]);

        $record = InsuranceChangeRecord::create([
            'report_id' => $janReport->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-15',
            'suggested_declaration_month' => '2026-02',
            'declaration_month' => '2026-01',
            'approval_status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        // Move to February
        $this->reportService->updateDeclarationMonth(
            $record,
            $this->admin,
            '2026-02',
            'Moving approved record'
        );

        // Check January counters
        $janReport->refresh();
        $this->assertEquals(0, $janReport->total_increase);
        $this->assertEquals(0, $janReport->approved_increase);

        // Check February counters
        $febReport = InsuranceMonthlyReport::where('year', 2026)->where('month', 2)->first();
        $this->assertEquals(1, $febReport->total_increase);
        $this->assertEquals(1, $febReport->approved_increase);
    }

    /** @test */
    public function it_logs_activity_when_updating_declaration_month()
    {
        Activity::truncate();

        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
            'total_decrease' => 0,
            'total_adjust' => 0,
        ]);

        $employee = Employee::create([
            'full_name' => 'John Doe',
            'employee_code' => 'EMP001',
            'dob' => '1990-01-01',
            'gender' => 'MALE',
            'personal_email' => 'john@example.com',
        ]);

        $record = InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-10',
            'suggested_declaration_month' => '2026-01',
            'declaration_month' => '2026-01',
            'approval_status' => 'PENDING',
        ]);

        $this->reportService->updateDeclarationMonth(
            $record,
            $this->admin,
            '2026-02',
            'Test override reason'
        );

        // Check activity was logged
        $activities = Activity::where('log_name', 'insurance-declaration')
            ->where('subject_type', InsuranceChangeRecord::class)
            ->where('subject_id', $record->id)
            ->get();

        $this->assertGreaterThan(0, $activities->count());

        // Find the update activity
        $updateActivity = $activities->firstWhere('description', 'Cập nhật tháng đóng BHXH');
        $this->assertNotNull($updateActivity);
        $this->assertEquals($this->admin->id, $updateActivity->causer_id);
        $this->assertStringContainsString('John Doe', $updateActivity->properties['employee_name']);
        $this->assertEquals('2026-01', $updateActivity->properties['suggested_month']);
        $this->assertEquals('2026-01', $updateActivity->properties['old_declaration_month']);
        $this->assertEquals('2026-02', $updateActivity->properties['new_declaration_month']);
        $this->assertEquals('Test override reason', $updateActivity->properties['override_reason']);
    }

    /** @test */
    public function it_logs_move_activity_when_record_moves_to_different_report()
    {
        Activity::truncate();

        $janReport = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
            'total_decrease' => 0,
            'total_adjust' => 0,
        ]);

        $employee = Employee::create([
            'full_name' => 'Jane Smith',
            'employee_code' => 'EMP002',
            'dob' => '1991-01-01',
            'gender' => 'FEMALE',
            'personal_email' => 'jane@example.com',
        ]);

        $record = InsuranceChangeRecord::create([
            'report_id' => $janReport->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 12000000,
            'effective_date' => '2026-01-20',
            'suggested_declaration_month' => '2026-02',
            'declaration_month' => '2026-01',
            'approval_status' => 'PENDING',
        ]);

        $this->reportService->updateDeclarationMonth(
            $record,
            $this->admin,
            '2026-02',
            'Moving to correct month'
        );

        // Check move activity was logged
        $moveActivity = Activity::where('log_name', 'insurance-declaration')
            ->where('description', 'Di chuyển record sang báo cáo khác')
            ->first();

        $this->assertNotNull($moveActivity);
        $this->assertEquals($this->admin->id, $moveActivity->causer_id);
        $this->assertStringContainsString('Jane Smith', $moveActivity->properties['employee_name']);
        $this->assertEquals('2026-01', $moveActivity->properties['old_report']);
        $this->assertEquals('2026-02', $moveActivity->properties['new_report']);
    }

    /** @test */
    public function it_prevents_updating_declaration_month_for_finalized_report()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'FINALIZED', // Report is finalized
            'total_increase' => 1,
            'total_decrease' => 0,
            'total_adjust' => 0,
            'finalized_by' => $this->admin->id,
            'finalized_at' => now(),
        ]);

        $employee = Employee::create([
            'full_name' => 'Test Employee',
            'employee_code' => 'EMP001',
            'dob' => '1990-01-01',
            'gender' => 'MALE',
            'personal_email' => 'test@example.com',
        ]);

        $record = InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-10',
            'suggested_declaration_month' => '2026-01',
            'declaration_month' => '2026-01',
            'approval_status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Báo cáo đã hoàn tất');

        $this->reportService->updateDeclarationMonth(
            $record,
            $this->admin,
            '2026-02',
            'Should not work'
        );
    }

    /** @test */
    public function it_prevents_finalizing_report_with_mismatched_declaration_months()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
            'approved_increase' => 1,
            'total_decrease' => 0,
            'total_adjust' => 0,
        ]);

        $employee = Employee::create([
            'full_name' => 'Test Employee',
            'employee_code' => 'EMP001',
            'dob' => '1990-01-01',
            'gender' => 'MALE',
            'personal_email' => 'test@example.com',
        ]);

        // Create approved record with declaration_month = February (mismatched with report month = January)
        $record = InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-15',
            'suggested_declaration_month' => '2026-02',
            'declaration_month' => '2026-02', // Mismatched!
            'approval_status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Không thể hoàn tất báo cáo');
        $this->expectExceptionMessage('record với tháng đóng BHXH không khớp');

        $this->reportService->finalizeReport($report, $this->admin);
    }

    /** @test */
    public function it_allows_finalizing_report_when_all_declaration_months_match()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 2,
            'status' => 'DRAFT',
            'total_increase' => 2,
            'approved_increase' => 2,
            'total_decrease' => 0,
            'total_adjust' => 0,
        ]);

        $employee1 = Employee::create([
            'full_name' => 'Employee 1',
            'employee_code' => 'EMP001',
            'dob' => '1990-01-01',
            'gender' => 'MALE',
            'personal_email' => 'emp1@example.com',
        ]);

        $employee2 = Employee::create([
            'full_name' => 'Employee 2',
            'employee_code' => 'EMP002',
            'dob' => '1991-01-01',
            'gender' => 'FEMALE',
            'personal_email' => 'emp2@example.com',
        ]);

        // Both records have declaration_month matching report month (2026-02)
        InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee1->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-15',
            'suggested_declaration_month' => '2026-02',
            'declaration_month' => '2026-02',
            'approval_status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee2->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 12000000,
            'effective_date' => '2026-01-20',
            'suggested_declaration_month' => '2026-02',
            'declaration_month' => '2026-02',
            'approval_status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        $result = $this->reportService->finalizeReport($report, $this->admin);

        $this->assertTrue($result);

        $report->refresh();
        $this->assertEquals('FINALIZED', $report->status);
        $this->assertEquals($this->admin->id, $report->finalized_by);
        $this->assertNotNull($report->finalized_at);
    }

    /** @test */
    public function it_does_not_move_record_if_declaration_month_matches_current_report()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
            'total_decrease' => 0,
            'total_adjust' => 0,
        ]);

        $employee = Employee::create([
            'full_name' => 'Test Employee',
            'employee_code' => 'EMP001',
            'dob' => '1990-01-01',
            'gender' => 'MALE',
            'personal_email' => 'test@example.com',
        ]);

        $record = InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-10',
            'suggested_declaration_month' => '2026-01',
            'declaration_month' => '2026-01',
            'approval_status' => 'PENDING',
        ]);

        $originalReportId = $record->report_id;

        // Update but keep in same month
        $this->reportService->updateDeclarationMonth(
            $record,
            $this->admin,
            '2026-01',
            null
        );

        $record->refresh();

        // Record should still be in the same report
        $this->assertEquals($originalReportId, $record->report_id);
        $this->assertEquals('2026-01', $record->declaration_month);
    }

    /** @test */
    public function it_handles_moving_record_across_multiple_months()
    {
        $janReport = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 1,
            'total_decrease' => 0,
            'total_adjust' => 0,
        ]);

        $employee = Employee::create([
            'full_name' => 'Test Employee',
            'employee_code' => 'EMP001',
            'dob' => '1990-01-01',
            'gender' => 'MALE',
            'personal_email' => 'test@example.com',
        ]);

        $record = InsuranceChangeRecord::create([
            'report_id' => $janReport->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-10',
            'suggested_declaration_month' => '2026-01',
            'declaration_month' => '2026-01',
            'approval_status' => 'PENDING',
        ]);

        // Move to March (skipping February)
        $this->reportService->updateDeclarationMonth(
            $record,
            $this->admin,
            '2026-03',
            'Special case - skip February'
        );

        $record->refresh();

        // March report should have been created
        $marReport = InsuranceMonthlyReport::where('year', 2026)->where('month', 3)->first();
        $this->assertNotNull($marReport);
        $this->assertEquals($marReport->id, $record->report_id);
        $this->assertEquals('2026-03', $record->declaration_month);

        // January should have 0 records
        $janReport->refresh();
        $this->assertEquals(0, $janReport->total_increase);

        // March should have 1 record
        $this->assertEquals(1, $marReport->total_increase);
    }
}
