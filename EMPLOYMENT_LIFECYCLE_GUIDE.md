# Employment Lifecycle Management

## 📌 Tổng quan

Hệ thống quản lý **chu kỳ làm việc (Employment Lifecycle)** cho phép tracking chính xác lịch sử làm việc của nhân viên, bao gồm:
- Nghỉ việc và tái tuyển dụng
- Tính thâm niên tích lũy (lifetime seniority)
- Quản lý BHXH đúng theo từng chu kỳ
- Tính phép năm chính xác

---

## 🏗️ Kiến trúc

### 1. Ba khái niệm cốt lõi

```
┌─────────────────────────────────────────────────────────────┐
│  1. hire_date (Employee)                                    │
│     - Ngày bắt đầu đợt làm việc HIỆN TẠI                    │
│     - Cập nhật khi tái tuyển dụng                           │
│     - KHÔNG phải tổng thâm niên                             │
├─────────────────────────────────────────────────────────────┤
│  2. EmployeeEmployment (Chu kỳ làm việc)                    │
│     - Mỗi đợt làm việc liên tục = 1 record                  │
│     - Có start_date, end_date, end_reason                   │
│     - is_current = true cho đợt hiện tại                    │
├─────────────────────────────────────────────────────────────┤
│  3. Lifetime Seniority (Thâm niên tích lũy)                 │
│     - Tổng thời gian của TẤT CẢ các employments            │
│     - Tính động, không lưu cứng                             │
│     - Dùng cho bonus phép năm, lương...                     │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 Database Schema

### Bảng `employee_employments`

```sql
CREATE TABLE employee_employments (
    id UUID PRIMARY KEY,
    employee_id UUID NOT NULL,

    start_date DATE NOT NULL,           -- Ngày vào làm đợt này
    end_date DATE NULL,                 -- NULL = đang làm

    end_reason ENUM(
        'RESIGN',           -- Nghỉ việc tự nguyện
        'TERMINATION',      -- Sa thải
        'CONTRACT_END',     -- Hết hạn HĐ
        'LAYOFF',           -- Cho thôi việc
        'RETIREMENT',       -- Nghỉ hưu
        'MATERNITY_LEAVE',  -- Nghỉ sinh
        'REHIRE',           -- Tái tuyển dụng
        'OTHER'
    ) NULL,

    is_current BOOLEAN DEFAULT TRUE,    -- Đợt hiện tại?
    note TEXT NULL,

    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY (employee_id, is_current) WHERE is_current = TRUE
);
```

### Thêm vào bảng `contracts`

```sql
ALTER TABLE contracts
ADD COLUMN employment_id UUID NULL
AFTER employee_id,
ADD FOREIGN KEY (employment_id) REFERENCES employee_employments(id);
```

---

## 💡 Use Cases

### Case 1: Nhân viên mới vào

```php
// 1. Tạo Employee
$employee = Employee::create([
    'employee_code' => 'NV001',
    'hire_date' => '2025-01-15',
    'status' => 'ACTIVE',
    // ...
]);

// 2. Tạo Employment Period
$employment = EmployeeEmployment::create([
    'employee_id' => $employee->id,
    'start_date' => '2025-01-15',
    'end_date' => null,
    'is_current' => true,
]);

// 3. Tạo Contract và gắn vào employment
$contract = Contract::create([
    'employee_id' => $employee->id,
    'employment_id' => $employment->id,
    'start_date' => '2025-01-15',
    // ...
]);
```

---

### Case 2: Nhân viên nghỉ việc

```php
$employee = Employee::find($id);
$currentEmployment = $employee->currentEmployment();

// End employment
$currentEmployment->endEmployment(
    endDate: now(),
    reason: 'RESIGN',
    note: 'Nhân viên xin nghỉ việc để theo đuổi cơ hội mới'
);

// Update employee status
$employee->update(['status' => 'TERMINATED']);

// BHXH: Tự động detect "giảm" trong tháng này
```

---

### Case 3: Tái tuyển dụng (Rehire)

```php
$employee = Employee::find($id);

// 1. Tạo employment mới
$newEmployment = EmployeeEmployment::create([
    'employee_id' => $employee->id,
    'start_date' => '2025-06-01',
    'end_date' => null,
    'is_current' => true,
]);

// 2. Update hire_date
$employee->update([
    'hire_date' => '2025-06-01',
    'status' => 'ACTIVE',
]);

// 3. Tạo contract mới
$newContract = Contract::create([
    'employee_id' => $employee->id,
    'employment_id' => $newEmployment->id,
    'start_date' => '2025-06-01',
    // ...
]);

// ✅ Lịch sử làm việc được giữ nguyên:
// Employment #1: 2018-01-01 → 2024-12-31 (RESIGN)
// Employment #2: 2025-06-01 → NULL (current)
```

---

## 🔢 Tính toán Thâm niên

### Tổng thâm niên (cho bonus phép)

```php
// Tự động tính tổng tất cả employments
$totalSeniority = $employee->getTotalSeniorityYears();

// VD:
// Period 1: 2018-2020 (2 năm)
// Period 2: 2022-2025 (3 năm)
// → Total: 5 năm
```

### Thâm niên employment hiện tại

```php
// Chỉ tính đợt hiện tại (cho phép năm)
$currentSeniority = $employee->getCurrentSeniorityYears();

