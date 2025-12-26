# Hệ Thống Lương BHXH - Implementation Summary

## ✅ Đã Hoàn Thành

### 1. InsuranceSalaryCalculatorService ✓

**File:** `app/Services/InsuranceSalaryCalculatorService.php`

Service tập trung vào **tính toán thuần túy** (pure calculation):

```php
// Tính lương BHXH cơ bản
$calc = $calculator->calculate($region, $positionId, $grade, $date);
// => ['amount' => 15611400, 'coefficient' => 3.54, 'breakdown' => [...]]

// Tính cho nhân viên cụ thể
$calc = $calculator->calculateForEmployee($employeeId, $region);

// Tính tất cả 7 bậc (hiển thị thang lương)
$allGrades = $calculator->calculateAllGrades($positionId, $region);

// So sánh 2 bậc
$compare = $calculator->compareGrades($positionId, 2, 3, $region);
// => ['current', 'new', 'difference' => ['amount', 'percent']]
```

**Methods chính:**
- `calculate()` - Tính lương BHXH = min_wage × coefficient
- `calculateForEmployee()` - Tính cho 1 nhân viên
- `calculateAllGrades()` - Tính cả 7 bậc
- `compareGrades()` - So sánh 2 bậc
- `getMinimumWage()` - Lấy lương tối thiểu vùng
- `getGradeCoefficient()` - Lấy hệ số bậc
- `calculateBulk()` - Tính cho nhiều nhân viên (payroll)

---

### 2. Card BHXH trong Payroll Tab ✓

**File:** `resources/js/Pages/Employees/Components/PayrollTab.vue`

**Cập nhật:**
- `app/Http/Controllers/EmployeeController.php` - Thêm data `insurance_data` và `insurance_history`
- `app/Models/Employee.php` - Thêm relationships `insuranceProfiles()` và `currentInsuranceProfile()`

**Features:**

#### 2.1. Card hiển thị BHXH (nếu có profile)
- **Thông tin bậc lương:**
  - Vị trí hiện tại
  - Bậc hiện tại (1-7)
  - Hệ số
  - Áp dụng từ ngày

- **Tính toán lương BHXH:**
  - Lương tối thiểu vùng
  - Hệ số bậc
  - Lương BHXH (tự động tính)
  - Công thức hiển thị rõ ràng

- **Đề xuất tăng bậc (nếu đủ điều kiện):**
  - Hiển thị số năm thâm niên
  - Bậc hiện tại → Bậc đề xuất
  - Nút "Tạo phụ lục tăng bậc"

- **Lịch sử thay đổi bậc (collapsible):**
  - Table hiển thị tất cả thay đổi
  - Thời gian, vị trí, bậc, lý do

#### 2.2. Banner chưa có profile
- Thông báo "Chưa khởi tạo hồ sơ BHXH"
- Nút "Khởi tạo" (placeholder)

**Screenshots logic:**
```vue
<div v-if="insuranceData && insuranceData.has_profile">
  <!-- Card BHXH đầy đủ -->
  
  <!-- Đề xuất tăng bậc -->
  <div v-if="insuranceData.suggestion && insuranceData.suggestion.eligible">
    <!-- Alert yellow với button "Tạo phụ lục" -->
  </div>
  
  <!-- Lịch sử -->
  <div v-if="insuranceHistory && insuranceHistory.length > 1">
    <!-- Collapsible table -->
  </div>
</div>
<div v-else-if="insuranceData && !insuranceData.has_profile">
  <!-- Banner: Chưa có profile -->
</div>
```

---

### 3. Suggestion System ✓

#### 3.1. Migration: `insurance_grade_suggestions`

**File:** `database/migrations/2025_12_25_000004_create_insurance_grade_suggestions_table.php`

**Cấu trúc:**
```
- id (UUID)
- employee_id
- current_grade (1-7)
- suggested_grade (1-7)
- tenure_years (decimal)
- reason (text)
- status (PENDING | APPROVED | REJECTED | EXPIRED)
- processed_by (user_id)
- processed_at (timestamp)
- process_note (text)
- created_appendix_id (UUID, nullable)
- suggested_at (date)
- expires_at (date) - Hết hạn sau 90 ngày
```

**Indexes:**
- `employee_id`, `status`
- `status`, `suggested_at`

#### 3.2. Model: `InsuranceGradeSuggestion`

**File:** `app/Models/InsuranceGradeSuggestion.php`

**Scopes:**
- `pending()` - Chờ duyệt
- `approved()` - Đã duyệt
- `rejected()` - Từ chối
- `expired()` - Quá hạn

**Methods:**
- `approve($appendixId, $note)` - Duyệt
- `reject($note)` - Từ chối
- `markExpired()` - Đánh dấu hết hạn
- `isExpired()` - Check quá hạn

#### 3.3. Console Commands

**A) SuggestInsuranceGradeRaiseCommand**

