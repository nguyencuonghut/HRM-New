<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractAppendixTemplate;

class ContractAppendixTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'              => 'Phụ lục điều chỉnh lương',
                'code'              => 'PL-LUONG-01',
                'appendix_type'     => 'SALARY',
                'engine'            => 'DOCX_MERGE',
                'body_path'         => 'templates/appendixes/salary_adjustment.docx',
                'content'           => null,
                'placeholders_json' => ['employee_full_name', 'employee_code', 'contract_number', 'old_salary', 'new_salary', 'adjustment_reason', 'effective_date'],
                'is_default'        => true,
                'is_active'         => true,
                'version'           => 1,
                'description'       => 'Mẫu phụ lục điều chỉnh mức lương, phụ cấp',
            ],
            [
                'name'              => 'Phụ lục điều chỉnh phụ cấp',
                'code'              => 'PL-PHUCAP-01',
                'appendix_type'     => 'ALLOWANCE',
                'engine'            => 'DOCX_MERGE',
                'body_path'         => 'templates/appendixes/allowance_adjustment.docx',
                'content'           => null,
                'placeholders_json' => ['employee_full_name', 'employee_code', 'allowance_name', 'old_amount', 'new_amount', 'effective_date'],
                'is_default'        => true,
                'is_active'         => true,
                'version'           => 1,
                'description'       => 'Mẫu phụ lục điều chỉnh các khoản phụ cấp',
            ],
            [
                'name'              => 'Phụ lục điều chuyển vị trí',
                'code'              => 'PL-VITRI-01',
                'appendix_type'     => 'POSITION',
                'engine'            => 'DOCX_MERGE',
                'body_path'         => 'templates/appendixes/position_change.docx',
                'content'           => null,
                'placeholders_json' => ['employee_full_name', 'employee_code', 'old_position', 'new_position', 'old_department', 'new_department', 'effective_date'],
                'is_default'        => true,
                'is_active'         => true,
                'version'           => 1,
                'description'       => 'Mẫu phụ lục thay đổi chức danh, phòng ban',
            ],
            [
                'name'              => 'Phụ lục điều chuyển phòng ban',
                'code'              => 'PL-PHONGBAN-01',
                'appendix_type'     => 'DEPARTMENT',
                'engine'            => 'DOCX_MERGE',
                'body_path'         => 'templates/appendixes/department_transfer.docx',
                'content'           => null,
                'placeholders_json' => ['employee_full_name', 'employee_code', 'old_department', 'new_department', 'effective_date'],
                'is_default'        => true,
                'is_active'         => true,
                'version'           => 1,
                'description'       => 'Mẫu phụ lục điều chuyển đơn vị làm việc',
            ],
        ];

        foreach ($templates as $tpl) {
            ContractAppendixTemplate::updateOrCreate(
                ['code' => $tpl['code']],
                $tpl
            );
        }

        $this->command->info('✓ Seeded ' . count($templates) . ' appendix templates');
    }
}
