<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceConfigSetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'effective_from' => $this->effective_from?->format('Y-m-d'),
            'effective_to' => $this->effective_to?->format('Y-m-d'),
            'based_on_set_id' => $this->based_on_set_id,

            // Relationships
            'based_on_set' => $this->when(
                $this->relationLoaded('basedOnSet'),
                fn() => $this->basedOnSet ? [
                    'id' => $this->basedOnSet->id,
                    'code' => $this->basedOnSet->code,
                    'name' => $this->basedOnSet->name,
                ] : null
            ),

            'minimum_wages' => $this->when(
                $this->relationLoaded('minimumWages'),
                fn() => $this->minimumWages->map(fn($wage) => [
                    'id' => $wage->id,
                    'region' => $wage->region,
                    'region_name' => $wage->region_name,
                    'amount' => (float) $wage->amount,
                    'formatted_amount' => $wage->formatted_amount,
                    'note' => $wage->note,
                    'effective_from' => $wage->effective_from?->format('Y-m-d'),
                    'effective_to' => $wage->effective_to?->format('Y-m-d'),
                ])
            ),

            'salary_grades' => $this->when(
                $this->relationLoaded('salaryGrades'),
                fn() => $this->salaryGrades->map(fn($grade) => [
                    'id' => $grade->id,
                    'grade' => $grade->grade,
                    'name' => $grade->name,
                    'coefficient' => (float) $grade->coefficient,
                    'formatted_coefficient' => $grade->formatted_coefficient,
                    'description' => $grade->description,
                    'is_active' => $grade->is_active,
                ])
            ),

            // Counts
            'minimum_wages_count' => $this->when(
                isset($this->minimum_wages_count),
                fn() => $this->minimum_wages_count
            ),
            'salary_grades_count' => $this->when(
                isset($this->salary_grades_count),
                fn() => $this->salary_grades_count
            ),

            // Audit trail
            'created_by' => $this->created_by,
            'activated_by' => $this->activated_by,
            'activated_at' => $this->activated_at?->format('Y-m-d H:i:s'),
            'archived_by' => $this->archived_by,
            'archived_at' => $this->archived_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
