<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InsuranceTestDataSeeder extends Seeder
{
    /**
     * Seed insurance test data for complete workflow testing
     *
     * Scenario mô phỏng:
     * 1. Nhân viên mới vào: INCREASE (NEW_HIRE)
     * 2. Nhân viên nghỉ việc: DECREASE (TERMINATION)
     * 3. Nhân viên nghỉ thai sản >30 ngày: DECREASE (LONG_ABSENCE)
     * 4. Nhân viên nghỉ ốm >30 ngày: DECREASE (LONG_ABSENCE)
     * 5. Nhân viên quay lại sau nghỉ dài: INCREASE (RETURN_TO_WORK)
     * 6. Nhân viên có phụ lục tăng lương: ADJUST (SALARY_CHANGE)
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Get employees
        $employees = DB::table('employees')->get()->keyBy('employee_code');

        // Get contracts
        $contracts = DB::table('contracts')
            ->where('status', 'ACTIVE')
            ->get()
            ->keyBy('contract_number');

        // Get leave types
        $leaveTypes = DB::table('leave_types')->get()->keyBy('code');

        // === 1. INSURANCE PARTICIPATIONS (Lịch sử tham gia BHXH) ===
        $this->createInsuranceParticipations($employees, $contracts);

        // === 2. LEAVE REQUESTS (Đơn xin nghỉ đã duyệt) - Create first ===
        $leaveRequestIds = $this->createLeaveRequests($employees, $leaveTypes);

        // === 3. EMPLOYEE ABSENCES (Nghỉ dài hạn >30 ngày) - Link to leave requests ===
        $this->createEmployeeAbsences($employees, $leaveRequestIds);

        // === 4. CONTRACT APPENDIXES (Phụ lục tăng lương) ===
        $this->createContractAppendixes($employees, $contracts);

        $this->command->info('✅ Insurance test data seeded successfully!');
        $this->command->info('📊 Ready to test workflow:');
        $this->command->info('   1. Create monthly report (Dec 2025)');
        $this->command->info('   2. Review auto-detected records:');
        $this->command->info('      - INCREASE: Employee 1992 (NEW_HIRE)');
        $this->command->info('      - DECREASE: Employee 2142 (MATERNITY), Employee 912 (SICK >30d)');
        $this->command->info('      - ADJUST: Employee 254, 2272 (SALARY_CHANGE)');
        $this->command->info('   3. Approve/Reject/Adjust records');
        $this->command->info('   4. Finalize report');
        $this->command->info('   5. Export to Excel');
    }

    /**
     * Create insurance participation history
     */
    private function createInsuranceParticipations($employees, $contracts): void
    {
        $now = Carbon::now();
        $participations = [];

        // Employees currently participating in insurance
        $activeEmployees = [
            '312' => ['start' => '2015-01-01', 'status' => 'ACTIVE'], // Giám đốc
            '254' => ['start' => '2018-03-01', 'status' => 'ACTIVE'], // Trưởng phòng HC
            '2411' => ['start' => '2022-06-01', 'status' => 'ACTIVE'], // Nhân viên NS
            '468' => ['start' => '2023-01-15', 'status' => 'ACTIVE'], // Nhân viên CL
            '2272' => ['start' => '2014-01-01', 'status' => 'ACTIVE'], // Thâm niên 11 năm
            '912' => ['start' => '2020-01-01', 'status' => 'SUSPENDED'], // Nghỉ ốm >30 ngày
            '1992' => ['start' => '2024-12-01', 'status' => 'ACTIVE'], // Nhân viên mới (tháng này)
            '2571' => ['start' => '2024-10-01', 'status' => 'ACTIVE'], // Thử việc
        ];

        // Employee nghỉ thai sản (đã SUSPEND)
        $participations[] = [
            'id' => (string) Str::uuid(),
            'employee_id' => $employees['2142']->id,
            'contract_id' => $contracts['HĐLĐ-2142-2024']->id,
            'participation_start_date' => '2024-09-01',
            'participation_end_date' => '2025-11-01', // Kết thúc tháng 11
            'has_social_insurance' => true,
            'has_health_insurance' => true,
            'has_unemployment_insurance' => true,
            'insurance_salary' => 10000000 * 0.7,
            'status' => 'SUSPENDED',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        foreach ($activeEmployees as $code => $data) {
            $employee = $employees[$code] ?? null;
            if (!$employee) continue;

            $contract = DB::table('contracts')
                ->where('employee_id', $employee->id)
                ->where('status', 'ACTIVE')
                ->first();

            if (!$contract) continue;

            $participations[] = [
                'id' => (string) Str::uuid(),
                'employee_id' => $employee->id,
                'contract_id' => $contract->id,
                'participation_start_date' => $data['start'],
                'participation_end_date' => null,
                'has_social_insurance' => true,
                'has_health_insurance' => true,
                'has_unemployment_insurance' => true,
                'insurance_salary' => $contract->insurance_salary,
                'status' => $data['status'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('insurance_participations')->insert($participations);
        $this->command->info('✓ Created ' . count($participations) . ' insurance participations');
    }

    /**
     * Create leave requests first and return IDs
     */
    private function createLeaveRequests($employees, $leaveTypes): array
    {
        $now = Carbon::now();
        $leaveRequests = [];
        $leaveApprovals = [];
        $leaveIds = [];
        $nsUser = DB::table('users')->where('email', 'ns@honghafeed.com.vn')->first();

        // 1. Thai sản
        $leaveId1 = (string) Str::uuid();
        $leaveRequests[] = [
            'id' => $leaveId1,
            'employee_id' => $employees['2142']->id,
            'leave_type_id' => $leaveTypes['MATERNITY']->id ?? null,
            'start_date' => '2025-08-01',
            'end_date' => '2025-11-30',
            'days' => 122,
            'reason' => 'Nghỉ thai sản',
            'status' => 'APPROVED',
            'submitted_at' => '2025-07-20 09:00:00',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $leaveIds['maternity'] = $leaveId1;

        if ($nsUser) {
            $leaveApprovals[] = [
                'id' => (string) Str::uuid(),
                'leave_request_id' => $leaveId1,
                'approver_id' => $nsUser->id,
                'step' => 1,
                'approver_role' => 'HR_MANAGER',
                'status' => 'APPROVED',
                'comment' => 'Đã duyệt nghỉ thai sản',
                'approved_at' => '2025-07-25 10:00:00',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 2. Ốm dài hạn
        $leaveId2 = (string) Str::uuid();
        $leaveRequests[] = [
            'id' => $leaveId2,
            'employee_id' => $employees['912']->id,
            'leave_type_id' => $leaveTypes['SICK']->id ?? null,
            'start_date' => '2025-10-15',
            'end_date' => '2025-12-31',
            'days' => 56,
            'reason' => 'Nghỉ ốm dài hạn',
            'status' => 'APPROVED',
            'submitted_at' => '2025-10-05 08:00:00',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $leaveIds['sick'] = $leaveId2;

        if ($nsUser) {
            $leaveApprovals[] = [
                'id' => (string) Str::uuid(),
                'leave_request_id' => $leaveId2,
                'approver_id' => $nsUser->id,
                'step' => 1,
                'approver_role' => 'HR_MANAGER',
                'status' => 'APPROVED',
                'comment' => 'Đã duyệt nghỉ ốm dài hạn',
                'approved_at' => '2025-10-10 14:30:00',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 3. Không lương
        $leaveId3 = (string) Str::uuid();
        $leaveRequests[] = [
            'id' => $leaveId3,
            'employee_id' => $employees['2571']->id,
            'leave_type_id' => $leaveTypes['UNPAID']->id ?? null,
            'start_date' => '2025-06-01',
            'end_date' => '2025-07-15',
            'days' => 45,
            'reason' => 'Nghỉ không lương',
            'status' => 'APPROVED',
            'submitted_at' => '2025-05-20 09:00:00',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $leaveIds['unpaid'] = $leaveId3;

        if ($nsUser) {
            $leaveApprovals[] = [
                'id' => (string) Str::uuid(),
                'leave_request_id' => $leaveId3,
                'approver_id' => $nsUser->id,
                'step' => 1,
                'approver_role' => 'HR_MANAGER',
                'status' => 'APPROVED',
                'comment' => 'Đã duyệt nghỉ không lương',
                'approved_at' => '2025-05-25 09:00:00',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('leave_requests')->insert($leaveRequests);
        $this->command->info('✓ Created ' . count($leaveRequests) . ' leave requests');

        if (!empty($leaveApprovals)) {
            DB::table('leave_approvals')->insert($leaveApprovals);
            $this->command->info('✓ Created ' . count($leaveApprovals) . ' leave approvals');
        }

        return $leaveIds;
    }

    /**
     * Create employee absences
     */
    private function createEmployeeAbsences($employees, $leaveIds): void
    {
        $now = Carbon::now();
        $absences = [];

        $absences[] = [
            'id' => (string) Str::uuid(),
            'employee_id' => $employees['2142']->id,
            'leave_request_id' => $leaveIds['maternity'] ?? null,
            'absence_type' => 'MATERNITY',
            'start_date' => '2025-08-01',
            'end_date' => '2025-11-30',
            'duration_days' => 122,
            'affects_insurance' => true,
            'status' => 'ENDED',
            'reason' => 'Nghỉ thai sản 4 tháng',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $absences[] = [
            'id' => (string) Str::uuid(),
            'employee_id' => $employees['912']->id,
            'leave_request_id' => $leaveIds['sick'] ?? null,
            'absence_type' => 'SICK_LONG',
            'start_date' => '2025-10-15',
            'end_date' => null,
            'duration_days' => 56,
            'affects_insurance' => true,
            'status' => 'ACTIVE',
            'reason' => 'Nghỉ ốm dài hạn >56 ngày',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $absences[] = [
            'id' => (string) Str::uuid(),
            'employee_id' => $employees['2571']->id,
            'leave_request_id' => $leaveIds['unpaid'] ?? null,
            'absence_type' => 'UNPAID_LONG',
            'start_date' => '2025-06-01',
            'end_date' => '2025-07-15',
            'duration_days' => 45,
            'affects_insurance' => true,
            'status' => 'ENDED',
            'reason' => 'Nghỉ không lương 45 ngày',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('employee_absences')->insert($absences);
        $this->command->info('✓ Created ' . count($absences) . ' employee absences');
    }

    /**
     * Create contract appendixes for salary adjustments
     */
    private function createContractAppendixes($employees, $contracts): void
    {
        $now = Carbon::now();
        $appendixes = [];

        // Employee 254 - Tăng lương từ 14M lên 16M (tháng 12/2025)
        $contract254 = DB::table('contracts')
            ->where('employee_id', $employees['254']->id)
            ->where('status', 'ACTIVE')
            ->first();

        if ($contract254) {
            $appendixes[] = [
                'id' => (string) Str::uuid(),
                'contract_id' => $contract254->id,
                'appendix_no' => 'PL-001',
                'appendix_type' => 'SALARY',
                'source' => 'WORKFLOW',
                'title' => 'Phụ lục tăng lương',
                'summary' => 'Tăng lương định kỳ từ 14M lên 16M',
                'effective_date' => '2024-12-01',
                'base_salary' => 16000000,
                'insurance_salary' => 16000000 * 0.7,
                'position_allowance' => 2000000,
                'other_allowances' => json_encode([]),
                'status' => 'ACTIVE',
                'note' => 'Tăng lương định kỳ',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Employee 2272 - Tăng lương từ 10M lên 12M (tháng 12/2025)
        $contract2272 = DB::table('contracts')
            ->where('employee_id', $employees['2272']->id)
            ->where('status', 'ACTIVE')
            ->first();

        if ($contract2272) {
            $appendixes[] = [
                'id' => (string) Str::uuid(),
                'contract_id' => $contract2272->id,
                'appendix_no' => 'PL-001',
                'appendix_type' => 'SALARY',
                'source' => 'WORKFLOW',
                'title' => 'Phụ lục tăng lương thâm niên',
                'summary' => 'Tăng lương thâm niên 11 năm từ 10M lên 12M',
                'effective_date' => '2024-12-01',
                'base_salary' => 12000000,
                'insurance_salary' => 12000000 * 0.7,
                'position_allowance' => 1500000,
                'other_allowances' => json_encode([]),
                'status' => 'ACTIVE',
                'note' => 'Tăng lương thâm niên 11 năm',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (count($appendixes) > 0) {
            DB::table('contract_appendixes')->insert($appendixes);
            $this->command->info('✓ Created ' . count($appendixes) . ' contract appendixes for salary adjustments');
        }
    }
}
