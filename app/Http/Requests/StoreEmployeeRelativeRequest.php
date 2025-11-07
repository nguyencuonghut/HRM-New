<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRelativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee'); // model binding
        return $this->user()->can('editProfile', $employee);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required','string','max:255'],
            'relation'  => ['required', Rule::in(['FATHER','MOTHER','SPOUSE','CHILD','SIBLING','OTHER'])],
            'dob'       => ['nullable','date'],
            'phone'     => ['nullable','string','max:50'],
            'occupation'=> ['nullable','string','max:255'],
            'address'   => ['nullable','string','max:500'],
            'is_emergency_contact' => ['boolean'],
            'note'      => ['nullable','string','max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'họ và tên',
            'relation'  => 'mối quan hệ',
            'dob'       => 'ngày sinh',
            'phone'     => 'số điện thoại',
            'occupation'=> 'nghề nghiệp',
            'address'   => 'địa chỉ',
            'is_emergency_contact' => 'liên hệ khẩn cấp',
            'note'      => 'ghi chú',
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.max' => 'Họ và tên không được vượt quá 255 ký tự.',
            'relation.required' => 'Vui lòng chọn mối quan hệ.',
            'relation.in' => 'Mối quan hệ không hợp lệ.',
            'dob.date' => 'Ngày sinh không hợp lệ.',
            'phone.max' => 'Số điện thoại không được vượt quá 50 ký tự.',
            'occupation.max' => 'Nghề nghiệp không được vượt quá 255 ký tự.',
            'address.max' => 'Địa chỉ không được vượt quá 500 ký tự.',
            'note.max' => 'Ghi chú không được vượt quá 2000 ký tự.',
        ];
    }
}
