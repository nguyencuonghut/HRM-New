# Hệ thống Phân quyền Dynamic cho HRM System

## 📋 Tổng quan

Thiết kế hệ thống phân quyền động (Dynamic Permission Management) cho phép Super Admin quản lý roles và permissions qua UI thay vì seeder cứng.

---

## 🎯 Roles Hierarchy & Responsibilities

### 1. **Super Admin** (Quản trị hệ thống tối cao)
**Mô tả:** Quyền cao nhất, quản lý toàn bộ hệ thống bao gồm cấu hình, bảo mật và phân quyền.

**Permissions:**
```
System Administration (5)
- manage system settings
- manage roles and permissions  ← ĐẶC BIỆT
- view system logs
- manage backups
- manage integrations

User Management (4)
- view users
- create users
- edit users
- delete users

All other permissions inherited
```

---

### 2. **HR Admin** (Quản trị nhân sự)
**Mô tả:** Quản lý toàn bộ nghiệp vụ nhân sự, hợp đồng, bảo hiểm nhưng không có quyền cấu hình hệ thống.

**Permissions:**
```
Organization Structure (4)
- view departments
- create departments
- edit departments
- delete departments

Employee Management (8)
- view employees
- create employees
- edit employees
- delete employees
- import employees
- export employees
- view employee profiles
- edit employee profiles

Contract Management (10)
- view contracts
- create contracts
- edit contracts
- delete contracts
- submit contracts
- approve contracts (all levels)
- reject contracts
- terminate contracts
- renew contracts
- view contract templates

Leave Management (8)
- view leave requests
- create leave requests (for others)
- edit leave requests
- delete leave requests
- approve leave requests
- reject leave requests
- view leave balances
- adjust leave balances

Insurance & Social Insurance (8)
- view insurance reports
- create insurance reports
- approve insurance records
- reject insurance records
- adjust insurance records
- finalize insurance reports
- export insurance reports
- delete insurance reports

Payroll & Benefits (6)
- view payroll
- process payroll
- view benefits
- manage benefits
- approve benefit payouts
- export payroll reports

Performance Management (6)
- view performance reviews
- create performance reviews
- edit performance reviews
- approve performance reviews
- view KPI data
- edit KPI data

Reports & Analytics (2)
- view all reports
- export all reports

Settings (4)
- manage leave types
- manage contract templates
- manage benefit types
- manage positions
```

---

### 3. **HR Manager** (Trưởng phòng nhân sự)
**Mô tả:** Quản lý nghiệp vụ nhân sự nhưng không có quyền delete và một số quyền admin.

**Permissions:**
```
Organization Structure (2)
- view departments
- edit departments (own department only)

Employee Management (5)
- view employees
- create employees
- edit employees
- view employee profiles
- export employees

Contract Management (7)
- view contracts
- create contracts
- edit contracts
- submit contracts
- approve contracts (manager level)
- renew contracts
- view contract templates

Leave Management (6)
- view leave requests
- create leave requests
- approve leave requests (manager level)
- reject leave requests (manager level)
- view leave balances
- export leave reports

Insurance (4)
- view insurance reports
- create insurance reports
- export insurance reports
- view insurance status

Performance Management (5)
- view performance reviews
- create performance reviews
- edit performance reviews
- view KPI data
- edit KPI data (own department)

Reports & Analytics (2)
- view department reports
- export department reports
```

---

### 4. **Payroll Admin** (Quản trị lương & BHXH)
**Mô tả:** Chuyên về lương, thưởng, phúc lợi, bảo hiểm xã hội.

**Permissions:**
```
Employee (View Only) (2)
- view employees
- view employee profiles

Contract (View Only) (2)
- view contracts
- view contract salary info

Payroll & Benefits (Full Access) (8)
- view payroll
- create payroll
- edit payroll
- process payroll
- approve payroll
- view benefits
- manage benefits
- approve benefit payouts

Insurance & Social Insurance (Full Access) (8)
- view insurance reports
- create insurance reports
- approve insurance records
- reject insurance records
- adjust insurance records
- finalize insurance reports
- export insurance reports
- delete insurance reports

Reports (2)
- view payroll reports
- export payroll reports
```

---

### 5. **Department Manager** (Trưởng phòng/ban)
**Mô tả:** Quản lý nhân sự trong phòng ban của mình.

