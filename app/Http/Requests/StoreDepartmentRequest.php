<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // tuỳ chính sách: return $this->user()->can('departments.create');
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id'           => ['nullable', 'uuid', 'exists:departments,id'],
            'type'                => ['required', 'in:DEPARTMENT,UNIT,TEAM'],
            'name'                => ['required', 'string', 'max:255'],
            'code'                => ['nullable', 'string', 'max:255', 'unique:departments,code'],
            'head_assignment_id'  => ['nullable', 'uuid'],
            'deputy_assignment_id'=> ['nullable', 'uuid'],
            'order_index'         => ['nullable', 'integer', 'min:0'],
            'is_active'           => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in'      => 'Loại đơn vị không hợp lệ.',
            'name.required'=> 'Vui lòng nhập tên phòng/ban.',
        ];
    }
}
