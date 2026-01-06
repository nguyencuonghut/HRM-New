# BÁO CÁO HỆ THỐNG - TỔNG QUAN NHANH
> **Cập nhật:** 2026-01-05  
> **Trạng thái:** ⚡ REDUCED SCOPE - Tập trung ưu tiên cao

---

## 📊 TỔNG QUAN SỐ LIỆU

| Trạng thái | Số lượng | Báo cáo |
|-----------|---------|---------|
| ✅ **Đã có** | 3 | RPT-050 (Insurance), RPT-070 (Activity), RPT-071 (Backup) |
| 🟢 **Triển khai** | 9 | Executive (4), Contracts (3), Leave (2) |
| ⏸️ **Hoãn** | 6 | Leave SLA (1), Payroll (2), Insurance (2) |
| **Tổng cộng** | **18** | Tất cả báo cáo trong hệ thống |

---

## ✅ CÁC BÁO CÁO ĐÃ CÓ (Không cần làm)

### 1. Insurance Monthly Report ✅
- **Route:** `/insurance-reports`
- **Controller:** `InsuranceReportController`
- **Views:** `resources/js/Pages/Insurance/Reports/`
- **Chức năng:** Báo cáo tăng/giảm/điều chỉnh bảo hiểm hàng tháng
- **Trạng thái:** Đầy đủ - có tạo, duyệt, xuất Excel

### 2. Activity Log ✅
- **Route:** `/activity-logs`
- **Controller:** `ActivityLogController`
- **Trạng thái:** Đã hoàn thiện

### 3. Backup Health ✅
- **Route:** `/backup`
- **Controller:** `BackupController`
- **Trạng thái:** Đã hoàn thiện với Google Drive integration

---

## 🟢 BÁO CÁO CẦN TRIỂN KHAI (9 reports - 6 tuần)

### Phase 1: Infrastructure (Week 1-2)
**Mục tiêu:** Xây dựng nền tảng chung

#### Backend
- [ ] `ReportController.php` - Controller chung
- [ ] `ReportService.php` - Service xử lý logic
- [ ] Routes `/reports/*`
- [ ] RBAC permissions

#### Frontend
- [ ] `Reports/Index.vue` - Reports Hub
- [ ] `Components/Reports/` - Reusable components:
  - ReportFilterBar.vue
  - ReportKpiCards.vue
  - ReportTable.vue
  - ReportChart.vue
  - ReportDrilldownDrawer.vue
  - ReportExportButton.vue
- [ ] `services/ReportService.js`
- [ ] Update sidebar navigation

---

### Phase 2: Executive & People (Week 3-4) - 4 Reports

#### RPT-001: Headcount Snapshot ⭐
**Mô tả:** Tổng số nhân viên theo dept/position tại một thời điểm  
**Params:** `as_of_date`  
**KPIs:** Total, By Dept, By Position, By Employment Type  
**Route:** `/reports/headcount`

**Tasks:**
- [ ] Backend: `headcount()` method với as-of-date logic
- [ ] Frontend: DatePicker + KPI Cards + Pie Chart + DataTable
- [ ] Export Excel

---

#### RPT-010: Employee List ⭐
**Mô tả:** Danh sách nhân viên chi tiết  
**Params:** `filters[]` (dept, position, status, type)  
**Route:** `/reports/employee-list`

**Tasks:**
- [ ] Backend: `employeeList()` với filters + pagination
- [ ] Frontend: Multi-select filters + Sortable DataTable
- [ ] Export Excel

---

#### RPT-011: Data Completeness ⭐
**Mô tả:** Đánh giá độ đầy đủ hồ sơ nhân viên  
**Params:** `filters[]` (department)  
**Route:** `/reports/data-completeness`

**Tasks:**
- [ ] Backend: Integrate `ProfileCompletionService`
- [ ] Frontend: Progress bars + Missing fields breakdown
- [ ] Export Excel

---

#### RPT-002: Employee Movement ⭐
**Mô tả:** Chuyển động nhân viên (Tuyển mới, Nghỉ việc, Điều chuyển)  
**Params:** `start_date`, `end_date`  
**Route:** `/reports/employee-movement`

**Tasks:**
- [ ] Backend: Compare assignments logic
- [ ] Frontend: DateRange + 3 Tabs (New Hires | Terminations | Transfers)
- [ ] Export Excel

