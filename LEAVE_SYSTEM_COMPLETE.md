# LEAVE MANAGEMENT SYSTEM - IMPLEMENTATION COMPLETE

## 📋 Overview

Successfully redesigned and implemented a comprehensive leave management system compliant with **Vietnamese Labor Law 2019** (Luật Lao động 2019).

## ✅ Completed Tasks

### 1. **Pro-rata Annual Leave Calculation**
- **Implementation**: 1 day per month worked
- **Formula**: `workingMonths = floor(start.diffInMonths(end)) + 1`
- **Partial months**: Count as full months
- **Example**: Employee hired Dec 13, 2025 gets **1 day** for December 2025
- **Seniority bonus**: Only applies if worked full 12 months (+1 day per 5 years)

### 2. **Event-based Leave Types**
Changed from quota-based to event-based for:
- **PERSONAL_PAID**: 0 days_per_year (calculated per event)
- **SICK**: 0 days_per_year (unlimited with medical cert)
- **MATERNITY**: 0 days_per_year (calculated based on conditions)
- **UNPAID**: 0 days_per_year (no limit)

### 3. **Leave Calculation Service**
Created `app/Services/LeaveCalculationService.php` with methods:

#### Personal Paid Leave (Phép riêng có lương - Điều 115)
```php
calculatePersonalPaidLeaveDays(string $reason): int
```
- MARRIAGE: 3 days
- CHILD_MARRIAGE: 1 day
- PARENT_DEATH: 3 days
- SIBLING_DEATH: 1 day
- SPOUSE_BIRTH: 5 days
- SPOUSE_BIRTH_TWINS: 7 days
- SPOUSE_BIRTH_CAESAREAN: 7 days
- MOVING_HOUSE: 1 day

#### Maternity Leave (Phép thai sản - Điều 139)
```php
calculateMaternityLeaveDays(array $conditions): int
```
- Base: 180 days (calendar days, includes weekends)
- Twins: +30 days per additional child
- Caesarean: +15 days
- Children under 36 months: +30 days total

**Example**: Twins + Caesarean + Young children = 180 + 30 + 15 + 30 = **255 days**

#### Sick Leave (Phép ốm - Điều 114)
```php
validateSickLeave(array $data): array
```
- Requires medical certificate
- No day limit
- Company pays max 30 days, BHXH pays after
- Returns `['valid' => bool, 'message' => string]`

### 4. **Database Schema Updates**
Added fields to `leave_requests` table:
- `personal_leave_reason` - For PERSONAL_PAID events
- `expected_due_date` - For MATERNITY (due date)
- `twins_count` - Number of children born
- `is_caesarean` - Boolean for caesarean birth
- `children_under_36_months` - Count for additional days
- `medical_certificate_path` - File path for SICK leave

### 5. **Form Request Validation**
Created `app/Http/Requests/StoreLeaveRequestRequest.php`:
- **Conditional validation** based on leave type
- **Auto-calculation** of days in `prepareForValidation()`
- **Vietnamese error messages**
- **File upload validation** for medical certificates (PDF/JPG/PNG, max 5MB)

### 6. **Controller Updates**
Updated `app/Http/Controllers/LeaveRequestController.php`:
- Integrated `StoreLeaveRequestRequest` validation
- Added `LeaveCalculationService` dependency
- **Balance checking** only for ANNUAL leave
- **File upload handling** for medical certificates
- **Event-based validation** for PERSONAL_PAID, MATERNITY, SICK

### 7. **Frontend Form (Vue 3 + PrimeVue)**
Updated `resources/js/Pages/LeaveRequests/Form.vue`:
- **Conditional fields** based on leave type
- **Personal Leave**: Dropdown with auto-calculated days
- **Maternity Leave**: Due date, twins count, caesarean checkbox, young children count
- **Sick Leave**: File upload for medical certificate
- **Auto-calculation**: Days calculated based on leave type
- **Real-time feedback**: Shows calculated days instantly

### 8. **Employment Period System**
Created `app/Listeners/CreateEmploymentPeriod.php`:
- Auto-creates `EmployeeEmployment` on contract approval
- Updates `employee.hire_date` to match current employment
- Handles rehired employees (new employment period vs existing)

### 9. **Insurance Integration**
Updated `app/Listeners/CreateInsuranceParticipation.php`:
- Uses `employment.start_date` instead of `contract.start_date`
- More accurate for insurance participation tracking

### 10. **Leave Balance Initialization**
Updated `app/Console/Commands/InitializeLeaveBalances.php`:
- Changed query from active contracts to active employments
- Fixed `floor()` calculation for partial months
- Only initializes ANNUAL leave (other types are event-based)
- Supports employee_code parameter for specific employee initialization

## 🧪 Test Results

### ✅ All Tests Passing

```
1. ANNUAL LEAVE - Pro-rata Calculation
   Employee: Bùi Thị Nết (Code: 185)
   Employment Start: 2025-12-13
   Total Days: 1.00 ✅
   Expected: 1 day (December 2025)

2. PERSONAL PAID LEAVE - Event-based Calculation
   MARRIAGE: 3 days ✅
   All 8 event types working correctly

3. MATERNITY LEAVE - Calculation
   Base (180 days): ✅
   Twins (+30): 210 days ✅
   Caesarean (+15): 195 days ✅
   Young children (+30): 210 days ✅
   All conditions: 255 days ✅

4. SICK LEAVE - Validation
   Without medical cert: Invalid ✅
   With medical cert: Valid ✅

5. LEAVE TYPES - Vietnamese Labor Law 2019
   All 7 types configured correctly ✅
```

## 📊 System Architecture

### Leave Type Categories