**File:** `app/Console/Commands/SuggestInsuranceGradeRaiseCommand.php`

**Chức năng:**
- Quét tất cả nhân viên active có insurance profile
- Tính thâm niên tại vị trí
- Tạo suggestion nếu đủ điều kiện (3 năm/bậc)
- Bỏ qua nếu đã có suggestion PENDING
- Hết hạn sau 90 ngày

**Usage:**
```bash
# Chạy bình thường
php artisan insurance:suggest-grade-raise

# Dry run (không lưu DB)
php artisan insurance:suggest-grade-raise --dry-run

# Force (bỏ qua check pending)
php artisan insurance:suggest-grade-raise --force
```

**Output:**
- Bảng thống kê: tổng, đủ điều kiện, bỏ qua, lỗi
- Danh sách nhân viên đủ điều kiện
- Số suggestions đã tạo

**Cron schedule (thêm vào `app/Console/Kernel.php`):**
```php
$schedule->command('insurance:suggest-grade-raise')
         ->monthlyOn(1, '00:00'); // Chạy đầu tháng
```

**B) ExpireInsuranceSuggestionsCommand**

**File:** `app/Console/Commands/ExpireInsuranceSuggestionsCommand.php`

**Chức năng:**
- Tìm suggestions PENDING đã quá hạn (expires_at < today)
- Đánh dấu status = EXPIRED

**Usage:**
```bash
php artisan insurance:expire-suggestions
```

**Cron schedule:**
```php
$schedule->command('insurance:expire-suggestions')
         ->daily(); // Chạy hàng ngày
```

---

## 🚧 TODO: Bước Tiếp Theo (Phụ lục tăng bậc tự động)

### 4. Flow tạo Appendix với prefill ⏳

**Cần làm:**

#### 4.1. Controller/Route xử lý suggestion

**File:** `app/Http/Controllers/InsuranceSuggestionController.php` (tạo mới)

```php
// GET: Danh sách suggestions pending
public function index()
{
    $suggestions = InsuranceGradeSuggestion::with('employee.currentInsuranceProfile.position')
        ->pending()
        ->orderBy('suggested_at', 'desc')
        ->paginate(20);
    
    return Inertia::render('InsuranceSuggestions/Index', [
        'suggestions' => InsuranceSuggestionResource::collection($suggestions),
    ]);
}

// POST: Duyệt suggestion → Tạo appendix
public function approve(InsuranceGradeSuggestion $suggestion)
{
    // 1. Validate
    // 2. Tạo Appendix SALARY với prefill:
    //    - insurance_salary (tính từ bậc mới)
    //    - effective_date (đầu tháng sau)
    // 3. Cập nhật insurance_profile (tăng bậc)
    // 4. Approve suggestion
    // 5. Return redirect với message
}

// POST: Từ chối suggestion
public function reject(InsuranceGradeSuggestion $suggestion, Request $request)
{
    $suggestion->reject($request->note);
    return back()->with('success', 'Đã từ chối đề xuất');
}
```

#### 4.2. Vue Page: Danh sách suggestions

**File:** `resources/js/Pages/InsuranceSuggestions/Index.vue` (tạo mới)

**Features:**
- Table hiển thị suggestions PENDING
- Columns: Nhân viên, Vị trí, Bậc hiện → Bậc đề xuất, Thâm niên, Ngày đề xuất, Actions
- Actions: 
  - Button "Duyệt" (màu xanh) → Mở modal confirm
  - Button "Từ chối" (màu đỏ) → Mở modal nhập lý do
- Filter: status, date range
- Pagination

#### 4.3. Modal: Confirm approve

**Component:** `InsuranceSuggestionApproveModal.vue`

**Hiển thị:**
- Thông tin nhân viên
- Bậc hiện tại → Bậc mới
- Lương BHXH hiện tại → Lương BHXH mới (preview)
- Ngày hiệu lực (default: đầu tháng sau)
- Note (textarea)

**Actions:**
- "Xác nhận và tạo phụ lục" → Call API approve
- "Hủy"

#### 4.4. Service: InsuranceAppendixService

**File:** `app/Services/InsuranceAppendixService.php` (tạo mới)

