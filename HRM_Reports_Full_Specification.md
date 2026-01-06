# HRM – REPORTS SPECIFICATION (DATA + UI + NAVIGATION)
> **Applies to:** HRM-New  
> **Stack:** Laravel + Inertia + Vue 3 + PrimeVue v4  
> **Scope:** Reports only (Recruitment excluded)  
> **Principle:** As-of date, admin-entered confirmed data, professional HRM standard

---

## PART A — DATA & REPORT DESIGN (AS-OF DATE)

### A1. Core Principles
- All allocation reports (department/position) are calculated **as-of date**
- Reports use **existing schema only**
- KPI, Payroll, Insurance data are **already confirmed**
- Reports are **read-only**

### A2. Assignment As-Of Logic (Reusable)
Assignment is considered effective at `as_of_date` if:
- `status = ACTIVE`
- `start_date <= as_of_date OR start_date IS NULL`
- `end_date >= as_of_date OR end_date IS NULL`
- Prefer `is_primary = true`

---

## PART B — REPORT CATALOG

### Executive & People
- RPT-001 Headcount Snapshot
- RPT-010 Employee List
- RPT-011 Data Completeness
- RPT-002 Employee Movement

### Contracts
- RPT-020 Contracts by Status
- RPT-021 Contracts Expiring
- RPT-022 Contract Approval SLA

### Leave
- RPT-030 Monthly Leave Summary
- RPT-031 Leave Balances
- ~~RPT-032 Leave Approval SLA~~ *(Deferred)*

### Payroll
- ~~RPT-040 Payroll Period Summary~~ *(Deferred)*
- ~~RPT-041 Payroll Adjustments~~ *(Deferred)*

### Insurance
- ~~RPT-050 Insurance Monthly Increase / Decrease~~ *(Already implemented - see InsuranceReportController)*
- ~~RPT-051 Insurance Participation~~ *(Deferred)*
- ~~RPT-052 Absences Affecting Insurance~~ *(Deferred)*

### Audit & Ops
- RPT-070 Activity Log
- RPT-071 Backup Health

---

## PART C — REPORT UI DESIGN (PrimeVue v4)

### Global Pattern
1. Breadcrumb  
2. Sticky Filter Bar  
3. KPI Summary  
4. Table / Chart  
5. Drilldown Drawer  
6. Export with metadata  

### Core Components
- ReportFilterBar.vue
- ReportKpiCards.vue
- ReportTable.vue
- ReportDrilldownDrawer.vue

---

## PART D — SIDEBAR & NAVIGATION

### Sidebar
```text
Dashboard
Employees
Contracts
Leave
Payroll
Insurance
Reports
Settings
```

### Reports Hub
- Route: `/reports`
- Acts as catalog & entry point

### Report Pages
Each report is a standalone page:
```text
/reports/headcount
/reports/contracts-expiring
/reports/leave-monthly
/reports/payroll-summary
/reports/insurance-monthly
```

---

## PART E — RBAC

- reports.view
- reports.headcount.view
- reports.contracts.view
- reports.leave.view
- reports.payroll.view
- reports.insurance.view
- reports.audit.view

---

## PART F — IMPLEMENTATION NOTES

- Backend: `GET /reports/{code}`
- Export: `GET /reports/{code}/export`
- Always require as-of or period params
- Reuse assignment-as-of logic

---

## PART G — UX CHECKLIST

- Single Reports menu in sidebar
- Reports Hub exists
- As-of respected everywhere
- Drawer-based drilldown
- Export includes filters
