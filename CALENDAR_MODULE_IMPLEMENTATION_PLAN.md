# 📅 CALENDAR MODULE - KẾ HOẠCH TRIỂN KHAI (ADMIN/MANAGER ONLY)

> **⚠️ CRITICAL DESIGN NOTE:**  
> Hệ thống HongHa HRM chỉ có **5 roles cho quản trị**: Super Admin, HR Admin, Payroll Admin, Director, Department Manager.  
> **KHÔNG CÓ** role "Employee" cho nhân viên thường.  
> → Calendar module chỉ phục vụ **Admin/Manager** để quản lý và theo dõi nhân viên.

## 🎯 PHÂN TÍCH DỰA TRÊN HỆ THỐNG HIỆN TẠI

### ✅ DATA SOURCES CÓ SẴN (Từ Migrations & Seeders)

#### 1. **LEAVE EVENTS** ✅ (leave_requests table)
**Data:** 
- 7 loại phép: ANNUAL, SICK, PERSONAL_PAID, UNPAID, MATERNITY, STUDY, BUSINESS
- Fields: start_date, end_date, days, status, leave_type_id
- Status: DRAFT, PENDING, APPROVED, REJECTED, CANCELLED
- Colors đã có sẵn trong leave_types

**Ví dụ thực tế từ seeder:**
```php
// Thai sản - 122 ngày (2025-08-01 đến 2025-11-30)
'leave_type' => 'MATERNITY',
'start_date' => '2025-08-01',
'end_date' => '2025-11-30',
'days' => 122,
'status' => 'APPROVED'

// Ốm - 56 ngày
'leave_type' => 'SICK',
'start_date' => '2025-10-15',
'end_date' => '2025-12-09',
'days' => 56,
'status' => 'APPROVED'
```

**Calendar Events:**
```javascript
{
  type: 'leave',
  title: 'Nghỉ thai sản - Bùi Thị Nụ',
  start: '2025-08-01',
  end: '2025-11-30',
  color: '#ec4899', // pink từ leave_types
  status: 'APPROVED',
  allDay: true,
  employee: { code: '2142', name: 'Bùi Thị Nụ' },
  leaveType: 'MATERNITY',
  days: 122
}
```

---

#### 2. **CONTRACT EVENTS** ✅ (contracts table)
**Data:**
- Contract start_date, end_date
- Contract types: INDEFINITE, FIXED_TERM, PROBATION, SEASONAL
- Status: DRAFT, PENDING, ACTIVE, EXPIRED, TERMINATED

**Ví dụ thực tế từ seeder:**
```php
// Thử việc sắp hết hạn
'employee_code' => '2571',
'contract_type' => 'PROBATION',
'start_date' => '2025-10-01',
'end_date' => '2025-12-31', // ⚠️ Sắp hết hạn
'status' => 'ACTIVE'

// Hợp đồng không xác định thời hạn (không có end_date)
'employee_code' => '312',
'contract_type' => 'INDEFINITE',
'start_date' => '2015-01-01',
'end_date' => null // Không hết hạn
```

**Calendar Events:**
```javascript
// Contract expiry warning (60 days before)
{
  type: 'contract_expiry_warning',
  title: '⚠️ Hợp đồng thử việc sắp hết hạn - Bùi Thế Tuyến',
  start: '2025-11-01', // 60 days before expiry
  color: '#f59e0b', // orange warning
  allDay: true,
  employee: { code: '2571', name: 'Bùi Thế Tuyến' },
  contractType: 'PROBATION',
  expiryDate: '2025-12-31',
  daysUntilExpiry: 60
}

// Contract expiry urgent (15 days before)
{
  type: 'contract_expiry_urgent',
  title: '🚨 Hợp đồng hết hạn trong 15 ngày - Bùi Thế Tuyến',
  start: '2025-12-16',
  color: '#ef4444', // red urgent
  allDay: true,
  daysUntilExpiry: 15
}

// Contract start date
{
  type: 'contract_start',
  title: 'Bắt đầu hợp đồng - Bùi Thế Tuyến',
  start: '2025-10-01',
  color: '#10b981', // green
  allDay: true
}
```

---

#### 3. **EMPLOYEE BIRTHDAYS** ✅ (employees table)
**Data:**
- Field: dob (date of birth)

**Ví dụ từ seeder:**
```php
'employee_code' => '2571',
'full_name' => 'Bùi Thế Tuyến',
'dob' => '1990-05-15'
```

**Calendar Events:**
```javascript
{
  type: 'birthday',
  title: '🎂 Sinh nhật - Bùi Thế Tuyến',
  start: '2026-05-15', // Current year
  color: '#ec4899', // pink
  allDay: true,
  recurring: 'yearly',
  employee: { code: '2571', name: 'Bùi Thế Tuyến' },
  age: 36
}
```

---

#### 4. **WORK ANNIVERSARY** ✅ (contracts table / employees.hire_date)
**Data:**
- Contract start_date (first contract = hire_date)
- Or employees.hire_date if set

**Ví dụ từ seeder:**
```php
// Giám đốc - 10 năm thâm niên
'employee_code' => '312',
'start_date' => '2015-01-01' // Hired Jan 1, 2015
```

**Calendar Events:**
```javascript
{
  type: 'work_anniversary',
  title: '🎉 Kỷ niệm 11 năm - Tạ Văn Toại',
  start: '2026-01-01',
  color: '#8b5cf6', // purple
  allDay: true,
  recurring: 'yearly',
  employee: { code: '312', name: 'Tạ Văn Toại' },
  yearsOfService: 11
}
```

---

#### 5. **BENEFITS EVENTS** ✅ (employee_benefit_payouts table)
**Data:**
- 8 loại phúc lợi từ BenefitTypeSeeder:
  - BIRTHDAY: Sinh nhật
  - BEREAVEMENT: Hiếu (ma chay)
  - WEDDING: Hỷ (đám cưới)
  - SICK: Ốm
  - CHILDBIRTH: Sinh con
  - CHILD_SICK: Con ốm
  - LONGEVITY: Mừng thọ
  - GOOD_STUDENT: Học sinh giỏi

**Calendar Events:**
```javascript
{
  type: 'benefit_payout',
  title: '🎁 Chi phúc lợi sinh nhật - Bùi Thế Tuyến',
  start: '2026-05-15',
  color: '#06b6d4', // cyan
  allDay: true,
  benefitType: 'BIRTHDAY',
  amount: 500000
}
```

---

#### 6. **PERFORMANCE REVIEW EVENTS** ✅ (employee_annual_reviews table)
**Data:**
- Đánh giá cuối năm (1 record/employee/year)
- Fields: year, final_rating (A/B/C/D), input_at

**Ví dụ:**
```php
'employee_id' => '2571',
'year' => 2025,
'final_rating' => 'B',
'input_at' => '2025-12-31'
```

