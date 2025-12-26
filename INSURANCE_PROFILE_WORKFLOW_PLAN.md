# 📋 KẾ HOẠCH TRIỂN KHAI: EmployeeInsuranceProfile Workflow

## 🎯 Mục tiêu

Triển khai logic CRUD gián tiếp cho EmployeeInsuranceProfile theo chuẩn HRM enterprise:
- ❌ KHÔNG CRUD trực tiếp
- ✅ CRUD gián tiếp qua Contract & ContractAppendix
- ✅ Audit trail đầy đủ
- ✅ Versioned (không update, chỉ insert)

---

## ✅ HIỆN TRẠNG (Đã có)

### 1. Database Schema ✓
- Migration `2025_12_25_000003_create_employee_insurance_profiles_table.php`
- Fields: employee_id, position_id, grade, applied_from/to, reason, source_appendix_id
- Indexes hợp lý, FK constraints
- **Status**: ✅ HOÀN THIỆN

### 2. Model EmployeeInsuranceProfile ✓  
- Relationships: employee, position, sourceAppendix
- Scopes: current(), atDate()
- Method: calculateInsuranceSalary()
- **Status**: ✅ HOÀN THIỆN

### 3. InsuranceSalaryService ✓
- `initializeInsuranceProfile()` - tạo profile ban đầu
- `raiseGrade()` - tăng bậc (transaction: close old + create new)
- `calculateInsuranceSalary()` - tính lương BHXH
- `suggestGradeRaise()` - gợi ý tăng bậc
- `getInsuranceHistory()` - lịch sử
- **Status**: ✅ HOÀN THIỆN

### 4. ContractAppendix Approval Workflow ✓
- Controller có action: `approve()`, `reject()`, `submitForApproval()`
- Event: `AppendixApproved`, `AppendixRejected`
- Status transitions: DRAFT → PENDING_APPROVAL → ACTIVE/REJECTED
- **Status**: ✅ HOÀN THIỆN

---

## ❌ THIẾU (Cần triển khai)

### 🔴 CRITICAL: Không có hook tự động tạo/cập nhật InsuranceProfile

**Luồng hiện tại:**
1. User tạo Contract → status ACTIVE → ❌ KHÔNG tạo InsuranceProfile
2. User approve Appendix SALARY → status ACTIVE → ❌ KHÔNG cập nhật InsuranceProfile
3. User approve Appendix POSITION → status ACTIVE → ❌ KHÔNG cập nhật InsuranceProfile

**Hậu quả:**
- InsuranceProfile hoàn toàn manual
- Không đồng bộ với Contract/Appendix
- Mất audit trail
- Payroll/BHXH không có dữ liệu

---

## 🚀 KẾ HOẠCH TRIỂN KHAI

### **Phase 1: Tạo EmployeeInsuranceProfileService** 

**File**: `app/Services/EmployeeInsuranceProfileService.php`

**Methods cần có:**

