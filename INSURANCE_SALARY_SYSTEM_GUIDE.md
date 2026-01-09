# Hệ Thống Lương BHXH Theo Thang - Bậc - Hệ Số

## Tổng Quan

Hệ thống quản lý lương Bảo hiểm xã hội (BHXH) theo mô hình **Thang - Bậc - Hệ Số** chuẩn nghiệp vụ Việt Nam.

### Công thức tính

```
Lương BHXH = Lương tối thiểu vùng × Hệ số bậc
```

**Ví dụ:**
- Vùng 2: 4,410,000 VNĐ
- Giám đốc bậc 3: hệ số 3.54
- Lương BHXH = 4,410,000 × 3.54 = **15,611,400 VNĐ**

### Quy tắc tăng bậc

- Có 7 bậc: 1, 2, 3, 4, 5, 6, 7
- Cứ **mỗi 3 năm thâm niên** ở cùng vị trí → tăng 1 bậc
- Tối đa bậc 7
- **Không tự động** tăng bậc, phải có quyết định/phụ lục HĐLĐ

---

## 🆕 Kiến Trúc Mới - Config System (2025)

### Insurance Config Versioning System

Hệ thống quản lý cấu hình bảo hiểm theo phiên bản (config sets) với trạng thái **DRAFT → ACTIVE → ARCHIVED**. Cho phép CRUD qua UI thay vì chỉ seeding JSON.

**3 bảng chính:**
1. **`insurance_config_sets`** - Phiên bản config (container)
2. **`insurance_minimum_wage_configs`** - Lương tối thiểu 4 vùng
3. **`insurance_salary_grade_configs`** - Hệ số 7 bậc

### 1. `insurance_config_sets` - Phiên bản Config

**Cấu trúc:**
```
- id (UUID, PK)
- code (string, unique): Mã định danh (VD: VN_INS_2024_07)
- name (string): Tên mô tả (VD: Bảng lương BHXH 2024)
- description (text): Mô tả chi tiết
- status (enum): DRAFT | ACTIVE | ARCHIVED
- effective_from (date): Ngày bắt đầu hiệu lực
- effective_to (date, nullable): Ngày kết thúc
- based_on_set_id (UUID, nullable, FK): Clone từ config set nào
- created_by (bigint, FK users)
- activated_by (bigint, nullable, FK users)
- activated_at (timestamp, nullable)
- archived_by (bigint, nullable, FK users)
- archived_at (timestamp, nullable)
- timestamps + soft_deletes
```

**Trạng thái workflow:**
```
DRAFT (soạn thảo) 
  ↓ activate()
ACTIVE (đang áp dụng)
  ↓ archive()  
ARCHIVED (lưu trữ)
```

**Quy tắc quan trọng:**
- ✅ Chỉ DRAFT mới được update/delete
- ✅ Khi activate → tự động archive các ACTIVE khác
- ✅ Validation: phải có đủ 4 vùng (1,2,3,4) + 7 bậc (1-7)
- ✅ Không được trùng khoảng effective dates với ACTIVE khác

### 2. `insurance_minimum_wage_configs` - Lương tối thiểu

**Cấu trúc:**
```
- id (UUID, PK)
- config_set_id (UUID, FK → insurance_config_sets)
- region (int 1-4): Vùng I, II, III, IV
- amount (bigint): Mức lương (VND)
- timestamps + soft_deletes
```

**Unique key:** `config_set_id` + `region`

### 3. `insurance_salary_grade_configs` - Hệ số bậc

**Cấu trúc:**
```
- id (UUID, PK)
- config_set_id (UUID, FK → insurance_config_sets)
- grade (int 1-7): Bậc lương
- coefficient (decimal 5,2): Hệ số nhân
- timestamps + soft_deletes
```

**Unique key:** `config_set_id` + `grade`

**Ví dụ data:**

**Config Set:**
| Code | Name | Status | Effective From | Effective To |
|------|------|--------|----------------|--------------|
| VN_INS_2024_07 | Bảng lương BHXH 2024 | ACTIVE | 2024-07-01 | null |

**Minimum Wages (VN_INS_2024_07):**
| Region | Amount |
|--------|--------|
| 1 | 4,960,000 |
| 2 | 4,410,000 |
| 3 | 3,860,000 |
| 4 | 3,450,000 |

**Salary Grades (VN_INS_2024_07):**
| Grade | Coefficient |
|-------|-------------|
| 1 | 1.00 |
| 2 | 1.15 |
| 3 | 1.32 |
| 4 | 1.52 |
| 5 | 1.75 |
| 6 | 2.01 |
| 7 | 2.32 |

---

## Kiến Trúc Database (Legacy - Deprecated)

### 1. `minimum_wages` - Lương tối thiểu vùng

Lưu trữ lương tối thiểu vùng theo thời gian (4 vùng của Việt Nam).

