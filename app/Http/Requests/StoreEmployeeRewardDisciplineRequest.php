<?php

namespace App\Http\Requests;

use App\Enums\RewardDisciplineCategory;
use App\Enums\RewardDisciplineStatus;
use App\Enums\RewardDisciplineType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRewardDisciplineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');
        return $this->user()->can('editProfile', $employee);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(RewardDisciplineType::class)],
            'category' => ['required', Rule::enum(RewardDisciplineCategory::class)],
            'decision_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employee_reward_disciplines', 'decision_no')
                    ->whereNull('deleted_at'),
            ],
            'decision_date' => 'required|date',
            'effective_date' => 'required|date|after_or_equal:decision_date',
            'amount' => 'nullable|numeric|min:0',
            'description' => 'required|string|min:10',
            'note' => 'nullable|string',
            'issued_by' => 'required|uuid|exists:employees,id',
            'status' => ['required', Rule::enum(RewardDisciplineStatus::class)],
            'related_contract_id' => 'nullable|exists:contracts,id',
            'evidence_files' => 'nullable|array',
            'evidence_files.*' => 'nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'loại',
            'category' => 'hạng mục',
            'decision_no' => 'số quyết định',
            'decision_date' => 'ngày quyết định',
            'effective_date' => 'ngày hiệu lực',
            'amount' => 'số tiền',
            'description' => 'mô tả',
            'note' => 'ghi chú',
            'issued_by' => 'người ký',
            'status' => 'trạng thái',
            'related_contract_id' => 'hợp đồng liên quan',
            'evidence_files' => 'tài liệu đính kèm',
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Vui lòng chọn loại.',
            'category.required' => 'Vui lòng chọn hạng mục.',
            'decision_no.required' => 'Vui lòng nhập số quyết định.',
            'decision_no.unique' => 'Số quyết định này đã tồn tại.',
            'decision_no.max' => 'Số quyết định không được vượt quá :max ký tự.',
            'decision_date.required' => 'Vui lòng chọn ngày quyết định.',
            'decision_date.date' => 'Ngày quyết định không hợp lệ.',
            'effective_date.required' => 'Vui lòng chọn ngày hiệu lực.',
            'effective_date.date' => 'Ngày hiệu lực không hợp lệ.',
            'effective_date.after_or_equal' => 'Ngày hiệu lực phải sau hoặc bằng ngày quyết định.',
            'amount.numeric' => 'Số tiền phải là số.',
            'amount.min' => 'Số tiền không được nhỏ hơn :min.',
            'description.required' => 'Vui lòng nhập mô tả.',
            'description.min' => 'Mô tả phải có ít nhất :min ký tự.',
            'issued_by.required' => 'Vui lòng chọn người ký.',
            'issued_by.exists' => 'Người ký không tồn tại.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'related_contract_id.exists' => 'Hợp đồng liên quan không tồn tại.',
            'evidence_files.array' => 'Tài liệu đính kèm không hợp lệ.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional validation: amount required for certain categories
            $category = RewardDisciplineCategory::tryFrom($this->input('category'));
            if ($category && $category->requiresAmount() && empty($this->input('amount'))) {
                $validator->errors()->add(
                    'amount',
                    'Số tiền là bắt buộc cho loại ' . $category->label()
                );
            }
        });
    }
}
