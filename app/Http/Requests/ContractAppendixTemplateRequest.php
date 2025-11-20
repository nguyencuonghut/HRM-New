<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractAppendixTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Ủy quyền ở Policy
    }

    public function rules(): array
    {
        $id = $this->route('template')?->id;

        return [
            'name'              => ['required', 'string', 'max:255'],
            'code'              => [
                'required',
                'string',
                'max:50',
                Rule::unique('contract_appendix_templates', 'code')->ignore($id),
            ],
            'appendix_type'     => [
                'required',
                Rule::in(['SALARY', 'ALLOWANCE', 'POSITION', 'DEPARTMENT', 'WORKING_TERMS', 'EXTENSION', 'OTHER'])
            ],
            'engine'            => ['sometimes', Rule::in(['DOCX_MERGE'])], // Chỉ cho phép DOCX_MERGE
            'body_path'         => ['required', 'string', 'max:255'], // Bắt buộc vì chỉ dùng DOCX
            'content'           => ['nullable', 'string'],
            'placeholders_json' => ['nullable', 'json'],
            'description'       => ['nullable', 'string', 'max:500'],
            'is_default'        => ['boolean'],
            'is_active'         => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'Tên mẫu phụ lục là bắt buộc.',
            'code.required'         => 'Mã mẫu là bắt buộc.',
            'code.unique'           => 'Mã mẫu đã tồn tại.',
            'appendix_type.required'=> 'Loại phụ lục là bắt buộc.',
            'appendix_type.in'      => 'Loại phụ lục không hợp lệ.',
            'body_path.required'    => 'Đường dẫn file DOCX là bắt buộc.',
        ];
    }

    /**
     * Prepare data for validation - đảm bảo engine luôn là DOCX_MERGE
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'engine' => 'DOCX_MERGE',
        ]);
    }
}