**Cấu trúc:**
```
- id (UUID)
- region (1-4): Vùng I, II, III, IV
- amount (bigint): Mức lương (VND)
- effective_from (date): Ngày bắt đầu hiệu lực
- effective_to (date, nullable): Ngày kết thúc hiệu lực
- is_active (boolean): Đang hiệu lực?
- note (text): Ghi chú (số QĐ, văn bản pháp lý...)
- timestamps
```

**Unique key:** `region` + `effective_from`

**Nguyên tắc quan trọng:**
- ✅ Khi nhà nước điều chỉnh → **INSERT** record mới
- ❌ **KHÔNG UPDATE** record cũ (để giữ lịch sử)
- Payroll/BHXH report phải lấy đúng mức theo `effective_from`

**Ví dụ data:**
| Region | Amount | Effective From | Effective To | Note |
|--------|--------|----------------|--------------|------|
| 1 | 4,960,000 | 2024-07-01 | null | Nghị định 24/2023/NĐ-CP - Vùng I |
| 2 | 4,410,000 | 2024-07-01 | null | Nghị định 24/2023/NĐ-CP - Vùng II |
| 3 | 3,860,000 | 2024-07-01 | null | Nghị định 24/2023/NĐ-CP - Vùng III |
| 4 | 3,450,000 | 2024-07-01 | null | Nghị định 24/2023/NĐ-CP - Vùng IV |

---

### 2. `position_salary_grades` - Thang hệ số theo Position

Lưu trữ 7 bậc lương với hệ số riêng cho mỗi vị trí/chức danh.

**Cấu trúc:**
```
- id (UUID)
- position_id (UUID, FK): ID vị trí/chức danh
- grade (1-7): Bậc lương
- coefficient (decimal 6,2): Hệ số nhân
- effective_from (date): Ngày bắt đầu hiệu lực
- effective_to (date, nullable): Ngày kết thúc hiệu lực
- is_active (boolean): Đang hiệu lực?
- note (text): Ghi chú
- timestamps
```

**Unique key:** `position_id` + `grade` + `effective_from`

**Ví dụ: Thang hệ số vị trí "Giám đốc"**
| Grade | Coefficient | Ví dụ lương (Vùng 2) |
|-------|-------------|----------------------|
| 1 | 2.68 | 11,818,800 VNĐ |
| 2 | 3.08 | 13,582,800 VNĐ |
| 3 | 3.54 | 15,611,400 VNĐ |
| 4 | 4.08 | 17,992,800 VNĐ |
| 5 | 4.98 | 21,961,800 VNĐ |
| 6 | 6.07 | 26,768,700 VNĐ |
| 7 | 7.41 | 32,678,100 VNĐ |

---

### 3. `employee_insurance_profiles` - Hồ sơ BHXH nhân viên

Lưu trữ bậc BHXH hiện tại + lịch sử thay đổi của từng nhân viên.

**Cấu trúc:**
```
- id (UUID)
- employee_id (UUID, FK): ID nhân viên
- position_id (UUID, FK, nullable): ID vị trí áp dụng
- grade (1-7): Bậc hiện tại
- applied_from (date): Ngày bắt đầu áp dụng
- applied_to (date, nullable): Ngày kết thúc (null = đang áp dụng)
- reason (enum): Lý do thay đổi
- source_appendix_id (UUID, nullable): ID phụ lục HĐLĐ làm căn cứ
- note (text): Ghi chú
- created_by (UUID): Người tạo
- timestamps
```

**Reason codes:**
- `INITIAL`: Khởi tạo ban đầu
- `SENIORITY`: Tăng bậc theo thâm niên (3 năm)
- `PROMOTION`: Tăng bậc do thăng chức
- `ADJUSTMENT`: Điều chỉnh đặc biệt
- `POSITION_CHANGE`: Chuyển vị trí
- `BACKFILL`: Bổ sung dữ liệu lịch sử

**Nguyên tắc quan trọng:**
- Record có `applied_to = NULL` là bậc **đang áp dụng**
- Khi tăng bậc:
  1. Đóng record cũ (set `applied_to`)
  2. Tạo record mới với `grade` mới

---

### 4. Cập nhật `positions` table

Trường `insurance_base_salary` đã được **DEPRECATED**.

**Lý do:**
- Lương BHXH không phải con số cố định
- Phụ thuộc: vùng, thời điểm, bậc, quyết định pháp lý

**Vai trò mới của `insurance_base_salary`:**
- Chỉ dùng làm **default gợi ý** khi tạo HĐLĐ/phụ lục
- **KHÔNG dùng** để tính BHXH chính thức
- Lương BHXH thực tế phải tính từ: `minimum_wage × coefficient`

---

## Flow Nghiệp Vụ

### 1. Khởi tạo dữ liệu cho nhân viên mới

