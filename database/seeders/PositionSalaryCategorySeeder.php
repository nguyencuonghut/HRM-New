<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder to assign salary categories to positions based on their titles
 * and create position_salary_grades based on category coefficients
 */
class PositionSalaryCategorySeeder extends Seeder
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

        // Load insurance_salary_categories from database
        $insuranceSalaryCategories = DB::table('insurance_salary_categories')
            ->select('id', 'code', 'name', 'description')
            ->get()
            ->keyBy('code');

        // Normalize string for matching
        $norm = function (string $s): string {
            $s = str_replace("\xC2\xA0", ' ', $s);
            $s = trim($s);
            $s = preg_replace('/\s+/u', ' ', $s);
            return mb_strtolower($s);
        };

        // Build mapping: normalized position title => category
        $titleToCategoryMap = [];
        foreach ($categories as $category) {
            foreach ($category['example_positions'] as $positionTitle) {
                $normalizedTitle = $norm($positionTitle);
                $titleToCategoryMap[$normalizedTitle] = [
                    'code' => $category['code'],
                    'coefficients' => $category['coefficients'],
                ];
            }
        }

        // Get all positions
        $positions = DB::table('positions')->select('id', 'title')->get();

        $updated = 0;
        $notMatched = [];
        $gradesInserted = 0;

        foreach ($positions as $position) {
            $normalizedTitle = $norm($position->title);

            // Try exact match first
            $matched = $titleToCategoryMap[$normalizedTitle] ?? null;

            // If not exact match, try partial match
            if (!$matched) {
                foreach ($titleToCategoryMap as $pattern => $categoryData) {
                    if (str_contains($normalizedTitle, $pattern) || str_contains($pattern, $normalizedTitle)) {
                        $matched = $categoryData;
                        break;
                    }
                }
            }

            if ($matched) {
                // Get the insurance_salary_category_id from loaded categories
                $insuranceCategoryId = $insuranceSalaryCategories->get($matched['code'])->id ?? null;

                if ($insuranceCategoryId) {
                    // Update position with category FK
                    DB::table('positions')
                        ->where('id', $position->id)
                        ->update([
                            'insurance_salary_category_id' => $insuranceCategoryId,
                            'updated_at' => now(),
                        ]);
                    $updated++;
                }

                // Create salary grades for this position (7 grades per category)
                $existingGrades = DB::table('position_salary_grades')
                    ->where('position_id', $position->id)
                    ->count();

                if ($existingGrades === 0) {
                    $gradeRows = [];
                    foreach ($matched['coefficients'] as $gradeIndex => $coefficient) {
                        $gradeRows[] = [
                            'id' => \Illuminate\Support\Str::uuid(),
                            'position_id' => $position->id,
                            'grade' => $gradeIndex + 1,
                            'coefficient' => $coefficient,
                            'effective_from' => '2024-01-01',
                            'effective_to' => null,
                            'is_active' => true,
                            'note' => 'Hệ số ban đầu từ bảng phân loại chức danh',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    DB::table('position_salary_grades')->insert($gradeRows);
                    $gradesInserted += count($gradeRows);
                }
            } else {
                $notMatched[] = [
                    'id' => $position->id,
                    'title' => $position->title,
                ];
            }
        }

        // Print summary
        dump('=== POSITION SALARY CATEGORY SEED SUMMARY ===');
        dump('Total positions:', count($positions));
        dump('Updated with category:', $updated);
        dump('Not matched:', count($notMatched));
        dump('Salary grades inserted:', $gradesInserted);

        if (!empty($notMatched)) {
            dump('--- POSITIONS WITHOUT CATEGORY MATCH ---');
            dump('These positions need manual category assignment:');
            foreach ($notMatched as $pos) {
                dump("  - {$pos['title']} (ID: {$pos['id']})");
            }
        }
    }
}
