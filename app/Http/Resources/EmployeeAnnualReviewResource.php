<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeAnnualReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'year' => $this->year,
            'kpi_avg_score' => $this->kpi_avg_score,
            'final_rating' => $this->final_rating,
            'final_score' => $this->final_score,
            'note' => $this->note,
            'input_by' => $this->input_by,
            'input_at' => optional($this->input_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'rating_label' => $this->rating_label,
            'employee' => $this->whenLoaded('employee', fn() => [
                'id' => $this->employee->id,
                'full_name' => $this->employee->full_name,
                'employee_code' => $this->employee->employee_code,
                'display_name' => $this->employee->display_name,
            ]),
            'input_by_user' => $this->whenLoaded('inputBy', fn() => [
                'id' => $this->inputBy->id,
                'name' => $this->inputBy->name,
            ]),
        ];
    }
}
