<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractTemplateDocMergeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $commonPlaceholders = [
            // key sẽ dùng trong DOCX: ${employee_full_name}, ${contract_number}, ...
            'employee_full_name',
            'employee_code',
            'department_name',
            'position_title',
            'contract_number',
            'contract_start_date',
            'contract_end_date',
            'base_salary',
            'insurance_salary',
            'position_allowance',
            'working_time',
            'work_location',
            'other_allowances_text',
        ];

        $rows = [
            [
                'id'                => Str::uuid(),
                'name'              => 'HĐ Thử việc (DOCX)',
                'type'              => 'PROBATION',
                'engine'            => 'DOCX_MERGE',
                'body_path'         => 'templates/contracts/probation_v1.docx', // lưu ở storage/app/...
                'content'           => null,
                'placeholders_json' => json_encode($commonPlaceholders, JSON_UNESCAPED_UNICODE),
                'is_default'        => true,
                'is_active'         => true,
                'version'           => 1,
                'created_at'        => $now,
                'updated_at'        => $now,
                'updated_by'        => null,
            ],
            [
                'id'                => Str::uuid(),
                'name'              => 'HĐ Xác định thời hạn (DOCX)',
                'type'              => 'FIXED_TERM',
                'engine'            => 'DOCX_MERGE',
                'body_path'         => 'templates/contracts/fixed_term_v1.docx',
                'content'           => null,
                'placeholders_json' => json_encode($commonPlaceholders, JSON_UNESCAPED_UNICODE),
                'is_default'        => false,
                'is_active'         => true,
                'version'           => 1,
                'created_at'        => $now,
                'updated_at'        => $now,
                'updated_by'        => null,
            ],
        ];

        DB::table('contract_templates')->upsert(
            $rows,
            ['id'],
            [
                'name','type','engine','body_path','content',
                'placeholders_json','is_default','is_active',
                'version','updated_at','updated_by',
            ]
        );
    }
}