// VD: Tái tuyển 2022 → hiện tại: 3 năm
```

---

## 📝 Áp dụng vào nghiệp vụ

### 1. BHXH (Insurance)

```php
// Phát hiện "tăng" BHXH
$newEmployments = EmployeeEmployment::whereMonth('start_date', $month)
    ->whereYear('start_date', $year)
    ->with('employee')
    ->get();

foreach ($newEmployments as $employment) {
    // Tạo InsuranceChangeRecord với type = 'INCREASE'
}

// Phát hiện "giảm" BHXH
$endedEmployments = EmployeeEmployment::whereMonth('end_date', $month)
    ->whereYear('end_date', $year)
    ->with('employee')
    ->get();

foreach ($endedEmployments as $employment) {
    // Tạo InsuranceChangeRecord với type = 'DECREASE'
}
```

### 2. Phép năm (Leave Balance)

```php
// Trong InitializeLeaveBalances command
private function calculateSeniorityYears(Employee $employee, int $year): int
{
    // Tính tổng thâm niên qua tất cả employments
    return $employee->employments()
        ->where('start_date', '<=', "{$year}-12-31")
        ->get()
        ->sum(fn($emp) => $emp->getDurationInYears());
}

// Bonus: +1 ngày phép / 5 năm thâm niên
$seniorityBonus = floor($totalSeniority / 5);
```

### 3. Payroll

```php
// Check employment active trong kỳ lương
$activeEmployment = EmployeeEmployment::forEmployee($employeeId)
    ->active($payrollPeriod->start_date)
    ->first();

if (!$activeEmployment) {
    // Không tính lương (đã nghỉ việc hoặc chưa vào làm)
}
```

---

## 🚀 Migration Steps

### Bước 1: Chạy migration

```bash
php artisan migrate
```

### Bước 2: Migrate dữ liệu cũ

```bash
php artisan db:seed --class=MigrateExistingEmployeesToEmploymentSeeder
```

**Logic migration:**
- Mỗi employee hiện tại → tạo 1 employment
- `start_date` = `hire_date` (hoặc `created_at` nếu null)
- `is_current` = (`status` == 'ACTIVE' hoặc 'ON_LEAVE')
- Các contracts hiện tại được gắn vào employment này

### Bước 3: Kiểm tra

```bash
php artisan tinker

>>> $employee = Employee::first();
>>> $employee->employments;  // Should have 1+ records
>>> $employee->getTotalSeniorityYears();
>>> $employee->currentEmployment();
```

---

## 🎯 Best Practices

### 1. Khi tạo Employee mới
✅ **LUÔN** tạo EmployeeEmployment cùng lúc
```php
DB::transaction(function() use ($data) {
    $employee = Employee::create($data);

    EmployeeEmployment::create([
        'employee_id' => $employee->id,
        'start_date' => $data['hire_date'],
        'is_current' => true,
    ]);
});
```

### 2. Khi nghỉ việc
✅ End employment + update employee status
```php
DB::transaction(function() use ($employee, $endDate, $reason) {
    $employment = $employee->currentEmployment();
    $employment->endEmployment($endDate, $reason);

    $employee->update(['status' => 'TERMINATED']);
});
```

### 3. Khi tái tuyển dụng
✅ Tạo employment MỚI, đừng update cái cũ
```php
// ❌ WRONG
$oldEmployment->update(['end_date' => null, 'is_current' => true]);

// ✅ CORRECT
EmployeeEmployment::create([
    'employee_id' => $employee->id,
    'start_date' => $rehireDate,
    'is_current' => true,
]);
```

### 4. Tính thâm niên
✅ Dùng `getTotalSeniorityYears()` để tính bonus
```php
// Cho phép năm, lương...
$seniority = $employee->getTotalSeniorityYears();
$bonus = floor($seniority / 5); // +1 ngày phép / 5 năm
```

---

## ❓ FAQ

**Q: hire_date vẫn dùng để làm gì?**
A: Hiển thị UI "Ngày vào công ty (lần này)", dùng cho báo cáo nhanh. Nhưng logic TÍNH TOÁN phải dùng EmployeeEmployment.

**Q: Nếu employee nghỉ 2 năm rồi quay lại, thâm niên tính sao?**
A: Có 2 options tùy policy công ty:
1. **Lifetime seniority**: `getTotalSeniorityYears()` → cộng dồn tất cả
2. **Current seniority**: `getCurrentSeniorityYears()` → chỉ tính đợt hiện tại

**Q: Contract cần gắn employment_id không?**
A: Có, để tracking contract thuộc đợt làm việc nào. Khi rehire → contract mới thuộc employment mới.

**Q: Cần update hire_date khi nghỉ việc không?**
A: KHÔNG. `hire_date` giữ nguyên. Chỉ update khi tái tuyển dụng.

---

## 📚 References

- Migration: `database/migrations/2025_12_12_000001_create_employee_employments_table.php`
- Model: `app/Models/EmployeeEmployment.php`
- Seeder: `database/seeders/MigrateExistingEmployeesToEmploymentSeeder.php`
- Command updated: `app/Console/Commands/InitializeLeaveBalances.php`
