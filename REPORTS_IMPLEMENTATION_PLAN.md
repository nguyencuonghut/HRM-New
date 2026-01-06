# KẾ HOẠCH TRIỂN KHAI HỆ THỐNG BÁO CÁO (REPORTS)
> **Dựa trên:** HRM_Reports_Full_Specification.md  
> **Stack:** Laravel + Inertia + Vue 3 + PrimeVue v4  
> **Nguyên tắc:** As-of date, Read-only, Admin-confirmed data  
> **Status:** ⚡ **REDUCED SCOPE** - Tập trung vào Executive, People, Contracts, và Leave Reports

---

## 🎯 EXECUTIVE SUMMARY

### Phạm vi triển khai (Updated)
- **11 báo cáo triển khai mới** (từ tổng 17 báo cáo)
- **3 báo cáo đã có sẵn** (Insurance Monthly, Activity Log, Backup Health)
- **6 báo cáo tạm hoãn** (1 Leave SLA, 2 Payroll, 2 Insurance, 1 Insurance đã có)

### Timeline: 7 tuần (giảm từ 9 tuần)
- **Week 1-2:** Infrastructure Setup
- **Week 3-4:** Executive & People Reports (4 reports)
- **Week 5:** Contract Reports (3 reports)  
- **Week 6:** Leave Reports (2 reports - bỏ RPT-032)
- **Week 7:** Integration & Testing

### Báo cáo theo ưu tiên

**✅ Triển khai ngay (Priority 1):**
- RPT-001: Headcount Snapshot
- RPT-010: Employee List
- RPT-011: Data Completeness
- RPT-002: Employee Movement
- RPT-020: Contracts by Status
- RPT-021: Contracts Expiring
- RPT-022: Contract Approval SLA
- RPT-030: Monthly Leave Summary
- RPT-031: Leave Balances

**✅ Đã có sẵn (No action needed):**
- RPT-050: Insurance Monthly Changes → `/insurance-reports`
- RPT-070: Activity Log → `/activity-logs`
- RPT-071: Backup Health → `/backup`

**⏸️ Tạm hoãn (Future phase):**
- RPT-032: Leave Approval SLA
- RPT-040: Payroll Period Summary
- RPT-041: Payroll Adjustments
- RPT-051: Insurance Participation
- RPT-052: Absences Affecting Insurance

---

## 📋 PHẦN 1: TỔNG QUAN

### 1.1. Mục tiêu ⚡ *REDUCED SCOPE*
Xây dựng hệ thống báo cáo tổng hợp cho HRM với các module:
- **Executive & People**: Tổng quan nhân sự, danh sách, chuyển động ✅
- **Contracts**: Trạng thái hợp đồng, hết hạn, SLA phê duyệt ✅
- **Leave**: Tóm tắt nghỉ phép, số dư ✅ (SLA deferred ⏸️)
- ~~**Payroll**: Tổng hợp lương, điều chỉnh lương~~ ⏸️ *DEFERRED*
- ~~**Insurance**: Tăng/giảm bảo hiểm, tham gia, nghỉ ảnh hưởng~~ (RPT-050 đã có ✅, khác deferred ⏸️)
- **Audit & Ops**: Activity log, backup health ✅ *EXISTING*

### 1.2. Nguyên tắc thiết kế
✅ **As-of Date Logic**: Tất cả báo cáo phân bổ (department/position) tính theo ngày cụ thể  
✅ **Read-only**: Báo cáo chỉ đọc, không chỉnh sửa dữ liệu  
✅ **Confirmed Data**: Sử dụng dữ liệu đã được admin xác nhận (KPI, Payroll, Insurance)  
✅ **Existing Schema**: Không tạo bảng mới, tái sử dụng schema hiện tại

