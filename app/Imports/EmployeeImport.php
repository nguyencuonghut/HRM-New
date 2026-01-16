<?php

namespace App\Imports;

use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Models\Ward;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeeImport implements ToModel, WithHeadingRow
{
    public int $successCount = 0;
    public int $errorCount = 0;
    public array $errors = [];

    public function model(array $row)
    {
        // ✅ Skip empty rows (Excel hay có hàng nghìn row trống do used range)
        if ($this->isEmptyRow($row)) {
            return null;
        }

        try {
            // Nhận key theo WithHeadingRow: header có thể khác nhau (có dấu / không dấu / có _)
            $employeeCode = $this->get($row, ['ma_nhan_vien', 'mã_nhân_viên', 'mã nhân viên']);
            $fullName     = $this->get($row, ['ho_va_ten', 'họ_và_tên', 'họ và tên']);

            if (!$employeeCode || !$fullName) {
                throw new \Exception("Thiếu Mã nhân viên hoặc Họ và tên");
            }

            // ✅ Parse địa chỉ thường trú / tạm trú
            $permanentText = $this->get($row, ['dia_chi_thuong_tru', 'địa_chỉ_thường_trú', 'địa chỉ thường trú']);
            $tempText      = $this->get($row, ['dia_chi_tam_tru', 'địa_chỉ_tạm_trú', 'địa chỉ tạm trú']);

            $permanent = $this->parseVietnamAddress($permanentText);
            $temp      = $this->parseVietnamAddress($tempText);

            $wardId     = $this->resolveWardId($permanent['ward'] ?? null, $permanent['province'] ?? null);
            $tempWardId = $this->resolveWardId($temp['ward'] ?? null, $temp['province'] ?? null);

            $rowData = [
                'employee_code' => $employeeCode,
                'full_name'     => $fullName,

                'gender'        => $this->mapGender($this->get($row, ['gioi_tinh', 'giới_tính', 'giới tính'])),
                'dob'           => $this->parseDate($this->get($row, ['ngay_sinh', 'ngày_sinh', 'ngày sinh'])),
                'marital_status'=> $this->mapMarital($this->get($row, ['tinh_trang_hon_nhan', 'tình_trạng_hôn_nhân', 'tình trạng hôn nhân'])),

                'phone'         => $this->get($row, ['so_dien_thoai', 'số_điện_thoại', 'số điện thoại']),
                'company_email' => $this->get($row, ['email_cong_ty', 'email_công_ty', 'email công ty']),
                'personal_email'=> $this->get($row, ['email_ca_nhan', 'email_cá_nhân', 'email cá nhân']),

                'hire_date'     => $this->parseDate($this->get($row, ['ngay_vao_lam', 'ngày_vao_làm', 'ngày vào làm'])),

                'si_number'     => $this->get($row, ['ma_so_bhxh', 'mã_số_bhxh', 'mã số bhxh']),
                'cccd'          => $this->get($row, ['cccd']),
                'cccd_issued_on'=> $this->parseDate($this->get($row, ['ngay_cap_cccd', 'ngày_cấp_cccd', 'ngày cấp cccd'])),
                'cccd_issued_by'=> $this->get($row, ['noi_cap_cccd', 'nơi_cấp_cccd', 'nơi cấp cccd']),

                'emergency_contact_phone' => $this->get($row, ['so_dien_thoai_khan_cap', 'số_điện_thoại_khẩn_cấp', 'số điện thoại khẩn cấp']),

                // ✅ thường trú
                'ward_id'            => $wardId,
                'address_street'     => $permanent['street'] ?? null,

                // ✅ tạm trú
                'temp_ward_id'       => $tempWardId,
                'temp_address_street'=> $temp['street'] ?? null,
            ];

            // ✅ ĐÚNG chữ ký updateOrCreate: match theo employee_code
            $employee = Employee::updateOrCreate(
                ['employee_code' => $employeeCode],
                $rowData
            );

            $this->successCount++;
            return $employee;

        } catch (\Throwable $e) {
            $this->errorCount++;
            $this->errors[] = $e->getMessage();

            \Log::error("Import dòng bị lỗi: ".$e->getMessage(), [
                'employee_code' => $employeeCode ?? null,
                'full_name' => $fullName ?? null,
            ]);

            return null;
        }
    }

    private function get(array $row, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                return is_string($row[$k]) ? trim($row[$k]) : (string) $row[$k];
            }
        }
        return null;
    }

    private function mapGender(?string $text): ?string
    {
        if (!$text) return null;
        $t = Str::lower(trim($text));

        return match (true) {
            in_array($t, ['nam', 'male', 'm']) => 'MALE',
            in_array($t, ['nữ', 'nu', 'female', 'f']) => 'FEMALE',
            in_array($t, ['khác', 'khac', 'other', 'o']) => 'OTHER',
            default => null,
        };
    }

    private function mapMarital(?string $text): ?string
    {
        if (!$text) return null;
        $t = Str::lower(trim($text));

        return match (true) {
            in_array($t, ['độc thân', 'doc than', 'single']) => 'SINGLE',
            in_array($t, ['kết hôn', 'ket hon', 'married']) => 'MARRIED',
            in_array($t, ['đã ly hôn', 'da ly hon', 'divorced']) => 'DIVORCED',
            in_array($t, ['goá', 'goa', 'widowed']) => 'WIDOWED',
            default => null,
        };
    }

    private function parseDate($value): ?string
    {
        if (!$value) return null;

        try {
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))
                    ->toDateString();
            }
            // Ưu tiên parse theo định dạng ngày/tháng/năm (d/m/Y)
            // Nếu không đúng format, fallback về Carbon::parse
            $date = Carbon::createFromFormat('d/m/Y', trim($value));
            if ($date !== false) {
                return $date->toDateString();
            }
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Parse chuỗi địa chỉ dạng:
     * "số nhà/đường, xã/phường, tỉnh/thành phố"
     *
     * Trả về:
     * [
     *   'street' => '...',
     *   'ward' => '...',
     *   'province' => '...'
     * ]
     */
    private function parseVietnamAddress(?string $text): array
    {
        if (!$text) return [];

        // Chuẩn hoá dấu phẩy, khoảng trắng
        $normalized = preg_replace('/\s+/', ' ', trim($text));
        $parts = array_values(array_filter(array_map('trim', explode(',', $normalized)), fn($p) => $p !== ''));

        if (count($parts) === 0) return [];

        // Nếu đúng format 3 phần:
        // street, ward, province
        if (count($parts) >= 3) {
            $province = $parts[count($parts) - 1];
            $ward = $parts[count($parts) - 2];

            // street có thể chứa thêm dấu phẩy (vd: "12/3, Đường ABC")
            $streetParts = array_slice($parts, 0, count($parts) - 2);
            $street = trim(implode(', ', $streetParts));

            return [
                'street' => $street ?: null,
                'ward' => $ward ?: null,
                'province' => $province ?: null,
            ];
        }

        // Nếu chỉ 2 phần: street, ward (thiếu province)
        if (count($parts) === 2) {
            return [
                'street' => $parts[0] ?: null,
                'ward' => $parts[1] ?: null,
                'province' => null,
            ];
        }

        // Nếu chỉ 1 phần: coi như street
        return [
            'street' => $parts[0] ?: null,
            'ward' => null,
            'province' => null,
        ];
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value === null) continue;
            if (is_string($value) && trim($value) === '') continue;
            // Có giá trị khác null/'' => không trống
            return false;
        }
        return true;
    }

    private function resolveWardId(?string $wardText, ?string $provinceText): ?string
    {
        $wardRaw = $wardText ? trim($wardText) : null;
        $wardCore = $this->normalizeWardName($wardText); // bỏ "Xã/Phường/Thị trấn"
        if (!$wardCore) return null;

        $provinceRaw = $provinceText ? trim($provinceText) : null;
        $provinceCore = $this->normalizeProvinceName($provinceText); // bỏ "Tỉnh/Thành phố/TP"

        // Ward trong DB có tiền tố "Xã/Phường" => tạo biến thể để match
        $wardVariants = array_values(array_unique(array_filter([
            $wardRaw,
            $wardCore,
            "Xã {$wardCore}",
            "Phường {$wardCore}",
            "Thị trấn {$wardCore}",
        ], fn($v) => is_string($v) && trim($v) !== '')));

        // Province trong DB có tiền tố "Tỉnh/Thành phố" => tạo biến thể để match
        $provinceVariants = array_values(array_unique(array_filter([
            $provinceRaw,
            $provinceCore,
            $provinceCore ? "Tỉnh {$provinceCore}" : null,
            $provinceCore ? "Thành phố {$provinceCore}" : null,
            $provinceCore ? "TP {$provinceCore}" : null,
            $provinceCore ? "Tp {$provinceCore}" : null,
        ], fn($v) => is_string($v) && trim($v) !== '')));

        $q = \App\Models\Ward::query();

        // Match ward name theo biến thể
        $q->where(function ($sub) use ($wardVariants) {
            foreach ($wardVariants as $v) {
                $sub->orWhere('name', 'like', '%' . $v . '%');
            }
        });

        // ✅ Sửa tại đây: Ward belongsTo Province trực tiếp, không có district()
        if (!empty($provinceVariants)) {
            $q->whereHas('province', function ($p) use ($provinceVariants) {
                $p->where(function ($sub) use ($provinceVariants) {
                    foreach ($provinceVariants as $pv) {
                        $sub->orWhere('name', 'like', '%' . $pv . '%');
                    }
                });
            });
        }

        // Ưu tiên match tốt hơn
        $ward = $q->orderByRaw('LENGTH(name) ASC')->first();

        return $ward?->id;
    }


    private function normalizeWardName(?string $text): ?string
    {
        if (!$text) return null;
        $t = trim($text);

        // Bỏ tiền tố phổ biến (giữ phần "core")
        $t = preg_replace('/^(xã|xa|phường|phuong|thị trấn|thi tran)\s+/iu', '', $t);
        $t = preg_replace('/\s+/', ' ', $t);

        return $t !== '' ? $t : null;
    }

    private function normalizeProvinceName(?string $text): ?string
    {
        if (!$text) return null;
        $t = trim($text);

        // Bỏ tiền tố phổ biến (giữ phần "core")
        $t = preg_replace('/^(tỉnh|tinh|thành phố|thanh pho|tp\.?|t\.p\.?)\s+/iu', '', $t);
        $t = preg_replace('/\s+/', ' ', $t);

        return $t !== '' ? $t : null;
    }

}
