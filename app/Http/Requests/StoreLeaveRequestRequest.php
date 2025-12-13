<?php

namespace App\Http\Requests;

use App\Models\LeaveType;
use App\Services\LeaveCalculationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $leaveType = $this->input('leave_type_id')
            ? LeaveType::find($this->input('leave_type_id'))
            : null;

        $rules = [
            'employee_id' => ['nullable', 'uuid', 'exists:employees,id'], // Nullable for non-admin (set in controller)
            'leave_type_id' => ['required', 'uuid', 'exists:leave_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'days' => ['nullable', 'numeric', 'min:0'], // Auto-calculated in prepareForValidation
            'reason' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
            'submit' => ['nullable', 'boolean'],
        ];

        // Conditional rules based on leave type
        if ($leaveType) {
            switch ($leaveType->code) {
                case 'PERSONAL_PAID':
                    $rules['personal_leave_reason'] = [
                        'required',
                        Rule::in([
                            'MARRIAGE', 'CHILD_MARRIAGE', 'PARENT_DEATH', 'SIBLING_DEATH',
                            'SPOUSE_BIRTH', 'SPOUSE_BIRTH_TWINS', 'SPOUSE_BIRTH_TRIPLETS',
                            'SPOUSE_BIRTH_CAESAREAN', 'MOVING_HOUSE'
                        ])
                    ];
                    break;

                case 'MATERNITY':
                    $rules['expected_due_date'] = ['required', 'date', 'after:today'];
                    $rules['twins_count'] = ['nullable', 'integer', 'min:1', 'max:5'];
                    $rules['is_caesarean'] = ['nullable', 'boolean'];
                    $rules['children_under_36_months'] = ['nullable', 'integer', 'min:0'];
                    break;

                case 'SICK':
                    $rules['medical_certificate_path'] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
                    break;
            }
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'personal_leave_reason.required' => 'Vui lòng chọn lý do nghỉ phép riêng',
            'expected_due_date.required' => 'Vui lòng nhập ngày dự kiến sinh',
            'medical_certificate_path.required' => 'Phép ốm yêu cầu giấy xác nhận y tế',
            'medical_certificate_path.mimes' => 'File giấy y tế phải là PDF, JPG, hoặc PNG',
            'medical_certificate_path.max' => 'File giấy y tế không được vượt quá 5MB',
        ];
    }

    /**
     * Configure validator to auto-calculate days based on leave type
     */
    protected function prepareForValidation(): void
    {
        $leaveType = $this->input('leave_type_id')
            ? LeaveType::find($this->input('leave_type_id'))
            : null;

        if (!$leaveType) {
            return;
        }

        $service = app(LeaveCalculationService::class);
        $calculatedDays = null;

        // Auto-calculate days based on leave type
        switch ($leaveType->code) {
            case 'PERSONAL_PAID':
                if ($this->input('personal_leave_reason')) {
                    $calculatedDays = $service->calculatePersonalPaidLeaveDays(
                        $this->input('personal_leave_reason')
                    );
                }
                break;

            case 'MATERNITY':
                $calculatedDays = $service->calculateMaternityLeaveDays([
                    'twins_count' => $this->input('twins_count', 1),
                    'is_caesarean' => $this->input('is_caesarean', false),
                    'children_under_36_months' => $this->input('children_under_36_months', 0),
                ]);
                break;

            case 'SICK':
            case 'UNPAID':
            case 'STUDY':
            case 'BUSINESS':
            case 'ANNUAL':
            default:
                // Calculate from start_date to end_date
                if ($this->input('start_date') && $this->input('end_date')) {
                    $start = \Carbon\Carbon::parse($this->input('start_date'));
                    $end = \Carbon\Carbon::parse($this->input('end_date'));
                    $calculatedDays = $start->diffInDays($end) + 1;
                }
                break;
        }

        // Set calculated days (always set, even if 0)
        $this->merge(['days' => $calculatedDays ?? 0]);
    }
}
