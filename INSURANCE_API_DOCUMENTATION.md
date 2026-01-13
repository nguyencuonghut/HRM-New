# Insurance Module API Documentation

## Overview
Complete API endpoints for the Insurance Management Module including reports, components, and change records.

---

## 1. Insurance Components API

### 1.1 Get Active Components (Public for forms)
**Endpoint:** `GET /insurance-components/active`  
**Permission:** None (used in contract forms)  
**Description:** Get all active insurance components for contract form selection

**Response:**
```json
[
  {
    "id": 1,
    "code": "BHXH_HUU_TU",
    "name_vi": "Bảo hiểm hưu trí",
    "name_en": "Pension Insurance",
    "default_rate_employee": 0.08,
    "default_rate_employer": 0.14,
    "default_rate_total": 0.22
  },
  ...
]
```

---

### 1.2 Component Management Page
**Endpoint:** `GET /insurance-components/manage`  
**Permission:** `manage insurance components`  
**Description:** Display admin page to manage insurance component rates

**Response:** Inertia page `Insurance/ComponentIndex`

---

### 1.3 List All Components (Admin)
**Endpoint:** `GET /insurance-components`  
**Permission:** `manage insurance components`  
**Description:** Get all insurance components with full details

**Response:**
```json
[
  {
    "id": 1,
    "code": "BHXH_HUU_TU",
    "name_vi": "Bảo hiểm hưu trí",
    "name_en": "Pension Insurance",
    "default_rate_employee": 0.08,
    "default_rate_employer": 0.14,
    "default_rate_total": 0.22,
    "is_active": true,
    "created_at": "2025-01-01T00:00:00.000000Z",
    "updated_at": "2025-01-10T10:30:00.000000Z"
  },
  ...
]
```

---

### 1.4 Update Component Rates
**Endpoint:** `PUT /insurance-components/{component}`  
**Permission:** `manage insurance components`  
**Description:** Update default rates for an insurance component

**Request Body:**
```json
{
  "default_rate_employee": 0.08,
  "default_rate_employer": 0.14,
  "is_active": true
}
```

**Validation:**
- `default_rate_employee`: required, numeric, 0-1 (percentage as decimal)
- `default_rate_employer`: required, numeric, 0-1
- `is_active`: required, boolean
- `default_rate_total` is auto-calculated (employee + employer)

**Response:**
```json
{
  "message": "Đã cập nhật tỷ lệ đóng BHXH",
  "component": {
    "id": 1,
    "code": "BHXH_HUU_TU",
    "default_rate_employee": 0.08,
    "default_rate_employer": 0.14,
    "default_rate_total": 0.22,
    "is_active": true,
    ...
  }
}
```

**Important Notes:**
- Rate changes only affect NEW contracts created after the change
- Existing contracts and participations retain their original rates
- Only users with `manage insurance components` permission can modify rates

---

## 2. Insurance Reports API

### 2.1 Update Declaration Month
**Endpoint:** `POST /insurance-records/{record}/update-declaration-month`  
**Permission:** `finalize insurance reports`  
**Description:** Update the declaration month for a change record

**Request Body:**
```json
{
  "declaration_month": "2025-02",
  "declaration_override_reason": "Nhân viên ký hợp đồng cuối tháng 01 nhưng xin kê khai từ tháng 02"
}
```

**Validation:**
- `declaration_month`: required, format YYYY-MM
- `declaration_override_reason`: required if month differs from suggested_declaration_month, max 500 chars

**Response:**
```json
{
  "message": "Đã cập nhật tháng kê khai",
  "record": { ... }
}
```

**Error Responses:**
- `422`: Missing override reason when month differs from suggestion
- `403`: User lacks permission to update report

---

### 2.2 Get Contribution Snapshot
**Endpoint:** `GET /insurance-reports/{insuranceReport}/snapshot`  
**Permission:** `view insurance reports`  
**Description:** Get snapshot of all monthly contributions for a finalized report

