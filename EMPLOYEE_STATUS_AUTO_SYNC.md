# ✅ EMPLOYEE STATUS AUTO-SYNC IMPLEMENTATION

## 📋 Tổng quan

Triển khai đầy đủ logic tự động cập nhật `employee.status` dựa trên Contract và Leave lifecycle, đáp ứng 100% yêu cầu nghiệp vụ.

---

## 🎯 Ý nghĩa các trạng thái

| Status | Ý nghĩa | Icon |
|--------|---------|------|
| **ACTIVE** | Đang là nhân sự chính thức / hợp lệ | ✅ pi-check-circle |
| **ON_LEAVE** | Còn là nhân sự nhưng đang nghỉ dài hạn | ⏰ pi-clock |
| **INACTIVE** | Tạm không làm việc nhưng chưa chấm dứt | ⏸️ pi-pause-circle |
| **TERMINATED** | Đã chấm dứt quan hệ lao động | ❌ pi-times-circle |

**Nguyên tắc vàng:**
> `employee.status` = trạng thái quan hệ lao động, KHÔNG phải trạng thái hợp đồng, KHÔNG phải trạng thái đi làm hôm nay.

---

## 🔧 Các thành phần đã triển khai

### 1. **EmployeeStatusService** ⭐
**File**: `app/Services/EmployeeStatusService.php`

**Chức năng chính:**
```php
// Sync từ contracts (khi contract APPROVED/TERMINATED)
$service->syncFromContracts($employee);

// Sync từ leaves (khi leave APPROVED/ENDED)
$service->syncFromLeaves($employee);
```

**Logic quyết định:**
```
Có contract ACTIVE?
├─ YES → Có long leave đang active?
│   ├─ YES → ON_LEAVE
│   └─ NO  → ACTIVE
└─ NO  → Có contract TERMINATED?
    ├─ YES → TERMINATED
    └─ NO  → INACTIVE (chưa có HĐ hoặc chờ ký)
```

**Long leave definition:**
- Leave type: `MATERNITY`, `SICK`, `UNPAID`
- Duration: >= 30 days OR type = MATERNITY
- Status: APPROVED
- Time: start_date <= now <= end_date

---

### 2. **UpdateEmployeeStatusOnContractApproved** 🔔
**File**: `app/Listeners/UpdateEmployeeStatusOnContractApproved.php`

**Trigger**: Khi `ContractApproved` event được dispatch

**Hành động**:
- Contract APPROVED → employee.status = **ACTIVE**
- Nếu có long leave → employee.status = **ON_LEAVE**

**Auto-discovery**: Sử dụng `#[ListensTo(ContractApproved::class)]`

---

### 3. **UpdateEmployeeStatusOnLeaveApproved** 🔔
**File**: `app/Listeners/UpdateEmployeeStatusOnLeaveApproved.php`

**Trigger**: Khi `LeaveRequestApproved` event được dispatch

**Hỗ trợ cả 2 chế độ:**
- ✅ **Khai báo** (Admin/HR auto-approve): Qua `LeaveApprovalService::autoApprove()`
- ✅ **Phê duyệt** (Normal workflow): Qua `LeaveApprovalService::finalizeApproval()`

**Hành động**:
- Long leave APPROVED + đang active → employee.status = **ON_LEAVE**

**Logic kiểm tra:**
```php
$isLongLeave = $leaveRequest->days >= 30 
    || in_array($leaveTypeCode, ['MATERNITY']);

$isActive = $leaveRequest->start_date <= now() 
    && $leaveRequest->end_date >= now();
```

**Auto-discovery**: Sử dụng `#[ListensTo(LeaveRequestApproved::class)]`

---

### 4. **UpdateEmployeeStatusOnLeaveEnded** 🔔 🆕
**File**: `app/Listeners/UpdateEmployeeStatusOnLeaveEnded.php`

**Trigger**: Khi `LeaveRequestEnded` event được dispatch

**Dispatch bởi:**
- Console command `leave:end-expired` (daily cron)
- Manual cancellation của leave

**Hành động**:
- Kiểm tra còn long leave nào đang active không
- Nếu KHÔNG → employee.status = **ACTIVE**
- Nếu CÓ → employee.status giữ nguyên **ON_LEAVE**

