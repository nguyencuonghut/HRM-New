# KẾ HOẠCH TRIỂN KHAI BẢO HIỂM XÃ HỘI

## 📋 TỔNG QUAN

### Yêu cầu chính
1. ✅ **Leave Types**: Đã đủ (có Maternity 180 ngày)
2. 🎯 **Báo cáo BH tháng**: Hệ thống tự tính → Admin duyệt từng row
3. 📄 **Excel Export**: Theo template của anh (chờ file)
4. 🗓️ **Calendar View**: Hiển thị all employees
5. 🔐 **Permissions**: Tất cả roles trừ Manager

---

## 📊 DATABASE DESIGN

### 4 Tables chính

#### 1. `insurance_participations`
- **Mục đích**: Lịch sử tham gia BH của nhân viên
- **Key fields**:
  - `insurance_salary`: Lương đóng BH (snapshot từ Contract/Appendix)
  - `has_social/health/unemployment_insurance`: 3 loại BH
  - `status`: ACTIVE/SUSPENDED/TERMINATED
  - Link: contract_id, contract_appendix_id

#### 2. `insurance_monthly_reports`
- **Mục đích**: Báo cáo tổng hợp theo tháng
- **Key fields**:
  - `year`, `month`: Tháng báo cáo (unique)
  - `total_increase/decrease/adjust`: Số lượng thay đổi
  - `approved_*`: Số đã được Admin duyệt
  - `status`: DRAFT → FINALIZED (không sửa được)
  - `export_file_path`: File Excel đã xuất

#### 3. `insurance_change_records` ⭐
- **Mục đích**: Chi tiết TỪNG thay đổi - PHẢI DUYỆT
- **Key fields**:
  - `change_type`: INCREASE/DECREASE/ADJUST
  - `auto_reason`: Lý do hệ thống phát hiện
    - NEW_HIRE, TERMINATION, LONG_ABSENCE, SALARY_CHANGE...
  - `approval_status`: PENDING → APPROVED/REJECTED/ADJUSTED
  - `approved_by`, `admin_notes`: Admin duyệt và ghi chú
  - `adjusted_salary`: Admin có thể SỬA số liệu
  - `effective_date`: Ngày có hiệu lực

#### 4. `employee_absences`
- **Mục đích**: Nghỉ dài hạn >30 ngày (ảnh hưởng BH)
- **Key fields**:
  - `absence_type`: MATERNITY, SICK_LONG, UNPAID_LONG...
  - `affects_insurance`: true nếu >30 ngày
  - `leave_request_id`: Link với Leave Request
  - `status`: PENDING → APPROVED → ACTIVE → ENDED

---

## 🔄 WORKFLOW CHI TIẾT

### Phase 1: Tạo báo cáo (Auto-Calculate)

```
Admin: Click "Tạo báo cáo tháng 12/2025"

↓ Hệ thống scan:

1. TĂNG (NEW_HIRE):
   - employees.hire_date trong tháng 12/2025
   - employees.status = ACTIVE
   → Tạo record: change_type=INCREASE, auto_reason=NEW_HIRE

2. GIẢM (TERMINATION):
   - employees.status = TERMINATED trong tháng 12
   → Tạo record: change_type=DECREASE, auto_reason=TERMINATION

3. GIẢM (LONG_ABSENCE):
   - leave_requests: (MATERNITY or SICK or UNPAID)
   - Ngày nghỉ > 30 ngày, start_date trong tháng 12
   → Tạo record: change_type=DECREASE, auto_reason=LONG_ABSENCE

4. ĐIỀU CHỈNH (SALARY_CHANGE):
   - contract_appendices: có insurance_salary thay đổi
   - effective_from trong tháng 12
   → Tạo record: change_type=ADJUST, auto_reason=SALARY_CHANGE

↓ Tạo InsuranceMonthlyReport (DRAFT)
↓ Tạo nhiều InsuranceChangeRecord (PENDING)
```

### Phase 2: Admin duyệt từng row

