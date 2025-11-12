<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Phụ lục hợp đồng - {{ $appendix->appendix_no ?? '' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.5; }
        h1, h2, h3 { margin: 0 0 10px 0; }
        .text-center { text-align: center; }
        .mt-2 { margin-top: 8px; }
        .mt-4 { margin-top: 16px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #000; padding: 4px 6px; }
        .small { font-size: 11px; }
    </style>
</head>
<body>
    <h2 class="text-center">PHỤ LỤC HỢP ĐỒNG LAO ĐỘNG</h2>
    <p class="text-center mb-4">
        Số: {{ $appendix->appendix_no ?? '......' }}
    </p>

    <p class="small">
        Căn cứ Hợp đồng lao động số {{ $contract->contract_number ?? '......' }} ký ngày
        {{ optional($contract->start_date)->format('d/m/Y') ?? '......' }} giữa
        Công ty và Ông/Bà {{ $employee->full_name ?? '' }} (Mã NV: {{ $employee->employee_code ?? '' }}).
    </p>

    <p class="small">
        Căn cứ nhu cầu và sự thỏa thuận của hai bên, nay lập phụ lục với nội dung như sau:
    </p>

    {{-- PHẦN NỘI DUNG TÙY THEO appendix_type --}}
    @switch($appendix->appendix_type)
        @case('SALARY')
            <h3 class="mt-4 mb-2">Điều chỉnh mức lương</h3>
            <table class="table">
                <tr>
                    <th>Mức lương cơ bản mới</th>
                    <td>{{ number_format($appendix->base_salary, 0, ',', '.') }} VND/tháng</td>
                </tr>
                <tr>
                    <th>Mức lương đóng BH mới</th>
                    <td>{{ number_format($appendix->insurance_salary, 0, ',', '.') }} VND/tháng</td>
                </tr>
                <tr>
                    <th>Phụ cấp vị trí</th>
                    <td>{{ number_format($appendix->position_allowance, 0, ',', '.') }} VND/tháng</td>
                </tr>
            </table>
            @break

        @case('ALLOWANCE')
            <h3 class="mt-4 mb-2">Điều chỉnh phụ cấp</h3>
            @if(!empty($appendix->other_allowances) && is_array($appendix->other_allowances))
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tên phụ cấp</th>
                            <th>Số tiền (VND/tháng)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($appendix->other_allowances as $allowance)
                            <tr>
                                <td>{{ $allowance['name'] ?? '' }}</td>
                                <td>{{ isset($allowance['amount']) ? number_format($allowance['amount'], 0, ',', '.') : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>Phụ lục này điều chỉnh các khoản phụ cấp của người lao động theo mô tả chi tiết kèm theo.</p>
            @endif
            @break

        @case('POSITION')
            <h3 class="mt-4 mb-2">Điều chỉnh chức danh / vị trí công việc</h3>
            <p>
                Chức danh / Vị trí công việc mới của người lao động:<br>
                <strong>{{ optional($appendix->position)->title ?? optional($contract->position)->title ?? '................................' }}</strong>
            </p>
            <p>
                Các quyền lợi, nghĩa vụ và mô tả công việc chi tiết được quy định trong bản mô tả công việc đính kèm.
            </p>
            @break

        @case('DEPARTMENT')
            <h3 class="mt-4 mb-2">Điều chuyển đơn vị công tác</h3>
            <p>
                Đơn vị công tác mới của người lao động:<br>
                <strong>{{ optional($appendix->department)->name ?? optional($contract->department)->name ?? '................................' }}</strong>
            </p>
            <p>
                Các quyền lợi, nghĩa vụ đi kèm với việc điều chuyển được giữ nguyên như Hợp đồng lao động trừ khi có quy định khác trong phụ lục này.
            </p>
            @break

        @case('WORKING_TERMS')
            <h3 class="mt-4 mb-2">Điều chỉnh thời gian / địa điểm làm việc</h3>
            <table class="table">
                <tr>
                    <th>Thời gian làm việc</th>
                    <td>{{ $appendix->working_time ?? $contract->working_time ?? '................................' }}</td>
                </tr>
                <tr>
                    <th>Địa điểm làm việc</th>
                    <td>{{ $appendix->work_location ?? $contract->work_location ?? '................................' }}</td>
                </tr>
            </table>
            @break

        @case('EXTENSION')
            <h3 class="mt-4 mb-2">Gia hạn hợp đồng lao động</h3>
            <p>
                Hai bên thống nhất gia hạn thời hạn Hợp đồng lao động đến ngày:
                <strong>{{ optional($appendix->end_date)->format('d/m/Y') ?? optional($contract->end_date)->format('d/m/Y') ?? '......' }}</strong>.
            </p>
            <p>
                Các điều khoản khác của Hợp đồng lao động không được sửa đổi tại phụ lục này vẫn giữ nguyên hiệu lực.
            </p>
            @break

        @case('OTHER')
        @default
            <h3 class="mt-4 mb-2">Nội dung điều chỉnh khác</h3>
            @if(!empty($appendix->summary))
                <p><strong>Tóm tắt:</strong> {{ $appendix->summary }}</p>
            @endif
            @if(!empty($appendix->note))
                <p><strong>Ghi chú:</strong> {{ $appendix->note }}</p>
            @endif
            <p>
                Chi tiết nội dung điều chỉnh được ghi nhận theo biên bản / tài liệu kèm theo (nếu có).
            </p>
    @endswitch

    {{-- THỜI HẠN HIỆU LỰC --}}
    <p class="mt-4 small">
        Phụ lục này có hiệu lực từ ngày
        <strong>{{ optional($appendix->effective_date)->format('d/m/Y') ?? '......' }}</strong>
        @if($appendix->end_date)
            đến ngày <strong>{{ optional($appendix->end_date)->format('d/m/Y') }}</strong>
        @endif
        và là một bộ phận không tách rời của Hợp đồng lao động số
        {{ $contract->contract_number ?? '......' }}.
    </p>

    @if(!empty($appendix->note))
        <p class="small"><strong>Ghi chú thêm:</strong> {{ $appendix->note }}</p>
    @endif

    <div class="mt-4">
        <table style="width:100%; border:0;">
            <tr>
                <td style="width:50%; text-align:center;">
                    Đại diện Người sử dụng lao động<br/>
                    <br/><br/><br/>
                    (Ký, ghi rõ họ tên)
                </td>
                <td style="width:50%; text-align:center;">
                    Người lao động<br/>
                    <br/><br/><br/>
                    (Ký, ghi rõ họ tên)
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
