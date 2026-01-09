<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeder: Company Regions - Vùng BHXH của công ty
 *
 * Seed dữ liệu vùng BHXH mặc định cho công ty
 */
class CompanyRegionSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📍 Đang seed company regions...');

        // Default: Công ty ở Vùng 3 (có thể thay đổi theo thực tế)
        DB::table('company_regions')->insert([
            'id' => Str::uuid()->toString(),
            'region' => 3, // Vùng III
            'effective_from' => '2026-01-01',
            'effective_to' => null, // Hiện tại
            'note' => 'Vùng BHXH mặc định của công ty',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('  ✓ Đã seed company region: Vùng 3');
    }
}