```php
// Bước 1: Tạo employee_insurance_profile với bậc ban đầu (thường là bậc 1)
EmployeeInsuranceProfile::create([
    'employee_id' => $employee->id,
    'position_id' => $employee->position_id,
    'grade' => 1, // Bắt đầu từ bậc 1
    'applied_from' => $employee->hire_date,
    'applied_to' => null,
    'reason' => 'INITIAL',
    'note' => 'Khởi tạo hồ sơ BHXH lúc nhập việc',
    'created_by' => auth()->id(),
]);
```

### 2. Tính lương BHXH tại thời điểm hiện tại

```php
// Lấy hồ sơ BHXH hiện tại
$insuranceProfile = EmployeeInsuranceProfile::where('employee_id', $employee->id)
    ->current() // applied_to = NULL
    ->first();

// Tính lương BHXH
$region = 2; // Vùng 2
$insuranceSalary = $insuranceProfile->calculateInsuranceSalary($region);

// Hoặc tính thủ công:
$minWage = MinimumWage::getForRegion($region);
$gradeData = PositionSalaryGrade::where('position_id', $insuranceProfile->position_id)
    ->where('grade', $insuranceProfile->grade)
    ->active()
    ->whereNull('effective_to')
    ->first();

$insuranceSalary = $minWage->amount * $gradeData->coefficient;
```

### 3. Đề xuất tăng bậc sau 3 năm (Cronjob hàng tháng)

```php
// Job: SuggestInsuranceGradeRaiseJob (chạy hàng tháng)

$employees = Employee::with('currentInsuranceProfile')->active()->get();

foreach ($employees as $employee) {
    $profile = $employee->currentInsuranceProfile;
    
    if (!$profile) continue;
    
    // Tính thâm niên ở cùng vị trí
    $tenureYears = $this->calculateTenureInPosition($employee, $profile->position_id);
    
    // Bậc mục tiêu = min(7, 1 + floor(tenure_years / 3))
    $targetGrade = min(7, 1 + floor($tenureYears / 3));
    
    // Nếu có thể tăng bậc
    if ($targetGrade > $profile->grade) {
        // Tạo gợi ý (lưu vào bảng suggestions hoặc ghi log)
        InsuranceGradeSuggestion::create([
            'employee_id' => $employee->id,
            'current_grade' => $profile->grade,
            'suggested_grade' => $targetGrade,
            'tenure_years' => $tenureYears,
            'reason' => 'SENIORITY',
            'status' => 'PENDING',
        ]);
        
        // Gửi thông báo cho HR
        Notification::send($hrManagers, new GradeRaiseSuggestion($employee));
    }
}
```

### 4. HR duyệt đề xuất tăng bậc

```php
// Bước 1: HR duyệt suggestion
$suggestion = InsuranceGradeSuggestion::find($suggestionId);

// Bước 2: Tạo Phụ lục SALARY
$appendix = ContractAppendix::create([
    'contract_id' => $employee->activeContract->id,
    'type' => 'SALARY',
    'effective_date' => now()->addMonth()->startOfMonth(), // Hiệu lực từ đầu tháng sau
    'status' => 'DRAFT',
    // ... các trường khác
]);

// Bước 3: Approve appendix (qua workflow)
$appendix->status = 'APPROVED';
$appendix->approved_at = now();
$appendix->approved_by = auth()->id();
$appendix->save();

// Bước 4: Khi appendix ACTIVE, cập nhật insurance profile
DB::transaction(function () use ($employee, $appendix, $suggestion) {
    // Đóng profile cũ
    $oldProfile = EmployeeInsuranceProfile::where('employee_id', $employee->id)
        ->current()
        ->first();
    
    $oldProfile->applied_to = $appendix->effective_date->subDay();
    $oldProfile->save();
    
    // Tạo profile mới
    EmployeeInsuranceProfile::create([
        'employee_id' => $employee->id,
        'position_id' => $oldProfile->position_id,
        'grade' => $suggestion->suggested_grade,
        'applied_from' => $appendix->effective_date,
        'applied_to' => null,
        'reason' => 'SENIORITY',
        'source_appendix_id' => $appendix->id,
        'note' => "Tăng bậc từ {$oldProfile->grade} lên {$suggestion->suggested_grade} sau {$suggestion->tenure_years} năm thâm niên",
        'created_by' => auth()->id(),
    ]);
    
    // Đánh dấu suggestion đã xử lý
    $suggestion->status = 'APPROVED';
    $suggestion->processed_at = now();
    $suggestion->save();
});
```

### 5. Payroll/BHXH lấy số nào?

**Thứ tự ưu tiên:**

1. **Appendix ACTIVE** gần nhất có `effective_date <= kỳ lương`
2. Nếu không có appendix → **Contract ACTIVE** (snapshot tại thời điểm ký)
3. Nếu không có contract → **EmployeeInsuranceProfile** (backfill)