**Calendar Events:**
```javascript
// Review due reminder
{
  type: 'performance_review_due',
  title: '📊 Đánh giá cuối năm - Deadline',
  start: '2025-12-15', // 15 days before year-end
  color: '#f59e0b', // orange
  allDay: true,
  year: 2025
}

// Review completed
{
  type: 'performance_review_completed',
  title: '✅ Đánh giá hoàn thành - Bùi Thế Tuyến',
  start: '2025-12-31',
  color: '#10b981', // green
  allDay: true,
  rating: 'B'
}
```

---

#### 7. **MONTHLY KPI EVENTS** ✅ (employee_kpi_months table)
**Data:**
- KPI theo tháng (1 record/employee/month)
- Fields: year, month, kpi_score, input_at

**Calendar Events:**
```javascript
// KPI input deadline (end of each month)
{
  type: 'kpi_deadline',
  title: '📈 KPI tháng 12 - Deadline nhập',
  start: '2025-12-31',
  color: '#f59e0b', // orange
  allDay: true,
  month: 12,
  year: 2025
}
```

---

#### 8. **REWARD/DISCIPLINE EVENTS** ✅ (employee_reward_disciplines table)
**Data:**
- Types: REWARD (thưởng) / DISCIPLINE (kỷ luật)
- Fields: decision_date, effective_date, category

**Calendar Events:**
```javascript
{
  type: 'reward',
  title: '🏆 Quyết định khen thưởng - Bùi Thế Tuyến',
  start: '2026-01-15', // effective_date
  color: '#10b981', // green
  allDay: true,
  category: 'BONUS',
  amount: 5000000
}

{
  type: 'discipline',
  title: '⚠️ Kỷ luật - Nhắc nhở',
  start: '2026-02-01',
  color: '#ef4444', // red
  allDay: true,
  category: 'WARNING'
}
```

---

#### 9. **INSURANCE EVENTS** ✅ (insurance_change_records table)
**Data:**
- Change types: INCREASE, DECREASE, ADJUST
- Reasons: NEW_HIRE, TERMINATION, SALARY_CHANGE, LONG_ABSENCE

**Ví dụ từ seeder:**
```php
// Nhân viên mới tham gia BHXH
'employee_code' => '1992',
'change_type' => 'INCREASE',
'reason' => 'NEW_HIRE',
'effective_date' => '2024-12-01'

// Nghỉ thai sản - tạm dừng BHXH
'employee_code' => '2142',
'change_type' => 'DECREASE',
'reason' => 'LONG_ABSENCE',
'effective_date' => '2025-08-01'
```

**Calendar Events:**
```javascript
{
  type: 'insurance_increase',
  title: '📋 Tăng BHXH - Phạm Hồng Hải',
  start: '2024-12-01',
  color: '#10b981', // green
  allDay: true,
  reason: 'NEW_HIRE'
}

{
  type: 'insurance_decrease',
  title: '📋 Giảm BHXH - Bùi Thị Nụ (Thai sản)',
  start: '2025-08-01',
  color: '#ef4444', // red
  allDay: true,
  reason: 'LONG_ABSENCE'
}
```

---

### 🔴 DATA CHƯA CÓ (CẦN BỔ SUNG)

#### 1. **COMPANY HOLIDAYS** 🔴 (Cần table mới)

