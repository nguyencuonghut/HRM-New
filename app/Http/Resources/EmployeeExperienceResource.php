<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeExperienceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'              => $this->id,
            'employee_id'     => $this->employee_id,
            'company_name'    => $this->company_name,
            'position_title'  => $this->position_title,
            'start_date'      => optional($this->start_date)->toDateString(),
            'end_date'        => optional($this->end_date)->toDateString(),
            'is_current'      => (bool) $this->is_current,
            'responsibilities'=> $this->responsibilities,
            'achievements'    => $this->achievements,
            'created_at'      => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