```php
class EmployeeInsuranceProfileService
{
    /**
     * Tạo profile từ Contract khi ACTIVE
     * 
     * Trigger: Contract status → ACTIVE
     * Source: CONTRACT
     */
    public function createProfileFromContract(Contract $contract): ?EmployeeInsuranceProfile
    {
        // Skip if contract doesn't have insurance_salary or position
        // Check if profile already exists for this contract
        // Create profile with:
        //   - employee_id from contract
        //   - position_id from contract
        //   - grade: detect from insurance_salary + position grades
        //   - applied_from: contract start_date
        //   - applied_to: NULL (đang active)
        //   - reason: INITIAL (nếu là contract đầu) hoặc POSITION_CHANGE
        //   - source_appendix_id: NULL (vì từ contract chính)
    }

    /**
     * Cập nhật profile từ Appendix SALARY khi ACTIVE
     * 
     * Trigger: Appendix (type=SALARY) status → ACTIVE
     * Source: APPENDIX
     */
    public function updateProfileFromSalaryAppendix(ContractAppendix $appendix): ?EmployeeInsuranceProfile
    {
        // Validate: appendix_type = SALARY
        // Get contract and employee
        // Calculate new grade from insurance_salary
        // Transaction:
        //   - Close current profile (set applied_to)
        //   - Create new profile with new grade
        //   - reason: SENIORITY/ADJUSTMENT (tùy nguồn gốc)
        //   - source_appendix_id: appendix.id
    }

    /**
     * Cập nhật profile từ Appendix POSITION khi ACTIVE
     * 
     * Trigger: Appendix (type=POSITION) status → ACTIVE
     * Source: APPENDIX
     */
    public function updateProfileFromPositionAppendix(ContractAppendix $appendix): ?EmployeeInsuranceProfile
    {
        // Validate: appendix_type = POSITION
        // Get contract and employee
        // Detect grade:
        //   - Option 1: Keep current grade (chuyển vị trí ngang bậc)
        //   - Option 2: Reset to grade 1 (chuyển vị trí mới)
        //   - Option 3: Read from appendix.insurance_salary
        // Transaction:
        //   - Close current profile
        //   - Create new profile with new position_id
        //   - reason: POSITION_CHANGE/PROMOTION
        //   - source_appendix_id: appendix.id
    }

    /**
     * Xử lý khi Contract EXPIRED/CANCELLED
     * 
     * Trigger: Contract status → EXPIRED/CANCELLED
     */
    public function closeProfileOnContractEnd(Contract $contract): void
    {
        // Find current profile for employee
        // Set applied_to = contract.end_date or contract.terminated_at
    }

    /**
     * Backfill profile cho Contract LEGACY
     * 
     * Trigger: Manual or Command
     */
    public function backfillProfileFromLegacyContract(Contract $contract): ?EmployeeInsuranceProfile
    {
        // Similar to createProfileFromContract
        // reason: BACKFILL
        // applied_from: contract.start_date (quá khứ)
        // applied_to: contract.end_date hoặc NULL
    }
}
```

---

### **Phase 2: Tạo Listener cho ContractApproved Event**

**File**: `app/Listeners/CreateInsuranceProfileOnContractApproved.php`

```php
use App\Events\ContractApproved;
use App\Services\EmployeeInsuranceProfileService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

#[ListensTo(ContractApproved::class)]
class CreateInsuranceProfileOnContractApproved implements ShouldQueue
{
    public function __construct(
        protected EmployeeInsuranceProfileService $service
    ) {}

    public function handle(ContractApproved $event): void
    {
        $contract = $event->contract;

        try {
            $this->service->createProfileFromContract($contract);
            
            Log::info("InsuranceProfile created from contract", [
                'contract_id' => $contract->id,
                'employee_id' => $contract->employee_id,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create InsuranceProfile from contract", [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

**Notes:**
- Dùng `ShouldQueue` để không block approval workflow
- Log đầy đủ để debug
- Try-catch để không làm crash hệ thống chính

---

### **Phase 3: Tạo Listener cho AppendixApproved Event**

**File**: `app/Listeners/UpdateInsuranceProfileOnAppendixApproved.php`

```php
use App\Events\AppendixApproved;
use App\Services\EmployeeInsuranceProfileService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

#[ListensTo(AppendixApproved::class)]
class UpdateInsuranceProfileOnAppendixApproved implements ShouldQueue
{
    public function __construct(
        protected EmployeeInsuranceProfileService $service
    ) {}