**Permissions:**
```
Organization (View Only) (1)
- view departments

Employee Management (Limited) (4)
- view employees (own department)
- edit employees (own department)
- view employee profiles (own department)
- export employees (own department)

Contract Management (Limited) (4)
- view contracts (own department)
- approve contracts (department level)
- view contract templates
- request contract renewals

Leave Management (4)
- view leave requests (own department)
- approve leave requests (own department)
- reject leave requests (own department)
- view leave balances (own department)

Performance Management (4)
- view performance reviews (own department)
- create performance reviews (own department)
- edit performance reviews (own department)
- view KPI data (own department)

Reports (2)
- view department reports
- export department reports
```

---

### 6. **Team Lead** (Trưởng nhóm)
**Mô tả:** Quản lý nhóm trực tiếp dưới quyền.

**Permissions:**
```
Employee (View Only) (2)
- view employees (direct reports)
- view employee profiles (direct reports)

Leave Management (3)
- view leave requests (direct reports)
- approve leave requests (team level)
- reject leave requests (team level)

Performance Management (3)
- view performance reviews (direct reports)
- create performance reviews (direct reports)
- edit performance reviews (direct reports)
```

---

### 7. **Employee** (Nhân viên)
**Mô tả:** Self-service, chỉ quản lý thông tin và yêu cầu của bản thân.

**Permissions:**
```
Profile Management (3)
- view own profile
- edit own profile (limited fields)
- upload own documents

Contract (View Only) (1)
- view own contracts

Leave Management (4)
- view own leave requests
- create own leave requests
- cancel own leave requests
- view own leave balance

Performance (View Only) (2)
- view own performance reviews
- view own KPI data

Payroll (View Only) (1)
- view own payroll slips

Benefits (View Only) (1)
- view own benefits
```

---

## 🗂️ Permission Groups (Modules)

### 1. **System Administration**
```php
'manage system settings',
'manage roles and permissions',  // ← CHỈ SUPER ADMIN
'view system logs',
'manage backups',
'manage integrations',
```

### 2. **User Management**
```php
'view users',
'create users',
'edit users',
'delete users',
'assign roles to users',
'reset user passwords',
```

### 3. **Organization Structure**
```php
'view departments',
'create departments',
'edit departments',
'delete departments',
'view org chart',
'manage department hierarchy',
```

### 4. **Employee Management**
```php
'view employees',
'create employees',
'edit employees',
'delete employees',
'import employees',
'export employees',
'view employee profiles',
'edit employee profiles',
'view employee documents',
'manage employee documents',
'view employee history',
```

### 5. **Position & Assignment**
```php
'view positions',
'create positions',
'edit positions',
'delete positions',
'view employee assignments',
'create employee assignments',
'edit employee assignments',
'delete employee assignments',
```

### 6. **Contract Management**
```php
'view contracts',
'create contracts',
'edit contracts',
'delete contracts',
'submit contracts',
'approve contracts',
'reject contracts',
'terminate contracts',
'renew contracts',
'view contract templates',
'create contract templates',
'edit contract templates',
'delete contract templates',
'approve appendixes',
'reject appendixes',
```

### 7. **Leave Management**
```php
'view leave requests',
'create leave requests',
'edit leave requests',
'delete leave requests',
'submit leave requests',
'approve leave requests',
'reject leave requests',
'cancel leave requests',
'view leave balances',
'adjust leave balances',
'view leave types',
'manage leave types',
'export leave reports',
```

### 8. **Payroll & Benefits**
```php
'view payroll',
'create payroll',
'edit payroll',
'delete payroll',
'process payroll',
'approve payroll',
'export payroll reports',
'view benefits',
'create benefits',
'edit benefits',
'delete benefits',
'manage benefit types',
'approve benefit payouts',
'view benefit reports',
```

### 9. **Insurance & Social Insurance**
```php
'view insurance reports',
'create insurance reports',
'edit insurance reports',
'delete insurance reports',
'approve insurance records',
'reject insurance records',
'adjust insurance records',
'finalize insurance reports',
'export insurance reports',
'view insurance status',
```

### 10. **Performance Management**
```php
'view performance reviews',
'create performance reviews',
'edit performance reviews',
'delete performance reviews',
'approve performance reviews',
'view KPI data',
'create KPI data',
'edit KPI data',
'delete KPI data',
'view annual reviews',
'create annual reviews',
'edit annual reviews',
'approve annual reviews',
```

### 11. **Rewards & Disciplines**
```php
'view rewards',
'create rewards',
'edit rewards',
'delete rewards',
'approve rewards',
'view disciplines',
'create disciplines',
'edit disciplines',
'delete disciplines',
'approve disciplines',
```

### 12. **Reports & Analytics**
```php
'view all reports',
'view department reports',
'view employee reports',
'view contract reports',
'view leave reports',
'view payroll reports',
'view insurance reports',
'view performance reports',
'export all reports',
'export department reports',
'schedule reports',
```

