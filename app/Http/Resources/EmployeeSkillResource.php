<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeSkillResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'         => $this->id,           // ID của employee_skills
            'employee_id'=> $this->employee_id,
            'skill_id'   => $this->skill_id,
            'skill_name' => $this->whenLoaded('skill', fn() => $this->skill->name),
            'level'      => (int) $this->level,
            'years'      => (int) $this->years,
            'note'       => $this->note,
            'created_at' => optional($this->created_at)->toDateTimeString(),
        ];
    }
}