```php
// Service: GetEmployeeInsuranceSalaryService

public function getInsuranceSalaryForPayroll($employee, $payrollPeriod)
{
    $payrollDate = $payrollPeriod->end_date;
    
    // 1. Tìm appendix SALARY gần nhất
    $appendix = ContractAppendix::where('contract_id', $employee->activeContract->id)
        ->where('type', 'SALARY')
        ->where('status', 'ACTIVE')
        ->where('effective_date', '<=', $payrollDate)
        ->orderBy('effective_date', 'desc')
        ->first();
    
    if ($appendix && $appendix->insurance_salary) {
        return $appendix->insurance_salary;
    }
    
    // 2. Fallback: Contract ACTIVE
    $contract = $employee->activeContract;
    if ($contract && $contract->insurance_salary) {
        return $contract->insurance_salary;
    }
    
    // 3. Fallback: Tính từ insurance profile (backfill)
    $profile = EmployeeInsuranceProfile::where('employee_id', $employee->id)
        ->atDate($payrollDate)
        ->first();
    
    if ($profile) {
        $region = $this->getEmployeeRegion($employee); // Lấy vùng của nhân viên
        return $profile->calculateInsuranceSalary($region);
    }
    
    return null;
}
```

---

## Cài Đặt & Sử Dụng

### Bước 1: Chạy Migration

```bash
php artisan migrate
```

Các migration sẽ được chạy theo thứ tự:
1. `2025_12_25_000001_create_minimum_wages_table.php`
2. `2025_12_25_000002_create_position_salary_grades_table.php`
3. `2025_12_25_000003_create_employee_insurance_profiles_table.php`

### Bước 2: Seed Dữ Liệu Mẫu

```bash
php artisan db:seed --class=InsuranceSalarySystemSeeder
```

Seeder sẽ tạo:
- 4 vùng lương tối thiểu (theo Nghị định 24/2023/NĐ-CP)
- Thang hệ số 7 bậc cho vị trí "Giám đốc" (nếu tồn tại)

### Bước 3: Backfill Dữ Liệu Cũ (Nếu Cần)

```php
// Script backfill cho nhân viên hiện tại

$employees = Employee::with('activeContract')->get();

foreach ($employees as $employee) {
    $contract = $employee->activeContract;
    
    if (!$contract) continue;
    
    // Lấy grade từ contract hoặc mặc định bậc 1
    $grade = 1; // Hoặc tính toán dựa trên thâm niên
    
    EmployeeInsuranceProfile::create([
        'employee_id' => $employee->id,
        'position_id' => $contract->position_id,
        'grade' => $grade,
        'applied_from' => $contract->start_date,
        'applied_to' => null,
        'reason' => 'BACKFILL',
        'note' => 'Bổ sung dữ liệu lịch sử từ hệ thống cũ',
        'created_by' => null,
    ]);
}
```

---

## Query Examples

### Lấy lương BHXH hiện tại của nhân viên

```php
$employee = Employee::find($id);

$insuranceProfile = $employee->currentInsuranceProfile;

if ($insuranceProfile) {
    $region = 2; // Vùng 2
    $salary = $insuranceProfile->calculateInsuranceSalary($region);
    
    echo "Bậc hiện tại: {$insuranceProfile->grade}\n";
    echo "Lương BHXH: " . number_format($salary, 0, ',', '.') . " VNĐ\n";
}
```

### Lấy lịch sử thay đổi bậc

```php
$history = EmployeeInsuranceProfile::where('employee_id', $employee->id)
    ->orderBy('applied_from', 'desc')
    ->with(['position', 'sourceAppendix'])
    ->get();

foreach ($history as $record) {
    echo "{$record->applied_from->format('d/m/Y')} - ";
    echo ($record->applied_to ? $record->applied_to->format('d/m/Y') : 'Hiện tại') . ": ";
    echo "Bậc {$record->grade} - {$record->position->title}\n";
}
```

### Lấy danh sách nhân viên cần tăng bậc

```php
$suggestions = DB::table('employee_insurance_profiles as eip')
    ->join('employees as e', 'e.id', '=', 'eip.employee_id')
    ->whereNull('eip.applied_to')
    ->where('eip.grade', '<', 7)
    ->selectRaw('
        e.id,
        e.name,
        eip.grade as current_grade,
        eip.applied_from,
        TIMESTAMPDIFF(YEAR, eip.applied_from, CURDATE()) as tenure_years,
        FLOOR(TIMESTAMPDIFF(YEAR, eip.applied_from, CURDATE()) / 3) as eligible_raises
    ')
    ->havingRaw('tenure_years >= 3')
    ->get();
```

### Lấy thang hệ số của vị trí

```php
$position = Position::find($positionId);

$grades = PositionSalaryGrade::getAllGradesForPosition($positionId);

$minWage = MinimumWage::getForRegion(2); // Vùng 2

foreach ($grades as $grade) {
    $salary = $grade->calculateSalary($minWage->amount);
    
    echo "Bậc {$grade->grade}: ";
    echo "Hệ số {$grade->coefficient} = ";
    echo number_format($salary, 0, ',', '.') . " VNĐ\n";
}
```

