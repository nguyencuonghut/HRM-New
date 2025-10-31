<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
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
            'type' => $this->type,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'code' => $this->code,
            'head_assignment_id' => $this->head_assignment_id,
            'deputy_assignment_id' => $this->deputy_assignment_id,
            'order_index' => $this->order_index,
            'is_active' => $this->is_active,
            'parent' => $this->when(
                $this->relationLoaded('parent'),
                fn() => $this->parent ? [
                    'id' => $this->parent->id,
                    'name' => $this->parent->name,
                ] : null
            ),
            'children' => $this->when(
                $this->relationLoaded('children'),
                fn() => DepartmentResource::collection($this->children)->resolve()
            ),
            'children_count' => $this->when(
                isset($this->children_count),
                fn() => $this->children_count
            ),
            'assignments_count' => $this->when(
                isset($this->assignments_count),
                fn() => $this->assignments_count
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