### 1.3. Kiến trúc hệ thống ⚡ *UPDATED*
```
/reports                         → Reports Hub (danh mục tất cả báo cáo)
/reports/headcount               → RPT-001: Headcount Snapshot ✅
/reports/employee-list           → RPT-010: Employee List ✅
/reports/data-completeness       → RPT-011: Data Completeness ✅
/reports/employee-movement       → RPT-002: Employee Movement ✅
/reports/contracts-status        → RPT-020: Contracts by Status ✅
/reports/contracts-expiring      → RPT-021: Contracts Expiring ✅
/reports/contract-approval-sla   → RPT-022: Contract Approval SLA ✅
/reports/leave-monthly           → RPT-030: Monthly Leave Summary ✅
/reports/leave-balances          → RPT-031: Leave Balances ✅
~~/reports/leave-approval-sla~~      → ~~RPT-032: Leave Approval SLA~~ ⏸️ DEFERRED
~~/reports/payroll-summary~~         → ~~RPT-040: Payroll Period Summary~~ ⏸️ DEFERRED
~~/reports/payroll-adjustments~~     → ~~RPT-041: Payroll Adjustments~~ ⏸️ DEFERRED
/insurance-reports               → RPT-050: Insurance Monthly Changes ✅ EXISTING
~~/reports/insurance-participation~~ → ~~RPT-051: Insurance Participation~~ ⏸️ DEFERRED
~~/reports/insurance-absences~~      → ~~RPT-052: Absences Affecting Insurance~~ ⏸️ DEFERRED
/activity-logs                   → RPT-070: Activity Log ✅ EXISTING
/backup                          → RPT-071: Backup Health ✅ EXISTING
```

---

## 📊 PHẦN 2: DANH SÁCH BÁO CÁO CHI TIẾT

### 📁 A. EXECUTIVE & PEOPLE REPORTS

#### **RPT-001: Headcount Snapshot** 
**Mô tả:** Tổng số nhân viên theo department/position tại một thời điểm  
**Params:** `as_of_date`  
**KPIs:** Total Headcount, By Department, By Position, By Employment Type  
**Tables:** `employees`, `employee_assignments`  
**Logic:** Assignment as-of-date, status = ACTIVE

#### **RPT-010: Employee List**
**Mô tả:** Danh sách nhân viên chi tiết với thông tin cơ bản  
**Params:** `filters[]` (department, position, status, employment_type)  
**Columns:** Code, Name, Department, Position, Email, Phone, Status, Join Date  
**Tables:** `employees`, `employee_assignments`  
**Export:** Excel với tất cả thông tin

#### **RPT-011: Data Completeness**
**Mô tả:** Đánh giá độ đầy đủ dữ liệu hồ sơ nhân viên  
**Params:** `filters[]` (department)  
**Metrics:** Profile completion %, Missing fields breakdown  
**Tables:** `employees`, `employee_educations`, `employee_experiences`, `employee_relatives`  
**Service:** Sử dụng `ProfileCompletionService`

#### **RPT-002: Employee Movement**
**Mô tả:** Chuyển động nhân viên trong khoảng thời gian  
**Params:** `start_date`, `end_date`  
**Sections:** New Hires, Terminations, Transfers (department/position changes)  
**Tables:** `employees`, `employee_assignments`, `employee_employments`  
**Logic:** Compare assignments between dates

---

### 📄 B. CONTRACT REPORTS

#### **RPT-020: Contracts by Status**
**Mô tả:** Phân bổ hợp đồng theo trạng thái  
**Params:** `as_of_date`  
**Breakdown:** DRAFT, PENDING, ACTIVE, EXPIRED, TERMINATED  
**Tables:** `contracts`  
**Chart:** Pie chart + DataTable

#### **RPT-021: Contracts Expiring**
**Mô tả:** Danh sách hợp đồng sắp hết hạn  
**Params:** `from_date`, `to_date`, `warning_days` (default 30)  
**Columns:** Employee, Contract Type, Start Date, End Date, Days Until Expiry  
**Tables:** `contracts`  
**Alert:** Highlight contracts < 15 days

#### **RPT-022: Contract Approval SLA**
**Mô tả:** Phân tích thời gian phê duyệt hợp đồng  
**Params:** `start_date`, `end_date`  
**Metrics:** Avg approval time, SLA compliance %, Pending > X days  
**Tables:** `contracts`, `contract_approvals`  
**Logic:** Calculate time between created_at and approved_at

---

### 🏖️ C. LEAVE REPORTS

