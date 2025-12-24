# Employee Status Data Integrity Fix

## Vấn đề (Problem)

Nhân viên **Bùi Thế Tuyến** (employee_code: 2571) có status `ACTIVE` (Đang làm việc) nhưng:
- ❌ Không có contract nào
- ❌ Không có employment nào  
- ❌ Không có leave request nào

**Root Cause**: EmployeeSeeder tạo employee với status='ACTIVE' thủ công, không tuân thủ business rule.

## Business Rules

Employee status phải được **derived** (suy ra) từ Contract và LeaveRequest:

```
1. Không có contract ACTIVE → status = INACTIVE
2. Có contract ACTIVE + không có long leave → status = ACTIVE  
3. Có contract ACTIVE + có long leave đang active → status = ON_LEAVE
4. Contract bị terminated → status = TERMINATED
```

## Giải pháp (Solutions Implemented)

### 1. Fix Seeder Data (Ngắn hạn)

**File**: `database/seeders/EmployeeSeeder.php`

**Changed**:
```php
// Before (SAI)
'status'=>'ACTIVE',  // Nhân viên chưa có contract mà đã ACTIVE

// After (ĐÚNG)
'status'=>'INACTIVE',  // Chưa có contract → INACTIVE
```

### 2. Enhanced EmployeeObserver (Dài hạn)

**File**: `app/Observers/EmployeeObserver.php`

**Added Methods**:

#### `creating()` - Prevent Invalid Initial Status
```php
public function creating(Employee $employee): void
{
    // Default to INACTIVE if not set
    if (!$employee->status) {
        $employee->status = 'INACTIVE';
    }
    
    // Log warning if manually set to ACTIVE
    if ($employee->status === 'ACTIVE') {
        Log::warning('Employee being created with ACTIVE status - should be set via contract');
    }
}
```

#### `updating()` - Audit Status Changes
```php
public function updating(Employee $employee): void
{
    // Track manual status changes for audit
    if ($employee->isDirty('status')) {
        Log::info('Employee status being changed', [
            'old_status' => $employee->getOriginal('status'),
            'new_status' => $employee->status,
        ]);
    }
}
```

#### `saved()` - Auto-Correct Inconsistencies
```php
public function saved(Employee $employee): void
{
    // Verify status consistency after save
    $hasActiveContract = $employee->contracts()->where('status', 'ACTIVE')->exists();
    $hasActiveLongLeave = $this->statusService->hasActiveLongLeave($employee->id);
    
    // Calculate expected status
    $expectedStatus = 'INACTIVE';
    if ($hasActiveContract) {
        $expectedStatus = $hasActiveLongLeave ? 'ON_LEAVE' : 'ACTIVE';
    }
    
    // Auto-fix if inconsistent
    if ($employee->status !== $expectedStatus) {
        Log::warning('Employee status inconsistent - auto-correcting');
        $employee->updateQuietly(['status' => $expectedStatus]);
    }
}
```

## Testing

### Before Fix
```bash
php check_employee_status.php
# Output:
# Status hiện tại: ACTIVE (Đang làm việc)  ← SAI!
# Tổng số contract: 0
```

### After Fix
```bash
php artisan migrate:refresh --seed
php check_employee_status.php
# Output:
# Status hiện tại: INACTIVE (Ngừng hoạt động)  ← ĐÚNG!
# Tổng số contract: 0
```

## How It Works

### 1. During Seed
```
EmployeeSeeder creates employee
    ↓
EmployeeObserver::creating() triggered
    ↓
Check if status = ACTIVE without contract
    ↓
Log warning (audit trail)
    ↓
Employee saved with INACTIVE
    ↓
EmployeeObserver::saved() triggered
    ↓
Verify consistency with contracts/leaves
    ↓
✅ Status = INACTIVE (correct, no contracts)
```

### 2. When Creating Contract
```
Contract created with status=ACTIVE
    ↓
ContractObserver::saved() triggered
    ↓
Call EmployeeStatusService::syncFromContracts()
    ↓
Check employee contracts
    ↓
Found ACTIVE contract → Update employee.status = ACTIVE
    ↓
EmployeeObserver::saved() triggered
    ↓
Verify: hasActiveContract=true, hasActiveLongLeave=false
    ↓
Expected status = ACTIVE
    ↓
✅ Actual status = ACTIVE (consistent)
```

