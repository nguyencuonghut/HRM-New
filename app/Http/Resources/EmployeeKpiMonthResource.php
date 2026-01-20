<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeKpiMonthResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'year' => $this->year,
            'month' => $this->month,
            'kpi_score' => $this->kpi_score,
            'note' => $this->note,
            'input_by' => $this->input_by,
            'input_at' => optional($this->input_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
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