#### **RPT-030: Monthly Leave Summary**
**Mô tả:** Tóm tắt nghỉ phép theo tháng  
**Params:** `year`, `month`  
**Breakdown:** By leave type, By department, Total days taken  
**Tables:** `leave_requests` (status = APPROVED)  
**KPIs:** Total requests, Total days, By type

#### **RPT-031: Leave Balances**
**Mô tả:** Số dư phép của nhân viên  
**Params:** `as_of_date`, `filters[]` (department, employee)  
**Columns:** Employee, Leave Type, Allowance, Used, Remaining, Expiry Date  
**Tables:** `leave_balances`, `leave_requests`  
**Alert:** Highlight expiring soon

#### ~~**RPT-032: Leave Approval SLA**~~ *(DEFERRED)*
**Mô tả:** Phân tích thời gian phê duyệt nghỉ phép  
**Status:** ⏸️ **Tạm hoãn - không triển khai trong phase hiện tại**

---

### 💰 D. PAYROLL REPORTS *(ALL DEFERRED)*

#### ~~**RPT-040: Payroll Period Summary**~~ *(DEFERRED)*
**Mô tả:** Tổng hợp lương theo kỳ  
**Status:** ⏸️ **Tạm hoãn - không triển khai trong phase hiện tại**

#### ~~**RPT-041: Payroll Adjustments**~~ *(DEFERRED)*
**Mô tả:** Danh sách điều chỉnh lương  
**Status:** ⏸️ **Tạm hoãn - không triển khai trong phase hiện tại**

---

### 🏥 E. INSURANCE REPORTS *(ALL DEFERRED OR EXISTING)*

#### ~~**RPT-050: Insurance Monthly Changes**~~ ✅ **(ĐÃ CÓ - KHÔNG CẦN TRIỂN KHAI)**
**Mô tả:** Báo cáo tăng/giảm/điều chỉnh bảo hiểm hàng tháng  
**Status:** ✅ **Đã hoàn thiện - có thể truy cập tại `/insurance-reports`**  
**Location:**
- Controller: `InsuranceReportController`
- Service: `InsuranceReportService`
- Views: `resources/js/Pages/Insurance/Reports/`
- Route: `/insurance-reports`

#### ~~**RPT-051: Insurance Participation**~~ *(DEFERRED)*
**Mô tả:** Tỷ lệ tham gia bảo hiểm  
**Status:** ⏸️ **Tạm hoãn - không triển khai trong phase hiện tại**

#### ~~**RPT-052: Absences Affecting Insurance**~~ *(DEFERRED)*
**Mô tả:** Nghỉ dài ngày ảnh hưởng đến bảo hiểm  
**Status:** ⏸️ **Tạm hoãn - không triển khai trong phase hiện tại**

---

### 🔍 F. AUDIT & OPS REPORTS

#### **RPT-070: Activity Log** ✅ **(ĐÃ CÓ)**
**Mô tả:** Nhật ký hoạt động hệ thống  
**Status:** ✅ **Đã có controller và views**  
**Location:**
- Controller: `ActivityLogController`
- Route: `/activity-logs`

#### **RPT-071: Backup Health** ✅ **(ĐÃ CÓ)**
**Mô tả:** Trạng thái sao lưu dữ liệu  
**Status:** ✅ **Đã có controller và views**  
**Location:**
- Controller: `BackupController`
- Route: `/backup`

---

## 🏗️ PHẦN 3: KẾ HOẠCH THỰC HIỆN

### Phase 1: Infrastructure Setup (Week 1-2)
**Mục tiêu:** Xây dựng nền tảng chung cho tất cả reports

#### Task 1.1: Backend Infrastructure
- [ ] **Create ReportController** (Base Controller cho tất cả reports)
  - File: `app/Http/Controllers/ReportController.php`
  - Methods: `index()`, `show()`, `export()`
  - Middleware: `auth`, `permission:reports.view`

- [ ] **Create ReportService** (Base Service)
  - File: `app/Services/ReportService.php`
  - Methods: `getAsOfAssignment()`, `calculateDateRange()`, `exportToExcel()`
  - Chứa logic as-of-date reusable

