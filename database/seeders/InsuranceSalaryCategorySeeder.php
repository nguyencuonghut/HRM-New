<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seed insurance salary categories from position_salary_categories.json
 */
class InsuranceSalaryCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Load category definitions
        $jsonPath = database_path('data/position_salary_categories.json');
        if (!file_exists($jsonPath)) {
            throw new \RuntimeException("position_salary_categories.json not found at: {$jsonPath}");
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        if (!isset($data['categories']) || !is_array($data['categories'])) {
            throw new \RuntimeException("position_salary_categories.json invalid format");
        }

        $categories = $data['categories'];
        $now = now();

        $salaryCategories = [];
        $displayOrder = 10; // Start from 10, increment by 10

        foreach ($categories as $category) {
            $salaryCategories[] = [
                'id' => Str::uuid()->toString(),
                'code' => $category['code'],
                'name' => $category['title'],
                'description' => $category['description'],
                'display_order' => $displayOrder,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $displayOrder += 10;
        }

        DB::table('insurance_salary_categories')->insert($salaryCategories);

        $this->command->info("✓ Inserted " . count($salaryCategories) . " salary categories");
    }
}