**Response:**
```json
{
  "contributions": [
    {
      "id": 123,
      "employee": {
        "id": 45,
        "employee_code": "NV001",
        "full_name": "Nguyễn Văn A"
      },
      "base_insurance_salary": 10000000,
      "total_amount": 2200000,
      "items": [
        {
          "component_id": 1,
          "component_code": "BHXH_HUU_TU",
          "component_name": "Bảo hiểm hưu trí",
          "base_type": "INSURANCE_SALARY",
          "base_used": 10000000,
          "rate_total": 0.22,
          "amount": 2200000
        },
        ...
      ]
    },
    ...
  ]
}
```

**Conditions:**
- Report must have status `FINALIZED`
- Returns all contributions with their component breakdown

---

### 2.3 Export to Excel
**Endpoint:** `GET /insurance-reports/{insuranceReport}/export-excel`  
**Permission:** `export insurance reports`  
**Description:** Download Excel file with contribution summary

**Response:** File download `BaoCao_BHXH_{year}_{month}.xlsx`

**Excel Structure:**
- Sheet 1: Employee list with contribution details
- Columns: Employee Code, Name, 5 component amounts, Total
- Footer: Sum totals for each component

**Error Responses:**
- `422`: Report not finalized
- `500`: Export generation failed

---

## 3. Permissions Summary

| Permission | Description | Assigned To |
|-----------|-------------|-------------|
| `view insurance reports` | View insurance reports | HR Staff, Payroll Admin |
| `create insurance reports` | Create new monthly reports | Payroll Admin |
| `approve insurance records` | Approve change records | Payroll Admin |
| `reject insurance records` | Reject change records | Payroll Admin |
| `adjust insurance records` | Adjust record data | Payroll Admin |
| `finalize insurance reports` | Finalize reports (create snapshot) | Payroll Admin |
| `export insurance reports` | Export to Excel | Payroll Admin |
| `delete insurance reports` | Delete reports | Payroll Admin |
| `manage insurance components` | Edit component rates | Payroll Admin, Super Admin |

---

## 4. Route Summary

### Protected Routes (require authentication)
```php
// Insurance Components
Route::get('insurance-components/active', 'InsuranceComponentController@getActiveComponents');
Route::get('insurance-components/manage', 'InsuranceComponentController@index'); // manage permission
Route::get('insurance-components', 'InsuranceComponentController@list'); // manage permission
Route::put('insurance-components/{component}', 'InsuranceComponentController@update'); // manage permission

// Insurance Reports
Route::post('insurance-records/{record}/update-declaration-month', 'InsuranceReportController@updateDeclarationMonth');
Route::get('insurance-reports/{insuranceReport}/snapshot', 'InsuranceReportController@getSnapshot');
Route::get('insurance-reports/{insuranceReport}/export-excel', 'InsuranceReportController@exportToExcel');
```

---

## 5. Frontend Pages

### 5.1 Contract Form
**Path:** `/contracts` (create/edit)  
**Component:** `resources/js/Pages/ContractIndex.vue`  
**Features:**
- Select insurance components with checkboxes
- View default rates for each component
- Special handling for BHTN (base type selection)
- Auto-sync to legacy boolean fields

### 5.2 Insurance Reports List
**Path:** `/insurance-reports`  
**Features:**
- View all monthly reports
- Filter by year/month
- Create new reports
- Navigate to report details

### 5.3 Insurance Report Detail
**Path:** `/insurance-reports/{id}`  
**Component:** `resources/js/Pages/Insurance/Reports/Detail.vue`  
**Tabs:**
1. **TĂNG LAO ĐỘNG** (Increase records)
2. **GIẢM** (Decrease records)
3. **ĐIỀU CHỈNH** (Adjustment records)
4. **TỔNG HỢP ĐÓNG BHXH** (Contribution Summary - requires finalized)

**Features per tab 1-3:**
- Editable declaration month with override reason
- Approve/reject actions
- Validation warnings

**Features tab 4:**
- DataTable with frozen columns
- 5 component breakdown columns
- Totals footer
- Excel export button