- [ ] **Create Report Routes**
  - File: `routes/web.php`
  ```php
  Route::group(['prefix' => 'reports', 'middleware' => 'auth'], function () {
      Route::get('/', [ReportController::class, 'hub'])->name('reports.hub');
      
      // Executive & People
      Route::get('/headcount', [ReportController::class, 'headcount'])->name('reports.headcount');
      Route::get('/employee-list', [ReportController::class, 'employeeList'])->name('reports.employee-list');
      Route::get('/data-completeness', [ReportController::class, 'dataCompleteness'])->name('reports.data-completeness');
      Route::get('/employee-movement', [ReportController::class, 'employeeMovement'])->name('reports.employee-movement');
      
      // Contracts
      Route::get('/contracts-status', [ReportController::class, 'contractsStatus'])->name('reports.contracts-status');
      Route::get('/contracts-expiring', [ReportController::class, 'contractsExpiring'])->name('reports.contracts-expiring');
      Route::get('/contract-approval-sla', [ReportController::class, 'contractApprovalSla'])->name('reports.contract-approval-sla');
      
      // Leave
      Route::get('/leave-monthly', [ReportController::class, 'leaveMonthly'])->name('reports.leave-monthly');
      Route::get('/leave-balances', [ReportController::class, 'leaveBalances'])->name('reports.leave-balances');
      Route::get('/leave-approval-sla', [ReportController::class, 'leaveApprovalSla'])->name('reports.leave-approval-sla');
      
      // Payroll
      Route::get('/payroll-summary', [ReportController::class, 'payrollSummary'])->name('reports.payroll-summary');
      Route::get('/payroll-adjustments', [ReportController::class, 'payrollAdjustments'])->name('reports.payroll-adjustments');
      
      // Insurance
      Route::get('/insurance-participation', [ReportController::class, 'insuranceParticipation'])->name('reports.insurance-participation');
      Route::get('/insurance-absences', [ReportController::class, 'insuranceAbsences'])->name('reports.insurance-absences');
      
      // Export routes
      Route::get('/{reportCode}/export', [ReportController::class, 'export'])->name('reports.export');
  });
  ```

#### Task 1.2: Frontend Infrastructure
- [ ] **Create Reports Hub Page**
  - File: `resources/js/Pages/Reports/Index.vue`
  - Layout: Card grid hiển thị tất cả báo cáo
  - Grouping: By category (People, Contracts, Leave, etc.)

- [ ] **Create Report Components**
  ```
  resources/js/Components/Reports/
  ├── ReportFilterBar.vue       (Filter chung: date range, department, etc.)
  ├── ReportKpiCards.vue        (KPI summary cards)
  ├── ReportTable.vue           (DataTable với sort, filter, pagination)
  ├── ReportChart.vue           (Chart wrapper cho Chart.js)
  ├── ReportDrilldownDrawer.vue (Sidebar drill-down detail)
  └── ReportExportButton.vue    (Export to Excel button)
  ```

- [ ] **Create Report Service (Frontend)**
  - File: `resources/js/services/ReportService.js`
  ```javascript
  export class ReportService {
      static viewReport(reportCode, params = {}) {
          router.get(`/reports/${reportCode}`, params);
      }
      
      static exportReport(reportCode, params = {}) {
          window.location.href = `/reports/${reportCode}/export?${new URLSearchParams(params)}`;
      }
  }
  ```

#### Task 1.3: Update Sidebar Navigation
- [ ] **Add Reports Menu Item**
  - File: Update layout/sidebar component
  - Add icon: `pi-chart-bar`
  - Route: `/reports`
  - Permission: `reports.view`

---

### Phase 2: Executive & People Reports (Week 3-4)

#### Task 2.1: RPT-001 Headcount Snapshot
**Backend:**
- [ ] Create `headcount()` method in ReportController
- [ ] Create service method in ReportService
- [ ] Implement as-of-date assignment logic
- [ ] Return KPIs + breakdown by department/position

**Frontend:**
- [ ] Create `resources/js/Pages/Reports/Headcount.vue`
- [ ] DatePicker for as_of_date
- [ ] KPI Cards: Total, By Dept, By Position, By Type
- [ ] Pie Chart + DataTable
- [ ] Export button

