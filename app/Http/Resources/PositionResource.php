<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get insurance grade data
        $grades = $this->whenLoaded('salaryGrades', function () {
            return $this->salaryGrades->map(function ($grade) {
                return [
                    'grade' => $grade->grade,
                    'coefficient' => (float) $grade->coefficient,
                ];
            })->values()->all();
        });

        return [
            'id' => $this->id,
            'department_id' => $this->department_id,
            'department_name' => $this->department?->name,
            'department_parent' => $this->department?->parent?->name,
            'title' => $this->title,
            'level' => $this->level,
            'insurance_base_salary' => $this->insurance_base_salary,
            'position_salary' => $this->position_salary,
            'competency_salary' => $this->competency_salary,
            'allowance' => $this->allowance,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Insurance grade data
            'insurance_grades' => $grades,
            'insurance_grade_min' => $grades ? ($grades[0]['coefficient'] ?? null) : null,
            'insurance_grade_max' => $grades ? ($grades[count($grades) - 1]['coefficient'] ?? null) : null,
            'insurance_grade_effective_from' => $this->whenLoaded('salaryGrades', function () {
                return $this->salaryGrades->first()?->effective_from?->format('d/m/Y');
            }),
        ];
    }
}