---

## Best Practices

### ✅ Nên làm

1. **Mọi thay đổi bậc phải có Appendix/Quyết định**
   - Không tự động cập nhật bậc
   - Luôn tạo phụ lục SALARY làm căn cứ pháp lý

2. **Giữ lịch sử thay đổi đầy đủ**
   - Không update record cũ
   - Luôn insert record mới với `effective_from`

3. **Tính lương BHXH theo thời điểm**
   - Payroll tháng 01/2025 phải lấy mức lương tối thiểu hiệu lực lúc đó
   - Không dùng giá trị "hiện tại" cho kỳ lương quá khứ

4. **Backfill cẩn thận**
   - Ghi rõ `reason = 'BACKFILL'`
   - Thêm note chi tiết

### ❌ Không nên làm

1. **Không dùng `insurance_base_salary` từ bảng `positions` để tính BHXH**
   - Đây chỉ là giá trị gợi ý cũ

2. **Không tính thâm niên dựa vào `hire_date` tổng**
   - Phải tính theo thời gian ở cùng vị trí
   - Trừ các khoảng nghỉ việc/terminated

3. **Không update `contracts.insurance_salary` khi tăng bậc**
   - Contract là snapshot pháp lý tại thời điểm ký
   - Dùng Appendix để thay đổi

4. **Không hardcode lương tối thiểu vùng trong code**
   - Luôn lấy từ bảng `minimum_wages`

---

## 🚀 InsuranceConfigResolver Service - Developer Guide

### Tại sao cần InsuranceConfigResolver?

**Vấn đề cũ:**
- Code phân tán, query trực tiếp `MinimumWage::where()...`
- Khó maintain khi thay đổi source data (từ `minimum_wages` → `insurance_*_configs`)
- Không consistent trong việc lấy config theo date

**Giải pháp:**
`InsuranceConfigResolver` là **centralized service** để resolve config tại bất kỳ thời điểm nào.

### Cách sử dụng

#### 1. Inject service qua constructor (khuyến nghị)

```php
use App\Services\InsuranceConfigResolver;

class MyService 
{
    protected InsuranceConfigResolver $configResolver;
    
    public function __construct(InsuranceConfigResolver $resolver)
    {
        $this->configResolver = $resolver;
    }
    
    public function calculate($region, $grade, $date = null)
    {
        // Tính lương BHXH
        $amount = $this->configResolver->calculate($region, $grade, $date);
        return $amount;
    }
}
```

#### 2. Sử dụng helper `app()`

```php
// Trong controller, model, blade, command...
$resolver = app(InsuranceConfigResolver::class);

// Lấy lương tối thiểu vùng 2 tại ngày hiện tại
$minWage = $resolver->getMinimumWage(2);
// Returns: 4410000.0

// Lấy hệ số bậc 3 tại ngày 2024-07-15
$coefficient = $resolver->getGradeCoefficient(3, '2024-07-15');
// Returns: 1.32

// Tính trực tiếp lương BHXH: Vùng 2, Bậc 3
$salary = $resolver->calculate(2, 3);
// Returns: 5821200.0 (= 4410000 × 1.32)
```

### API Reference

#### `getActiveSet(?string $date = null): ?InsuranceConfigSet`

Lấy config set ACTIVE tại thời điểm cụ thể.

```php
$configSet = $resolver->getActiveSet(); // Hôm nay
$configSet = $resolver->getActiveSet('2024-07-01'); // 01/07/2024

// Returns null nếu không tìm thấy config nào ACTIVE
```

#### `getMinimumWage(int $region, ?string $date = null): float`

Lấy lương tối thiểu vùng (trả về số tiền VNĐ).

```php
$amount = $resolver->getMinimumWage(1); // Vùng 1, hôm nay
// Returns: 4960000.0

$amount = $resolver->getMinimumWage(4, '2024-06-30'); 
// Returns lương tối thiểu vùng 4 ngày 30/06/2024
// Throws exception nếu không có config ACTIVE tại date đó
```

**Validation:**
- Region phải trong 1-4, throw `InvalidArgumentException` nếu sai
- Throw exception nếu không tìm thấy config set ACTIVE hoặc region không tồn tại

#### `getGradeCoefficient(int $grade, ?string $date = null): float`

Lấy hệ số bậc lương.

```php
$coef = $resolver->getGradeCoefficient(1); // Bậc 1, hôm nay
// Returns: 1.00

$coef = $resolver->getGradeCoefficient(7, '2025-01-01'); 
// Returns hệ số bậc 7 ngày 01/01/2025
```

**Validation:**
- Grade phải trong 1-7, throw `InvalidArgumentException` nếu sai
- Throw exception nếu không tìm thấy grade trong config set ACTIVE