**Test:**
- [ ] Test với nhiều as_of dates
- [ ] Verify assignment logic đúng
- [ ] Test export Excel

#### Task 2.2: RPT-010 Employee List
**Backend:**
- [ ] Create `employeeList()` method
- [ ] Implement filters (dept, position, status, employment type)
- [ ] Pagination support
- [ ] Export với full data

**Frontend:**
- [ ] Create `resources/js/Pages/Reports/EmployeeList.vue`
- [ ] Multi-select filters
- [ ] Sortable DataTable
- [ ] Column visibility toggle
- [ ] Export button

**Test:**
- [ ] Test filters combinations
- [ ] Test pagination
- [ ] Test export

#### Task 2.3: RPT-011 Data Completeness
**Backend:**
- [ ] Create `dataCompleteness()` method
- [ ] Integrate with ProfileCompletionService
- [ ] Calculate completion % per employee
- [ ] Group by department

**Frontend:**
- [ ] Create `resources/js/Pages/Reports/DataCompleteness.vue`
- [ ] Progress bars for completion %
- [ ] Missing fields breakdown
- [ ] DataTable with drill-down
- [ ] Export button

**Test:**
- [ ] Verify completion calculation
- [ ] Test missing fields detection

#### Task 2.4: RPT-002 Employee Movement
**Backend:**
- [ ] Create `employeeMovement()` method
- [ ] Logic: Compare assignments start_date/end_date
- [ ] Identify: New hires, Terminations, Transfers
- [ ] Date range filters

**Frontend:**
- [ ] Create `resources/js/Pages/Reports/EmployeeMovement.vue`
- [ ] DateRangePicker
- [ ] Tabs: New Hires | Terminations | Transfers
- [ ] DataTable per tab
- [ ] Export button

**Test:**
- [ ] Test date range logic
- [ ] Verify new hire/termination detection
- [ ] Test transfer detection

---

### Phase 3: Contract Reports (Week 5)

#### Task 3.1: RPT-020 Contracts by Status
**Backend:**
- [ ] Create `contractsStatus()` method
- [ ] Count contracts by status
- [ ] As-of-date filtering
- [ ] Return KPIs + breakdown

**Frontend:**
- [ ] Create `resources/js/Pages/Reports/ContractsStatus.vue`
- [ ] DatePicker for as_of_date
- [ ] Pie Chart by status
- [ ] DataTable with drill-down
- [ ] Export button

**Test:**
- [ ] Test status counting
- [ ] Test as-of-date filtering

#### Task 3.2: RPT-021 Contracts Expiring
**Backend:**
- [ ] Create `contractsExpiring()` method
- [ ] Filter contracts: end_date between from_date and to_date
- [ ] Calculate days_until_expiry
- [ ] Warning highlighting (< 15 days)

**Frontend:**
- [ ] Create `resources/js/Pages/Reports/ContractsExpiring.vue`
- [ ] DateRangePicker
- [ ] Warning_days input
- [ ] Color-coded DataTable (red < 15 days, yellow < 30 days)
- [ ] Export button

**Test:**
- [ ] Test date range filtering
- [ ] Test days calculation
- [ ] Test warning highlighting

#### Task 3.3: RPT-022 Contract Approval SLA
**Backend:**
- [ ] Create `contractApprovalSla()` method
- [ ] Calculate approval time: approved_at - created_at
- [ ] Metrics: Avg time, SLA compliance %, Pending > X days
- [ ] Date range filtering

**Frontend:**
- [ ] Create `resources/js/Pages/Reports/ContractApprovalSla.vue`
- [ ] DateRangePicker
- [ ] KPI Cards: Avg time, SLA %
- [ ] DataTable with approval times
- [ ] Chart: Approval time trend
- [ ] Export button

**Test:**
- [ ] Test approval time calculation
- [ ] Test SLA metrics

---

### Phase 4: Leave Reports (Week 5) ⚡ *REDUCED SCOPE*

#### Task 4.1: RPT-030 Monthly Leave Summary
**Backend:**
- [ ] Create `leaveMonthly()` method
- [ ] Filter leave_requests: year, month, status = APPROVED
- [ ] Breakdown by leave_type, department
- [ ] Calculate total days

