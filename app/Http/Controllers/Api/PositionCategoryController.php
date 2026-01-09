<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Support\Facades\DB;

class PositionCategoryController extends Controller
{
    /**
     * Get all position categories with their positions and salary grades
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCategoriesWithGrades()
    {
        // Get all positions with their salary categories and grades
        $positions = Position::whereNotNull('salary_category')
            ->with(['salaryGrades' => function($query) {
                $query->where('is_active', true)
                    ->orderBy('grade');
            }])
            ->orderBy('salary_category')
            ->orderBy('title')
            ->get();

        // Group positions by salary_category
        $categorized = $positions->groupBy('salary_category');

        // Transform to array format
        $categories = [];
        foreach ($categorized as $categoryName => $categoryPositions) {
            $categories[] = [
                'name' => $categoryName,
                'positions' => $categoryPositions->map(function($position) {
                    return [
                        'id' => $position->id,
                        'title' => $position->title,
                        'department' => $position->department?->name,
                        'grades' => $position->salaryGrades->map(function($grade) {
                            return [
                                'grade' => $grade->grade,
                                'coefficient' => (float) $grade->coefficient,
                                'effective_from' => $grade->effective_from?->format('Y-m-d'),
                                'effective_to' => $grade->effective_to?->format('Y-m-d'),
                            ];
                        })->toArray()
                    ];
                })->toArray()
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $categories,
            'summary' => [
                'total_categories' => count($categories),
                'total_positions' => $positions->count(),
                'positions_with_grades' => $positions->filter(fn($p) => $p->salaryGrades->count() > 0)->count(),
            ]
        ]);
    }
}