#### `calculate(int $region, int $grade, ?string $date = null): float`

Tính lương BHXH = Lương tối thiểu vùng × Hệ số bậc.

```php
// Vùng 2, Bậc 5, hôm nay
$salary = $resolver->calculate(2, 5);
// Returns: 7717500.0 (= 4410000 × 1.75)

// Tính cho quá khứ
$salary = $resolver->calculate(3, 2, '2024-08-15');
// Returns lương BHXH vùng 3, bậc 2 tại 15/08/2024
```

#### `getMinimumWageDetail(int $region, ?string $date = null): array`

Lấy thông tin chi tiết minimum wage (bao gồm model instance).

```php
$detail = $resolver->getMinimumWageDetail(2);
// Returns:
// [
//     'config_set' => InsuranceConfigSet instance,
//     'wage_config' => InsuranceMinimumWageConfig instance,
//     'region' => 2,
//     'amount' => 4410000.0
// ]
```

#### `calculateDetailed(int $region, int $grade, ?string $date = null): array`

Tính lương BHXH với thông tin chi tiết.

```php
$detail = $resolver->calculateDetailed(2, 3);
// Returns:
// [
//     'config_set' => InsuranceConfigSet instance,
//     'region' => 2,
//     'minimum_wage' => 4410000.0,
//     'grade' => 3,
//     'coefficient' => 1.32,
//     'insurance_salary' => 5821200.0
// ]
```

#### `getAllMinimumWages(?string $date = null): array`

Lấy toàn bộ 4 vùng với lương tối thiểu.

```php
$wages = $resolver->getAllMinimumWages();
// Returns:
// [
//     1 => 4960000.0,
//     2 => 4410000.0,
//     3 => 3860000.0,
//     4 => 3450000.0
// ]
```

### Migration Guide: Từ MinimumWage → InsuranceConfigResolver

#### ❌ Cách cũ (Deprecated)

```php
// Trực tiếp query model
$minWage = MinimumWage::where('region', 2)
    ->where('is_active', true)
    ->whereNull('effective_to')
    ->first();

if ($minWage) {
    $amount = $minWage->amount;
}

// Tính lương BHXH
$gradeData = PositionSalaryGrade::where('position_id', $positionId)
    ->where('grade', $grade)
    ->active()
    ->first();

$insuranceSalary = $minWage->amount * $gradeData->coefficient;
```

**Vấn đề:**
- Hardcode logic query
- Không xử lý null/missing data
- Khó test, khó maintain
- Không support versioning config

#### ✅ Cách mới (Recommended)

```php
use App\Services\InsuranceConfigResolver;

// Inject service
$resolver = app(InsuranceConfigResolver::class);

// Lấy lương tối thiểu (đã có validation + exception handling)
$amount = $resolver->getMinimumWage(2);

// Tính lương BHXH trực tiếp
$insuranceSalary = $resolver->calculate($region, $grade);

// Hoặc lấy chi tiết đầy đủ
$detail = $resolver->calculateDetailed($region, $grade);
echo "Config set: {$detail['config_set']->name}\n";
echo "Lương BHXH: " . number_format($detail['insurance_salary']) . " VNĐ\n";
```

**Lợi ích:**
- ✅ Centralized logic, dễ bảo trì
- ✅ Validation + exception handling built-in
- ✅ Support config versioning với effective dates
- ✅ Testable (mock service trong unit test)
- ✅ Consistent API across codebase

### Ví dụ thực tế

#### Trong Service: EmployeeInsuranceService

```php
use App\Services\InsuranceConfigResolver;

class EmployeeInsuranceService
{
    public function __construct(
        private InsuranceConfigResolver $configResolver
    ) {}
    
    public function calculateCurrentInsuranceSalary(Employee $employee): array
    {
        $profile = $employee->currentInsuranceProfile;
        
        if (!$profile) {
            throw new \Exception('Nhân viên chưa có hồ sơ BHXH');
        }
        
        $region = $employee->branch->insurance_region;
        $grade = $profile->grade;
        
        // Tính lương BHXH với detail
        return $this->configResolver->calculateDetailed($region, $grade);
    }
}
```

#### Trong Model: EmployeeInsuranceProfile

```php
use App\Services\InsuranceConfigResolver;

class EmployeeInsuranceProfile extends Model
{
    public function calculateInsuranceSalary(int $region, ?string $date = null): float
    {
        $resolver = app(InsuranceConfigResolver::class);
        return $resolver->calculate($region, $this->grade, $date);
    }
    
    public function getInsuranceSalaryAttribute(): ?float
    {
        if (!$this->employee || !$this->employee->branch) {
            return null;
        }
        
        $region = $this->employee->branch->insurance_region;
        return $this->calculateInsuranceSalary($region);
    }
}
```

