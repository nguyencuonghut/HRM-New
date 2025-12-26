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

## Kiến Trúc Database

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
