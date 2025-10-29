<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // tuỳ chính sách: return $this->user()->can('departments.update');
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('department')?->id ?? null;

        return [
            'parent_id'            => ['nullable', 'uuid', 'exists:departments,id', Rule::notIn([$id])], // không cho parent = chính nó
            'type'                 => ['required', 'in:DEPARTMENT,UNIT,TEAM'],
            'name'                 => ['required', 'string', 'max:255'],
            'code'                 => ['nullable', 'string', 'max:255', Rule::unique('departments', 'code')->ignore($id)],
            'head_assignment_id'   => ['nullable', 'uuid'],
            'deputy_assignment_id' => ['nullable', 'uuid'],
            'order_index'          => ['nullable', 'integer', 'min:0'],
            'is_active'            => ['nullable', 'boolean'],
        ];
    }
}