---

### Phase 3: Contracts (Week 5) - 3 Reports

#### RPT-020: Contracts by Status ⭐
**Mô tả:** Phân bổ hợp đồng theo trạng thái  
**Params:** `as_of_date`  
**Route:** `/reports/contracts-status`

**Tasks:**
- [ ] Backend: Count by status (DRAFT, PENDING, ACTIVE, EXPIRED, TERMINATED)
- [ ] Frontend: Pie Chart + DataTable drill-down
- [ ] Export Excel

---

#### RPT-021: Contracts Expiring ⭐⭐
**Mô tả:** Hợp đồng sắp hết hạn  
**Params:** `from_date`, `to_date`, `warning_days`  
**Route:** `/reports/contracts-expiring`

**Tasks:**
- [ ] Backend: Filter by end_date range + calculate days_until_expiry
- [ ] Frontend: Color-coded table (red < 15 days, yellow < 30 days)
- [ ] Export Excel

---

#### RPT-022: Contract Approval SLA ⭐
**Mô tả:** Phân tích thời gian phê duyệt hợp đồng  
**Params:** `start_date`, `end_date`  
**Route:** `/reports/contract-approval-sla`

**Tasks:**
- [ ] Backend: Calculate approval time (approved_at - created_at)
- [ ] Frontend: KPI Cards + DataTable + Trend Chart
- [ ] Export Excel

---

### Phase 4: Leave (Week 6) - 2 Reports

#### RPT-030: Monthly Leave Summary ⭐
**Mô tả:** Tóm tắt nghỉ phép theo tháng  
**Params:** `year`, `month`  
**Route:** `/reports/leave-monthly`

**Tasks:**
- [ ] Backend: Filter approved requests by month + breakdown by type/dept
- [ ] Frontend: Year/Month selector + Bar Chart + DataTable
- [ ] Export Excel

---

#### RPT-031: Leave Balances ⭐⭐
**Mô tả:** Số dư phép của nhân viên  
**Params:** `as_of_date`, `filters[]`  
**Route:** `/reports/leave-balances`

**Tasks:**
- [ ] Backend: Calculate Used/Remaining + highlight expiring
- [ ] Frontend: Color-coded DataTable (red = expiring soon)
- [ ] Export Excel

---

### Phase 5: Integration & Testing (Week 7)

#### Tasks
- [ ] RBAC integration (permissions)
- [ ] Unit tests cho ReportService
- [ ] Integration tests cho endpoints
- [ ] UI tests cho report pages
- [ ] Performance testing (large datasets)
- [ ] Export functionality testing
- [ ] Documentation (API + User Guide)

---

## ⏸️ BÁO CÁO TẠM HOÃN (6 reports)

### Leave Reports
- **RPT-032: Leave Approval SLA** - Phân tích SLA phê duyệt phép

### Payroll Reports
- **RPT-040: Payroll Period Summary** - Tổng hợp lương theo kỳ
- **RPT-041: Payroll Adjustments** - Điều chỉnh lương

### Insurance Reports
- **RPT-051: Insurance Participation** - Tỷ lệ tham gia bảo hiểm
- **RPT-052: Absences Affecting Insurance** - Nghỉ dài ảnh hưởng BH

**Lý do hoãn:** Tập trung resource vào các báo cáo quan trọng nhất trước

---

## 🎯 ƯU TIÊN THỰC HIỆN

### Priority 1 (CRITICAL) ⚠️
1. **RPT-021: Contracts Expiring** - Cảnh báo hợp đồng hết hạn
2. **RPT-031: Leave Balances** - Quản lý số dư phép

### Priority 2 (HIGH) ⭐
3. **RPT-001: Headcount Snapshot** - Báo cáo điều hành
4. **RPT-010: Employee List** - Danh sách nhân sự
5. **RPT-002: Employee Movement** - Chuyển động nhân sự

### Priority 3 (MEDIUM) 
6. **RPT-020: Contracts by Status**
7. **RPT-030: Monthly Leave Summary**
8. **RPT-011: Data Completeness**
9. **RPT-022: Contract Approval SLA**

---

## 📐 KIẾN TRÚC TỔNG QUAN