**Auto-discovery**: Sử dụng `#[ListensTo(LeaveRequestEnded::class)]`

---

### 5. **EndEmployeeAbsenceOnLeaveEnded** 🔔 🆕
**File**: `app/Listeners/EndEmployeeAbsenceOnLeaveEnded.php`

**Trigger**: Khi `LeaveRequestEnded` event được dispatch

**Hành động**:
- Tìm `EmployeeAbsence` liên quan với leave_request_id
- Update status từ ACTIVE → **ENDED**
- Log activity

**Auto-discovery**: Sử dụng `#[ListensTo(LeaveRequestEnded::class)]`

---

### 6. **EmployeeAbsenceObserver** 👁️
**File**: `app/Observers/EmployeeAbsenceObserver.php`

**Đăng ký**: `AppServiceProvider::boot()`

**Trigger 1 - created()**: Khi tạo `EmployeeAbsence` mới (từ long leave)
- absence.status = ACTIVE + affects_insurance → Sync status

**Trigger 2 - updated()**: Khi `absence.status` chuyển sang **ENDED**
- Kiểm tra còn long leave nào khác không
- Nếu không → Quay lại ACTIVE (hoặc sync từ contract)

---

### 7. **EndExpiredLeaves Command** ⚙️ 🆕
**File**: `app/Console/Commands/EndExpiredLeaves.php`

**Command**: `php artisan leave:end-expired`

**Schedule**: Daily at 01:00 (trong `routes/console.php`)

**Chức năng:**
- Tìm tất cả leave APPROVED có end_date < today
- Dispatch `LeaveRequestEnded` event cho mỗi leave
- Event sẽ trigger 2 listeners:
  1. `EndEmployeeAbsenceOnLeaveEnded` → End absence
  2. `UpdateEmployeeStatusOnLeaveEnded` → Sync employee status

**Options:**
```bash
# Dry run (xem danh sách không thực thi)
php artisan leave:end-expired --dry-run

# Thực thi
php artisan leave:end-expired
```

---

### 8. **ContractTerminationService** (Updated) 🔄
**File**: `app/Services/ContractTerminationService.php`

**Thay đổi:**
```php
// OLD: Hardcode status = TERMINATED
$employee->update(['status' => 'TERMINATED']);

// NEW: Sử dụng service (intelligent sync)
$this->statusService->syncFromContracts($employee);
```

**Lợi ích:**
- Kiểm tra còn contract ACTIVE khác không
- Không update nếu còn HĐ khác đang hiệu lực

---

## 📊 Luồng dữ liệu tự động

### Scenario 1: Contract được approve
```
ContractApprovalService::approve()
    ↓
Contract.status = ACTIVE
    ↓
event(new ContractApproved(...))
    ↓
UpdateEmployeeStatusOnContractApproved
    ↓
EmployeeStatusService::syncFromContracts()
    ↓
employee.status = ACTIVE (nếu không có long leave)
```

### Scenario 2: Admin khai báo leave (auto-approve)
```
LeaveApprovalService::submitForApproval()
    ↓
canAutoApproveAsAdmin() = true
    ↓
LeaveApprovalService::autoApprove()
    ↓
LeaveRequest.status = APPROVED
    ↓
event(new LeaveRequestApproved(...))
    ↓
[Listener 1] CreateEmployeeAbsence
    ↓
EmployeeAbsence created (status = ACTIVE)
    ↓
[Observer] EmployeeAbsenceObserver::created()
    ↓
EmployeeStatusService::syncFromLeaves()
    ↓
employee.status = ON_LEAVE
```

### Scenario 3: Leave thông thường (approval workflow)
```
Manager/Director approve
    ↓
LeaveApprovalService::finalizeApproval()
    ↓
LeaveRequest.status = APPROVED
    ↓
event(new LeaveRequestApproved(...))
    ↓
[Listener 1] CreateEmployeeAbsence
[Listener 2] UpdateEmployeeStatusOnLeaveApproved
    ↓
employee.status = ON_LEAVE (if long leave + active)
```