**Thiết kế table:**
```sql
CREATE TABLE company_holidays (
    id UUID PRIMARY KEY,
    name VARCHAR(255), -- Tên ngày lễ
    holiday_date DATE, -- Ngày lễ
    year INT, -- Năm áp dụng
    is_compensated BOOLEAN DEFAULT false, -- Có làm bù không
    compensated_date DATE, -- Ngày làm bù
    is_recurring BOOLEAN DEFAULT false, -- Lặp hàng năm không
    note TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Dữ liệu mẫu Vietnam 2026:**
```php
$holidays = [
    ['name' => 'Tết Dương lịch', 'date' => '2026-01-01', 'recurring' => true],
    ['name' => 'Tết Âm lịch', 'date' => '2026-01-29', 'recurring' => false], // Varies yearly
    ['name' => 'Tết Âm lịch', 'date' => '2026-01-30', 'recurring' => false],
    ['name' => 'Tết Âm lịch', 'date' => '2026-01-31', 'recurring' => false],
    ['name' => 'Tết Âm lịch', 'date' => '2026-02-01', 'recurring' => false],
    ['name' => 'Tết Âm lịch', 'date' => '2026-02-02', 'recurring' => false],
    ['name' => 'Giỗ Tổ Hùng Vương', 'date' => '2026-04-02', 'recurring' => false],
    ['name' => '30/4 - Giải phóng', 'date' => '2026-04-30', 'recurring' => true],
    ['name' => '01/5 - Quốc tế lao động', 'date' => '2026-05-01', 'recurring' => true],
    ['name' => '02/9 - Quốc khánh', 'date' => '2026-09-02', 'recurring' => true],
];
```

**Calendar Events:**
```javascript
{
  type: 'company_holiday',
  title: '🎊 Tết Nguyên Đán',
  start: '2026-01-29',
  end: '2026-02-02',
  color: '#dc2626', // red
  allDay: true,
  isCompanyWide: true,
  isRecurring: false
}
```

---

#### 2. **PERSONAL EVENTS** 🔴 (Optional - User-created events)

**Thiết kế table:**
```sql
CREATE TABLE calendar_events (
    id UUID PRIMARY KEY,
    user_id UUID, -- Owner của event
    title VARCHAR(255),
    description TEXT,
    start_datetime DATETIME,
    end_datetime DATETIME,
    all_day BOOLEAN DEFAULT false,
    color VARCHAR(7),
    location VARCHAR(255),
    reminder_minutes INT, -- Nhắc trước bao nhiêu phút
    is_private BOOLEAN DEFAULT false, -- Chỉ user mới thấy
    recurrence_rule VARCHAR(255), -- RRULE format (RFC 5545)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Calendar Events:**
```javascript
{
  type: 'personal',
  title: 'Họp team weekly',
  start: '2026-01-22 14:00',
  end: '2026-01-22 15:00',
  color: '#6b7280', // gray
  allDay: false,
  location: 'Phòng họp A',
  reminder: 15, // 15 mins before
  recurrence: 'FREQ=WEEKLY;BYDAY=TH' // Every Thursday
}
```

---

## 🎨 CALENDAR UI DESIGN (Cho Admin/Manager)

### **HR Admin Calendar View (Company-wide)**

```
┌────────────────────────────────────────────────────────────┐
│ 📅 Quản lý lịch công ty              [Month] [Week] [Day] │
│                                                             │
│ Filters:                                                    │
│ • Department: [All ▼] [Hành chính] [Kinh doanh]          │
│ • Position:   [All ▼] [Nhân viên] [Trưởng phòng]         │
│ • Employee:   [All ▼] Search by name/code...              │
│ • Event Type: ☑ Nghỉ phép  ☑ Hợp đồng  ☑ Sinh nhật      │
│               ☑ Đánh giá   ☑ Phúc lợi  ☐ Thưởng/Kỷ luật │
│                                                             │
│ [Export Excel] [Export PDF] [Manage Holidays]             │
├────────────────────────────────────────────────────────────┤
│                  January 2026                              │
│ ┌────┬────┬────┬────┬────┬────┬────┐                     │
│ │ Mo │ Tu │ We │ Th │ Fr │ Sa │ Su │                     │
│ ├────┼────┼────┼────┼────┼────┼────┤                     │
│ │ 29 │ 30 │ 31 │  1 │  2 │  3 │  4 │                     │
│ │🎊  │🎊  │🎊  │🎊  │🎊  │    │    │                     │
│ │Tết │Tết │Tết │Tết │Tết │    │    │                     │
│ │20p │20p │20p │20p │20p │    │    │ ← 20 người nghỉ    │
│ ├────┼────┼────┼────┼────┼────┼────┤                     │
│ │  5 │  6 │  7 │  8 │  9 │ 10 │ 11 │                     │
│ │    │    │🏖️  │🏖️  │🏖️  │    │🎂  │                     │
│ │    │    │3p  │5p  │2p  │    │1p  │ ← View details      │
│ ├────┼────┼────┼────┼────┼────┼────┤                     │
│ │ 12 │ 13 │ 14 │ 15 │ 16 │ 17 │ 18 │                     │
│ │    │    │⚠️  │    │    │    │    │                     │
│ │    │    │3HĐ │    │    │    │    │ ← 3 contracts expiry│
│ ├────┼────┼────┼────┼────┼────┼────┤                     │
│ │ 19 │ 20 │ 21 │ 22 │ 23 │ 24 │ 25 │                     │
│ │    │    │    │📊  │    │    │🏖️  │                     │
│ │    │    │    │KPI │    │    │8p  │                     │
│ │    │    │    │    │    │    │phép │                     │
│ └────┴────┴────┴────┴────┴────┴────┘                     │
│                                                             │
│ Thống kê hôm nay (21/01/2026):                            │
│ • 🏖️ Nghỉ phép: 5 người (25% team)                       │
│ • 📊 Deadline KPI: 15 người chưa nhập                     │
│ • ⚠️ Hợp đồng hết hạn 60 ngày: 3 người                   │
│                                                             │
│ Sự kiện quan trọng sắp tới:                               │
│ • 14/01: 3 hợp đồng hết hạn - Cần gia hạn                │
│ • 25/01: 8 người nghỉ phép - Team coverage 40%           │
│ • 31/01: Deadline annual reviews Q4/2025                  │
└────────────────────────────────────────────────────────────┘
```

### **Department Manager Calendar View (Team-focused)**

```
┌────────────────────────────────────────────────────────────┐
│ 📅 Lịch Phòng Hành chính             [Month] [Week] [Day] │
│                                                             │
│ Team: 8 người | Đang làm: 6 | Nghỉ phép: 2               │
│                                                             │
│ [View All] [On Leave] [Birthdays] [Contract Expiry]       │
├────────────────────────────────────────────────────────────┤
│ Team Summary:                                              │
│ ┌─────────────────┬─────────────────┬─────────────────┐  │
│ │ 🏖️ Nghỉ hôm nay │ 🎂 Sinh nhật    │ ⚠️ HĐ hết hạn  │  │
│ │      2 người    │   tháng này     │   60 ngày       │  │
│ │                 │    1 người      │    1 người      │  │
│ │ • Bùi Thế Tuyến │                 │                 │  │
│ │   (ANNUAL-2d)   │ • 15/02: Tuyến  │ • 31/12: Tuyến  │  │
│ │ • Hoàng Ngọc    │                 │   (PROBATION)   │  │
│ │   (SICK-1d)     │                 │                 │  │
│ │ [Approve]       │ [Send wishes]   │ [Renew]         │  │
│ └─────────────────┴─────────────────┴─────────────────┘  │
│                                                             │
│                  January 2026                              │
│ ┌────┬────┬────┬────┬────┬────┬────┐                     │
│ │ Mo │ Tu │ We │ Th │ Fr │ Sa │ Su │                     │
│ ├────┼────┼────┼────┼────┼────┼────┤                     │
│ │ 19 │ 20 │ 21 │ 22 │ 23 │ 24 │ 25 │                     │
│ │    │    │🏖️ │🏖️ │    │    │🏖️ │                     │
│ │    │    │2p  │2p  │    │    │3p  │                     │
│ │    │    │60% │60% │100%│100%│40% │ ← Team coverage    │
│ └────┴────┴────┴────┴────┴────┴────┘                     │
│                                                             │
│ Quick Actions:                                             │
│ • [Approve Leave] • [View Profile] • [Create Review]      │
│                                                             │
│ Team Workload (This Week):                                │
│ ▓▓▓▓▓▓░░░░ 60% (6/10 people working)                     │
└────────────────────────────────────────────────────────────┘
```

### **Director Calendar View (Executive Overview)**

```
┌────────────────────────────────────────────────────────────┐
│ 📅 Executive Calendar                [Month] [Week] [Day] │
│                                                             │
│ Critical Events Only                                       │
├────────────────────────────────────────────────────────────┤
│ Company Overview:                                          │
│ ┌──────────────┬──────────────┬──────────────┬──────────┐│
│ │ 👥 Headcount │ 🏖️ On Leave │ ⚠️ Critical  │ 📊 Reviews││
│ │    20 EE     │   5 (25%)    │   8 items    │  60%     ││
│ └──────────────┴──────────────┴──────────────┴──────────┘│
│                                                             │
│ 🚨 Critical Events (Next 30 days):                        │
│ ┌──────────────────────────────────────────────────────┐ │
│ │ 14/01 • ⚠️ 3 contracts expiring (60-day warning)     │ │
│ │         → Bùi Thế Tuyến, Hoàng Văn A, Nguyễn Thị B │ │
│ │         [Review] [Approve Renewal]                   │ │
│ ├──────────────────────────────────────────────────────┤ │
│ │ 25/01 • 🏖️ High leave volume (8 people, 40% team)   │ │
│ │         → Coverage risk for Kinh doanh dept         │ │
│ │         [View Details]                               │ │
│ ├──────────────────────────────────────────────────────┤ │
│ │ 31/01 • 📊 Annual Review deadline (8 pending)        │ │
│ │         → 40% completion rate                        │ │
│ │         [Follow up]                                  │ │
│ └──────────────────────────────────────────────────────┘ │
│                                                             │
│ [View Full Calendar] [Export Report] [Drill Down Dept]    │
└────────────────────────────────────────────────────────────┘
```

---

## 🎯 PERMISSIONS & VISIBILITY

### **HR Admin / Payroll Admin Role**
```php
Calendar Permissions:
- view calendar             ✅ (all employees)
- manage company holidays   ✅
- view all events           ✅ (with filters)
- export calendar           ✅
- calendar analytics        ✅
```

**Visible Events:**
- All employees' leaves (all statuses)
- All contract events (starts, expiry warnings)
- All birthdays & anniversaries
- All performance reviews & KPI deadlines
- All rewards/disciplines
- All benefits payouts
- All insurance changes
- Company holidays

---

### **Department Manager Role**
```php
Calendar Permissions:
- view team calendar        ✅ (own department only)
- view team leaves          ✅
- view team birthdays       ✅
- view team contract expiry ✅
- quick approve leaves      ✅ (from calendar)
- view team coverage        ✅
```

**Visible Events (Filtered by department):**
- Team members' approved leaves (+ pending for approval)
- Team members' birthdays & anniversaries
- Team members' contract expiry warnings
- Team members' performance deadlines
- Company holidays

**Access Control:**
```php
// Manager chỉ xem employees trong department mình quản lý
// Dựa vào employee_assignments where position_id IN (HEAD, DEPUTY)
```

---

### **Director Role**
```php
Calendar Permissions:
- view executive calendar   ✅ (critical events only)
- view company metrics      ✅
- drill down departments    ✅
- export executive reports  ✅
```

**Visible Events (Critical only):**
- Contracts expiring < 60 days
- Leave requests > 5 days
- Annual reviews pending
- Insurance changes pending approval
- Company holidays

**Thresholds (Configurable):**
```php
'calendar.director_filters' => [
    'contract_expiry_days' => 60,
    'leave_min_days' => 5,
    'high_leave_volume_threshold' => 0.3, // 30% team
]
```

---

## 🏗️ TECHNICAL IMPLEMENTATION

### **Phase 1: HR Admin Calendar (Week 1)**

#### **1.1 Database**
```bash
php artisan make:migration create_company_holidays_table
```

**Migration: company_holidays**
```php
Schema::create('company_holidays', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->date('holiday_date')->index();
    $table->unsignedInteger('year')->index();
    $table->boolean('is_compensated')->default(false);
    $table->date('compensated_date')->nullable();
    $table->boolean('is_recurring')->default(false);
    $table->text('note')->nullable();
    $table->timestamps();
    
    $table->index(['year', 'holiday_date']);
});
```

#### **1.2 Models**
```bash
php artisan make:model CompanyHoliday
php artisan make:model CalendarEvent
```

#### **1.3 Seeder**
```bash
php artisan make:seeder CompanyHolidaySeeder
```

**CompanyHolidaySeeder.php:**
```php
<?php

namespace Database\Seeders;

use App\Models\CompanyHoliday;
use Illuminate\Database\Seeder;

class CompanyHolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            // 2026 Vietnam Holidays
            ['name' => 'Tết Dương lịch', 'date' => '2026-01-01', 'year' => 2026, 'recurring' => true],
            ['name' => 'Tết Âm lịch', 'date' => '2026-01-29', 'year' => 2026, 'recurring' => false],
            ['name' => 'Tết Âm lịch', 'date' => '2026-01-30', 'year' => 2026, 'recurring' => false],
            ['name' => 'Tết Âm lịch', 'date' => '2026-01-31', 'year' => 2026, 'recurring' => false],
            ['name' => 'Tết Âm lịch', 'date' => '2026-02-01', 'year' => 2026, 'recurring' => false],
            ['name' => 'Tết Âm lịch', 'date' => '2026-02-02', 'year' => 2026, 'recurring' => false],
            ['name' => 'Giỗ Tổ Hùng Vương', 'date' => '2026-04-02', 'year' => 2026, 'recurring' => false],
            ['name' => '30/4 - Giải phóng', 'date' => '2026-04-30', 'year' => 2026, 'recurring' => true],
            ['name' => '01/5 - Quốc tế lao động', 'date' => '2026-05-01', 'year' => 2026, 'recurring' => true],
            ['name' => '02/9 - Quốc khánh', 'date' => '2026-09-02', 'year' => 2026, 'recurring' => true],
        ];

        foreach ($holidays as $holiday) {
            CompanyHoliday::create([
                'name' => $holiday['name'],
                'holiday_date' => $holiday['date'],
                'year' => $holiday['year'],
                'is_recurring' => $holiday['recurring'],
            ]);
        }
    }
}
```

#### **1.4 Controller**
```bash
php artisan make:controller CalendarController
```

**CalendarController.php (Admin-focused):**
```php
<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\CompanyHoliday;
use App\Models\EmployeeAnnualReview;
use App\Models\EmployeeBenefitPayout;
use App\Models\EmployeeRewardDiscipline;
use App\Models\EmployeeAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    /**
     * Show calendar page (Admin/Manager view)
     */
    public function index()
    {
        $user = auth()->user();
        
        // Check permissions
        if ($user->hasRole(['Super Admin', 'HR Admin', 'Payroll Admin'])) {
            $viewType = 'company-wide'; // All employees
        } elseif ($user->hasRole('Department Manager')) {
            $viewType = 'department'; // Own department only
        } elseif ($user->hasRole('Director')) {
            $viewType = 'executive'; // Critical events only
        } else {
            abort(403, 'Unauthorized access to calendar');
        }
        
        return Inertia::render('Calendar/Index', [
            'viewType' => $viewType,
            'departments' => \App\Models\Department::select('id', 'name')->get(),
            'positions' => \App\Models\Position::select('id', 'name')->get(),
        ]);
    }

    /**
     * Get calendar events (Admin/Manager)
     * Supports filters: departments, positions, employees, event types
     */
    public function events(Request $request)
    {
        $user = auth()->user();
        $start = Carbon::parse($request->start);
        $end = Carbon::parse($request->end);

        // Get employees based on role
        $employees = $this->getAuthorizedEmployees($user, $request);

        $events = [];

        // 1. Leave events
        if ($request->get('filter_leaves', true)) {
            $events = array_merge($events, $this->getLeaveEvents($employees, $start, $end));
        }

        // 2. Contract events
        if ($request->get('filter_contracts', true)) {
            $events = array_merge($events, $this->getContractEvents($employees, $start, $end));
        }

        // 3. Birthdays
        if ($request->get('filter_birthdays', true)) {
            $events = array_merge($events, $this->getBirthdayEvents($employees, $start, $end));
        }

        // 4. Work anniversaries
        if ($request->get('filter_anniversaries', true)) {
            $events = array_merge($events, $this->getAnniversaryEvents($employees, $start, $end));
        }

        // 5. Company holidays
        if ($request->get('filter_holidays', true)) {
            $events = array_merge($events, $this->getCompanyHolidays($start, $end));
        }

        // 6. Performance reviews
        if ($request->get('filter_reviews', true)) {
            $events = array_merge($events, $this->getReviewEvents($employees, $start, $end));
        }

        // 7. Benefits
        if ($request->get('filter_benefits', true)) {
            $events = array_merge($events, $this->getBenefitEvents($employees, $start, $end));
        }

        // 8. Rewards/Disciplines
        if ($request->get('filter_rewards', true)) {
            $events = array_merge($events, $this->getRewardDisciplineEvents($employees, $start, $end));
        }

        return response()->json(['events' => $events]);
    }

    /**
     * Get authorized employees based on user role and filters
     */
    private function getAuthorizedEmployees($user, $request)
    {
        $query = Employee::query();

        // Role-based access control
        if ($user->hasRole(['Super Admin', 'HR Admin', 'Payroll Admin'])) {
            // Full access to all employees
        } elseif ($user->hasRole('Department Manager')) {
            // Only employees in departments they manage
            $managedDepartmentIds = EmployeeAssignment::where('employee_id', $user->employee->id)
                ->whereIn('position_type', ['HEAD', 'DEPUTY'])
                ->pluck('department_id');
            
            $query->whereHas('assignments', function ($q) use ($managedDepartmentIds) {
                $q->whereIn('department_id', $managedDepartmentIds);
            });
        } elseif ($user->hasRole('Director')) {
            // All employees (for executive view)
        } else {
            return collect(); // No access
        }

        // Apply filters
        if ($request->filled('departments')) {
            $departmentIds = is_array($request->departments) 
                ? $request->departments 
                : [$request->departments];
            
            $query->whereHas('assignments', function ($q) use ($departmentIds) {
                $q->whereIn('department_id', $departmentIds);
            });
        }

        if ($request->filled('positions')) {
            $positionIds = is_array($request->positions) 
                ? $request->positions 
                : [$request->positions];
            
            $query->whereHas('assignments', function ($q) use ($positionIds) {
                $q->whereIn('position_id', $positionIds);
            });
        }

        if ($request->filled('employees')) {
            $employeeIds = is_array($request->employees) 
                ? $request->employees 
                : [$request->employees];
            
            $query->whereIn('id', $employeeIds);
        }

        return $query->get();
    }

    /**
     * Get leave events for multiple employees
     */
    private function getLeaveEvents($employees, $start, $end)
    {
        $employeeIds = $employees->pluck('id');
        
        $leaves = LeaveRequest::whereIn('employee_id', $employeeIds)
            ->where('status', 'APPROVED')
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_date', [$start, $end])
                      ->orWhereBetween('end_date', [$start, $end])
                      ->orWhere(function ($q) use ($start, $end) {
                          $q->where('start_date', '<=', $start)
                            ->where('end_date', '>=', $end);
                      });
            })
            ->with('leaveType')
            ->get();

        return $leaves->map(function ($leave) {
            return [
                'id' => 'leave-' . $leave->id,
                'type' => 'leave',
                'title' => $leave->leaveType->name,
                'start' => $leave->start_date->format('Y-m-d'),
                'end' => $leave->end_date->addDay()->format('Y-m-d'), // FullCalendar exclusive end
                'color' => $leave->leaveType->color,
                'allDay' => true,
                'extendedProps' => [
                    'leaveType' => $leave->leaveType->code,
                    'days' => $leave->days,
                    'status' => $leave->status,
                    'reason' => $leave->reason,
                ],
            ];
        })->toArray();
    }

    /**
     * Get contract events (expiry warnings)
     */
    private function getContractEvents($employee, $start, $end)
    {
        $events = [];
        
        $contract = Contract::where('employee_id', $employee->id)
            ->where('status', 'ACTIVE')
            ->whereNotNull('end_date')
            ->first();

        if (!$contract || !$contract->end_date) {
            return $events;
        }

        $expiryDate = Carbon::parse($contract->end_date);
        $today = Carbon::today();

        // Warning 60 days before
        $warning60 = $expiryDate->copy()->subDays(60);
        if ($warning60->between($start, $end) && $today->lte($warning60)) {
            $events[] = [
                'id' => 'contract-warning-60-' . $contract->id,
                'type' => 'contract_expiry_warning',
                'title' => '⚠️ Hợp đồng hết hạn trong 60 ngày',
                'start' => $warning60->format('Y-m-d'),
                'color' => '#f59e0b', // orange
                'allDay' => true,
                'extendedProps' => [
                    'contractNumber' => $contract->contract_number,
                    'expiryDate' => $expiryDate->format('Y-m-d'),
                    'daysUntilExpiry' => $today->diffInDays($expiryDate),
                ],
            ];
        }

        // Urgent 15 days before
        $urgent15 = $expiryDate->copy()->subDays(15);
        if ($urgent15->between($start, $end) && $today->lte($urgent15)) {
            $events[] = [
                'id' => 'contract-urgent-15-' . $contract->id,
                'type' => 'contract_expiry_urgent',
                'title' => '🚨 Hợp đồng hết hạn trong 15 ngày',
                'start' => $urgent15->format('Y-m-d'),
                'color' => '#ef4444', // red
                'allDay' => true,
                'extendedProps' => [
                    'contractNumber' => $contract->contract_number,
                    'expiryDate' => $expiryDate->format('Y-m-d'),
                    'daysUntilExpiry' => $today->diffInDays($expiryDate),
                ],
            ];
        }

        return $events;
    }

    /**
     * Get birthday events for multiple employees
     */
    private function getBirthdayEvents($employees, $start, $end)
    {
        $events = [];
        
        foreach ($employees as $employee) {
            if (!$employee->dob) {
                continue;
            }

            $dob = Carbon::parse($employee->dob);

            // Check each year in the range
            for ($year = $start->year; $year <= $end->year; $year++) {
                $birthday = Carbon::create($year, $dob->month, $dob->day);
                
                if ($birthday->between($start, $end)) {
                    $events[] = [
                        'id' => 'birthday-' . $employee->id . '-' . $year,
                        'type' => 'birthday',
                        'title' => '🎂 Sinh nhật - ' . $employee->full_name,
                        'start' => $birthday->format('Y-m-d'),
                        'color' => '#ec4899', // pink
                        'allDay' => true,
                        'extendedProps' => [
                            'employeeId' => $employee->id,
                            'employeeCode' => $employee->code,
                            'employeeName' => $employee->full_name,
                            'age' => $year - $dob->year,
                            'recurring' => true,
                        ],
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * Get work anniversary events for multiple employees
     */
    private function getAnniversaryEvents($employees, $start, $end)
    {
        $events = [];
        
        foreach ($employees as $employee) {
            // Get first contract start date as hire date
            $firstContract = Contract::where('employee_id', $employee->id)
                ->orderBy('start_date', 'asc')
                ->first();

            if (!$firstContract) {
                continue;
            }

            $hireDate = Carbon::parse($firstContract->start_date);

            // Check each year in the range
            for ($year = $start->year; $year <= $end->year; $year++) {
                $anniversary = Carbon::create($year, $hireDate->month, $hireDate->day);
                
                if ($anniversary->between($start, $end) && $year > $hireDate->year) {
                    $yearsOfService = $year - $hireDate->year;
                    
                    $events[] = [
                        'id' => 'anniversary-' . $employee->id . '-' . $year,
                        'type' => 'work_anniversary',
                        'title' => "🎉 {$yearsOfService} năm - {$employee->full_name}",
                        'start' => $anniversary->format('Y-m-d'),
                        'color' => '#8b5cf6', // purple
                        'allDay' => true,
                        'extendedProps' => [
                            'employeeId' => $employee->id,
                            'employeeCode' => $employee->code,
                            'employeeName' => $employee->full_name,
                            'yearsOfService' => $yearsOfService,
                            'hireDate' => $hireDate->format('Y-m-d'),
                            'recurring' => true,
                        ],
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * Get company holidays
     */
    private function getCompanyHolidays($start, $end)
    {
        $holidays = CompanyHoliday::whereBetween('holiday_date', [$start, $end])
            ->orderBy('holiday_date')
            ->get();

        return $holidays->map(function ($holiday) {
            return [
                'id' => 'holiday-' . $holiday->id,
                'type' => 'company_holiday',
                'title' => '🎊 ' . $holiday->name,
                'start' => $holiday->holiday_date->format('Y-m-d'),
                'color' => '#dc2626', // red
                'allDay' => true,
                'display' => 'background', // Show as background event
                'extendedProps' => [
                    'isCompanyWide' => true,
                    'isRecurring' => $holiday->is_recurring,
                ],
            ];
        })->toArray();
    }

    /**
     * Get performance review events for multiple employees
     */
    private function getReviewEvents($employees, $start, $end)
    {
        $events = [];
        
        foreach ($employees as $employee) {
            // Review deadline (Dec 31 each year)
            for ($year = $start->year; $year <= $end->year; $year++) {
                $deadline = Carbon::create($year, 12, 31);
                
                if ($deadline->between($start, $end)) {
                    // Check if review already completed
                    $review = EmployeeAnnualReview::where('employee_id', $employee->id)
                        ->where('year', $year)
                        ->first();

                    if (!$review) {
                        // Reminder 15 days before deadline
                        $reminder = $deadline->copy()->subDays(15);
                        if ($reminder->between($start, $end)) {
                            $events[] = [
                                'id' => 'review-reminder-' . $employee->id . '-' . $year,
                                'type' => 'performance_review_reminder',
                                'title' => '📊 Đánh giá năm ' . $year . ' - ' . $employee->full_name,
                                'start' => $reminder->format('Y-m-d'),
                                'color' => '#f59e0b', // orange
                                'allDay' => true,
                                'extendedProps' => [
                                    'employeeId' => $employee->id,
                                    'employeeCode' => $employee->code,
                                    'employeeName' => $employee->full_name,
                                    'year' => $year,
                                ],
                            ];
                        }
                } else {
                    // Review completed
                    $events[] = [
                        'id' => 'review-completed-' . $year,
                        'type' => 'performance_review_completed',
                        'title' => "✅ Đánh giá {$year} - Xếp loại {$review->final_rating}",
                        'start' => $review->input_at->format('Y-m-d'),
                        'color' => '#10b981', // green
                        'allDay' => true,
                        'extendedProps' => [
                            'rating' => $review->final_rating,
                            'score' => $review->final_score,
                        ],
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * Get benefit payout events for multiple employees
     */
    private function getBenefitEvents($employees, $start, $end)
    {
        $employeeIds = $employees->pluck('id');
        
        $payouts = EmployeeBenefitPayout::whereIn('employee_id', $employeeIds)
            ->whereBetween('payout_date', [$start, $end])
            ->with(['benefitType', 'employee'])
            ->get();

        return $payouts->map(function ($payout) {
            return [
                'id' => 'benefit-' . $payout->id,
                'type' => 'benefit_payout',
                'title' => '🎁 ' . $payout->benefitType->name . ' - ' . $payout->employee->full_name,
                'start' => $payout->payout_date->format('Y-m-d'),
                'color' => '#06b6d4', // cyan
                'allDay' => true,
                'extendedProps' => [
                    'employeeId' => $payout->employee->id,
                    'employeeCode' => $payout->employee->code,
                    'employeeName' => $payout->employee->full_name,
                    'benefitType' => $payout->benefitType->code,
                    'amount' => $payout->amount,
                ],
            ];
        })->toArray();
    }

    /**
     * Get reward/discipline events for multiple employees
     */
    private function getRewardDisciplineEvents($employees, $start, $end)
    {
        $employeeIds = $employees->pluck('id');
        
        $records = EmployeeRewardDiscipline::whereIn('employee_id', $employeeIds)
            ->whereBetween('effective_date', [$start, $end])
            ->with('employee')
            ->get();

        return $records->map(function ($record) {
            $isReward = $record->type === 'REWARD';
            
            return [
                'id' => 'reward-discipline-' . $record->id,
                'type' => $isReward ? 'reward' : 'discipline',
                'title' => ($isReward ? '🏆 Khen thưởng' : '⚠️ Kỷ luật') . ' - ' . $record->employee->full_name,
                'start' => $record->effective_date->format('Y-m-d'),
                'color' => $isReward ? '#10b981' : '#ef4444', // green : red
                'allDay' => true,
                'extendedProps' => [
                    'employeeId' => $record->employee->id,
                    'employeeCode' => $record->employee->code,
                    'employeeName' => $record->employee->full_name,
                    'category' => $record->category,
                    'amount' => $record->amount,
                    'decisionNo' => $record->decision_no,
                ],
            ];
        })->toArray();
    }

    /**
     * Get team summary statistics (for Department Manager)
     */
    public function teamSummary(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->hasRole('Department Manager')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get managed department IDs
        $managedDepartmentIds = EmployeeAssignment::where('employee_id', $user->employee->id)
            ->whereIn('position_type', ['HEAD', 'DEPUTY'])
            ->pluck('department_id');
        
        $teamEmployees = Employee::whereHas('assignments', function ($q) use ($managedDepartmentIds) {
            $q->whereIn('department_id', $managedDepartmentIds);
        })->get();

        $today = Carbon::today();
        $thisMonth = Carbon::now();

        // On leave today
        $onLeaveToday = LeaveRequest::whereIn('employee_id', $teamEmployees->pluck('id'))
            ->where('status', 'APPROVED')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with(['employee', 'leaveType'])
            ->get();

        // Birthdays this month
        $birthdaysThisMonth = $teamEmployees->filter(function ($emp) use ($thisMonth) {
            return $emp->dob && Carbon::parse($emp->dob)->month === $thisMonth->month;
        });

        // Contracts expiring soon (60 days)
        $contractsExpiring = Contract::whereIn('employee_id', $teamEmployees->pluck('id'))
            ->where('status', 'ACTIVE')
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [$today, $today->copy()->addDays(60)])
            ->with('employee')
            ->get();

        // Team coverage
        $teamSize = $teamEmployees->count();
        $onLeaveCount = $onLeaveToday->count();
        $coveragePercent = $teamSize > 0 ? round((($teamSize - $onLeaveCount) / $teamSize) * 100) : 0;

        return response()->json([
            'onLeaveToday' => $onLeaveToday->map(fn($leave) => [
                'id' => $leave->employee->id,
                'name' => $leave->employee->full_name,
                'code' => $leave->employee->code,
                'leaveType' => $leave->leaveType->name,
                'daysRemaining' => Carbon::today()->diffInDays(Carbon::parse($leave->end_date)),
            ]),
            'birthdaysThisMonth' => $birthdaysThisMonth->map(fn($emp) => [
                'id' => $emp->id,
                'name' => $emp->full_name,
                'code' => $emp->code,
                'birthday' => Carbon::parse($emp->dob)->format('d/m'),
            ]),
            'contractsExpiring' => $contractsExpiring->map(fn($contract) => [
                'id' => $contract->employee->id,
                'name' => $contract->employee->full_name,
                'code' => $contract->employee->code,
                'contractType' => $contract->contract_type,
                'expiryDate' => $contract->end_date->format('d/m/Y'),
                'daysUntilExpiry' => Carbon::today()->diffInDays($contract->end_date),
            ]),
            'teamCoverage' => [
                'teamSize' => $teamSize,
                'onLeave' => $onLeaveCount,
                'working' => $teamSize - $onLeaveCount,
                'coveragePercent' => $coveragePercent,
            ],
        ]);
    }
}
```

        // Get team members from assignments where user is HEAD/DEPUTY
        $teamMembers = Employee::whereHas('assignments', function ($query) use ($user) {
            $query->whereHas('department.assignments', function ($q) use ($user) {
                $q->where('employee_id', $user->employee->id)
                  ->whereIn('role_type', ['HEAD', 'DEPUTY'])
                  ->where('is_primary', true)
                  ->where('status', 'ACTIVE');
            });
        })->get();

        $start = Carbon::parse($request->start);
        $end = Carbon::parse($request->end);
        $events = [];

        // Get leaves for team members
        foreach ($teamMembers as $member) {
            $leaves = LeaveRequest::where('employee_id', $member->id)
                ->where('status', 'APPROVED')
                ->whereBetween('start_date', [$start, $end])
                ->with('leaveType')
                ->get();

            foreach ($leaves as $leave) {
                $events[] = [
                    'id' => 'team-leave-' . $leave->id,
                    'type' => 'team_leave',
                    'title' => $member->full_name . ' - ' . $leave->leaveType->name,
                    'start' => $leave->start_date->format('Y-m-d'),
                    'end' => $leave->end_date->addDay()->format('Y-m-d'),
                    'color' => $leave->leaveType->color,
                    'allDay' => true,
                    'extendedProps' => [
                        'employeeId' => $member->id,
                        'employeeName' => $member->full_name,
                        'employeeCode' => $member->employee_code,
                        'leaveType' => $leave->leaveType->code,
                        'days' => $leave->days,
                    ],
                ];
            }
        }

        return response()->json(['events' => $events]);
    }
}
```

#### **1.5 Routes**
**routes/web.php:**
```php
Route::group(['middleware' => 'auth'], function () {
    // ... existing routes

    // Calendar Routes
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
    Route::get('calendar/team-events', [CalendarController::class, 'teamEvents'])->name('calendar.team-events');
    
    // Company Holidays Management (HR only)
    Route::resource('company-holidays', CompanyHolidayController::class)
        ->middleware('can:manage company holidays');
});
```

#### **1.6 Frontend Setup**
```bash
npm install @fullcalendar/vue3 @fullcalendar/core @fullcalendar/daygrid @fullcalendar/timegrid @fullcalendar/interaction
```

**resources/js/Pages/Calendar/Index.vue:**
```vue
<template>
  <div class="calendar-container">
    <div class="calendar-header">
      <h1>📅 Lịch của tôi</h1>
      
      <div class="calendar-filters">
        <label>
          <input type="checkbox" v-model="filters.leaves" @change="fetchEvents" />
          🏖️ Nghỉ phép
        </label>
        <label>
          <input type="checkbox" v-model="filters.contracts" @change="fetchEvents" />
          📄 Hợp đồng
        </label>
        <label>
          <input type="checkbox" v-model="filters.birthdays" @change="fetchEvents" />
          🎂 Sinh nhật
        </label>
        <label>
          <input type="checkbox" v-model="filters.anniversaries" @change="fetchEvents" />
          🎉 Kỷ niệm
        </label>
        <label>
          <input type="checkbox" v-model="filters.holidays" @change="fetchEvents" />
          🎊 Ngày lễ
        </label>
        <label>
          <input type="checkbox" v-model="filters.reviews" @change="fetchEvents" />
          📊 Đánh giá
        </label>
        <label>
          <input type="checkbox" v-model="filters.benefits" @change="fetchEvents" />
          🎁 Phúc lợi
        </label>
        <label>
          <input type="checkbox" v-model="filters.rewards" @change="fetchEvents" />
          🏆 Thưởng/KL
        </label>
      </div>
    </div>

    <FullCalendar
      ref="calendar"
      :options="calendarOptions"
    />

    <!-- Event detail dialog -->
    <Dialog v-model:visible="showEventDialog" header="Chi tiết sự kiện" :modal="true">
      <div v-if="selectedEvent" class="event-details">
        <h3>{{ selectedEvent.title }}</h3>
        <p><strong>Ngày:</strong> {{ formatDate(selectedEvent.start) }}</p>
        <p v-if="selectedEvent.end"><strong>Đến:</strong> {{ formatDate(selectedEvent.end) }}</p>
        <p v-if="selectedEvent.extendedProps">
          <strong>Chi tiết:</strong>
          <pre>{{ JSON.stringify(selectedEvent.extendedProps, null, 2) }}</pre>
        </p>
      </div>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import Dialog from 'primevue/dialog';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const calendar = ref(null);
