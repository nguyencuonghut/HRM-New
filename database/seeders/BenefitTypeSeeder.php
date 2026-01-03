<?php

namespace Database\Seeders;

use App\Models\BenefitType;
use Illuminate\Database\Seeder;

class BenefitTypeSeeder extends Seeder
{
    public function run(): void
    {
        $benefitTypes = [
            [
                'code' => 'BIRTHDAY',
                'name' => 'Sinh nhật',
                'description' => 'Phúc lợi sinh nhật nhân viên',
                'is_active' => true,
            ],
            [
                'code' => 'BEREAVEMENT',
                'name' => 'Hiếu',
                'description' => 'Hỗ trợ ma chay (gia đình nhân viên qua đời)',
                'is_active' => true,
            ],
            [
                'code' => 'WEDDING',
                'name' => 'Hỷ',
                'description' => 'Hỗ trợ đám cưới nhân viên',
                'is_active' => true,
            ],
            [
                'code' => 'SICK',
                'name' => 'Ốm',
                'description' => 'Hỗ trợ khi nhân viên ốm đau',
                'is_active' => true,
            ],
            [
                'code' => 'CHILDBIRTH',
                'name' => 'Sinh con',
                'description' => 'Hỗ trợ khi nhân viên sinh con',
                'is_active' => true,
            ],
            [
                'code' => 'CHILD_SICK',
                'name' => 'Con ốm',
                'description' => 'Hỗ trợ khi con nhân viên ốm',
                'is_active' => true,
            ],
            [
                'code' => 'LONGEVITY',
                'name' => 'Mừng thọ',
                'description' => 'Hỗ trợ mừng thọ cha mẹ nhân viên',
                'is_active' => true,
            ],
            [
                'code' => 'GOOD_STUDENT',
                'name' => 'Học sinh giỏi',
                'description' => 'Hỗ trợ con nhân viên học sinh giỏi',
                'is_active' => true,
            ],
        ];

        foreach ($benefitTypes as $type) {
            BenefitType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }
    }
}
