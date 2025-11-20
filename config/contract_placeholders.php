<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contract Placeholder Presets
    |--------------------------------------------------------------------------
    |
    | Định nghĩa mapping mặc định cho các placeholders phổ biến.
    | Khi user upload DOCX template, hệ thống sẽ tự động tạo mappings
    | dựa theo presets này nếu placeholder key match.
    |
    | Format: 'placeholder_key' => [data_source, source_path, transformer, default_value]
    |
    */

    'presets' => [
        // ========== EMPLOYEE INFO ==========
        'employee_full_name' => ['CONTRACT', 'employee.full_name', null, 'N/A'],
        'employee_code' => ['CONTRACT', 'employee.employee_code', null, 'N/A'],
        'employee_phone' => ['CONTRACT', 'employee.phone', null, ''],
        'employee_emergency_phone' => ['CONTRACT', 'employee.emergency_contact_phone', null, ''],
        'employee_personal_email' => ['CONTRACT', 'employee.personal_email', null, ''],
        'employee_company_email' => ['CONTRACT', 'employee.company_email', null, ''],

        // Employee identification
        'employee_cccd' => ['CONTRACT', 'employee.cccd', null, ''],
        'employee_id_number' => ['CONTRACT', 'employee.cccd', null, ''],
        'employee_cccd_issued_date' => ['CONTRACT', 'employee.cccd_issued_on', 'date_vn', ''],
        'employee_cccd_issued_by' => ['CONTRACT', 'employee.cccd_issued_by', null, ''],
        'employee_si_number' => ['CONTRACT', 'employee.si_number', null, ''],

        // Employee personal info
        'employee_birthday' => ['CONTRACT', 'employee.dob', 'date_vn', ''],
        'employee_dob' => ['CONTRACT', 'employee.dob', 'date_vn', ''],
        'employee_gender' => ['CONTRACT', 'employee.gender', 'gender_vn', ''],
        'employee_marital_status' => ['CONTRACT', 'employee.marital_status', 'marital_status_vn', ''],

        // Employee addresses (full with ward, district, province)
        'employee_address' => ['COMPUTED', 'employee_full_address', null, ''],
        'employee_full_address' => ['COMPUTED', 'employee_full_address', null, ''],
        'employee_temp_address' => ['COMPUTED', 'employee_temp_full_address', null, ''],
        'employee_temp_full_address' => ['COMPUTED', 'employee_temp_full_address', null, ''],

        // Street only (no ward/province)
        'employee_address_street' => ['CONTRACT', 'employee.address_street', null, ''],
        'employee_temp_address_street' => ['CONTRACT', 'employee.temp_address_street', null, ''],

        // Employee work info
        'employee_hire_date' => ['CONTRACT', 'employee.hire_date', 'date_vn', ''],
        'employee_status' => ['CONTRACT', 'employee.status', null, ''],

        // ========== DEPARTMENT INFO ==========
        'department_name' => ['CONTRACT', 'department.name', null, 'N/A'],
        'department_code' => ['CONTRACT', 'department.code', null, ''],
        'department_type' => ['CONTRACT', 'department.type', null, ''],
        'department_is_active' => ['CONTRACT', 'department.is_active', null, ''],

        // ========== POSITION INFO ==========
        'position_title' => ['CONTRACT', 'position.title', null, 'N/A'],
        'position_level' => ['CONTRACT', 'position.level', null, ''],
        'position_insurance_base_salary' => ['CONTRACT', 'position.insurance_base_salary', 'number_format', '0'],
        'position_salary' => ['CONTRACT', 'position.position_salary', 'number_format', '0'],
        'position_competency_salary' => ['CONTRACT', 'position.competency_salary', 'number_format', '0'],
        'position_allowance' => ['CONTRACT', 'position.allowance', 'number_format', '0'],

        // ========== CONTRACT INFO ==========
        'contract_number' => ['CONTRACT', 'contract_number', null, ''],
        'contract_type' => ['CONTRACT', 'contract_type', 'contract_type_vn', ''],
        'contract_status' => ['CONTRACT', 'status', null, ''],
        'contract_source' => ['CONTRACT', 'source', null, ''],

        // Contract dates
        'contract_sign_date' => ['CONTRACT', 'sign_date', 'date_vn', ''],
        'sign_date' => ['CONTRACT', 'sign_date', 'date_vn', ''],
        'contract_start_date' => ['CONTRACT', 'start_date', 'date_vn', ''],
        'contract_end_date' => ['CONTRACT', 'end_date', 'date_vn', ''],
        'probation_end_date' => ['CONTRACT', 'probation_end_date', 'date_vn', ''],
        'contract_terminated_at' => ['CONTRACT', 'terminated_at', 'date_vn', ''],
        'contract_approved_at' => ['CONTRACT', 'approved_at', 'datetime_vn', ''],

        // Contract termination
        'contract_termination_reason' => ['CONTRACT', 'termination_reason', null, ''],

        // ========== SALARY & ALLOWANCES ==========
        'base_salary' => ['CONTRACT', 'base_salary', 'number_format', '0'],
        'insurance_salary' => ['CONTRACT', 'insurance_salary', 'number_format', '0'],
        'insurance_salary_words' => ['CONTRACT', 'insurance_salary', 'currency_to_words', 'Không đồng'],
        'position_allowance' => ['CONTRACT', 'position_allowance', 'number_format', '0'],
        'total_salary' => ['COMPUTED', 'total_salary', 'number_format', '0'],
        'total_salary_words' => ['COMPUTED', 'total_salary', 'currency_to_words', 'Không đồng'],

        // ========== INSURANCE ==========
        'social_insurance' => ['CONTRACT', 'social_insurance', null, 'Có'],
        'health_insurance' => ['CONTRACT', 'health_insurance', null, 'Có'],
        'unemployment_insurance' => ['CONTRACT', 'unemployment_insurance', null, 'Có'],

        // ========== WORKING CONDITIONS ==========
        'working_time' => ['CONTRACT', 'working_time', null, 'Toàn thời gian'],
        'work_location' => ['CONTRACT', 'work_location', null, ''],
        'contract_note' => ['CONTRACT', 'note', null, ''],
        'approval_note' => ['CONTRACT', 'approval_note', null, ''],

        // ========== COMPUTED FIELDS ==========
        'contract_duration_months' => ['COMPUTED', 'contract_duration_months', null, ''],
        'probation_duration_days' => ['COMPUTED', 'probation_duration_days', null, ''],
        'employee_full_address' => ['COMPUTED', 'employee_full_address', null, ''],
        'employee_temp_full_address' => ['COMPUTED', 'employee_temp_full_address', null, ''],

        // ========== SYSTEM VALUES ==========
        'today' => ['SYSTEM', 'today', null, ''],
        'now' => ['SYSTEM', 'now', null, ''],
        'current_year' => ['SYSTEM', 'current_year', null, ''],
        'company_name' => ['SYSTEM', 'company_name', null, 'Công ty'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Available Transformers
    |--------------------------------------------------------------------------
    |
    | Danh sách transformers có sẵn để format giá trị
    |
    */

    'transformers' => [
        'number_format' => 'Format số với dấu phẩy ngăn cách hàng nghìn',
        'currency_to_words' => 'Chuyển số tiền thành chữ (VD: 6500000 → Sáu triệu năm trăm nghìn đồng)',
        'date_vn' => 'Format ngày theo dd/mm/yyyy',
        'datetime_vn' => 'Format ngày giờ theo dd/mm/yyyy HH:ii',
        'gender_vn' => 'Chuyển giới tính sang tiếng Việt (male → Nam, female → Nữ)',
        'marital_status_vn' => 'Chuyển tình trạng hôn nhân sang tiếng Việt',
        'contract_type_vn' => 'Chuyển loại hợp đồng sang tiếng Việt',
        'uppercase' => 'Chuyển chữ HOA',
        'lowercase' => 'Chuyển chữ thường',
        'ucfirst' => 'Viết hoa chữ cái đầu',
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Sources
    |--------------------------------------------------------------------------
    |
    | Các loại data source hỗ trợ
    |
    */

    'data_sources' => [
        'CONTRACT' => 'Lấy từ Contract model',
        'COMPUTED' => 'Tính toán động',
        'MANUAL' => 'Người dùng nhập thủ công',
        'SYSTEM' => 'Giá trị hệ thống',
    ],
];
