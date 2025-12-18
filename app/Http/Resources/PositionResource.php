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
        ];
    }
}
