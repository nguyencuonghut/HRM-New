# Insurance Auto-Sync Implementation

## Tổng quan

Đã implement hệ thống tự động sync dữ liệu bảo hiểm từ các workflow nghiệp vụ khác (Contract, Leave).

## Event Listeners đã tạo

### 1. **CreateInsuranceParticipation**
- **Trigger**: Khi Contract được APPROVED
- **Action**: Tạo bản ghi `insurance_participations` với status ACTIVE
- **Điều kiện**: Contract phải có `insurance_salary > 0`
- **File**: `app/Listeners/CreateInsuranceParticipation.php`

### 2. **UpdateInsuranceParticipation**  
- **Trigger**: Khi Appendix (type SALARY) được APPROVED
- **Action**: Cập nhật `insurance_participations` với lương mới hoặc tạo mới nếu chưa có
- **Điều kiện**: Appendix type = 'SALARY' và có `insurance_salary > 0`
- **File**: `app/Listeners/UpdateInsuranceParticipation.php`

### 3. **TerminateInsuranceParticipation**
- **Trigger**: Khi Contract bị TERMINATED
- **Action**: Update status = TERMINATED và set `participation_end_date`
- **File**: `app/Listeners/TerminateInsuranceParticipation.php`

### 4. **CreateEmployeeAbsence**
- **Trigger**: Khi LeaveRequest được APPROVED (fully approved)
- **Action**: Tạo `employee_absences` nếu nghỉ >30 ngày hoặc là MATERNITY
- **Điều kiện**: Leave type IN (MATERNITY, SICK, UNPAID) AND (days > 30 OR type = MATERNITY)
- **File**: `app/Listeners/CreateEmployeeAbsence.php`

## Event đã tạo mới

### **LeaveRequestApproved**
- Dispatch từ: `LeaveApprovalService::finalizeApproval()`
- Khi nào: Leave request được fully approved (tất cả steps APPROVED)
- File: `app/Events/LeaveRequestApproved.php`

## Luồng dữ liệu tự động

```
Contract APPROVED
    ↓
CreateInsuranceParticipation Listener
    ↓
insurance_participations (ACTIVE)

---

Appendix SALARY APPROVED  
    ↓
UpdateInsuranceParticipation Listener
    ↓
insurance_participations (updated salary)

---

Contract TERMINATED
    ↓
TerminateInsuranceParticipation Listener
    ↓
insurance_participations (status = TERMINATED)

---

LeaveRequest APPROVED (>30 days)
    ↓
LeaveRequestApproved Event
    ↓
CreateEmployeeAbsence Listener
    ↓
employee_absences (ACTIVE)
```

## Luồng tạo báo cáo BH

1. **Admin tạo monthly report** (tháng 12/2025)
2. **InsuranceCalculationService** tự động detect:
   - INCREASE: Từ contracts mới (NEW_HIRE) + trở lại làm việc (RETURN_TO_WORK)
   - DECREASE: Từ termination + employee_absences (>30 days)
   - ADJUST: Từ contract_appendixes (SALARY type)
3. **Tạo insurance_change_records** với status PENDING
4. **Admin duyệt/từ chối/điều chỉnh** từng record
5. **Finalize** báo cáo
6. **Export Excel**

## Migration đã có

- ✅ `insurance_participations` - Lịch sử tham gia BH
- ✅ `insurance_monthly_reports` - Báo cáo tháng
- ✅ `insurance_change_records` - Chi tiết thay đổi (TĂNG/GIẢM/ĐIỀU CHỈNH)
- ✅ `employee_absences` - Nghỉ dài hạn ảnh hưởng BH

## Testing

### Test Contract → Insurance Participation:
```bash
# 1. Tạo contract mới
# 2. Approve contract
# 3. Check: insurance_participations có record mới với status ACTIVE
```

### Test Appendix → Update Participation:
```bash
# 1. Tạo appendix salary
# 2. Approve appendix
# 3. Check: insurance_participations có update lương mới
```

### Test Leave → Employee Absence:
```bash
# 1. Tạo leave request SICK >30 ngày
# 2. Approve leave (fully approved)
# 3. Check: employee_absences có record mới với type SICK_LONG
```

### Test Monthly Report:
```bash
# 1. Đã có contract approved, appendix approved, leave approved
# 2. Tạo monthly report tháng hiện tại
# 3. Check: Auto-detect INCREASE, DECREASE, ADJUST records
# 4. Approve các records
# 5. Finalize report
# 6. Export Excel
```

## Activity Log

Tất cả các listeners đều log activity với log_name:
- `insurance-participation` - Cho các thay đổi participation
- `employee-absence` - Cho các nghỉ dài hạn

## Đặc điểm kỹ thuật

1. **Auto-discovery**: Sử dụng `#[ListensTo]` attribute (Laravel 11)
2. **After Commit**: Implement `ShouldHandleEventsAfterCommit` để đảm bảo data consistency
3. **Idempotent**: Check exists trước khi create để tránh duplicate
4. **Error Handling**: Try-catch với logging, không throw exception để không break workflow chính
5. **Activity Logging**: Log tất cả các thao tác quan trọng

## Kết luận

✅ Dữ liệu BH giờ được tự động sync từ:
- Contract workflow
- Leave workflow
- Termination workflow

❌ Không cần seeder nữa (trừ test data)
✅ Data realtime, không cần manual entry
