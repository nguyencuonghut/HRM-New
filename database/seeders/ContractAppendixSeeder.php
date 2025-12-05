<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractAppendixSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Get employees
        $employees = DB::table('employees')->get()->keyBy('employee_code');

        // Get contracts
        $contracts = DB::table('contracts')->get()->keyBy('contract_number');

        $appendices = [
            // Phụ lục tăng lương cho Giám đốc
            [
                'contract_number' => 'HĐLĐ-312-2015',
                'appendix_type' => 'SALARY',
                'appendix_no' => 'PL-312-2023',
                'title' => 'Điều chỉnh mức lương cơ bản',
                'summary' => 'Tăng lương từ 45.000.000đ lên 50.000.000đ',
                'effective_date' => '2023-01-01',
                'status' => 'ACTIVE',
                'base_salary' => 50000000,
                'insurance_salary' => 35000000,
                'note' => 'Điều chỉnh lương theo hiệu quả công việc',
            ],

            // Phụ lục tăng lương cho Trưởng phòng HC
            [
                'contract_number' => 'HĐLĐ-254-2018',
                'appendix_type' => 'SALARY',
                'appendix_no' => 'PL-254-2024',
                'title' => 'Điều chỉnh mức lương định kỳ',
                'summary' => 'Tăng lương từ 20.000.000đ lên 25.000.000đ',
                'effective_date' => '2024-01-01',
                'status' => 'ACTIVE',
                'base_salary' => 25000000,
                'insurance_salary' => 17500000,
                'note' => 'Tăng lương định kỳ',
            ],

            // Phụ lục thay đổi vị trí
            [
                'contract_number' => 'HĐLĐ-2411-2022',
                'appendix_type' => 'POSITION',
                'appendix_no' => 'PL-2411-2024',
                'title' => 'Thay đổi chức danh',
                'summary' => 'Chuyển từ thử việc sang chính thức',
                'effective_date' => '2024-06-01',
                'status' => 'ACTIVE',
                'note' => 'Kết thúc thử việc, chuyển sang chính thức',
            ],

            // Phụ lục gia hạn hợp đồng
            [
                'contract_number' => 'HĐLĐ-468-2023',
                'appendix_type' => 'EXTENSION',
                'appendix_no' => 'PL-468-2024',
                'title' => 'Gia hạn hợp đồng',
                'summary' => 'Chuyển sang hợp đồng không xác định thời hạn',
                'effective_date' => '2024-01-15',
                'end_date' => null,
                'status' => 'ACTIVE',
                'note' => 'Gia hạn hợp đồng không xác định thời hạn',
            ],

            // Phụ lục tăng lương nhân viên thâm niên 11 năm
            [
                'contract_number' => 'HĐLĐ-2272-2014',
                'appendix_type' => 'SALARY',
                'appendix_no' => 'PL-2272-2024',
                'title' => 'Điều chỉnh lương theo thâm niên',
                'summary' => 'Tăng lương từ 15.000.000đ lên 18.000.000đ',
                'effective_date' => '2024-01-01',
                'status' => 'ACTIVE',
                'base_salary' => 18000000,
                'insurance_salary' => 12600000,
                'note' => 'Tăng lương do thâm niên',
            ],

            // Phụ lục tăng lương nhân viên 5 năm
            [
                'contract_number' => 'HĐLĐ-912-2020',
                'appendix_type' => 'SALARY',
                'appendix_no' => 'PL-912-2025',
                'title' => 'Điều chỉnh lương 5 năm',
                'summary' => 'Tăng lương từ 12.000.000đ lên 14.000.000đ',
                'effective_date' => '2025-01-01',
                'status' => 'ACTIVE',
                'base_salary' => 14000000,
                'insurance_salary' => 9800000,
                'note' => 'Tăng lương sau 5 năm',
            ],

            // Phụ lục chuyển đơn vị
            [
                'contract_number' => 'HĐLĐ-2142-2024',
                'appendix_type' => 'DEPARTMENT',
                'appendix_no' => 'PL-2142-2025',
                'title' => 'Điều chuyển đơn vị',
                'summary' => 'Chuyển từ Hành chính sang KSNB',
                'effective_date' => '2025-01-01',
                'status' => 'ACTIVE',
                'note' => 'Điều chuyển theo nhu cầu công việc',
            ],

            // Phụ lục khác
            [
                'contract_number' => 'HĐLĐ-185-2023',
                'appendix_type' => 'OTHER',
                'appendix_no' => 'PL-185-2024',
                'title' => 'Thay đổi điều khoản khác',
                'summary' => 'Các điều chỉnh khác về hợp đồng',
                'effective_date' => '2024-06-01',
                'end_date' => '2024-08-31',
                'status' => 'CANCELLED',
                'note' => 'Đã hủy do hợp đồng hết hạn',
            ],
        ];

        foreach ($appendices as $appendix) {
            $contract = $contracts[$appendix['contract_number']] ?? null;
            if (!$contract) continue;

            DB::table('contract_appendixes')->insert([
                'id' => (string) Str::uuid(),
                'contract_id' => $contract->id,
                'appendix_no' => $appendix['appendix_no'],
                'appendix_type' => $appendix['appendix_type'],
                'title' => $appendix['title'],
                'summary' => $appendix['summary'],
                'effective_date' => $appendix['effective_date'],
                'end_date' => $appendix['end_date'] ?? null,
                'status' => $appendix['status'],
                'base_salary' => $appendix['base_salary'] ?? null,
                'insurance_salary' => $appendix['insurance_salary'] ?? null,
                'note' => $appendix['note'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command->info('Contract appendices seeded successfully!');
    }
}
