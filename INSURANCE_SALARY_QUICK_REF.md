# Hệ Thống Lương BHXH - Quick Reference

⚠️ **NOTE:** Tài liệu này dùng cho hệ thống mới (2025+). Xem `INSURANCE_SALARY_SYSTEM_GUIDE.md` cho chi tiết đầy đủ.

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

# 2. Seed dữ liệu config BHXH (hệ thống mới)
php artisan db:seed --class=InsuranceConfigSeeder
```

## Files quan trọng

### Database (New Config System)
- `database/migrations/2026_01_08_091537_create_insurance_config_sets_table.php`
- `database/migrations/2026_01_08_091538_create_insurance_minimum_wage_configs_table.php`
- `database/migrations/2026_01_08_091539_create_insurance_salary_grade_configs_table.php`
- `database/migrations/2025_12_25_000002_create_position_salary_grades_table.php`
- `database/migrations/2025_12_25_000003_create_employee_insurance_profiles_table.php`
- `database/seeders/InsuranceConfigSeeder.php`

### Models
- `app/Models/InsuranceConfigSet.php` - Phiên bản config
- `app/Models/InsuranceMinimumWageConfig.php` - Lương tối thiểu vùng
- `app/Models/InsuranceSalaryGradeConfig.php` - Hệ số bậc chung
- `app/Models/PositionSalaryGrade.php` - Thang hệ số 7 bậc theo position
- `app/Models/EmployeeInsuranceProfile.php` - Hồ sơ BHXH nhân viên

### Services
- `app/Services/InsuranceConfigResolver.php` - **Dùng service này để lấy config**
- `app/Services/InsuranceSalaryService.php` - Logic nghiệp vụ
- `app/Services/InsuranceSalaryCalculatorService.php` - Tính toán

### Controllers
- `app/Http/Controllers/InsuranceConfigSetController.php` - CRUD config sets

### Documentation
- `INSURANCE_SALARY_SYSTEM_GUIDE.md` - Hướng dẫn chi tiết đầy đủ

## Usage Examples

### Tính lương BHXH (Recommended - Dùng InsuranceConfigResolver)

```php
use App\Services\InsuranceConfigResolver;

$resolver = app(InsuranceConfigResolver::class);

// Cách 1: Tính trực tiếp (đơn giản nhất)
$salary = $resolver->calculate(
    region: 2,      // Vùng 2
    grade: 3,       // Bậc 3
    date: '2024-07-15'  // Tùy chọn, null = hôm nay
);
// Returns: 5821200.0 (float)

// Cách 2: Lấy chi tiết đầy đủ
$detail = $resolver->calculateDetailed(2, 3);
echo "Config: {$detail['config_set']->name}\n";
echo "Lương tối thiểu: " . number_format($detail['minimum_wage']) . "\n";
echo "Hệ số: {$detail['coefficient']}\n";
echo "Lương BHXH: " . number_format($detail['insurance_salary']) . " VNĐ\n";
```

### Tính lương BHXH (Legacy - Qua Service)

```php
use App\Services\InsuranceSalaryService;

$service = app(InsuranceSalaryService::class);

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

## Kiến trúc hệ thống mới (2025+)

```
┌──────────────────────────────┐
│ insurance_config_sets        │ ← Versioning system
│ - code (VN_INS_2024_07)      │   DRAFT → ACTIVE → ARCHIVED
│ - status                     │
│ - effective_from/to          │
└──────────────────────────────┘
           ↓                  ↓
┌─────────────────────────────────┐   ┌────────────────────────────┐
│ insurance_minimum_wage_configs  │   │ insurance_salary_grade_    │
│ - config_set_id                 │   │ configs                    │
│ - region (1-4)                  │   │ - config_set_id            │
│ - amount                        │   │ - grade (1-7)              │
└─────────────────────────────────┘   │ - coefficient              │
                                      └────────────────────────────┘
                    ↓
┌─────────────────────────────┐
│ position_salary_grades      │ ← Hệ số theo Position (legacy)
│ - position_id               │   VẪN ĐANG DÙNG
│ - grade (1-7)               │
│ - coefficient               │
└─────────────────────────────┘
           ↓
┌───────────────────────────────┐
│ employee_insurance_profiles   │ ← Bậc hiện tại + lịch sử
│ - employee_id                 │   của nhân viên
│ - grade                       │
│ - applied_from/to             │
└───────────────────────────────┘
```

**Key Changes:**
- ✅ Lương tối thiểu vùng: `minimum_wages` → `insurance_minimum_wage_configs` (với versioning)
- ✅ Hệ số bậc chung: Mới thêm `insurance_salary_grade_configs`
- ⚠️ Hệ số theo position: `position_salary_grades` vẫn giữ nguyên (chưa migrate)

## Ví dụ thực tế

### Config System: VN_INS_2024_07

**Lương tối thiểu vùng:**
| Vùng | Amount |
|------|--------|
| 1 | 4,960,000 VNĐ |
| 2 | 4,410,000 VNĐ |
| 3 | 3,860,000 VNĐ |
| 4 | 3,450,000 VNĐ |

**Hệ số bậc chung (insurance_salary_grade_configs):**
| Bậc | Hệ số |
|-----|-------|
| 1 | 1.00 |
| 2 | 1.15 |
| 3 | 1.32 |
| 4 | 1.52 |
| 5 | 1.75 |
| 6 | 2.01 |
| 7 | 2.32 |

**Tính lương BHXH: Vùng 2, Bậc 3**
```
4,410,000 × 1.32 = 5,821,200 VNĐ
```

### Hệ số theo Position (position_salary_grades)

**Ví dụ: Vị trí "Giám đốc" - Vùng 2 (4,410,000 VNĐ)**

| Bậc | Hệ số | Lương BHXH |
|-----|-------|------------|
| 1 | 2.68 | 11,818,800 VNĐ |
| 2 | 3.08 | 13,582,800 VNĐ |
| 3 | 3.54 | 15,611,400 VNĐ |
| 4 | 4.08 | 17,992,800 VNĐ |
| 5 | 4.98 | 21,961,800 VNĐ |
| 6 | 6.07 | 26,768,700 VNĐ |
| 7 | 7.41 | 32,678,100 VNĐ |

**NOTE:** Position-specific grades vẫn dùng bảng `position_salary_grades` (chưa migrate sang config system)

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