    public function handle(AppendixApproved $event): void
    {
        $appendix = $event->appendix;

        try {
            // Dispatch dựa vào appendix_type
            match($appendix->appendix_type->value) {
                'SALARY' => $this->service->updateProfileFromSalaryAppendix($appendix),
                'POSITION' => $this->service->updateProfileFromPositionAppendix($appendix),
                default => null, // ALLOWANCE, DEPARTMENT... không ảnh hưởng BHXH
            };

            Log::info("InsuranceProfile updated from appendix", [
                'appendix_id' => $appendix->id,
                'appendix_type' => $appendix->appendix_type->value,
                'contract_id' => $appendix->contract_id,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update InsuranceProfile from appendix", [
                'appendix_id' => $appendix->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

---

### **Phase 4: Hook vào ContractObserver**

**File**: `app/Observers/ContractObserver.php`

**Thêm method:**

```php
/**
 * Handle Contract "updated" event - Close insurance profile when contract ends
 */
public function updated(Contract $contract): void
{
    // Kiểm tra nếu status chuyển sang EXPIRED/CANCELLED
    if ($contract->isDirty('status') && 
        in_array($contract->status, ['EXPIRED', 'CANCELLED'])) {
        
        try {
            $insuranceService = app(EmployeeInsuranceProfileService::class);
            $insuranceService->closeProfileOnContractEnd($contract);
            
            Log::info("InsuranceProfile closed on contract end", [
                'contract_id' => $contract->id,
                'status' => $contract->status,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to close InsuranceProfile", [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

---

### **Phase 5: Backfill Command**

**File**: `app/Console/Commands/BackfillInsuranceProfilesCommand.php`

```php
use Illuminate\Console\Command;
use App\Models\Contract;
use App\Services\EmployeeInsuranceProfileService;

class BackfillInsuranceProfilesCommand extends Command
{
    protected $signature = 'insurance:backfill-profiles 
                            {--dry-run : Preview without creating}
                            {--employee= : Specific employee ID}';

    protected $description = 'Backfill insurance profiles from existing contracts';

    public function handle(EmployeeInsuranceProfileService $service)
    {
        $query = Contract::where('status', 'ACTIVE')
            ->whereDoesntHave('employee.insuranceProfiles');

        if ($employeeId = $this->option('employee')) {
            $query->where('employee_id', $employeeId);
        }

        $contracts = $query->with('employee')->get();
        $this->info("Found {$contracts->count()} contracts without insurance profiles");

        foreach ($contracts as $contract) {
            if ($this->option('dry-run')) {
                $this->line("Would create profile for: {$contract->employee->full_name}");
                continue;
            }

            try {
                $service->backfillProfileFromLegacyContract($contract);
                $this->info("✓ Created profile for: {$contract->employee->full_name}");
            } catch (\Exception $e) {
                $this->error("✗ Failed for: {$contract->employee->full_name} - {$e->getMessage()}");
            }
        }
    }
}
```

---

## 📊 TESTING PLAN

### Test Case 1: Contract ACTIVE → Tạo Profile
```php
// Given: Contract mới được approve
$contract = Contract::factory()->create([
    'status' => 'PENDING_APPROVAL',
    'insurance_salary' => 10000000,
    'position_id' => $position->id,
]);

// When: Approve contract
$approvalService->approve($contract, $director);

// Then: Profile được tạo
$profile = EmployeeInsuranceProfile::where('employee_id', $contract->employee_id)->first();
$this->assertNotNull($profile);
$this->assertEquals($contract->position_id, $profile->position_id);
$this->assertNull($profile->applied_to); // Đang active
```

### Test Case 2: Appendix SALARY ACTIVE → Cập nhật Profile
```php
// Given: Appendix tăng lương BHXH
$appendix = ContractAppendix::factory()->create([
    'appendix_type' => 'SALARY',
    'status' => 'PENDING_APPROVAL',
    'insurance_salary' => 12000000, // Tăng từ 10M lên 12M
]);

// When: Approve appendix
$appendixController->approve($appendix);

// Then: Profile cũ bị đóng, profile mới được tạo
$oldProfile = EmployeeInsuranceProfile::where('employee_id', $employee->id)
    ->whereNotNull('applied_to')
    ->latest()
    ->first();
$this->assertNotNull($oldProfile->applied_to);

$newProfile = $employee->currentInsuranceProfile;
$this->assertEquals(3, $newProfile->grade); // Grade tăng
$this->assertEquals($appendix->id, $newProfile->source_appendix_id);
```

### Test Case 3: Appendix POSITION ACTIVE → Chuyển vị trí
```php
// Given: Appendix chuyển chức danh
$newPosition = Position::factory()->create();
$appendix = ContractAppendix::factory()->create([
    'appendix_type' => 'POSITION',
    'status' => 'PENDING_APPROVAL',
    'position_id' => $newPosition->id,
]);

// When: Approve appendix
$appendixController->approve($appendix);

// Then: Profile có position_id mới
$newProfile = $employee->currentInsuranceProfile;
$this->assertEquals($newPosition->id, $newProfile->position_id);
$this->assertEquals('POSITION_CHANGE', $newProfile->reason);
```

---

## 🎯 SUCCESS CRITERIA

### Must Have
- [ ] Contract ACTIVE → Tự động tạo InsuranceProfile
- [ ] Appendix SALARY ACTIVE → Tự động cập nhật grade
- [ ] Appendix POSITION ACTIVE → Tự động chuyển position
- [ ] Contract END → Tự động đóng profile (set applied_to)
- [ ] Profile cũ luôn bị đóng trước khi tạo mới (versioning)
- [ ] source_appendix_id luôn được ghi nhận
- [ ] Backfill command hoạt động

### Should Have
- [ ] Listener queue-based (không block main flow)
- [ ] Comprehensive logging
- [ ] Error handling không crash system
- [ ] Test coverage > 80%

### Nice to Have
- [ ] UI hiển thị audit trail (Contract/Appendix → Profile)
- [ ] Dashboard: "Profiles without source"
- [ ] Validation: Prevent manual profile creation

---

## 📝 IMPLEMENTATION ORDER

1. ✅ **Day 1**: Tạo `EmployeeInsuranceProfileService` với 5 methods
2. ✅ **Day 2**: Tạo 2 Listeners (ContractApproved, AppendixApproved)
3. ✅ **Day 3**: Hook vào ContractObserver (updated event)
4. ✅ **Day 4**: Tạo BackfillCommand + Test manual
5. ✅ **Day 5**: Viết test cases + Fix bugs
6. ✅ **Day 6**: Backfill dữ liệu production

---

## ⚠️ RISKS & MITIGATION

### Risk 1: Queue job failure
**Impact**: Profile không được tạo/cập nhật
**Mitigation**: 
- Implement retry logic (3 attempts)
- Failed job monitoring
- Manual trigger button in UI

### Risk 2: Grade detection sai
**Impact**: Profile có grade không đúng
**Mitigation**:
- Validate insurance_salary vs position grades
- Log warning nếu không match
- Allow manual override

### Risk 3: Backfill conflict
**Impact**: Tạo duplicate profiles
**Mitigation**:
- Check existing profiles trước khi tạo
- Unique constraint: [employee_id, applied_to=NULL]
- Dry-run mode

---

## 📚 DOCUMENTATION NEEDED

1. **API Documentation**: Service methods với params/returns
2. **Flow Diagram**: Contract → Profile workflow
3. **User Guide**: Cách backfill dữ liệu cũ
4. **Troubleshooting**: Common errors và cách fix

---

## ✅ DEFINITION OF DONE

- [ ] Code review passed
- [ ] All tests green (unit + integration)
- [ ] Documentation complete
- [ ] Backfill command tested on staging
- [ ] Production data backfilled successfully
- [ ] No manual InsuranceProfile CRUD in codebase
- [ ] Payroll/BHXH có dữ liệu đầy đủ

---

**Người thực hiện**: AI Assistant + Dev Team  
**Ước lượng**: 6 ngày (1 developer)  
**Priority**: 🔴 HIGH (blocking Payroll/BHXH features)
