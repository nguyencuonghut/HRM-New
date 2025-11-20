<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contract Appendix Placeholder Presets
    |--------------------------------------------------------------------------
    |
    | Định nghĩa mapping mặc định cho các placeholders của phụ lục hợp đồng.
    | Các placeholders này thường liên quan đến thay đổi lương, chức danh,
    | phòng ban, điều kiện làm việc, v.v.
    |
    | Format: 'placeholder_key' => [data_source, source_path, transformer, default_value]
    |
    */

    'presets' => [
        // ========== SHARED EMPLOYEE INFO (Giống contract template) ==========
        'employee_full_name' => ['CONTRACT', 'contractAppendix.contract.employee.full_name', null, 'N/A'],
        'employee_code' => ['CONTRACT', 'contractAppendix.contract.employee.employee_code', null, 'N/A'],
        'employee_phone' => ['CONTRACT', 'contractAppendix.contract.employee.phone', null, ''],
        'employee_personal_email' => ['CONTRACT', 'contractAppendix.contract.employee.personal_email', null, ''],
        'employee_cccd' => ['CONTRACT', 'contractAppendix.contract.employee.cccd', null, ''],
        'employee_cccd_issued_date' => ['CONTRACT', 'contractAppendix.contract.employee.cccd_issued_on', 'date_vn', ''],
        'employee_cccd_issued_by' => ['CONTRACT', 'contractAppendix.contract.employee.cccd_issued_by', null, ''],
        'employee_dob' => ['CONTRACT', 'contractAppendix.contract.employee.dob', 'date_vn', ''],
        'employee_birthday' => ['CONTRACT', 'contractAppendix.contract.employee.dob', 'date_vn', ''],
        'employee_address' => ['COMPUTED', 'employee_full_address', null, ''],
        'employee_temp_address' => ['COMPUTED', 'employee_temp_full_address', null, ''],
        'employee_position' => ['CONTRACT', 'contractAppendix.contract.position.title', null, ''],
        'employee_department' => ['CONTRACT', 'contractAppendix.contract.department.name', null, ''],

        // ========== CONTRACT INFO ==========
        'contract_number' => ['CONTRACT', 'contractAppendix.contract.contract_number', null, ''],
        'contract_type' => ['CONTRACT', 'contractAppendix.contract.type', 'contract_type_vn', ''],
        'contract_start_date' => ['CONTRACT', 'contractAppendix.contract.start_date', 'date_vn', ''],
        // Note: Many contracts don't have sign_date, fallback to start_date
        'contract_sign_date' => ['CONTRACT', 'contractAppendix.contract.start_date', 'date_vn', ''],
        'contract_signed_date' => ['CONTRACT', 'contractAppendix.contract.start_date', 'date_vn', ''],

        // ========== APPENDIX INFO ==========
        'appendix_number' => ['CONTRACT', 'contractAppendix.appendix_number', null, ''],
        'appendix_type' => ['CONTRACT', 'contractAppendix.appendix_type', null, ''],
        'appendix_title' => ['CONTRACT', 'contractAppendix.title', null, ''],
        'effective_date' => ['CONTRACT', 'contractAppendix.effective_date', 'date_vn', ''],
        'signed_date' => ['CONTRACT', 'contractAppendix.signed_date', 'date_vn', ''],
        'notes' => ['CONTRACT', 'contractAppendix.notes', null, ''],

        // ========== SALARY CHANGES (Điều chỉnh lương) ==========
        'old_base_salary' => ['CONTRACT', 'contractAppendix.old_terms.base_salary', 'number_format', ''],
        'old_base_salary_words' => ['CONTRACT', 'contractAppendix.old_terms.base_salary', 'currency_to_words', ''],
        'new_base_salary' => ['CONTRACT', 'contractAppendix.new_terms.base_salary', 'number_format', ''],
        'new_base_salary_words' => ['CONTRACT', 'contractAppendix.new_terms.base_salary', 'currency_to_words', ''],

        'old_insurance_salary' => ['CONTRACT', 'contractAppendix.old_terms.insurance_salary', 'number_format', ''],
        'old_insurance_salary_words' => ['CONTRACT', 'contractAppendix.old_terms.insurance_salary', 'currency_to_words', ''],
        'new_insurance_salary' => ['CONTRACT', 'contractAppendix.new_terms.insurance_salary', 'number_format', ''],
        'new_insurance_salary_words' => ['CONTRACT', 'contractAppendix.new_terms.insurance_salary', 'currency_to_words', ''],

        'old_position_allowance' => ['CONTRACT', 'contractAppendix.old_terms.position_allowance', 'number_format', ''],
        'new_position_allowance' => ['CONTRACT', 'contractAppendix.new_terms.position_allowance', 'number_format', ''],

        'old_total_salary' => ['COMPUTED', 'old_total_salary', 'number_format', ''],
        'old_total_salary_words' => ['COMPUTED', 'old_total_salary', 'currency_to_words', ''],
        'new_total_salary' => ['COMPUTED', 'new_total_salary', 'number_format', ''],
        'new_total_salary_words' => ['COMPUTED', 'new_total_salary', 'currency_to_words', ''],

        'salary_increase_amount' => ['COMPUTED', 'salary_increase_amount', 'number_format', ''],
        'salary_increase_percent' => ['COMPUTED', 'salary_increase_percent', 'number_format', ''],

        // ========== ALLOWANCE CHANGES (Điều chỉnh phụ cấp) ==========
        'allowance_name' => ['CONTRACT', 'contractAppendix.change_details.allowance_name', null, ''],
        'old_allowance_amount' => ['CONTRACT', 'contractAppendix.old_terms.allowance_amount', 'number_format', ''],
        'old_allowance_amount_words' => ['CONTRACT', 'contractAppendix.old_terms.allowance_amount', 'currency_to_words', ''],
        'new_allowance_amount' => ['CONTRACT', 'contractAppendix.new_terms.allowance_amount', 'number_format', ''],
        'new_allowance_amount_words' => ['CONTRACT', 'contractAppendix.new_terms.allowance_amount', 'currency_to_words', ''],

        // ========== POSITION CHANGES (Điều chỉnh chức danh) ==========
        'old_position' => ['CONTRACT', 'contractAppendix.old_terms.position_title', null, ''],
        'new_position' => ['CONTRACT', 'contractAppendix.new_terms.position_title', null, ''],
        'position_change_reason' => ['CONTRACT', 'contractAppendix.change_details.reason', null, ''],

        // ========== DEPARTMENT CHANGES (Điều chuyển đơn vị) ==========
        'old_department' => ['CONTRACT', 'contractAppendix.old_terms.department_name', null, ''],
        'new_department' => ['CONTRACT', 'contractAppendix.new_terms.department_name', null, ''],
        'department_change_reason' => ['CONTRACT', 'contractAppendix.change_details.reason', null, ''],

        // ========== WORKING TERMS CHANGES (Điều chỉnh điều kiện làm việc) ==========
        'old_working_time' => ['CONTRACT', 'contractAppendix.old_terms.working_time', null, ''],
        'new_working_time' => ['CONTRACT', 'contractAppendix.new_terms.working_time', null, ''],

        'old_work_location' => ['CONTRACT', 'contractAppendix.old_terms.work_location', null, ''],
        'new_work_location' => ['CONTRACT', 'contractAppendix.new_terms.work_location', null, ''],

        // ========== EXTENSION (Gia hạn hợp đồng) ==========
        'old_end_date' => ['CONTRACT', 'contractAppendix.old_terms.end_date', 'date_vn', ''],
        'new_end_date' => ['CONTRACT', 'contractAppendix.new_terms.end_date', 'date_vn', ''],
        'extension_duration_months' => ['COMPUTED', 'extension_duration_months', null, ''],
        'extension_reason' => ['CONTRACT', 'contractAppendix.change_details.reason', null, ''],

        // ========== SYSTEM INFO ==========
        'current_date' => ['SYSTEM', 'current_date', 'date_vn', ''],
        'current_datetime' => ['SYSTEM', 'current_datetime', 'datetime_vn', ''],
        'current_year' => ['SYSTEM', 'current_year', null, ''],

        // Company info (có thể thêm nếu cần)
        'company_name' => ['SYSTEM', 'company_name', null, 'CÔNG TY CỔ PHẦN HỒNG HÀ'],
        'company_address' => ['SYSTEM', 'company_address', null, ''],
        'company_tax_code' => ['SYSTEM', 'company_tax_code', null, ''],
        'company_representative' => ['SYSTEM', 'company_representative', null, ''],
        'company_representative_title' => ['SYSTEM', 'company_representative_title', null, 'GIÁM ĐỐC'],
    ],
];
