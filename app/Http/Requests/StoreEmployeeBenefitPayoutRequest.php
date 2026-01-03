<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeBenefitPayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'uuid', 'exists:employees,id'],
            'benefit_type_id' => ['required', 'integer', 'exists:benefit_types,id'],
            'paid_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['string', 'max:3'],
            'note' => ['nullable', 'string'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'source' => ['in:MANUAL,IMPORT'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Vui lòng chọn nhân viên.',
            'employee_id.exists' => 'Nhân viên không tồn tại.',
            'benefit_type_id.required' => 'Vui lòng chọn loại phúc lợi.',
            'benefit_type_id.exists' => 'Loại phúc lợi không tồn tại.',
            'paid_date.required' => 'Vui lòng nhập ngày chi trả.',
            'paid_date.date' => 'Ngày chi trả không hợp lệ.',
            'amount.required' => 'Vui lòng nhập số tiền.',
            'amount.numeric' => 'Số tiền phải là số.',
            'amount.min' => 'Số tiền phải lớn hơn 0.',
        ];
    }
}