**Frontend:**
- [ ] Create `resources/js/Pages/Reports/LeaveMonthly.vue`
- [ ] Year/Month selectors
- [ ] KPI Cards: Total requests, Total days
- [ ] Bar Chart by leave type
- [ ] DataTable by department
- [ ] Export button

**Test:**
- [ ] Test year/month filtering
- [ ] Test breakdown calculations

#### Task 4.2: RPT-031 Leave Balances
**Backend:**
- [ ] Create `leaveBalances()` method
- [ ] Join leave_balances with leave_requests
- [ ] Calculate: Used = Sum(approved requests), Remaining = Allowance - Used
- [ ] As-of-date filtering
- [ ] Highlight expiring soon

**Frontend:**
- [ ] Create `resources/js/Pages/Reports/LeaveBalances.vue`
- [ ] DatePicker for as_of_date
- [ ] Filters: Department, Employee
- [ ] Color-coded DataTable (red = expiring soon)
- [ ] Export button

**Test:**
- [ ] Test balance calculation
- [ ] Test expiry detection

#### ~~Task 4.3: RPT-032 Leave Approval SLA~~ *(DEFERRED)*
**Status:** ⏸️ **Bỏ qua - không thực hiện trong phase hiện tại**

---

### ~~Phase 5: Payroll Reports~~ *(COMPLETELY DEFERRED)*
**Status:** ⏸️ **Toàn bộ phase bị hoãn - không triển khai**

---

### ~~Phase 6: Insurance Reports~~ *(COMPLETELY DEFERRED)*
**Status:** ⏸️ **Toàn bộ phase bị hoãn - RPT-050 đã có sẵn, các báo cáo khác không triển khai**

---

### Phase 5: Integration & Testing (Week 6) ⚡ *UPDATED*

#### Task 7.1: RBAC Integration
- [ ] Create permissions:
  ```php
  'reports.view',
  'reports.headcount.view',
  'reports.contracts.view',
  'reports.leave.view',
  'reports.payroll.view',
  'reports.insurance.view',
  'reports.audit.view',
  'reports.export'
  ```
- [ ] Assign to roles: Admin, Super Admin, Manager
- [ ] Add permission checks to all report routes

#### Task 7.2: Comprehensive Testing
- [ ] Unit tests for ReportService
- [ ] Integration tests for all report endpoints
- [ ] UI tests for all report pages
- [ ] Performance testing (large datasets)
- [ ] Export functionality testing

#### Task 7.3: Documentation
- [ ] API documentation for all endpoints
- [ ] User guide cho từng báo cáo
- [ ] Technical documentation for developers

---

## 📦 PHẦN 4: FILE STRUCTURE

```
app/
├── Http/
│   └── Controllers/
│       └── ReportController.php         [NEW]
├── Services/
│   └── ReportService.php                [NEW]
└── Http/
    └── Resources/
        └── ReportResource.php           [NEW]

resources/
└── js/
    ├── Pages/
    │   └── Reports/
    │       ├── Index.vue                [NEW] Reports Hub
    │       ├── Headcount.vue            [NEW] RPT-001
    │       ├── EmployeeList.vue         [NEW] RPT-010
    │       ├── DataCompleteness.vue     [NEW] RPT-011
    │       ├── EmployeeMovement.vue     [NEW] RPT-002
    │       ├── ContractsStatus.vue      [NEW] RPT-020
    │       ├── ContractsExpiring.vue    [NEW] RPT-021
    │       ├── ContractApprovalSla.vue  [NEW] RPT-022
    │       ├── LeaveMonthly.vue         [NEW] RPT-030
    │       ├── LeaveBalances.vue        [NEW] RPT-031
    │       ├── LeaveApprovalSla.vue     [NEW] RPT-032
    │       ├── PayrollSummary.vue       [NEW] RPT-040
    │       ├── PayrollAdjustments.vue   [NEW] RPT-041
    │       ├── InsuranceParticipation.vue [NEW] RPT-051
    │       └── InsuranceAbsences.vue    [NEW] RPT-052
    ├── Components/
    │   └── Reports/
    │       ├── ReportFilterBar.vue      [NEW]
    │       ├── ReportKpiCards.vue       [NEW]
    │       ├── ReportTable.vue          [NEW]
    │       ├── ReportChart.vue          [NEW]
    │       ├── ReportDrilldownDrawer.vue [NEW]
    │       └── ReportExportButton.vue   [NEW]
    └── services/
        └── ReportService.js             [NEW]

routes/
└── web.php                              [MODIFY] Add report routes
```