```php
/**
 * Tạo Appendix SALARY từ suggestion
 */
public function createAppendixFromSuggestion(
    InsuranceGradeSuggestion $suggestion,
    string $effectiveDate,
    ?string $note = null
): ContractAppendix
{
    // 1. Lấy contract active của employee
    // 2. Tính lương BHXH mới (bậc mới)
    // 3. Tạo Appendix SALARY với:
    //    - type = SALARY
    //    - insurance_salary = lương BHXH mới
    //    - effective_date
    //    - status = DRAFT (hoặc APPROVED nếu auto-approve)
    //    - title = "Tăng bậc BHXH từ X lên Y"
    // 4. Return appendix
}

/**
 * Approve appendix và cập nhật insurance profile
 */
public function approveAndUpdateProfile(
    ContractAppendix $appendix,
    InsuranceGradeSuggestion $suggestion
): void
{
    DB::transaction(function () use ($appendix, $suggestion) {
        // 1. Approve appendix
        $appendix->status = 'APPROVED';
        $appendix->save();
        
        // 2. Tăng bậc (InsuranceSalaryService::raiseGrade)
        $this->insuranceService->raiseGrade(
            employee: $suggestion->employee,
            newGrade: $suggestion->suggested_grade,
            effectiveDate: $appendix->effective_date,
            reason: 'SENIORITY',
            appendixId: $appendix->id,
            note: "Tăng bậc theo suggestion #{$suggestion->id}"
        );
        
        // 3. Approve suggestion
        $suggestion->approve($appendix->id);
    });
}
```

#### 4.5. Routes

**File:** `routes/web.php`

```php
// Insurance suggestions
Route::middleware(['auth'])->prefix('insurance-suggestions')->group(function () {
    Route::get('/', [InsuranceSuggestionController::class, 'index'])
         ->name('insurance-suggestions.index');
    
    Route::post('/{suggestion}/approve', [InsuranceSuggestionController::class, 'approve'])
         ->name('insurance-suggestions.approve');
    
    Route::post('/{suggestion}/reject', [InsuranceSuggestionController::class, 'reject'])
         ->name('insurance-suggestions.reject');
});
```

#### 4.6. Menu item (Sidebar)

Thêm vào menu:
```
Nhân sự
  ├── Nhân viên
  ├── ...
  └── Đề xuất tăng bậc BHXH [Badge: pending count]
```

---

## 📋 Checklist Hoàn Thành

### Đã xong ✅
- [x] InsuranceSalaryCalculatorService
- [x] Card BHXH trong PayrollTab
- [x] Migration insurance_grade_suggestions
- [x] Model InsuranceGradeSuggestion
- [x] Command: SuggestInsuranceGradeRaiseCommand
- [x] Command: ExpireInsuranceSuggestionsCommand
- [x] Employee relationship với insurance profiles

### Cần làm tiếp ⏳
- [ ] InsuranceSuggestionController
- [ ] InsuranceAppendixService
- [ ] Vue Page: InsuranceSuggestions/Index
- [ ] Component: ApproveModal, RejectModal
- [ ] Routes insurance-suggestions
- [ ] Menu item + badge pending count
- [ ] Tests cho toàn bộ flow

---

## 🚀 Cách Sử Dụng Hiện Tại

### 1. Khởi tạo dữ liệu

```bash
# Chạy migrations
php artisan migrate

# Seed data mẫu (lương tối thiểu vùng + thang hệ số)
php artisan db:seed --class=InsuranceSalarySystemSeeder
```

### 2. Tạo insurance profile cho nhân viên

```php
use App\Services\InsuranceSalaryService;

$service = app(InsuranceSalaryService::class);

// Khởi tạo profile cho nhân viên mới (bậc 1)
$profile = $service->initializeInsuranceProfile(
    employee: $employee,
    positionId: $position->id,
    grade: 1
);
```

### 3. Xem thông tin BHXH

Vào **Employee Profile → Tab Payroll** để xem:
- Bậc hiện tại
- Lương BHXH
- Đề xuất tăng bậc (nếu có)
- Lịch sử

### 4. Chạy command quét tăng bậc

```bash
# Dry run (xem kết quả không lưu)
php artisan insurance:suggest-grade-raise --dry-run

# Chạy thật
php artisan insurance:suggest-grade-raise
```

### 5. Setup Cron (Production)

Thêm vào `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Quét đề xuất tăng bậc (đầu tháng)
    $schedule->command('insurance:suggest-grade-raise')
             ->monthlyOn(1, '00:00')
             ->appendOutputTo(storage_path('logs/insurance-suggestions.log'));
    
    // Đánh dấu suggestions quá hạn (hàng ngày)
    $schedule->command('insurance:expire-suggestions')
             ->daily();
}
```

Cron entry:
```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📖 Documentation

Xem thêm:
- [INSURANCE_SALARY_SYSTEM_GUIDE.md](INSURANCE_SALARY_SYSTEM_GUIDE.md) - Hướng dẫn đầy đủ
- [INSURANCE_SALARY_QUICK_REF.md](INSURANCE_SALARY_QUICK_REF.md) - Quick reference

---

## 🎯 Next Steps

1. **Hoàn thiện flow approve suggestion** → tạo appendix tự động
2. **UI management cho HR** (danh sách suggestions)
3. **Notification** khi có suggestion mới
4. **Report BHXH** theo tháng/quý
5. **Export Excel** danh sách BHXH
6. **Dashboard** thống kê (số nhân viên theo bậc, chart...)

---

**Last updated:** 2025-12-25