### Scenario 4: Kết thúc leave (AUTO)
```
Daily Cron (01:00)
    ↓
php artisan leave:end-expired
    ↓
Find leaves with end_date < today
    ↓
For each leave:
    ↓
    event(new LeaveRequestEnded(...))
    ↓
    [Listener 1] EndEmployeeAbsenceOnLeaveEnded
        ↓
        EmployeeAbsence.status = ENDED
    ↓
    [Listener 2] UpdateEmployeeStatusOnLeaveEnded
        ↓
        EmployeeStatusService::syncFromLeaves()
        ↓
        Check: Còn long leave khác?
        ├─ YES → Giữ nguyên ON_LEAVE
        └─ NO  → syncFromContracts() → ACTIVE
```

### Scenario 5: Contract bị chấm dứt
```
ContractTerminationService::terminateContract()
    ↓
Contract.status = TERMINATED
    ↓
Check: Còn contract ACTIVE khác?
├─ YES → ems:**
- `test_employee_status_service.php` - Test service logic
- `test_leave_end.php` - Test leave end event 🆕

**Commands:**
```bash
# Test service
php test_employee_status_service.php

# Test leave end
php test_leave_end.php

# Test command (dry-run)
php artisan leave:end-expired --dry-run

# Execute command
php artisan leave:end-expired
```

**Kết quả:**
```bash
php test_employee_status_service.php
```

✅ Test 1: Employee với ACTIVE contract → status = ACTIVE  
✅ Test 2: Employee với long leave → status = ON_LEAVE  
✅ Test 3: Employee TERMINATED → status = TERMINATED

```bashUpdateEmployeeStatusOnLeaveEnded` listener 🆕
- [x] Tạo `EndEmployeeAbsenceOnLeaveEnded` listener 🆕
- [x] Tạo `EmployeeAbsenceObserver` observer
- [x] Tạo `EndExpiredLeaves` command 🆕
- [x] Schedule command trong `routes/console.php` 🆕
- [x] Đăng ký observer trong `AppServiceProvider`
- [x] Update `ContractTerminationService` sử dụng service
- [x] Chạy `composer dump-autoload`
- [x] Test service hoạt động
- [x] Test leave end event 🆕spatched  
✅ EmployeeAbsence status → ENDED  
✅ Employee status synced (ACTIVE if no other leaves)
**Test file**: `test_employee_status_service.php`

**Kết quả:**
```bash
php test_employee_status_service.php
```

✅ Test 1: Employee với ACTIVE contract → status = ACTIVE  
✅ Test 2: Employee với long leave → status = ON_LEAVE  
✅ Test 3: Employee TERMINATED → status = TERMINATED  

---

## 🚀 Deployment Checklist

- [x] Tạo `EmployeeStatusService`
- [x] Tạo `UpdateEmployeeStatusOnContractApproved` listener
- [x] Tạo `UpdateEmployeeStatusOnLeaveApproved` listener
- [x] Tạo `EmployeeAbsenceObserver` observer
- [x] Đăng ký observer trong `AppServiceProvider`
- [x] Update `ContractTerminationService` sử dụng service
- [x] Chạy `composer dump-autoload`
- [x] Test service hoạt động

---

## 📝 Lưu ý quan trọng

### 1. Auto-discovery Listeners
Listeners sử dụng attribute `#[ListensTo()]` nên **KHÔNG CẦN** đăng ký thủ công trong `EventServiceProvider`.

Laravel tự động phát hiện qua:
```php
#[ListensTo(ContractApproved::class)]
class UpdateEmployeeStatusOnContractApproved
```

### 2. Observer Registration
Observer **PHẢI** đăng ký trong `AppServiceProvider::boot()`:
```php
EmployeeAbsence::observe(EmployeeAbsenceObserver::class);
```

### 3. Leave Auto-Approve cho Admin
Logic đã có sẵn trong `LeaveApprovalService`:
```php
protected function canAutoApproveAsAdmin(User $user): bool
{
    return $user->hasAnyRole(['Admin', 'Super Admin']);
}
```

Event `LeaveRequestApproved` được dispatch cho **CẢ 2 trường hợp**:
- Khai báo (auto-approve)
- Phê duyệt (manual approval)

