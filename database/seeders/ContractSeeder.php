<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Get employees
        $employees = DB::table('employees')->get()->keyBy('employee_code');

        // Get contract templates (nếu có)
        $templates = DB::table('contract_templates')->get()->keyBy('type');

        $contracts = [
            // 1. Giám đốc - Hợp đồng không xác định thời hạn từ 10 năm trước (có thâm niên)
            [
                'employee_code' => '312',
                'contract_type' => 'INDEFINITE',
                'contract_number' => 'HĐLĐ-312-2015',
                'start_date' => '2015-01-01',
                'end_date' => null,
                'status' => 'ACTIVE',
                'salary' => 50000000,
                'position' => 'Giám đốc',
                'department' => 'Ban Giám Đốc',
            ],

            // 2. Trưởng phòng HC - Hợp đồng không xác định thời hạn 7 năm (có thâm niên)
            [
                'employee_code' => '254',
                'contract_type' => 'INDEFINITE',
                'contract_number' => 'HĐLĐ-254-2018',
                'start_date' => '2018-03-01',
                'end_date' => null,
                'status' => 'ACTIVE',
                'salary' => 25000000,
                'position' => 'Trưởng phòng',
                'department' => 'Hành chính',
            ],

            // 3. Nhân viên NS - Hợp đồng không xác định thời hạn 3 năm (chưa đủ thâm niên)
            [
                'employee_code' => '2411',
                'contract_type' => 'INDEFINITE',
                'contract_number' => 'HĐLĐ-2411-2022',
                'start_date' => '2022-06-01',
                'end_date' => null,
                'status' => 'ACTIVE',
                'salary' => 15000000,
                'position' => 'Nhân viên',
                'department' => 'Nhân sự',
            ],

            // 4. Nhân viên Chất lượng - Hợp đồng không xác định thời hạn 2 năm
            [
                'employee_code' => '468',
                'contract_type' => 'INDEFINITE',
                'contract_number' => 'HĐLĐ-468-2023',
                'start_date' => '2023-01-15',
                'end_date' => null,
                'status' => 'ACTIVE',
                'salary' => 12000000,
                'position' => 'Nhân viên',
                'department' => 'Chất lượng',
            ],

            // 5. Nhân viên thử việc đang active
            [
                'employee_code' => '2571',
                'contract_type' => 'PROBATION',
                'contract_number' => 'HĐTV-2571-2025',
                'start_date' => '2025-10-01',
                'end_date' => '2025-12-31',
                'status' => 'ACTIVE',
                'salary' => 8000000,
                'position' => 'Nhân viên',
                'department' => 'Hành chính',
            ],

            // 6. Nhân viên có hợp đồng thử việc đã kết thúc
            [
                'employee_code' => '2142',
                'contract_type' => 'PROBATION',
                'contract_number' => 'HĐTV-2142-2024',
                'start_date' => '2024-06-01',
                'end_date' => '2024-08-31',
                'status' => 'EXPIRED',
                'salary' => 9000000,
                'position' => 'Nhân viên',
                'department' => 'KSNB',
            ],
            // Sau đó có hợp đồng không xác định thời hạn
            [
                'employee_code' => '2142',
                'contract_type' => 'INDEFINITE',
                'contract_number' => 'HĐLĐ-2142-2024',
                'start_date' => '2024-09-01',
                'end_date' => null,
                'status' => 'ACTIVE',
                'salary' => 10000000,
                'position' => 'Nhân viên',
                'department' => 'KSNB',
            ],

            // 7. Nhân viên có thâm niên 11 năm (>10 năm)
            [
                'employee_code' => '2272',
                'contract_type' => 'INDEFINITE',
                'contract_number' => 'HĐLĐ-2272-2014',
                'start_date' => '2014-01-01',
                'end_date' => null,
                'status' => 'ACTIVE',
                'salary' => 18000000,
                'position' => 'Nhân viên chính',
                'department' => 'KSNB',
            ],

            // 8. Nhân viên có thâm niên đúng 5 năm
            [
                'employee_code' => '912',
                'contract_type' => 'INDEFINITE',
                'contract_number' => 'HĐLĐ-912-2020',
                'start_date' => '2020-01-01',
                'end_date' => null,
                'status' => 'ACTIVE',
                'salary' => 14000000,
                'position' => 'Nhân viên',
                'department' => 'KSNB',
            ],

            // 9. Nhân viên mới gia nhập tháng này (pro-rata)
            [
                'employee_code' => '1992',
                'contract_type' => 'INDEFINITE',
                'contract_number' => 'HĐLĐ-1992-2025',
                'start_date' => '2025-12-01',
                'end_date' => null,
                'status' => 'ACTIVE',
                'salary' => 11000000,
                'position' => 'Nhân viên',
                'department' => 'KSNB',
            ],

            // 10. Nhân viên có hợp đồng hết hạn - hợp đồng xác định thời hạn
            [
                'employee_code' => '185',
                'contract_type' => 'FIXED_TERM',
                'contract_number' => 'HĐLĐ-185-2023',
                'start_date' => '2023-01-01',
                'end_date' => '2024-12-31',
                'status' => 'EXPIRED',
                'salary' => 13000000,
                'position' => 'Nhân viên',
                'department' => 'Hành chính',
            ],
        ];

        foreach ($contracts as $contract) {
            $employee = $employees[$contract['employee_code']] ?? null;
            if (!$employee) continue;

            $template = $templates['OFFICIAL'] ?? $templates->first();

            // Get employee's assignment for snapshot data
            $assignment = DB::table('employee_assignments')
                ->where('employee_id', $employee->id)
                ->where('is_primary', true)
                ->first();

            DB::table('contracts')->insert([
                'id' => (string) Str::uuid(),
                'employee_id' => $employee->id,
                'department_id' => $assignment?->department_id,
                'position_id' => $assignment?->position_id,
                'snapshot_department_name' => $contract['department'],
                'snapshot_position_title' => $contract['position'],
                'snapshot_role_type' => $assignment?->role_type ?? 'MEMBER',
                'template_id' => $template?->id,
                'contract_type' => $contract['contract_type'],
                'contract_number' => $contract['contract_number'],
                'start_date' => $contract['start_date'],
                'end_date' => $contract['end_date'],
                'status' => $contract['status'],
                'base_salary' => $contract['salary'],
                'insurance_salary' => $contract['salary'] * 0.7, // 70% for insurance
                'sign_date' => $contract['start_date'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Contracts seeded successfully!');
    }
}
