<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInsuranceConfigSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $configSetId = $this->route('insurance_config_set');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('insurance_config_sets', 'code')->ignore($configSetId)
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after:effective_from'],

            // Minimum wages (4 regions required)
            'minimum_wages' => ['required', 'array', 'min:4', 'max:4'],
            'minimum_wages.*.id' => ['nullable', 'integer', 'exists:insurance_minimum_wage_configs,id'],
            'minimum_wages.*.region' => ['required', 'integer', 'between:1,4', 'distinct'],
            'minimum_wages.*.amount' => ['required', 'numeric', 'min:0'],
            'minimum_wages.*.note' => ['nullable', 'string', 'max:500'],

            // Salary grades (7 grades required)
            'salary_grades' => ['required', 'array', 'min:7', 'max:7'],
            'salary_grades.*.id' => ['nullable', 'integer', 'exists:insurance_salary_grade_configs,id'],
            'salary_grades.*.grade' => ['required', 'integer', 'between:1,7', 'distinct'],
            'salary_grades.*.name' => ['required', 'string', 'max:100'],
            'salary_grades.*.coefficient' => ['required', 'numeric', 'min:0', 'max:10'],
            'salary_grades.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã config set.',
            'code.unique' => 'Mã config set đã tồn tại.',
            'code.max' => 'Mã config set không được vượt quá 50 ký tự.',

            'name.required' => 'Vui lòng nhập tên config set.',
            'name.max' => 'Tên config set không được vượt quá 255 ký tự.',

            'effective_from.required' => 'Vui lòng chọn ngày hiệu lực.',
            'effective_from.date' => 'Ngày hiệu lực không hợp lệ.',

            'effective_to.date' => 'Ngày kết thúc không hợp lệ.',
            'effective_to.after' => 'Ngày kết thúc phải sau ngày hiệu lực.',

            // Minimum wages
            'minimum_wages.required' => 'Vui lòng nhập lương tối thiểu cho 4 vùng.',
            'minimum_wages.min' => 'Phải có đủ 4 vùng lương tối thiểu.',
            'minimum_wages.max' => 'Chỉ được nhập tối đa 4 vùng.',

            'minimum_wages.*.region.required' => 'Vui lòng chọn vùng.',
            'minimum_wages.*.region.between' => 'Vùng phải từ 1 đến 4.',
            'minimum_wages.*.region.distinct' => 'Mỗi vùng chỉ được nhập một lần.',

            'minimum_wages.*.amount.required' => 'Vui lòng nhập mức lương tối thiểu.',
            'minimum_wages.*.amount.numeric' => 'Mức lương phải là số.',
            'minimum_wages.*.amount.min' => 'Mức lương phải lớn hơn 0.',

            'minimum_wages.*.note.max' => 'Ghi chú không được vượt quá 500 ký tự.',

            // Salary grades
            'salary_grades.required' => 'Vui lòng nhập 7 bậc lương.',
            'salary_grades.min' => 'Phải có đủ 7 bậc lương.',
            'salary_grades.max' => 'Chỉ được nhập tối đa 7 bậc.',

            'salary_grades.*.grade.required' => 'Vui lòng chọn bậc.',
            'salary_grades.*.grade.between' => 'Bậc phải từ 1 đến 7.',
            'salary_grades.*.grade.distinct' => 'Mỗi bậc chỉ được nhập một lần.',

            'salary_grades.*.name.required' => 'Vui lòng nhập tên bậc.',
            'salary_grades.*.name.max' => 'Tên bậc không được vượt quá 100 ký tự.',

            'salary_grades.*.coefficient.required' => 'Vui lòng nhập hệ số.',
            'salary_grades.*.coefficient.numeric' => 'Hệ số phải là số.',
            'salary_grades.*.coefficient.min' => 'Hệ số phải lớn hơn 0.',
            'salary_grades.*.coefficient.max' => 'Hệ số không được vượt quá 10.',

            'salary_grades.*.description.max' => 'Mô tả không được vượt quá 500 ký tự.',
        ];
    }

    /**
     * Validate that all 4 regions (1-4) are present
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('minimum_wages')) {
                $regions = collect($this->minimum_wages)->pluck('region')->sort()->values();
                $expected = collect([1, 2, 3, 4]);

                if (!$regions->diff($expected)->isEmpty() || !$expected->diff($regions)->isEmpty()) {
                    $validator->errors()->add(
                        'minimum_wages',
                        'Phải có đủ 4 vùng: Vùng I (1), Vùng II (2), Vùng III (3), Vùng IV (4).'
                    );
                }
            }

            if ($this->has('salary_grades')) {
                $grades = collect($this->salary_grades)->pluck('grade')->sort()->values();
                $expected = collect([1, 2, 3, 4, 5, 6, 7]);

                if (!$grades->diff($expected)->isEmpty() || !$expected->diff($grades)->isEmpty()) {
                    $validator->errors()->add(
                        'salary_grades',
                        'Phải có đủ 7 bậc lương từ Bậc 1 đến Bậc 7.'
                    );
                }
            }
        });
    }
}