const showEventDialog = ref(false);
const selectedEvent = ref(null);

const filters = reactive({
  leaves: true,
  contracts: true,
  birthdays: true,
  anniversaries: true,
  holidays: true,
  reviews: true,
  benefits: false,
  rewards: false,
});

const calendarOptions = reactive({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay'
  },
  locale: 'vi',
  firstDay: 1, // Monday
  buttonText: {
    today: 'Hôm nay',
    month: 'Tháng',
    week: 'Tuần',
    day: 'Ngày',
  },
  events: fetchEvents,
  eventClick: handleEventClick,
  height: 'auto',
});

async function fetchEvents(info, successCallback, failureCallback) {
  try {
    const response = await axios.get('/calendar/events', {
      params: {
        start: info.startStr,
        end: info.endStr,
        filter_leaves: filters.leaves,
        filter_contracts: filters.contracts,
        filter_birthdays: filters.birthdays,
        filter_anniversaries: filters.anniversaries,
        filter_holidays: filters.holidays,
        filter_reviews: filters.reviews,
        filter_benefits: filters.benefits,
        filter_rewards: filters.rewards,
      }
    });
    
    if (successCallback) {
      successCallback(response.data.events);
    }
    
    return response.data.events;
  } catch (error) {
    console.error('Failed to fetch events:', error);
    if (failureCallback) {
      failureCallback(error);
    }
  }
}