### 3. When Creating Long Leave
```
LeaveRequest approved with days >= 30
    ↓
UpdateEmployeeStatusOnLeaveApproved listener triggered
    ↓
Call EmployeeStatusService::syncFromLeaves()
    ↓
Found active long leave → Update employee.status = ON_LEAVE
    ↓
EmployeeObserver::saved() triggered
    ↓
Verify: hasActiveContract=true, hasActiveLongLeave=true
    ↓
Expected status = ON_LEAVE
    ↓
✅ Actual status = ON_LEAVE (consistent)
```

### 4. Auto-Correction Example
```
Someone manually updates employee.status = ACTIVE (bypassing service)
    ↓
EmployeeObserver::updating() triggered
    ↓
Log warning: "Manual status change detected"
    ↓
EmployeeObserver::saved() triggered
    ↓
Check contracts: no ACTIVE contracts found
    ↓
Expected status = INACTIVE
    ↓
Actual status = ACTIVE (inconsistent!)
    ↓
Log warning: "Status inconsistent - auto-correcting"
    ↓
employee->updateQuietly(['status' => 'INACTIVE'])
    ↓
✅ Status auto-corrected to INACTIVE
```

## Integration Points

### Observer Registration
EmployeeObserver đã được register trong `AppServiceProvider.php`:
```php
Employee::observe(EmployeeObserver::class);
```

### Service Injection
EmployeeObserver nhận `EmployeeStatusService` qua constructor injection:
```php
public function __construct(
    protected EmployeeStatusService $statusService
) {}
```

### Related Components
- ✅ **EmployeeStatusService** - Core business logic for status calculation
- ✅ **ContractObserver** - Syncs status when contracts change
- ✅ **UpdateEmployeeStatusOnContractApproved** - Handles contract approval
- ✅ **UpdateEmployeeStatusOnLeaveApproved** - Handles long leave approval
- ✅ **UpdateEmployeeStatusOnLeaveEnded** - Handles leave end
- ✅ **EmployeeObserver** - Validates and auto-corrects status

## Prevention Strategy

### ❌ DON'T
```php
// NEVER update employee status directly
$employee->update(['status' => 'ACTIVE']); // ❌ Bypasses business logic
Employee::where('id', $id)->update(['status' => 'ACTIVE']); // ❌ Bypasses observer
```

### ✅ DO
```php
// Use EmployeeStatusService
$statusService->syncFromContracts($employeeId); // ✅ Follows business rules
$statusService->syncFromLeaves($employeeId); // ✅ Follows business rules

// Or trigger via events (preferred)
event(new ContractApproved($contract)); // ✅ Event-driven
event(new LeaveRequestApproved($leaveRequest)); // ✅ Event-driven
```

## Benefits

1. **Data Integrity** - Status luôn consistent với contracts và leaves
2. **Audit Trail** - Mọi status change đều được log
3. **Auto-Correction** - Tự động sửa lỗi data inconsistency
4. **Prevention** - Cảnh báo khi có attempt tạo employee với status không hợp lệ
5. **Maintainability** - Centralized business logic trong Service và Observer

## Related Documentation

- [EMPLOYEE_STATUS_AUTO_SYNC.md](./EMPLOYEE_STATUS_AUTO_SYNC.md) - Employee status auto-sync system
- [CONTRACT_APPROVAL_WORKFLOW.md](./CONTRACT_APPROVAL_WORKFLOW.md) - Contract approval flow
- [LEAVE_SYSTEM_COMPLETE.md](./LEAVE_SYSTEM_COMPLETE.md) - Leave management system

## Conclusion

Vấn đề **'Bùi Thế Tuyến' chưa có Contract sao status lại là 'Đang làm việc'** đã được fix bằng:

1. ✅ Sửa seeder data (ACTIVE → INACTIVE)
2. ✅ Thêm validation trong EmployeeObserver
3. ✅ Auto-correction cho data inconsistencies
4. ✅ Audit logging cho status changes

Giờ đây employee status sẽ **LUÔN** được derived từ contracts và leaves, đảm bảo data integrity.