---

## 🎯 PHẦN 5: CHECKLIST HOÀN THIỆN

### Infrastructure ✅
- [ ] ReportController created
- [ ] ReportService created
- [ ] Report routes added
- [ ] Reports Hub page created
- [ ] Report components created
- [ ] ReportService.js created
- [ ] Sidebar updated with Reports menu

### Executive & People Reports
- [ ] RPT-001: Headcount Snapshot
- [ ] RPT-010: Employee List
- [ ] RPT-011: Data Completeness
- [ ] RPT-002: Employee Movement

### Contract Reports
- [ ] RPT-020: Contracts by Status
- [ ] RPT-021: Contracts Expiring
- [ ] RPT-022: Contract Approval SLA

### Leave Reports
- [ ] RPT-030: Monthly Leave Summary
- [ ] RPT-031: Leave Balances
- [ ] ~~RPT-032: Leave Approval SLA~~ ⏸️ *DEFERRED*

### Payroll Reports ⏸️ *ALL DEFERRED*
- [ ] ~~RPT-040: Payroll Period Summary~~ ⏸️ *DEFERRED*
- [ ] ~~RPT-041: Payroll Adjustments~~ ⏸️ *DEFERRED*

### Insurance Reports ⏸️ *ALL DEFERRED*
- [ ] ✅ RPT-050: Insurance Monthly Changes (ĐÃ CÓ - `/insurance-reports`)
- [ ] ~~RPT-051: Insurance Participation~~ ⏸️ *DEFERRED*
- [ ] ~~RPT-052: Absences Affecting Insurance~~ ⏸️ *DEFERRED*

### Audit & Ops Reports
- [ ] ✅ RPT-070: Activity Log (ĐÃ CÓ)
- [ ] ✅ RPT-071: Backup Health (ĐÃ CÓ)

### Integration & Testing
- [ ] RBAC permissions configured
- [ ] All reports tested
- [ ] Export functionality tested
- [ ] Performance optimized
- [ ] Documentation completed

---

## 💡 GHI CHÚ QUAN TRỌNG

### As-Of-Date Logic (Reusable)
```php
// app/Services/ReportService.php
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

### Export Metadata Pattern
```php
// Include filters + timestamp in export
public function export(Request $request, $reportCode)
{
    $data = $this->getReportData($reportCode, $request->all());
    
    return Excel::download(
        new ReportExport($data, $request->all()),
        "{$reportCode}_" . now()->format('YmdHis') . ".xlsx"
    );
}
```

### Report Card Component Pattern
```vue
<!-- ReportKpiCards.vue -->
<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <Card v-for="kpi in kpis" :key="kpi.label">
            <template #content>
                <div class="text-center">
                    <div class="text-3xl font-bold text-primary">{{ kpi.value }}</div>
                    <div class="text-sm text-gray-600 mt-2">{{ kpi.label }}</div>
                </div>
            </template>
        </Card>
    </div>
</template>
```

---

## 🚀 NEXT STEPS

1. **Review kế hoạch** với team và stakeholders
2. **Xác định priority** cho từng phase
3. **Assign tasks** cho developers
4. **Setup timeline** cụ thể
5. **Bắt đầu với Phase 1**: Infrastructure Setup

---

**Tài liệu tham khảo:**
- `HRM_Reports_Full_Specification.md` - Specification chi tiết
- `INSURANCE_IMPLEMENTATION_PLAN.md` - Mẫu reference cho Insurance reports
- `app/Services/InsuranceReportService.php` - Logic reference
- `resources/js/Pages/Insurance/Reports/` - UI reference

**Người thực hiện:** Development Team  
**Ngày tạo:** {{ date('Y-m-d') }}  
**Trạng thái:** 🟡 Planning