→ Listener hoạt động đúng cho cả 2!

### 4. Activity Log
Mỗi lần update status, tự động tạo activity log:
```php
activity()
    ->useLog('employee-status')
    ->performedOn($employee)
    ->withProperties([...])
    ->log("Cập nhật trạng thái nhân viên: {$old} → {$new}");
```

---
Events:**
- `app/Events/LeaveRequestApproved.php` (existing)
- `app/Events/LeaveRequestEnded.php` 🆕

**Listeners:**
- `app/Listeners/UpdateEmployeeStatusOnContractApproved.php` 🆕
- `app/Listeners/UpdateEmployeeStatusOnLeaveApproved.php` 🆕
- `app/Listeners/UpdateEmployeeStatusOnLeaveEnded.php` 🆕
- `app/Listeners/EndEmployeeAbsenceOnLeaveEnded.php` 🆕
- `app/Listeners/CreateEmployeeAbsence.php` (existing)
- `app/Listeners/CreateEmploymentPeriod.php` (existing)

**Observers:**
- `app/Observers/EmployeeAbsenceObserver.php` 🆕
- `app/Observers/ContractObserver.php` (existing)

**Commands:**
- `app/Console/Commands/EndExpiredLeaves.php` 🆕

**Enums:**
- `app/Enums/EmployeeStatus.php` (existing)

**Provider:**
- `app/Providers/AppServiceProvider.php` (updated)

**Schedule:****TỰ ĐỘNG** (daily cron) → **ACTIVE** 🆕
5. ✅ Không có contract → **INACTIVE**

**Hỗ trợ đầy đủ:**
- ✅ Leave khai báo (Admin auto-approve)
- ✅ Leave phê duyệt (workflow thường)
- ✅ Leave kết thúc tự động (cronjob) 🆕
- ✅ Multiple contracts (kiểm tra còn HĐ ACTIVE khác)
- ✅ Multiple leaves (kiểm tra còn leave dài hạn khác)
- ✅ Activity logging
- ✅ Error handling
- ✅ Dry-run mode cho testing 🆕
## 📚 Related Files

**Services:**
- `app/Services/EmployeeStatusService.php` ⭐
- `app/Services/ContractTerminationService.php` (updated)
- `app/Services/LeaveApprovalService.php` (existing)

**Listeners:**
- `app/Listeners/UpdateEmployeeStatusOnContractApproved.php` 🆕
- `app/Listeners/UpdateEmployeeStatusOnLeaveApproved.php` 🆕
- `app/Listeners/CreateEmployeeAbsence.php` (existing)
- `app/Listeners/CreateEmploymentPeriod.php` (existing)

**Observers:**
- `app/Observers/EmployeeAbsenceObserver.php` 🆕
- `app/Observers/ContractObserver.php` (existing)

**Enums:**
- `app/Enums/EmployeeStatus.php` (existing)

**Provider:**
- `app/Providers/AppServiceProvider.php` (updated)

---

## ✅ Kết luận

Hệ thống đã triển khai **HOÀN CHỈNH** logic tự động cập nhật `employee.status` theo đúng yêu cầu nghiệp vụ:

1. ✅ Contract ACTIVE → employee.status = **ACTIVE**
2. ✅ Contract TERMINATED (không còn HĐ khác) → **TERMINATED**
3. ✅ Long leave APPROVED → **ON_LEAVE**
4. ✅ Leave kết thúc (không còn leave dài hạn khác) → **ACTIVE**
5. ✅ Không có contract → **INACTIVE**

**Hỗ trợ đầy đủ:**
- ✅ Leave khai báo (Admin auto-approve)
- ✅ Leave phê duyệt (workflow thường)
- ✅ Multiple contracts (kiểm tra còn HĐ ACTIVE khác)
- ✅ Multiple leaves (kiểm tra còn leave dài hạn khác)
- ✅ Activity logging
- ✅ Error handling

**Architecture chuẩn:**
- Service-based logic
- Event-driven updates
- Observer pattern for model changes
- Single responsibility principle
- Easy to test and maintain

🎉 **READY FOR PRODUCTION!**
