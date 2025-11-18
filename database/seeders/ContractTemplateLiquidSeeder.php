<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ContractTemplateLiquidSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $commonPlaceholders = [
            'employee.full_name',
            'employee.employee_code',
            'department.name',
            'position.title',
            'contract.contract_number',
            'contract.start_date',
            'contract.end_date',
            'terms.base_salary',
            'terms.insurance_salary',
            'terms.position_allowance',
            'terms.working_time',
            'terms.work_location',
        ];

        $probationContent = <<<'LIQ'
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 12px; }
        .mt-2 { margin-top: 8px; } .mt-4 { margin-top: 16px; }
        .section-title { font-weight: bold; margin-top: 12px; }
    </style>
</head>
<body>
    <h1>HỢP ĐỒNG THỬ VIỆC</h1>

    <p><strong>Số HĐ:</strong> {{ contract.contract_number }}</p>
    <p><strong>Nhân viên:</strong> {{ employee.full_name }} (Mã: {{ employee.employee_code }})</p>
    <p><strong>Đơn vị:</strong> {{ department.name }} — <strong>Chức danh:</strong> {{ position.title }}</p>

    <div class="section-title">Điều 1. Thời hạn & Công việc</div>
    <p>Hợp đồng có hiệu lực từ {{ contract.start_date | date_vn }} đến {{ contract.end_date | date_vn }}.</p>
    <p>Thời gian làm việc: {{ terms.working_time }}. Địa điểm: {{ terms.work_location }}.</p>

    <div class="section-title">Điều 2. Lương & Phụ cấp</div>
    <p>Lương cơ bản: {{ terms.base_salary | number }} VND/tháng.</p>
    <p>Lương đóng bảo hiểm: {{ terms.insurance_salary | number }} VND/tháng.</p>
    <p>Phụ cấp vị trí: {{ terms.position_allowance | number }} VND/tháng.</p>
    {% if terms.other_allowances %}
    <p>Phụ cấp khác:</p>
    <ul>
        {% for al in terms.other_allowances %}
        <li>{{ al.name }}: {{ al.amount | number }} VND/tháng</li>
        {% endfor %}
    </ul>
    {% endif %}

    <div class="section-title">Điều 3. Quyền lợi & Nghĩa vụ</div>
    <p>Áp dụng theo Nội quy lao động, Quy chế lương thưởng và các chính sách hiện hành của Công ty.</p>

    <div class="mt-4">
        <p>ĐẠI DIỆN CÔNG TY _______________________    NGƯỜI LAO ĐỘNG _______________________</p>
        <p class="mt-2">Ngày ký: __________</p>
    </div>
</body>
</html>
LIQ;

        $fixedTermContent = <<<'LIQ'
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
        h1 { font-size: 18px; text-align: center; margin-bottom: 12px; }
        .mt-2 { margin-top: 8px; } .mt-4 { margin-top: 16px; }
        .section-title { font-weight: bold; margin-top: 12px; }
    </style>
</head>
<body>
    <h1>HỢP ĐỒNG THỬ VIỆC</h1>

    <p><strong>Số HĐ:</strong> {{ contract.contract_number }}</p>
    <p><strong>Nhân viên:</strong> {{ employee.full_name }} (Mã: {{ employee.employee_code }})</p>
    <p><strong>Đơn vị:</strong> {{ department.name }} — <strong>Chức danh:</strong> {{ position.title }}</p>

    <div class="section-title">Điều 1. Thời hạn & Công việc</div>
    <p>Hợp đồng có hiệu lực từ {{ contract.start_date | date_vn }} đến {{ contract.end_date | date_vn }}.</p>
    <p>Thời gian làm việc: {{ terms.working_time }}. Địa điểm: {{ terms.work_location }}.</p>

    <div class="section-title">Điều 2. Lương & Phụ cấp</div>
    <p>Lương cơ bản: {{ terms.base_salary | number }} VND/tháng.</p>
    <p>Lương đóng bảo hiểm: {{ terms.insurance_salary | number }} VND/tháng.</p>
    <p>Phụ cấp vị trí: {{ terms.position_allowance | number }} VND/tháng.</p>
    {% if terms.other_allowances %}
    <p>Phụ cấp khác:</p>
    <ul>
        {% for al in terms.other_allowances %}
        <li>{{ al.name }}: {{ al.amount | number }} VND/tháng</li>
        {% endfor %}
    </ul>
    {% endif %}

    <div class="section-title">Điều 3. Quyền lợi & Nghĩa vụ</div>
    <p>Áp dụng theo Nội quy lao động, Quy chế lương thưởng và các chính sách hiện hành của Công ty.</p>

    <div class="mt-4">
        <p>ĐẠI DIỆN CÔNG TY _______________________    NGƯỜI LAO ĐỘNG _______________________</p>
        <p class="mt-2">Ngày ký: __________</p>
    </div>
</body>
</html>
LIQ;

        $rows = [
            [
                'id'                => Str::uuid(),
                'name'              => 'Mẫu Hợp đồng Thử việc (Liquid)',
                'type'              => 'PROBATION',
                'engine'            => 'LIQUID',
                'body_path'         => null, // dùng content thay vì file blade
                'content'           => $probationContent,
                'placeholders_json' => json_encode($commonPlaceholders, JSON_UNESCAPED_UNICODE),
                'is_default'        => true,
                'is_active'         => true,
                'version'           => 1,
                'created_at'        => $now,
                'updated_at'        => $now,
                'updated_by'        => null,
            ],
            [
                'id'                => Str::uuid(),
                'name'              => 'Mẫu HĐ Xác định thời hạn (Liquid)',
                'type'              => 'FIXED_TERM',
                'engine'            => 'LIQUID',
                'body_path'         => null,
                'content'           => $fixedTermContent,
                'placeholders_json' => json_encode($commonPlaceholders, JSON_UNESCAPED_UNICODE),
                'is_default'        => true,
                'is_active'         => true,
                'version'           => 1,
                'created_at'        => $now,
                'updated_at'        => $now,
                'updated_by'        => null,
            ],
        ];

        DB::table('contract_templates')->upsert(
            $rows,
            ['id'],
            [
                'name',
                'type',
                'engine',
                'body_path',
                'content',
                'placeholders_json',
                'is_default',
                'is_active',
                'version',
                'updated_at',
                'updated_by',
            ]
        );
    }
}
