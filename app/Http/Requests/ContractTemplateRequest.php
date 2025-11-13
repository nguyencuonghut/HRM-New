<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ủy quyền ở Policy
    }

    public function rules(): array
    {
        $id = $this->route('template')?->id;

        return [
            'name'          => ['required','string','max:255'],
            'code'          => ['required','string','max:100', Rule::unique('contract_templates','code')->ignore($id)],
            'type' => ['required', Rule::in(['PROBATION','FIXED_TERM','INDEFINITE','SERVICE'])],
            'body_path'    => ['required','string','max:255'], // ví dụ: contracts/templates/default
            'is_default'    => ['required','boolean'],
            'is_active'     => ['required','boolean'],
            'description'   => ['nullable','string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Tên mẫu là bắt buộc.',
            'code.required'          => 'Mã mẫu là bắt buộc.',
            'code.unique'            => 'Mã mẫu đã tồn tại.',
            'type.required' => 'Loại hợp đồng là bắt buộc.',
            'body_path.required'    => 'Đường dẫn blade là bắt buộc.',
        ];
    }
}
