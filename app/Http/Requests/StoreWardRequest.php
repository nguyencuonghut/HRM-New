<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'province_id' => ['required', 'uuid', 'exists:provinces,id'],
            'code' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'province_id' => 'tỉnh/thành phố',
            'code' => 'mã phường/xã',
            'name' => 'tên phường/xã',
        ];
    }

    public function messages(): array
    {
        return [
            'province_id.required' => 'Vui lòng chọn tỉnh/thành phố.',
            'province_id.exists' => 'Tỉnh/thành phố không tồn tại.',
            'name.required' => 'Vui lòng nhập tên phường/xã.',
        ];
    }
}
