<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\InsuranceChangeRecord;
use App\Models\InsuranceMonthlyReport;
use App\Services\InsuranceDeclarationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceDeclarationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InsuranceDeclarationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InsuranceDeclarationService::class);
    }

    /** @test */
    public function it_suggests_same_month_for_day_1()
    {
        $date = Carbon::parse('2026-01-01');

        $result = $this->service->suggestDeclarationMonth($date);

        $this->assertEquals('2026-01', $result);
    }

    /** @test */
    public function it_suggests_same_month_for_day_14()
    {
        $date = Carbon::parse('2026-01-14');

        $result = $this->service->suggestDeclarationMonth($date);

        $this->assertEquals('2026-01', $result);
    }

    /** @test */
    public function it_suggests_next_month_for_day_15()
    {
        $date = Carbon::parse('2026-01-15');

        $result = $this->service->suggestDeclarationMonth($date);

        $this->assertEquals('2026-02', $result);
    }

    /** @test */
    public function it_suggests_next_month_for_day_31()
    {
        $date = Carbon::parse('2026-01-31');

        $result = $this->service->suggestDeclarationMonth($date);

        $this->assertEquals('2026-02', $result);
    }

    /** @test */
    public function it_handles_year_rollover_for_december_late_entry()
    {
        $date = Carbon::parse('2025-12-20');

        $result = $this->service->suggestDeclarationMonth($date);

        $this->assertEquals('2026-01', $result);
    }

    /** @test */
    public function it_validates_report_with_all_matching_declaration_months()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 0,
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

        InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-01',
            'declaration_month' => '2026-01', // Matches report month
            'suggested_declaration_month' => '2026-01',
            'approval_status' => 'PENDING',
        ]);

        $result = $this->service->validateDeclarationMonth($report);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertCount(0, $result['mismatchedRecords']);
    }

    /** @test */
    public function it_validates_report_with_mismatched_declaration_months()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 0,
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

        InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-15',
            'declaration_month' => '2026-02', // Does NOT match report month (2026-01)
            'suggested_declaration_month' => '2026-02',
            'approval_status' => 'PENDING',
        ]);

        $result = $this->service->validateDeclarationMonth($report);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertCount(1, $result['mismatchedRecords']);
        $this->assertStringContainsString('Test Employee', $result['errors'][0]);
        $this->assertStringContainsString('2026-02', $result['errors'][0]);
        $this->assertStringContainsString('2026-01', $result['errors'][0]);
    }

    /** @test */
    public function it_detects_records_with_null_declaration_month()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 0,
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

        InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-01',
            'declaration_month' => null, // NULL declaration month
            'suggested_declaration_month' => '2026-01',
            'approval_status' => 'PENDING',
        ]);

        $result = $this->service->validateDeclarationMonth($report);

        $this->assertFalse($result['valid']);
        $this->assertCount(1, $result['mismatchedRecords']);
    }

    /** @test */
    public function it_checks_if_record_can_belong_to_report()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 2,
            'status' => 'DRAFT',
            'total_increase' => 0,
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
            'effective_date' => '2026-01-15',
            'declaration_month' => '2026-02',
            'suggested_declaration_month' => '2026-02',
            'approval_status' => 'PENDING',
        ]);

        $canBelong = $this->service->canRecordBelongToReport($record, $report);

        $this->assertTrue($canBelong);
    }

    /** @test */
    public function it_returns_false_if_record_cannot_belong_to_report()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 0,
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
            'effective_date' => '2026-01-15',
            'declaration_month' => '2026-02', // Different from report month (2026-01)
            'suggested_declaration_month' => '2026-02',
            'approval_status' => 'PENDING',
        ]);

        $canBelong = $this->service->canRecordBelongToReport($record, $report);

        $this->assertFalse($canBelong);
    }

    /** @test */
    public function it_finds_report_for_record_by_declaration_month()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 2,
            'status' => 'DRAFT',
            'total_increase' => 0,
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
            'effective_date' => '2026-01-15',
            'declaration_month' => '2026-02',
            'suggested_declaration_month' => '2026-02',
            'approval_status' => 'PENDING',
        ]);

        $foundReport = $this->service->findReportForRecord($record);

        $this->assertNotNull($foundReport);
        $this->assertEquals($report->id, $foundReport->id);
        $this->assertEquals(2026, $foundReport->year);
        $this->assertEquals(2, $foundReport->month);
    }

    /** @test */
    public function it_returns_null_if_no_report_found_for_record()
    {
        $employee = Employee::create([
            'full_name' => 'Test Employee',
            'employee_code' => 'EMP001',
            'dob' => '1990-01-01',
            'gender' => 'MALE',
            'personal_email' => 'test@example.com',
        ]);

        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 0,
            'total_decrease' => 0,
            'total_adjust' => 0,
        ]);

        $record = InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-15',
            'declaration_month' => '2026-03', // No report exists for March
            'suggested_declaration_month' => '2026-02',
            'approval_status' => 'PENDING',
        ]);

        $foundReport = $this->service->findReportForRecord($record);

        $this->assertNull($foundReport);
    }

    /** @test */
    public function it_gets_existing_report_for_month()
    {
        $existingReport = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 3,
            'status' => 'DRAFT',
            'total_increase' => 0,
            'total_decrease' => 0,
            'total_adjust' => 0,
        ]);

        $report = $this->service->getOrCreateReportForMonth('2026-03');

        $this->assertEquals($existingReport->id, $report->id);
    }

    /** @test */
    public function it_creates_new_report_if_not_exists()
    {
        $this->assertDatabaseMissing('insurance_monthly_reports', [
            'year' => 2026,
            'month' => 4,
        ]);

        $report = $this->service->getOrCreateReportForMonth('2026-04');

        $this->assertDatabaseHas('insurance_monthly_reports', [
            'id' => $report->id,
            'year' => 2026,
            'month' => 4,
            'status' => 'DRAFT',
        ]);
    }

    /** @test */
    public function it_returns_early_for_day_1_to_14()
    {
        $this->assertEquals('early', $this->service->getDeclarationPeriod(Carbon::parse('2026-01-01')));
        $this->assertEquals('early', $this->service->getDeclarationPeriod(Carbon::parse('2026-01-07')));
        $this->assertEquals('early', $this->service->getDeclarationPeriod(Carbon::parse('2026-01-14')));
    }

    /** @test */
    public function it_returns_late_for_day_15_to_31()
    {
        $this->assertEquals('late', $this->service->getDeclarationPeriod(Carbon::parse('2026-01-15')));
        $this->assertEquals('late', $this->service->getDeclarationPeriod(Carbon::parse('2026-01-20')));
        $this->assertEquals('late', $this->service->getDeclarationPeriod(Carbon::parse('2026-01-31')));
    }

    /** @test */
    public function it_analyzes_declaration_month_with_no_override()
    {
        $effectiveDate = Carbon::parse('2026-01-10'); // Early period, suggests 2026-01

        $analysis = $this->service->analyzeDeclarationMonth($effectiveDate, '2026-01');

        $this->assertEquals('2026-01', $analysis['suggested']);
        $this->assertEquals('2026-01', $analysis['actual']);
        $this->assertFalse($analysis['isOverride']);
        $this->assertEquals(0, $analysis['monthsDiff']);
    }

    /** @test */
    public function it_analyzes_declaration_month_with_override()
    {
        $effectiveDate = Carbon::parse('2026-01-10'); // Suggests 2026-01

        $analysis = $this->service->analyzeDeclarationMonth($effectiveDate, '2026-02'); // Overridden to Feb

        $this->assertEquals('2026-01', $analysis['suggested']);
        $this->assertEquals('2026-02', $analysis['actual']);
        $this->assertTrue($analysis['isOverride']);
        $this->assertEquals(1, $analysis['monthsDiff']); // Actual is 1 month after suggested
    }

    /** @test */
    public function it_groups_records_by_declaration_month()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 0,
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
            'dob' => '1990-01-01',
            'gender' => 'FEMALE',
            'personal_email' => 'emp2@example.com',
        ]);

        $record1 = InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee1->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-01',
            'declaration_month' => '2026-01',
            'suggested_declaration_month' => '2026-01',
            'approval_status' => 'PENDING',
        ]);

        $record2 = InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee2->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-15',
            'declaration_month' => '2026-02',
            'suggested_declaration_month' => '2026-02',
            'approval_status' => 'PENDING',
        ]);

        $records = collect([$record1, $record2]);
        $grouped = $this->service->groupRecordsByDeclarationMonth($records);

        $this->assertArrayHasKey('2026-01', $grouped);
        $this->assertArrayHasKey('2026-02', $grouped);
        $this->assertEquals(1, $grouped['2026-01']);
        $this->assertEquals(1, $grouped['2026-02']);
    }

    /** @test */
    public function it_gets_records_without_declaration_month()
    {
        $report = InsuranceMonthlyReport::create([
            'year' => 2026,
            'month' => 1,
            'status' => 'DRAFT',
            'total_increase' => 0,
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
            'dob' => '1990-01-01',
            'gender' => 'FEMALE',
            'personal_email' => 'emp2@example.com',
        ]);

        InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee1->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-01',
            'declaration_month' => '2026-01', // Has declaration month
            'suggested_declaration_month' => '2026-01',
            'approval_status' => 'PENDING',
        ]);

        InsuranceChangeRecord::create([
            'report_id' => $report->id,
            'employee_id' => $employee2->id,
            'change_type' => 'INCREASE',
            'insurance_salary' => 10000000,
            'effective_date' => '2026-01-15',
            'declaration_month' => null, // Missing declaration month
            'suggested_declaration_month' => '2026-02',
            'approval_status' => 'PENDING',
        ]);

        $recordsWithoutMonth = $this->service->getRecordsWithoutDeclarationMonth($report);

        $this->assertCount(1, $recordsWithoutMonth);
        $this->assertEquals($employee2->id, $recordsWithoutMonth->first()->employee_id);
    }
}