```
Admin xem danh sách records (PENDING)

Với mỗi record:

Option 1: APPROVE ✅
  - Click "Duyệt"
  - Có thể thêm admin_notes
  - approval_status = APPROVED

Option 2: REJECT ❌
  - Click "Từ chối"
  - Bắt buộc nhập lý do (admin_notes)
  - approval_status = REJECTED
  - Record không xuất vào Excel

Option 3: ADJUST 🔧
  - Click "Điều chỉnh"
  - Sửa: adjusted_salary (nếu số liệu sai)
  - Nhập: adjustment_reason
  - approval_status = ADJUSTED
```

### Phase 3: Finalize & Export

```
Khi: Tất cả records đã APPROVED/REJECTED/ADJUSTED

Admin: Click "Hoàn tất báo cáo"
  ↓
  - Kiểm tra: Không còn record PENDING
  - Update report.status = FINALIZED
  - Ghi log: finalized_by, finalized_at
  ↓
Admin: Click "Xuất Excel"
  ↓
  - Generate Excel theo template
  - Chỉ xuất records: APPROVED + ADJUSTED
  - Lưu: export_file_path, exported_at, exported_by
  ↓
  - Download file
```

---

## 🧮 BUSINESS RULES

### Quy tắc TĂNG BH
1. **Nhân viên mới**: `hire_date` trong tháng báo cáo
2. **Quay lại làm việc**: Kết thúc absence >30 ngày

### Quy tắc GIẢM BH
1. **Nghỉ việc**: `status` = TERMINATED
2. **Thai sản**: Leave type MATERNITY (180 ngày)
   - Theo luật VN: Giảm BH từ ngày bắt đầu nghỉ
3. **Nghỉ ốm dài**: Leave type SICK > 30 ngày
4. **Nghỉ không lương dài**: Leave type UNPAID > 30 ngày

### Quy tắc ĐIỀU CHỈNH
1. **Thay đổi lương**: Contract Appendix có `insurance_salary` mới
2. **Thay đổi loại BH**: Toggle has_social/health/unemployment_insurance

---

## 📱 UI SCREENS

### 1. Insurance Dashboard
```
/insurance/dashboard
- Card: Tổng nhân viên đang tham gia BH
- Card: Báo cáo tháng hiện tại (status)
- Chart: Xu hướng TĂNG/GIẢM 12 tháng
- Quick actions: "Tạo báo cáo tháng mới"
```

### 2. Monthly Reports List
```
/insurance/reports
- DataTable: Danh sách báo cáo các tháng
  - Columns: Tháng | TĂNG | GIẢM | ĐIỀU CHỈNH | Status | Actions
  - Filter: Year, Status
  - Actions: View Details | Export Excel (nếu FINALIZED)
```

### 3. Report Details (Main Screen) ⭐
```
/insurance/reports/{id}

Header:
  - Tháng: 12/2025
  - Status: DRAFT / FINALIZED
  - Progress: X/Y records đã duyệt

Tabs:
  1. TĂNG (Increase)
     DataTable:
     - STT | Mã NV | Họ tên | Lương BH | Lý do | Ngày hiệu lực | Trạng thái | Actions
     - Filter: approval_status
     - Actions: Duyệt | Từ chối | Điều chỉnh

  2. GIẢM (Decrease)
     [Tương tự]

  3. ĐIỀU CHỈNH (Adjust)
     [Tương tự]

Footer Actions:
  - [Hoàn tất báo cáo] (disabled nếu còn PENDING)
  - [Xuất Excel] (disabled nếu chưa FINALIZED)
  - [Hủy báo cáo] (chỉ khi DRAFT)
```

### 4. Approval Dialog
```
Dialog: Duyệt thay đổi BH

- Thông tin nhân viên: Mã NV, Họ tên
- Loại thay đổi: TĂNG/GIẢM/ĐIỀU CHỈNH
- Lý do hệ thống: NEW_HIRE / TERMINATION / ...
- Lương BH hiện tại: 10,000,000 VNĐ

[Nếu ADJUST]:
  - Input: Lương BH điều chỉnh
  - Textarea: Lý do điều chỉnh

Textarea: Ghi chú của Admin (optional)

Buttons:
  - [✅ Duyệt]
  - [❌ Từ chối]
  - [Hủy]
```

