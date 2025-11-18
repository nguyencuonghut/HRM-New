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
            'name'              => ['required','string','max:255'],
            'type'              => ['required', Rule::in(['PROBATION','FIXED_TERM','INDEFINITE','SERVICE','INTERNSHIP','PARTTIME'])],
            'engine'            => ['required', Rule::in(['LIQUID','BLADE','HTML_TO_PDF','DOCX_MERGE'])],
            'body_path'         => [Rule::requiredIf(fn()=> in_array($this->engine, ['BLADE', 'DOCX_MERGE'])), 'string','max:255'],
            'content'           => ['nullable','string'], // LIQUID: nội dung template
            'placeholders_json' => ['nullable','json'],
            'is_default'        => ['boolean'],
            'is_active'         => ['boolean'],
            'description'       => ['nullable','string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'Tên mẫu là bắt buộc.',
            'type.required'          => 'Loại hợp đồng là bắt buộc.',
            'type.in'                => 'Loại hợp đồng không hợp lệ.',
            'engine.required'        => 'Engine là bắt buộc.',
            'engine.in'              => 'Engine không hợp lệ.',
            'content.required_if'    => 'Nội dung template là bắt buộc khi dùng LIQUID.',
            'body_path.required'     => 'Đường dẫn file là bắt buộc khi dùng BLADE hoặc DOCX_MERGE.',
        ];
    }
}