### 13. **Settings & Configuration**
```php
'view settings',
'edit settings',
'manage leave types',
'manage contract templates',
'manage benefit types',
'manage positions',
'manage education levels',
'manage schools',
'manage skills',
'manage provinces',
'manage wards',
```

### 14. **Activity Logs**
```php
'view activity logs',
'view own activity logs',
'delete activity logs',
'export activity logs',
```

---

## 🏗️ Implementation Plan

### Phase 1: Database & Models (Đã có sẵn với Spatie)
✅ Spatie Laravel Permission đã cài đặt
✅ Tables: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`

### Phase 2: Backend API

#### 2.1. PermissionController
```php
app/Http/Controllers/PermissionController.php

Methods:
- index() : Danh sách tất cả permissions (grouped by module)
- getGroupedPermissions() : Permissions grouped by module
```

#### 2.2. RoleController (Mở rộng)
```php
app/Http/Controllers/RoleController.php

Thêm methods:
- assignPermissions(Role $role, Request $request)
- revokePermissions(Role $role, Request $request)
- syncPermissions(Role $role, Request $request)
- getUsersWithRole(Role $role)
```

#### 2.3. UserController (Mở rộng)
```php
app/Http/Controllers/UserController.php

Thêm methods:
- assignRole(User $user, Request $request)
- revokeRole(User $user, Request $request)
- syncRoles(User $user, Request $request)
```

### Phase 3: Frontend UI

#### 3.1. Permission Management Page
```
resources/js/Pages/Permissions/Index.vue

Features:
- Danh sách permissions grouped by module
- Search & filter permissions
- View permissions by role
- Bulk select permissions
```

#### 3.2. Role Management Enhancement
```
resources/js/Pages/Roles/Create.vue
resources/js/Pages/Roles/Edit.vue

Thêm:
- Permission selector (grouped by module)
- Checkboxes với indeterminate state
- "Select All" per module
- Permission count badge
- Preview assigned permissions
```

#### 3.3. User Management Enhancement
```
resources/js/Pages/Users/Create.vue
resources/js/Pages/Users/Edit.vue

Thêm:
- Role selector (radio hoặc select)
- Show inherited permissions từ role
- Direct permission assignment (nếu cần)
```

### Phase 4: Middleware & Policies

#### 4.1. Custom Middleware
```php
app/Http/Middleware/CheckDepartmentScope.php
- Kiểm tra quyền theo department scope
- Manager chỉ thấy nhân viên trong department của mình

app/Http/Middleware/CheckDirectReports.php
- Team Lead chỉ thấy direct reports
```

#### 4.2. Update Policies
Cập nhật các Policy để support dynamic permissions:
```php
app/Policies/EmployeePolicy.php
app/Policies/ContractPolicy.php
app/Policies/LeaveRequestPolicy.php
```

### Phase 5: Seeder Update

#### 5.1. New Seeder Structure
```php
database/seeders/PermissionsSeeder.php
- Tạo TẤT CẢ permissions theo groups
- Không tạo roles (hoặc chỉ tạo Super Admin)