### 5. Participation History
```
/insurance/participations
- DataTable: Lịch sử tham gia BH của tất cả nhân viên
- Filter: Employee, Status, Year
- Columns: Mã NV | Họ tên | Lương BH | BHXH | BHYT | BHTN | Từ ngày | Đến ngày | Status
```

### 6. Long Absences
```
/insurance/absences
- DataTable: Danh sách nghỉ dài hạn (>30 ngày)
- Auto-create từ Leave Requests: MATERNITY, SICK >30, UNPAID >30
- Filter: Type, Status, affects_insurance
- Columns: Mã NV | Loại | Từ ngày | Đến ngày | Số ngày | Ảnh hưởng BH | Trạng thái
```

---

## 🎨 TECHNICAL STACK

### Backend
- **Services**:
  - `InsuranceCalculationService`: Logic tính TĂNG/GIẢM
  - `InsuranceReportService`: Generate & manage reports
  - `InsuranceExportService`: Export Excel
- **Jobs**:
  - `GenerateMonthlyInsuranceReport`: Queue job tính toán
  - `AutoDetectLongAbsence`: Detect Leave >30 days
- **Policies**:
  - `InsuranceReportPolicy`: can(view, approve, finalize, export)

### Frontend
- **Pages**: Insurance/Dashboard, Reports, ReportDetail, Participations, Absences
- **Components**: 
  - `ApprovalDialog.vue`
  - `ChangeRecordTable.vue`
  - `InsuranceStatusBadge.vue`
- **Services**: `InsuranceService.js` (API calls)

### Packages
- `maatwebsite/laravel-excel`: Export Excel
- `spatie/laravel-permission`: Role-based access

---

## 📅 TIMELINE (4 TUẦN)

### Week 1: Database & Core Logic
- [x] Create migrations (insurance tables)
- [ ] Create Models + Relationships
- [ ] InsuranceCalculationService (TĂNG/GIẢM logic)
- [ ] Seeders (test data)
- [ ] Unit Tests

### Week 2: API & Services
- [ ] InsuranceReportService (CRUD reports)
- [ ] API Routes + Controllers
- [ ] Approval logic (approve/reject/adjust)
- [ ] Auto-detect long absence (Observer)
- [ ] Integration Tests

### Week 3: UI Implementation
- [ ] Dashboard + Reports List
- [ ] Report Details (Main Screen)
- [ ] Approval Dialog
- [ ] Participations & Absences
- [ ] Responsive Design

### Week 4: Excel Export & Polish
- [ ] Excel Export (theo template anh gửi)
- [ ] Permissions & Policies
- [ ] Activity Logging
- [ ] Testing end-to-end
- [ ] Bug fixes

---

## 🔐 PERMISSIONS

### Role Access Matrix

| Feature | Super Admin | Admin | HR Head | HR | Director | LINE_MANAGER | Manager |
|---------|-------------|-------|---------|----|---------|--------------| --------|
| View Reports | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| Create Report | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Approve Records | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Finalize Report | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Export Excel | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| View Participations | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |

---

## ❓ CÂU HỎI CẦN XÁC NHẬN

1. **Template Excel**: Anh gửi file mẫu để tôi code đúng format
2. **Maternity Leave**: Giảm BH ngay từ ngày bắt đầu nghỉ? Hay sau 30 ngày?
3. **Sick Leave**: >30 ngày mới giảm BH đúng không?
4. **Unpaid Leave**: >30 ngày mới giảm BH đúng không?
5. **Return to work**: Khi kết thúc absence, có tự động tạo record TĂNG không?
6. **Multiple changes**: Nếu 1 nhân viên có nhiều thay đổi trong tháng (vừa tăng lương vừa nghỉ dài), xử lý thế nào?

---

## 🚀 NEXT STEPS

1. ✅ **Migration created** (insurance tables)
2. ⏳ **Chờ anh**: Template Excel
3. ⏳ **Xác nhận**: Business rules (câu hỏi trên)
4. 🔜 **Start coding**: Models + Services

Anh xem kế hoạch này có ổn không? Có điểm nào cần điều chỉnh không ạ?
