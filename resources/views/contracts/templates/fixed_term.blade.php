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

    <p><strong>Số HĐ:</strong> {{ $contract->contract_number ?? '---' }}</p>
    <p><strong>Nhân viên:</strong> {{ $employee->full_name }} (Mã: {{ $employee->employee_code }})</p>
    <p><strong>Đơn vị:</strong> {{ $department->name ?? '-' }} — <strong>Chức danh:</strong> {{ $position->title ?? '-' }}</p>

    <div class="section-title">Điều 1. Thời hạn & Công việc</div>
    <p>Hợp đồng có hiệu lực từ {{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}
       đến {{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('d/m/Y') : 'khi có thông báo khác' }}.</p>
    <p>Thời gian làm việc: {{ $terms['working_time'] ?? 'Theo quy định công ty' }}. Địa điểm: {{ $terms['work_location'] ?? 'Văn phòng công ty' }}.</p>

    <div class="section-title">Điều 2. Lương & Phụ cấp</div>
    <p>Lương cơ bản: {{ number_format($terms['base_salary'] ?? 0) }} VND/tháng.</p>
    <p>Lương đóng bảo hiểm: {{ number_format($terms['insurance_salary'] ?? 0) }} VND/tháng.</p>
    <p>Phụ cấp vị trí: {{ number_format($terms['position_allowance'] ?? 0) }} VND/tháng.</p>
    @if(!empty($terms['other_allowances']))
        <p>Phụ cấp khác:</p>
        <ul>
            @foreach($terms['other_allowances'] as $al)
                <li>{{ $al['name'] }}: {{ number_format($al['amount']) }} VND/tháng</li>
            @endforeach
        </ul>
    @endif

    <div class="section-title">Điều 3. Quyền lợi & Nghĩa vụ</div>
    <p>Áp dụng theo Nội quy lao động, Quy chế lương thưởng và các chính sách hiện hành của Công ty.</p>

    <div class="mt-4">
        <p>ĐẠI DIỆN CÔNG TY _______________________    NGƯỜI LAO ĐỘNG _______________________</p>
        <p class="mt-2">Ngày ký: __________</p>
    </div>
</body>
</html>