1. **Quota-based** (Check balance before approval)
   - ANNUAL: 12 days/year, pro-rated

2. **Event-based** (No balance check, validate conditions)
   - PERSONAL_PAID: 3/1 days per event
   - MATERNITY: 180+ days calculated
   - SICK: Unlimited with medical cert

3. **Simple** (No quota, no special validation)
   - UNPAID: By agreement
   - STUDY: Company policy
   - BUSINESS: Work day, not leave

### Data Flow

```
User creates leave request
    ↓
Form validates based on leave type
    ↓
StoreLeaveRequestRequest auto-calculates days
    ↓
Controller validates with LeaveCalculationService
    ↓
Controller checks balance (if ANNUAL)
    ↓
LeaveRequest saved
    ↓
Approval workflow starts
```

## 🎯 Key Features

### 1. **Vietnamese Law Compliance**
- Điều 113: Phép năm (Annual leave)
- Điều 114: Phép ốm (Sick leave)
- Điều 115: Phép riêng (Personal paid leave)
- Điều 139: Phép thai sản (Maternity leave)

### 2. **Calendar Days vs Working Days**
- **ANNUAL**: Working days (excludes weekends)
- **MATERNITY**: Calendar days (includes weekends)
- **PERSONAL_PAID**: Event days (not affected by weekends)

### 3. **Pro-rata Calculation**
- Partial month = full month
- Example: Hired Dec 13 → Gets 1 day (December)
- Example: Hired Nov 5 → Gets 2 days (Nov + Dec)

### 4. **File Upload**
- Medical certificates for sick leave
- Stored in `storage/app/public/medical-certificates/`
- Validation: PDF, JPG, PNG only, max 5MB

### 5. **Auto-calculation**
- Personal leave: Based on reason selected
- Maternity leave: Based on conditions (twins, caesarean, etc.)
- Sick leave: Date range (user input)

## 🔧 Configuration Files Updated

1. `database/migrations/2025_12_04_100000_create_leave_tables.php`
2. `database/seeders/LeaveTypeSeeder.php`
3. `app/Services/LeaveCalculationService.php` *(new)*
4. `app/Http/Requests/StoreLeaveRequestRequest.php` *(new)*
5. `app/Http/Controllers/LeaveRequestController.php`
6. `app/Console/Commands/InitializeLeaveBalances.php`
7. `app/Listeners/CreateEmploymentPeriod.php` *(new)*
8. `app/Listeners/CreateInsuranceParticipation.php`
9. `app/Listeners/InitializeLeaveBalanceForContract.php`
10. `resources/js/Pages/LeaveRequests/Form.vue`

## 📝 Usage Examples

### Initialize Leave Balances
```bash
# All employees with active employment
php artisan leave:initialize-balances 2025

# Specific employee by employee_code
php artisan leave:initialize-balances 2025 185
```

### Create Leave Request (API)
```javascript
// Personal paid leave
{
  employee_id: "uuid",
  leave_type_id: "PERSONAL_PAID",
  personal_leave_reason: "MARRIAGE", // Auto-calculates to 3 days
  start_date: "2025-12-20",
  end_date: "2025-12-22",
  reason: "My wedding"
}

// Maternity leave
{
  employee_id: "uuid",
  leave_type_id: "MATERNITY",
  expected_due_date: "2025-06-15",
  twins_count: 2,
  is_caesarean: true,
  children_under_36_months: 1,
  start_date: "2025-04-16", // 60 days before due date
  end_date: "2025-12-31" // Auto-calculated: 255 days total
}

// Sick leave
{
  employee_id: "uuid",
  leave_type_id: "SICK",
  medical_certificate_path: File,
  start_date: "2025-12-15",
  end_date: "2025-12-20",
  reason: "Flu"
}
```

## 🚀 Next Steps (Optional Enhancements)

1. **UI Improvements**
   - Add real-time balance display when selecting leave type
   - Show historical leave requests in sidebar
   - Add calendar view for team leave schedules

2. **Notifications**
   - Email/SMS when leave approved/rejected
   - Reminder before leave starts
   - Manager notification for pending approvals

3. **Reports**
   - Leave usage by department
   - Leave trends over time
   - Employee leave history

4. **Integration**
   - Sync with Google Calendar
   - Export to payroll system
   - Attendance system integration

## 📚 References

- [Vietnamese Labor Law 2019](https://vanbanphapluat.co/luat-lao-dong-2019) - Luật Lao động số 45/2019/QH14
- Điều 113: Nghỉ phép năm (Annual leave)
- Điều 114: Nghỉ ốm (Sick leave)
- Điều 115: Nghỉ phép riêng (Personal paid leave)
- Điều 139: Nghỉ thai sản (Maternity leave)

## ✅ Implementation Status

| Feature | Status | Notes |
|---------|--------|-------|
| Pro-rata calculation | ✅ Complete | 1 day/month worked |
| Event-based leave types | ✅ Complete | PERSONAL_PAID, MATERNITY, SICK |
| Leave calculation service | ✅ Complete | All validations implemented |
| Database schema | ✅ Complete | New fields added |
| Form request validation | ✅ Complete | Auto-calculation working |
| Controller integration | ✅ Complete | Balance checking, file upload |
| Frontend form | ✅ Complete | Conditional fields, auto-calc |
| Employment system | ✅ Complete | Auto-create on contract approval |
| Insurance integration | ✅ Complete | Uses employment dates |
| Command updates | ✅ Complete | Works with employments |
| Testing | ✅ Complete | All scenarios tested |

---

**System is ready for production use! 🎉**

Date: 2025-12-XX
Implemented by: GitHub Copilot
