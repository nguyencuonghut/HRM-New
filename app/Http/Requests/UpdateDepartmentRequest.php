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
            'order_index'          => ['nullable', 'integer', 'min:1'], // Optional, can be updated manually
            'is_active'            => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'            => 'Loại đơn vị không hợp lệ.',
            'name.required'      => 'Vui lòng nhập tên phòng/ban.',
            'order_index.min'    => 'Thứ tự phải lớn hơn 0.',
            'order_index.integer'=> 'Thứ tự phải là số nguyên.',
        ];
    }
}
