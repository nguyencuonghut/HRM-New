# Hệ Thống Lương BHXH - Quick Reference

## Công thức cơ bản

```
Lương BHXH = Lương tối thiểu vùng × Hệ số bậc
```

## Quy tắc tăng bậc

- 7 bậc: 1 → 2 → 3 → 4 → 5 → 6 → 7
- Cứ **3 năm thâm niên** ở cùng vị trí → tăng 1 bậc
- Phải có **Phụ lục SALARY** làm căn cứ pháp lý (không tự động)

## Cài đặt

```bash
# 1. Chạy migration
php artisan migrate

# 2. Seed dữ liệu mẫu (lương tối thiểu vùng + thang hệ số)
php artisan db:seed --class=InsuranceSalarySystemSeeder
```

## Files quan trọng

### Database
- `database/migrations/2025_12_25_000001_create_minimum_wages_table.php`
- `database/migrations/2025_12_25_000002_create_position_salary_grades_table.php`
- `database/migrations/2025_12_25_000003_create_employee_insurance_profiles_table.php`
- `database/seeders/InsuranceSalarySystemSeeder.php`

### Models
- `app/Models/MinimumWage.php` - Lương tối thiểu vùng
- `app/Models/PositionSalaryGrade.php` - Thang hệ số 7 bậc
- `app/Models/EmployeeInsuranceProfile.php` - Hồ sơ BHXH nhân viên

### Services
- `app/Services/InsuranceSalaryService.php` - Logic nghiệp vụ

### Documentation
- `INSURANCE_SALARY_SYSTEM_GUIDE.md` - Hướng dẫn chi tiết đầy đủ

## Usage Examples

### Tính lương BHXH

```php
use App\Services\InsuranceSalaryService;

$service = new InsuranceSalaryService();

// Tính lương BHXH tại thời điểm hiện tại
$result = $service->calculateInsuranceSalary($employee, region: 2);

if ($result) {
    echo "Lương BHXH: " . number_format($result['amount'], 0) . " VNĐ\n";
    echo "Công thức: {$result['breakdown']['formula']}\n";
}
```

### Đề xuất tăng bậc

```php
$suggestion = $service->suggestGradeRaise($employee);

if ($suggestion && $suggestion['eligible']) {
    echo "Nhân viên đủ điều kiện tăng bậc!\n";
    echo "Bậc hiện tại: {$suggestion['current_grade']}\n";
    echo "Đề xuất: {$suggestion['suggested_grade']}\n";
    echo "Thâm niên: {$suggestion['tenure_years']} năm\n";
}
```

### Tăng bậc (sau khi có Phụ lục)

```php
// HR duyệt → Tạo Appendix SALARY → Tăng bậc
$newProfile = $service->raiseGrade(
    employee: $employee,
    newGrade: 3,
    effectiveDate: '2025-01-01',
    reason: 'SENIORITY',
    appendixId: $appendix->id,
    note: 'Tăng bậc sau 6 năm thâm niên'
);
```

### Xem lịch sử

```php
$history = $service->getInsuranceHistory($employee);

foreach ($history as $record) {
    echo "{$record['period']}: Bậc {$record['grade']} - {$record['reason_display']}\n";
}
```

## Kiến trúc 3 bảng

```
┌─────────────────────┐
│  minimum_wages      │ ← Lương tối thiểu vùng (1-4)
│  - region           │   theo thời gian
│  - amount           │
│  - effective_from   │
└─────────────────────┘
           ↓
┌─────────────────────────┐
│ position_salary_grades  │ ← 7 bậc với hệ số
│ - position_id           │   cho mỗi vị trí
│ - grade (1-7)           │
│ - coefficient           │
└─────────────────────────┘
           ↓
┌───────────────────────────┐
│ employee_insurance_       │ ← Bậc hiện tại
│ profiles                  │   + lịch sử
│ - employee_id             │   của nhân viên
│ - grade                   │
│ - applied_from/to         │
└───────────────────────────┘
```

## Ví dụ thực tế

### Vị trí "Giám đốc" - Vùng 2 (4,410,000 VNĐ)

| Bậc | Hệ số | Lương BHXH |
|-----|-------|------------|
| 1 | 2.68 | 11,818,800 VNĐ |
| 2 | 3.08 | 13,582,800 VNĐ |
| 3 | 3.54 | 15,611,400 VNĐ |
| 4 | 4.08 | 17,992,800 VNĐ |
| 5 | 4.98 | 21,961,800 VNĐ |
| 6 | 6.07 | 26,768,700 VNĐ |
| 7 | 7.41 | 32,678,100 VNĐ |

## Timeline ví dụ

```
2020-01-01: Nhập việc → Bậc 1
2023-01-01: 3 năm thâm niên → Bậc 2 ✓
2026-01-01: 6 năm thâm niên → Bậc 3 ✓
2029-01-01: 9 năm thâm niên → Bậc 4 ✓
...
```

## Support

Xem hướng dẫn chi tiết: [INSURANCE_SALARY_SYSTEM_GUIDE.md](INSURANCE_SALARY_SYSTEM_GUIDE.md)
