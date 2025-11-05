<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeAssignmentResource extends JsonResource
{
    /**
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'employee_id'   => $this->employee_id,
            'department_id' => $this->department_id,
            'position_id'   => $this->position_id,
            'is_primary'    => (bool) $this->is_primary,
            'role_type'     => $this->role_type,
            'start_date'    => $this->start_date?->toDateString(),
            'end_date'      => $this->end_date?->toDateString(),
            'status'        => $this->status,
            'created_at'    => optional($this->created_at)->toDateTimeString(),

            // Thông tin để hiển thị
            'employee'  => [
                'id'        => $this->employee?->id,
                'full_name' => $this->employee?->full_name,
                'code'      => $this->employee?->employee_code ?? null,
            ],
            'department' => [
                'id'   => $this->department?->id,
                'name' => $this->department?->name,
                'type' => $this->department?->type,
            ],
            'position' => [
                'id'    => $this->position?->id,
                'title' => $this->position?->title,
            ],
        ];
    }
}
