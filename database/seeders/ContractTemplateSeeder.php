<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'id' => Str::uuid(),
                'name' => 'Mẫu Hợp đồng Thử việc (VN)',
                'type' => 'PROBATION',
                'engine' => 'BLADE',
                'body_path' => 'contracts/templates/probation', // dùng view('contracts/templates/probation')
                'placeholders_json' => json_encode([
                    'employee.full_name','employee.employee_code','department.name','position.name',
                    'contract.contract_number','contract.start_date','contract.end_date',
                    'terms.base_salary','terms.insurance_salary','terms.position_allowance','terms.working_time','terms.work_location'
                ]),
                'is_active' => true, 'version' => 1,
                'created_at'=>now(),'updated_at'=>now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Mẫu HĐ Xác định thời hạn (VN)',
                'type' => 'FIXED_TERM',
                'engine' => 'BLADE',
                'body_path' => 'contracts/templates/fixed_term',
                'placeholders_json' => json_encode([
                    'employee.full_name','employee.employee_code','department.name','position.name',
                    'contract.contract_number','contract.start_date','contract.end_date',
                    'terms.base_salary','terms.insurance_salary','terms.position_allowance','terms.working_time','terms.work_location'
                ]),
                'is_active' => true, 'version' => 1,
                'created_at'=>now(),'updated_at'=>now(),
            ],
        ];

        DB::table('contract_templates')->upsert($rows, ['id'], ['name','type','engine','body_path','placeholders_json','is_active','version','updated_at']);
    }
}