#### Trong Command/Job: Payroll Calculation

```php
use App\Services\InsuranceConfigResolver;

class CalculatePayrollCommand extends Command
{
    public function handle(InsuranceConfigResolver $configResolver)
    {
        $period = PayrollPeriod::find($this->argument('period_id'));
        
        foreach ($period->employees as $employee) {
            $region = $employee->branch->insurance_region;
            $grade = $employee->currentInsuranceProfile->grade;
            
            // Tính lương BHXH tại thời điểm kỳ lương (không phải hôm nay!)
            $insuranceSalary = $configResolver->calculate(
                $region,
                $grade,
                $period->end_date // Quan trọng: dùng date của kỳ lương
            );
            
            // Lưu vào payroll record...
        }
    }
}
```

---

## UI Workflow - Quản lý Config BHXH

### Quy trình sử dụng UI

**Bước 1: Tạo config set mới (DRAFT)**
1. Vào menu: Cấu hình → Bảo hiểm → Config Sets
2. Click "Tạo mới"
3. Điền thông tin:
   - Code: `VN_INS_2025_01` (unique)
   - Tên: `Bảng lương BHXH 2025`
   - Khoảng hiệu lực: 01/01/2025 → 31/12/2025
   - Mô tả: Theo Nghị định XX/2024/NĐ-CP
4. Click "Sao chép từ config cũ" (nếu muốn) → Chọn `VN_INS_2024_07`
5. Lưu → Config ở trạng thái **DRAFT**

**Bước 2: Điều chỉnh lương tối thiểu vùng**
1. Form tự động hiển thị 4 vùng (nếu clone từ config cũ)
2. Cập nhật số tiền mới:
   - Vùng 1: 5,200,000 VNĐ
   - Vùng 2: 4,620,000 VNĐ
   - Vùng 3: 4,050,000 VNĐ
   - Vùng 4: 3,620,000 VNĐ

**Bước 3: Điều chỉnh hệ số bậc (nếu cần)**
1. Form hiển thị 7 bậc với hệ số
2. Chỉnh sửa nếu có thay đổi chính sách
3. Hệ số mặc định: 1.00, 1.15, 1.32, 1.52, 1.75, 2.01, 2.32

**Bước 4: Kiểm tra validation**
- Hệ thống tự động validate:
  - ✅ Phải có đủ 4 vùng (1, 2, 3, 4)
  - ✅ Phải có đủ 7 bậc (1, 2, 3, 4, 5, 6, 7)
  - ✅ Không trùng khoảng hiệu lực với config ACTIVE khác
  - ✅ Code không trùng

**Bước 5: Kích hoạt config**
1. Click "Kích hoạt"
2. Hệ thống:
   - Kiểm tra validation lần cuối
   - Tự động archive tất cả config ACTIVE cũ
   - Chuyển config hiện tại → **ACTIVE**
3. Từ thời điểm này, toàn bộ hệ thống dùng config mới

**Bước 6: Lưu trữ config cũ (tùy chọn)**
- Config ACTIVE cũ đã tự động chuyển sang **ARCHIVED**
- Có thể xem lịch sử trong tab "Archived"
- Không thể chỉnh sửa config ARCHIVED

### Quy tắc quan trọng

**❌ Không thể:**
- Update config đang ACTIVE (chỉ DRAFT mới edit được)
- Xóa config ACTIVE hoặc ARCHIVED (chỉ xóa DRAFT)
- Activate config thiếu vùng/bậc
- Activate config trùng effective dates với ACTIVE khác

**✅ Có thể:**
- Clone config bất kỳ (DRAFT, ACTIVE, ARCHIVED)
- Archive config ACTIVE thủ công
- Xem chi tiết/lịch sử config đã archived
- Tạo nhiều config DRAFT song song

### Form Request Validation

Backend validation rules (không cần validate lại ở FE):

**StoreInsuranceConfigSetRequest:**
```php
// Code unique
'code' => 'required|string|unique:insurance_config_sets,code'

// Phải có 4 vùng (distinct, region 1-4)
'minimum_wages' => 'required|array|size:4'
'minimum_wages.*.region' => 'required|integer|between:1,4|distinct'

// Phải có 7 bậc (distinct, grade 1-7)
'salary_grades' => 'required|array|size:7'
'salary_grades.*.grade' => 'required|integer|between:1,7|distinct'

// Effective dates
'effective_from' => 'required|date'
'effective_to' => 'nullable|date|after:effective_from'
```

**Custom validation (withValidator):**
- Kiểm tra tồn tại đủ 4 regions: [1, 2, 3, 4]
- Kiểm tra tồn tại đủ 7 grades: [1, 2, 3, 4, 5, 6, 7]

**Error messages:** 
- Tất cả validation errors trả về từ BE dạng JSON
- FE hiển thị toast notification tự động
- Flash message type: `success` hoặc `error`

### Resource Transformation

