<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ContractDocxTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Danh sách placeholder hỗ trợ (dành cho reference)
        $docxPlaceholders = [
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
                'name'              => 'Mẫu Hợp đồng Thử việc (DOCX)',
                'type'              => 'PROBATION',
                'engine'            => 'DOCX_MERGE',
                'body_path'         => 'templates/contracts/probation.docx',
                'content'           => null, // DOCX không dùng content field
                'placeholders_json' => json_encode($docxPlaceholders, JSON_UNESCAPED_UNICODE),
                'is_default'        => true,
                'is_active'         => true,
                'version'           => 1,
                'created_at'        => $now,
                'updated_at'        => $now,
                'updated_by'        => null,
            ],
            [
                'id'                => Str::uuid(),
                'name'              => 'Mẫu HĐ Xác định thời hạn (DOCX)',
                'type'              => 'FIXED_TERM',
                'engine'            => 'DOCX_MERGE',
                'body_path'         => 'templates/contracts/fixed_term.docx',
                'content'           => null,
                'placeholders_json' => json_encode($docxPlaceholders, JSON_UNESCAPED_UNICODE),
                'is_default'        => true,
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
                'name',
                'type',
                'engine',
                'body_path',
                'content',
                'placeholders_json',
                'is_default',
                'is_active',
                'version',
                'updated_at',
                'updated_by',
            ]
        );
    }
}