database/seeders/DefaultRolesSeeder.php  
- Tạo roles mẫu
- Assign permissions theo design trên
- Có thể skip nếu muốn tạo qua UI
```

---

## 📊 Permission Matrix Summary

| Role | Employees | Contracts | Leave | Payroll | Insurance | Performance | Reports | System |
|------|-----------|-----------|-------|---------|-----------|-------------|---------|--------|
| Super Admin | Full | Full | Full | Full | Full | Full | Full | ✅ Full |
| HR Admin | Full | Full | Full | Full | Full | Full | View/Export | ❌ No |
| HR Manager | CRUD | Approve | Approve | View | View | CRUD | View/Export | ❌ No |
| Payroll Admin | View | View | View | Full | Full | View | Payroll only | ❌ No |
| Dept Manager | Own dept | Own dept | Own dept | View | View | Own dept | Own dept | ❌ No |
| Team Lead | Direct | View | Approve | View | View | Direct | Direct | ❌ No |
| Employee | Own | Own | Own | Own | View | Own | Own | ❌ No |

---

## 🎨 UI/UX Design Suggestions

### Permission Management Page
```
┌─────────────────────────────────────────────┐
│ Permissions                          [+ New] │
├─────────────────────────────────────────────┤
│ [Search...]                [Filter by Role] │
├─────────────────────────────────────────────┤
│ 📁 System Administration (5)                │
│   ☐ manage system settings                  │
│   ☐ manage roles and permissions            │
│   ☐ view system logs                        │
│   ☐ manage backups                          │
│   ☐ manage integrations                     │
│                                              │
│ 👥 User Management (6)                      │
│   ☐ view users                              │
│   ☐ create users                            │
│   ...                                        │
│                                              │
│ 🏢 Organization Structure (6)               │
│   ...                                        │
└─────────────────────────────────────────────┘
```

### Role Edit Page
```
┌─────────────────────────────────────────────┐
│ Edit Role: HR Manager          [Save] [Cancel]│
├─────────────────────────────────────────────┤
│ Name: [HR Manager                        ]  │
│ Description: [Manages HR operations...   ]  │
├─────────────────────────────────────────────┤
│ Permissions (45 selected)                   │
│                                              │
│ ☑ System Administration (0/5)               │
│ ☑ User Management (2/6)          [Select All]│
│   ☑ view users                              │
│   ☑ create users                            │
│   ☐ edit users                              │
│   ☐ delete users                            │
│   ...                                        │
│                                              │
│ ☑ Employee Management (8/11)    [Select All]│
│   ☑ view employees                          │
│   ☑ create employees                        │
│   ...                                        │
└─────────────────────────────────────────────┘
```

---

## 🔐 Security Best Practices

1. **Super Admin Protection**
   - Chỉ có Super Admin mới được "manage roles and permissions"
   - Không cho phép tự remove quyền của chính mình
   - Luôn có ít nhất 1 Super Admin

2. **Role Hierarchy**
   - Lower roles không thể grant permissions cao hơn mình
   - Manager không thể edit permissions của Admin

3. **Audit Logs**
   - Log tất cả thay đổi về roles/permissions
   - Hiển thị trong Activity Logs
   - Include: who, what, when, IP address

4. **Permission Naming Convention**
   - Format: `{action} {resource}`
   - Actions: view, create, edit, delete, approve, export, manage
   - Lowercase with spaces

5. **Database Constraints**
   - Soft delete cho roles (không xóa hẳn nếu đang được dùng)
   - Cascade relationships properly
   - Index cho performance

---

## 🚀 Migration Path

### Step 1: Backup
```bash
php artisan backup:database
```

### Step 2: Create New Permissions
```bash
php artisan db:seed --class=PermissionsSeeder
```

### Step 3: Update Existing Roles (Optional)
```bash
php artisan db:seed --class=UpdateRolesPermissionsSeeder
```

### Step 4: Deploy Frontend
```bash
npm run build
```

### Step 5: Clear Cache
```bash
php artisan permission:cache-reset
php artisan cache:clear
```

---

## 📝 Notes

1. **Spatie Package đã hỗ trợ:**
   - Direct permissions cho users
   - Wildcard permissions (nếu cần)
   - Role inheritance
   - Permission caching

2. **Không cần implement:**
   - Basic CRUD cho roles/permissions (Spatie đã có)
   - Database structure (Spatie đã tạo)

3. **Cần implement:**
   - UI cho quản lý permissions
   - Grouped permissions display
   - Department/Team scope middleware
   - Enhanced RoleController với permission assignment

4. **Testing:**
   - Unit tests cho policies
   - Feature tests cho permission assignment
   - Browser tests cho UI flows

---

## 📚 Reference

- **Spatie Docs:** https://spatie.be/docs/laravel-permission/
- **BambooHR Roles:** https://www.bamboohr.com/
- **Zoho People Permissions:** https://www.zoho.com/people/
- **Gusto Access Levels:** https://gusto.com/

---

## ✅ Checklist Implementation

- [ ] Tạo PermissionsSeeder với tất cả permissions
- [ ] Tạo UpdateRolesPermissionsSeeder để update roles hiện tại
- [ ] Mở rộng RoleController: assignPermissions, syncPermissions
- [ ] Mở rộng UserController: assignRole, syncRoles
- [ ] Tạo PermissionController: index, getGroupedPermissions
- [ ] UI: resources/js/Pages/Permissions/Index.vue
- [ ] UI: Cập nhật Roles/Create.vue với permission selector
- [ ] UI: Cập nhật Roles/Edit.vue với permission selector
- [ ] UI: Cập nhật Users/Create.vue với role selector
- [ ] UI: Cập nhật Users/Edit.vue với role selector
- [ ] Middleware: CheckDepartmentScope
- [ ] Middleware: CheckDirectReports
- [ ] Update các Policies để support dynamic permissions
- [ ] Testing: Unit tests
- [ ] Testing: Feature tests
- [ ] Documentation: User guide