`InsuranceConfigSetResource` format data chuẩn:

```json
{
    "id": "uuid",
    "code": "VN_INS_2024_07",
    "name": "Bảng lương BHXH 2024",
    "status": "ACTIVE",
    "effective_from": "2024-07-01",
    "effective_to": null,
    "minimum_wages": [
        {
            "id": "uuid",
            "region": 1,
            "region_name": "Vùng I",
            "amount": 4960000.0,
            "formatted_amount": "4,960,000 VNĐ"
        }
    ],
    "salary_grades": [
        {
            "id": "uuid",
            "grade": 1,
            "coefficient": 1.00,
            "formatted_coefficient": "1.00x"
        }
    ],
    "based_on_set": { ... },
    "created_by": 1,
    "activated_by": 2,
    "activated_at": "2024-07-01T00:00:00Z",
    "created_at": "2024-06-15T00:00:00Z"
}
```

### Activity Logging

Mọi thao tác đều được log vào `activity_log`:

```php
// Store
activity()
    ->performedOn($configSet)
    ->causedBy(auth()->user())
    ->log('Tạo bộ config bảo hiểm: ' . $configSet->code);

// Activate
activity()
    ->performedOn($configSet)
    ->causedBy(auth()->user())
    ->withProperties([
        'attributes' => [
            'code' => $configSet->code,
            'status' => 'ACTIVE',
        ]
    ])
    ->log('Kích hoạt bộ config bảo hiểm');

// Update
activity()
    ->performedOn($configSet)
    ->causedBy(auth()->user())
    ->withProperties([
        'old' => $oldAttributes,
        'attributes' => $newAttributes,
    ])
    ->log('Cập nhật bộ config bảo hiểm');
```

**Xem activity log:**
```php
$activities = Activity::forSubject($configSet)
    ->with('causer')
    ->orderBy('created_at', 'desc')
    ->get();
```

---

## Troubleshooting

### Vấn đề: Không tính được lương BHXH

**Nguyên nhân:**
- Thiếu data trong `minimum_wages` hoặc `position_salary_grades`
- Record không có `effective_from` phù hợp

**Giải pháp:**
```php
// Check minimum wage
$minWage = MinimumWage::getForRegion(2);
if (!$minWage) {
    // Seed data: php artisan db:seed --class=InsuranceSalarySystemSeeder
}

// Check position grades
$grades = PositionSalaryGrade::getAllGradesForPosition($positionId);
if ($grades->isEmpty()) {
    // Tạo thang hệ số cho position này
}
```

### Vấn đề: Nhân viên không có insurance profile

**Giải pháp:**
```php
// Khởi tạo profile cho nhân viên
EmployeeInsuranceProfile::create([
    'employee_id' => $employee->id,
    'position_id' => $employee->position_id,
    'grade' => 1,
    'applied_from' => $employee->hire_date ?? now(),
    'applied_to' => null,
    'reason' => 'INITIAL',
    'created_by' => auth()->id(),
]);
```

---

## Tích Hợp Với Hệ Thống Hiện Tại

### 1. Relationship với Employee Model

```php
// app/Models/Employee.php

public function insuranceProfiles()
{
    return $this->hasMany(EmployeeInsuranceProfile::class)
                ->orderBy('applied_from', 'desc');
}

public function currentInsuranceProfile()
{
    return $this->hasOne(EmployeeInsuranceProfile::class)
                ->whereNull('applied_to')
                ->latest('applied_from');
}
```

### 2. Relationship với Position Model

```php
// app/Models/Position.php

public function salaryGrades()
{
    return $this->hasMany(PositionSalaryGrade::class)
                ->orderBy('grade');
}

public function currentSalaryGrades()
{
    return $this->hasMany(PositionSalaryGrade::class)
                ->whereNull('effective_to')
                ->where('is_active', true)
                ->orderBy('grade');
}
```

### 3. Relationship với ContractAppendix Model

```php
// app/Models/ContractAppendix.php

public function insuranceProfilesCreated()
{
    return $this->hasMany(EmployeeInsuranceProfile::class, 'source_appendix_id');
}
```

---

## Kết Luận

Hệ thống lương BHXH theo **Thang - Bậc - Hệ Số** này:

✅ **Chuẩn nghiệp vụ BHXH Việt Nam**
✅ **Linh hoạt** với thay đổi của nhà nước (lương tối thiểu vùng)
✅ **Có lịch sử đầy đủ** (audit trail)
✅ **Tách biệt** dữ liệu gợi ý vs dữ liệu pháp lý
✅ **Tích hợp tốt** với hệ thống Contract/Appendix hiện tại

Nếu có thắc mắc hoặc cần tùy chỉnh, vui lòng tham khảo:
- Migration files trong `database/migrations/`
- Model files trong `app/Models/`
- Seeder trong `database/seeders/InsuranceSalarySystemSeeder.php`