### File Structure
```
app/
├── Http/Controllers/
│   └── ReportController.php             [NEW]
├── Services/
│   └── ReportService.php                [NEW]
└── Http/Resources/
    └── ReportResource.php               [NEW]

resources/js/
├── Pages/Reports/
│   ├── Index.vue                        [NEW] Hub
│   ├── Headcount.vue                    [NEW]
│   ├── EmployeeList.vue                 [NEW]
│   ├── DataCompleteness.vue             [NEW]
│   ├── EmployeeMovement.vue             [NEW]
│   ├── ContractsStatus.vue              [NEW]
│   ├── ContractsExpiring.vue            [NEW]
│   ├── ContractApprovalSla.vue          [NEW]
│   ├── LeaveMonthly.vue                 [NEW]
│   └── LeaveBalances.vue                [NEW]
├── Components/Reports/
│   ├── ReportFilterBar.vue              [NEW]
│   ├── ReportKpiCards.vue               [NEW]
│   ├── ReportTable.vue                  [NEW]
│   ├── ReportChart.vue                  [NEW]
│   └── ReportDrilldownDrawer.vue        [NEW]
└── services/
    └── ReportService.js                 [NEW]
```

### Routes Pattern
```php
Route::group(['prefix' => 'reports', 'middleware' => 'auth'], function () {
    Route::get('/', [ReportController::class, 'hub']);
    Route::get('/headcount', [ReportController::class, 'headcount']);
    Route::get('/employee-list', [ReportController::class, 'employeeList']);
    // ... other routes
    Route::get('/{reportCode}/export', [ReportController::class, 'export']);
});
```

---

## 🔑 AS-OF-DATE LOGIC (Core Pattern)

```php
// Reusable method in ReportService
public function getActiveAssignmentAsOf($employeeId, $asOfDate)
{
    return EmployeeAssignment::where('employee_id', $employeeId)
        ->where('status', 'ACTIVE')
        ->where(function($q) use ($asOfDate) {
            $q->where('start_date', '<=', $asOfDate)
              ->orWhereNull('start_date');
        })
        ->where(function($q) use ($asOfDate) {
            $q->where('end_date', '>=', $asOfDate)
              ->orWhereNull('end_date');
        })
        ->where('is_primary', true)
        ->first();
}
```

---

## ✅ CHECKLIST TỔNG HỢP

### Infrastructure
- [ ] ReportController created
- [ ] ReportService created  
- [ ] Report routes configured
- [ ] Reports Hub page created
- [ ] Report components created
- [ ] ReportService.js created
- [ ] Sidebar navigation updated

### Reports Implementation
**Executive & People:**
- [ ] RPT-001: Headcount Snapshot
- [ ] RPT-010: Employee List
- [ ] RPT-011: Data Completeness
- [ ] RPT-002: Employee Movement

**Contracts:**
- [ ] RPT-020: Contracts by Status
- [ ] RPT-021: Contracts Expiring
- [ ] RPT-022: Contract Approval SLA

**Leave:**
- [ ] RPT-030: Monthly Leave Summary
- [ ] RPT-031: Leave Balances

### Integration
- [ ] RBAC permissions
- [ ] All tests passed
- [ ] Export functionality working
- [ ] Performance optimized
- [ ] Documentation completed

---

## 📚 TÀI LIỆU THAM KHẢO

1. **[HRM_Reports_Full_Specification.md](HRM_Reports_Full_Specification.md)** - Specification đầy đủ
2. **[REPORTS_IMPLEMENTATION_PLAN.md](REPORTS_IMPLEMENTATION_PLAN.md)** - Kế hoạch chi tiết
3. **Insurance Reports** (Reference):
   - `app/Http/Controllers/InsuranceReportController.php`
   - `app/Services/InsuranceReportService.php`
   - `resources/js/Pages/Insurance/Reports/`

---

## 🚀 NEXT ACTIONS

1. ✅ Review và approve plan
2. ⏭️ Setup development environment
3. ⏭️ Start Phase 1: Infrastructure (Week 1-2)
4. ⏭️ Implement Phase 2: Executive Reports (Week 3-4)

---

**Status:** 🟡 Planning Complete - Ready for Implementation  
**Timeline:** 7 weeks  
**Team:** Backend + Frontend Developers  
**Start Date:** TBD