### 5.4 Component Management
**Path:** `/insurance-components/manage`  
**Component:** `resources/js/Pages/Insurance/ComponentIndex.vue`  
**Permission:** `manage insurance components`  
**Features:**
- List all 5 components with rates
- Edit dialog for rate_employee, rate_employer
- Auto-calculate rate_total
- Toggle is_active
- Warning: changes only affect NEW contracts

---

## 6. Menu Structure

```
Home
├── Quản lý BHXH (Insurance Management)
│   ├── Báo cáo BHXH (Reports) - Permission: view insurance reports
│   └── Cấu hình BHXH (Component Management) - Permission: manage insurance components
```

---

## 7. Data Flow

### 7.1 Contract Creation → Participation
1. User creates contract with insurance components selected
2. Backend creates `InsuranceParticipation` record
3. Backend creates `InsuranceParticipationComponent` for each enabled component
4. Rates are copied from `InsuranceComponent.default_rate_*` at creation time
5. Future rate changes do NOT affect this contract

### 7.2 Monthly Report Generation
1. System detects changes (new contracts, terminations, adjustments)
2. Creates `InsuranceChangeRecord` for each change
3. Auto-suggests `declaration_month` based on change date
4. HR can override month with reason

### 7.3 Report Finalization
1. HR finalizes report (sets status = FINALIZED)
2. System creates `InsuranceMonthlyContribution` snapshot for each employee
3. System creates `InsuranceMonthlyContributionItem` for each component
4. Snapshot is immutable (used for official declaration)

### 7.4 Excel Export
1. Reads snapshot data from `InsuranceMonthlyContribution` + items
2. Formats into Excel with employee list and component breakdown
3. Downloads file `BaoCao_BHXH_{year}_{month}.xlsx`

---

## 8. Testing Checklist

### Component Management
- [ ] Only users with `manage insurance components` can access page
- [ ] Can view all 5 components
- [ ] Can edit rate_employee and rate_employer
- [ ] rate_total auto-calculates correctly
- [ ] Can toggle is_active
- [ ] Changes saved successfully
- [ ] New contracts use updated rates
- [ ] Existing contracts retain old rates

### Declaration Month
- [ ] Suggested month displays correctly
- [ ] Can select different month from dropdown
- [ ] Override reason required when changing month
- [ ] Validation prevents save without reason
- [ ] Changes saved to backend
- [ ] Toast notifications work

### Contribution Summary
- [ ] Tab disabled until report finalized
- [ ] Tab loads snapshot data after finalization
- [ ] All 5 components display correctly
- [ ] BHTN shows "Cố định" note when FIXED_AMOUNT
- [ ] Totals calculate correctly
- [ ] Excel export downloads file
- [ ] Excel format matches specification

---

## 9. Common Issues & Solutions

### Issue: "403 Forbidden" on component management
**Solution:** Ensure user has `manage insurance components` permission. Check role assignment in database.

### Issue: "Report not finalized" error on snapshot tab
**Solution:** Finalize the report first using the finalize action in the report detail page.

### Issue: Override reason not saving
**Solution:** Ensure reason is provided when declaration_month differs from suggested_declaration_month. Backend validates this.

### Issue: Old contracts showing new rates
**Solution:** This should NOT happen. Rates are copied at participation creation time. If it happens, check `InsuranceParticipationComponent.rate_total` - it should have a value, not be NULL.

---

## 10. Migration & Seeding Commands

```bash
# Run migrations (already completed)
php artisan migrate

# Seed insurance components with default rates
php artisan db:seed --class=InsuranceComponentSeeder

# Seed permissions and roles
php artisan db:seed --class=RolesAndPermissionsSeeder

# Assign permissions to existing users
php artisan db:seed --class=RoleAssignmentSeeder
```

---

## 11. Future Enhancements

- Add audit log for component rate changes
- Add notification when rates are updated
- Add batch update for multiple components
- Add historical rate tracking
- Add report template customization
- Add auto-reminder for declaration deadlines
