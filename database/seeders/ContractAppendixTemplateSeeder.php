<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractAppendixTemplate;

class ContractAppendixTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Xoá để tránh trùng code khi seed lại (tuỳ môi trường)
        // ContractAppendixTemplate::truncate();

        $templates = [
            [
                'name'          => 'PL Điều chỉnh lương cơ bản',
                'code'          => 'PL-SALARY-01',
                'appendix_type' => 'SALARY',
                'blade_view'    => 'contracts.appendixes.default',
                'description'   => 'Phụ lục điều chỉnh lương cơ bản, lương BH và phụ cấp vị trí.',
                'is_default'    => true,
                'is_active'     => true,
            ],
            [
                'name'          => 'PL Điều chỉnh phụ cấp',
                'code'          => 'PL-ALLOWANCE-01',
                'appendix_type' => 'ALLOWANCE',
                'blade_view'    => 'contracts.appendixes.default',
                'description'   => 'Phụ lục điều chỉnh các khoản phụ cấp.',
                'is_default'    => true,
                'is_active'     => true,
            ],
            [
                'name'          => 'PL Điều chỉnh chức danh',
                'code'          => 'PL-POSITION-01',
                'appendix_type' => 'POSITION',
                'blade_view'    => 'contracts.appendixes.default',
                'description'   => 'Phụ lục thay đổi chức danh công việc của người lao động.',
                'is_default'    => true,
                'is_active'     => true,
            ],
            [
                'name'          => 'PL Điều chuyển đơn vị',
                'code'          => 'PL-DEPT-01',
                'appendix_type' => 'DEPARTMENT',
                'blade_view'    => 'contracts.appendixes.default',
                'description'   => 'Phụ lục điều chuyển người lao động sang đơn vị/phòng ban khác.',
                'is_default'    => true,
                'is_active'     => true,
            ],
            [
                'name'          => 'PL Điều chỉnh thời gian/địa điểm làm việc',
                'code'          => 'PL-WORKTERMS-01',
                'appendix_type' => 'WORKING_TERMS',
                'blade_view'    => 'contracts.appendixes.default',
                'description'   => 'Phụ lục thay đổi thời gian làm việc hoặc địa điểm làm việc.',
                'is_default'    => true,
                'is_active'     => true,
            ],
            [
                'name'          => 'PL Gia hạn hợp đồng',
                'code'          => 'PL-EXT-01',
                'appendix_type' => 'EXTENSION',
                'blade_view'    => 'contracts.appendixes.default',
                'description'   => 'Phụ lục gia hạn thời hạn của hợp đồng lao động.',
                'is_default'    => true,
                'is_active'     => true,
            ],
            [
                'name'          => 'PL Khác (tuỳ chỉnh)',
                'code'          => 'PL-OTHER-01',
                'appendix_type' => 'OTHER',
                'blade_view'    => 'contracts.appendixes.default',
                'description'   => 'Phụ lục dùng cho các điều chỉnh khác không nằm trong các loại chuẩn.',
                'is_default'    => true,
                'is_active'     => true,
            ],
        ];

        foreach ($templates as $tpl) {
            ContractAppendixTemplate::updateOrCreate(
                ['code' => $tpl['code']],
                $tpl
            );
        }
    }
}
