<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        $school = $this->route('school');
        return $this->user()->can('update', $school);
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable','string','max:50'],
            'name' => ['required','string','max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên trường là bắt buộc.',
        ];
    }
}
