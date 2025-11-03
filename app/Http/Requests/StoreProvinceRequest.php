<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProvinceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', 'unique:provinces,code'],
            'name' => ['required', 'string', 'max:255', 'unique:provinces,name'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'mã tỉnh/thành phố',
            'name' => 'tên tỉnh/thành phố',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã tỉnh/thành phố.',
            'code.unique' => 'Mã tỉnh/thành phố này đã tồn tại.',
            'name.required' => 'Vui lòng nhập tên tỉnh/thành phố.',
            'name.unique' => 'Tỉnh/thành phố này đã tồn tại.',
        ];
    }
}
