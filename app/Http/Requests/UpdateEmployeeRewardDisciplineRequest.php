<?php

namespace App\Http\Requests;

use App\Enums\RewardDisciplineCategory;
use App\Enums\RewardDisciplineStatus;
use App\Enums\RewardDisciplineType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRewardDisciplineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');
        $rewardDiscipline = $this->route('rewardDiscipline');

        return $this->user()->can('editProfile', $employee)
            && $rewardDiscipline->employee_id === $employee->id;
    }

    public function rules(): array
    {
        $currentId = optional($this->route('rewardDiscipline'))->id;

        return [
            'type' => ['sometimes', Rule::enum(RewardDisciplineType::class)],
            'category' => ['sometimes', Rule::enum(RewardDisciplineCategory::class)],
            'decision_no' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('employee_reward_disciplines', 'decision_no')
                    ->ignore($currentId)
                    ->whereNull('deleted_at'),
            ],
            'decision_date' => 'sometimes|date',
            'effective_date' => 'sometimes|date|after_or_equal:decision_date',
            'amount' => 'sometimes|nullable|numeric|min:0',
            'description' => 'sometimes|string|min:10',
            'note' => 'sometimes|nullable|string',
            'issued_by' => 'sometimes|uuid|exists:employees,id',
            'status' => ['sometimes', Rule::enum(RewardDisciplineStatus::class)],
            'related_contract_id' => 'sometimes|nullable|exists:contracts,id',
            'evidence_files' => 'sometimes|nullable|array',
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
            'decision_no.unique' => 'Số quyết định này đã tồn tại.',
            'decision_no.max' => 'Số quyết định không được vượt quá :max ký tự.',
            'decision_date.date' => 'Ngày quyết định không hợp lệ.',
            'effective_date.date' => 'Ngày hiệu lực không hợp lệ.',
            'effective_date.after_or_equal' => 'Ngày hiệu lực phải sau hoặc bằng ngày quyết định.',
            'amount.numeric' => 'Số tiền phải là số.',
            'amount.min' => 'Số tiền không được nhỏ hơn :min.',
            'description.min' => 'Mô tả phải có ít nhất :min ký tự.',
            'issued_by.exists' => 'Người ký không tồn tại.',
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