function handleEventClick(info) {
  selectedEvent.value = info.event;
  showEventDialog.value = true;
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('vi-VN');
}
</script>

<style scoped>
.calendar-container {
  padding: 1rem;
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.calendar-filters {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.calendar-filters label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  user-select: none;
}

.event-details pre {
  background: #f5f5f5;
  padding: 0.5rem;
  border-radius: 4px;
  font-size: 12px;
}
</style>
```

---

### **Phase 2: Team Calendar (Week 2)**

#### **2.1 Team Calendar Page**
```vue
<template>
  <div class="team-calendar-container">
    <h1>📅 Lịch Team</h1>
    
    <!-- Team summary cards -->
    <div class="team-summary">
      <div class="summary-card">
        <h3>🏖️ Nghỉ phép hôm nay</h3>
        <div class="card-content">
          <span class="big-number">{{ onLeaveToday.length }}</span>
          <ul v-if="onLeaveToday.length">
            <li v-for="member in onLeaveToday" :key="member.id">
              {{ member.name }} ({{ member.leaveType }})
            </li>
          </ul>
        </div>
      </div>
      
      <div class="summary-card">
        <h3>🎂 Sinh nhật tháng này</h3>
        <div class="card-content">
          <span class="big-number">{{ birthdaysThisMonth.length }}</span>
        </div>
      </div>
      
      <div class="summary-card">
        <h3>⚠️ Hợp đồng sắp hết hạn</h3>
        <div class="card-content">
          <span class="big-number">{{ contractsExpiring.length }}</span>
        </div>
      </div>
    </div>

    <FullCalendar ref="calendar" :options="teamCalendarOptions" />
  </div>
</template>
```

---

## 📊 PRIORITY & ROADMAP

### **Phase 1: HR Admin Calendar - Toàn cảnh công ty** (Week 1) - PRIORITY HIGH
**Deliverable:** HR Admin xem lịch toàn bộ nhân viên

**Features:**
- View tất cả events của tất cả nhân viên
- 9 event types: Leaves, Contracts, Birthdays, Anniversaries, Holidays, Reviews, Benefits, Rewards, Insurance
- Filters: Department, Position, Employee, Event Type, Date Range
- Export calendar to Excel/PDF
- Event detail dialog với employee profile link
- Color-coded events từ leave_types

**Implementation:**
- [ ] Database migrations (company_holidays)
- [ ] CompanyHoliday model & seeder (Vietnam 2026 holidays)
- [ ] CalendarController với events() method
- [ ] Routes: GET /calendar, GET /calendar/events
- [ ] Permissions: 'view calendar' (HR Admin, Payroll Admin, Director)
- [ ] Frontend: Calendar/Index.vue (FullCalendar)
- [ ] Multi-select filters (Department, Position, Employee, Event Types)
- [ ] Export functionality (Excel/PDF)
- [ ] Event detail dialog

**Estimated:** 5-6 days

---

### **Phase 2: Department Manager Calendar - Quản lý phòng ban** (Week 2) - PRIORITY MEDIUM
**Deliverable:** Managers xem lịch nhân viên trong phòng ban mình quản lý

**Features:**
- View lịch employees trong phòng ban (theo employee_assignments HEAD/DEPUTY)
- Team summary widgets:
  - 🏖️ Nghỉ phép hôm nay: X người
  - 🎂 Sinh nhật tháng này: X người
  - ⚠️ Hợp đồng sắp hết hạn: X người
  - 📊 Team coverage: % attendance
- Quick actions từ calendar:
  - Approve/Reject leave requests (1-click)
  - View employee profile
  - Create performance review
- Department workload visualization (heatmap)
- Team event filtering

**Implementation:**
- [ ] TeamEventsController với teamEvents() method
- [ ] Permission check: Chỉ xem employees trong department mình quản lý
- [ ] Query: Lọc theo employee_assignments (HEAD/DEPUTY)
- [ ] Team summary API endpoints
- [ ] Team calendar view component
- [ ] Quick approval modals
- [ ] Workload heatmap component
- [ ] Permission: 'view team calendar' (Department Manager)

**Estimated:** 4-5 days

---

### **Phase 3: Director Calendar - Executive Dashboard** (Week 3) - PRIORITY LOW
**Deliverable:** Director xem overview critical events toàn công ty

**Features:**
- Critical events only:
  - 🚨 Hợp đồng hết hạn trong 15 ngày
  - 🏖️ Nghỉ phép > 5 ngày
  - 📊 Annual reviews chưa complete
  - ⚠️ Insurance changes chờ approve
- Executive summary cards:
  - Total headcount by status
  - Leave utilization rate
  - Contract renewal backlog
  - Performance review completion %
- Drill-down capability vào department
- Export executive reports
- Mobile-optimized view

**Implementation:**
- [ ] ExecutiveController với criticalEvents() method
- [ ] Filter logic: Critical events only (thresholds configurable)
- [ ] Executive dashboard component
- [ ] Summary cards API
- [ ] Drill-down modal với department breakdown
- [ ] Mobile responsive CSS
- [ ] Permission: 'view executive calendar' (Director)

**Optional Enhancements:**
- [ ] Calendar widget on main dashboard
- [ ] Email reminders for critical events
- [ ] Slack/Teams integration
- [ ] ICS export for Outlook/Google Calendar

**Estimated:** 3-4 days

---

## ✅ KẾT QUẢ ĐẠT ĐƯỢC

### **Sau Phase 1: HR Admin Calendar**
✅ HR Admin xem được toàn bộ events của công ty  
✅ Dữ liệu từ 9 modules tự động hiển thị  
✅ Company holidays quản lý tập trung  
✅ Export Excel/PDF cho báo cáo  
✅ Filters linh hoạt theo department/position/employee  

### **Sau Phase 2: Department Manager Calendar**
✅ Managers quản lý lịch team hiệu quả  
✅ Team coverage visibility (ai nghỉ, ai làm việc)  
✅ Quick approval leaves từ calendar (1-click)  
✅ Team birthdays & anniversaries tracking  
✅ Workload visualization  

### **Sau Phase 3: Director Calendar**
✅ Executive overview critical events  
✅ Company-wide metrics dashboard  
✅ Drill-down capability  
✅ Mobile access  
✅ Export executive reports  

---

## 🎯 KẾT LUẬN

Calendar module cho HongHa HRM (Admin/Manager Only) sẽ:

1. **Tận dụng 100% data có sẵn** - Không cần nhập data mới
2. **Tích hợp 9 modules** - Leave, Contract, Benefits, Performance, Rewards, Insurance, Employees
3. **Role-based visibility**:
   - **HR Admin/Payroll Admin**: View toàn công ty + Export
   - **Department Manager**: View phòng ban mình quản lý + Quick actions
   - **Director**: Executive overview + Critical events only
4. **Low effort, high value** - 2-3 tuần implementation
5. **Management tool** - Giúp Admin/Manager theo dõi và quản lý nhân viên hiệu quả

**Target Users:** 
- HR Admin (primary user)
- Department Managers (secondary user)
- Director (executive overview)

**Total Timeline:** 2-3 tuần (Phase 1 + 2 + 3)

**ROI: VERY HIGH** 🚀 - Essential management tool

