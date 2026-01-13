<?php

namespace Database\Seeders;

use App\Models\InsuranceComponent;
use Illuminate\Database\Seeder;

class InsuranceComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seed 5 insurance components theo quy định BHXH Việt Nam:
     * - RETIREMENT_SURVIVOR: BHXH (Hưu trí & Tử tuất) = 22%
     * - SICKNESS_MATERNITY: BHXH (Ốm đau & thai sản) = 3%
     * - OCC_ACCIDENT_DISEASE: BHTNLĐ-BNN (Tai nạn lao động & bệnh nghề nghiệp) = 0.5%
     * - UNEMPLOYMENT: BHTN (Thất nghiệp) = 2%
     * - HEALTH: BHYT (Y tế) = 4.5%
     *
     * Total: 32% tổng đóng cho toàn bộ các khoản.
     */
    public function run(): void
    {
        $components = [
            [
                'code' => 'RETIREMENT_SURVIVOR',
                'name_vi' => 'BHXH - Hưu trí và Tử tuất',
                'default_rate_total' => 0.22000, // 22%
                'is_active' => true,
            ],
            [
                'code' => 'SICKNESS_MATERNITY',
                'name_vi' => 'BHXH - Ốm đau và Thai sản',
                'default_rate_total' => 0.03000, // 3%
                'is_active' => true,
            ],
            [
                'code' => 'OCC_ACCIDENT_DISEASE',
                'name_vi' => 'BHTNLĐ-BNN - Tai nạn lao động và Bệnh nghề nghiệp',
                'default_rate_total' => 0.00500, // 0.5%
                'is_active' => true,
            ],
            [
                'code' => 'UNEMPLOYMENT',
                'name_vi' => 'BHTN - Thất nghiệp',
                'default_rate_total' => 0.02000, // 2%
                'is_active' => true,
            ],
            [
                'code' => 'HEALTH',
                'name_vi' => 'BHYT - Y tế',
                'default_rate_total' => 0.04500, // 4.5%
                'is_active' => true,
            ],
        ];

        foreach ($components as $componentData) {
            InsuranceComponent::updateOrCreate(
                ['code' => $componentData['code']],
                $componentData
            );
        }

        $this->command->info('✅ Seeded 5 insurance components successfully.');
    }
}
