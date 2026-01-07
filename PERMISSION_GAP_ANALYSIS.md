# Phân tích Gap Permissions - HRM System

## 📊 Tổng quan
Phân tích sự khác biệt giữa permissions hiện có trong seeder và permissions đã thiết kế đầy đủ.

---

## ✅ Permissions Hiện có (Seeder - 143 permissions)

### 1. User Management (4)
- ✅ view users
- ✅ create users
- ✅ edit users
- ✅ delete users

### 2. Role Management (4)
- ✅ view roles
- ✅ create roles
- ✅ edit roles
- ✅ delete roles

### 3. Permission Management (2)
- ✅ view permissions
- ✅ assign permissions

### 4. Backup Management (5)
- ✅ view backups
- ✅ create backups
- ✅ restore backups
- ✅ delete backups
- ✅ configure backups

### 5. Activity Log (2)
- ✅ view activity logs
- ✅ delete activity logs

### 6. Department Management (4)
- ✅ view departments
- ✅ create departments
- ✅ edit departments
- ✅ delete departments

### 7. Employee Management (4)
- ✅ view employees
- ✅ create employees
- ✅ edit employees
- ✅ delete employees

### 8. Employee Assignment (4)
- ✅ view employee assignments
- ✅ create employee assignments
- ✅ edit employee assignments
- ✅ delete employee assignments

### 9. Position Management (4)
- ✅ view positions
- ✅ create positions
- ✅ edit positions
- ✅ delete positions

### 10. Province Management (4)
- ✅ view provinces
- ✅ create provinces
- ✅ edit provinces
- ✅ delete provinces

### 11. Ward Management (4)
- ✅ view wards
- ✅ create wards
- ✅ edit wards
- ✅ delete wards

### 12. Education Level Management (4)
- ✅ view education levels
- ✅ create education levels
- ✅ edit education levels
- ✅ delete education levels

### 13. School Management (4)
- ✅ view schools
- ✅ create schools
- ✅ edit schools
- ✅ delete schools

### 14. Skill Management (4)
- ✅ view skills
- ✅ create skills
- ✅ edit skills
- ✅ delete skills

### 15. Skill Category Management (4)
- ✅ view skill categories
- ✅ create skill categories
- ✅ edit skill categories
- ✅ delete skill categories

### 16. Contract Management (10)
- ✅ view contracts
- ✅ create contracts
- ✅ edit contracts
- ✅ delete contracts
- ✅ submit contracts
- ✅ approve contracts
- ✅ recall contracts
- ✅ renew contracts
- ✅ approve appendixes
- ✅ reject appendixes

### 17. Contract Template Management (4)
- ✅ view contract templates
- ✅ create contract templates
- ✅ edit contract templates
- ✅ delete contract templates

### 18. Appendix Template Management (4)
- ✅ view appendix templates
- ✅ create appendix templates
- ✅ edit appendix templates
- ✅ delete appendix templates

### 19. Insurance Reports Management (8)
- ✅ view insurance reports
- ✅ create insurance reports
- ✅ approve insurance records
- ✅ reject insurance records
- ✅ adjust insurance records
- ✅ finalize insurance reports
- ✅ export insurance reports
- ✅ delete insurance reports

### 20. Backfill/Legacy Data (6)
- ✅ import legacy data
- ✅ backfill employees
- ✅ backfill contracts
- ✅ backfill leave requests
- ✅ backfill insurance records
- ✅ backfill payroll records

**Tổng: 89 permissions**

---

## ❌ Permissions Còn thiếu (từ Design Document)

### 1. System Administration (3 missing)
- ❌ manage system settings
- ❌ view system logs
- ❌ manage integrations

### 2. User Management (3 missing)
- ❌ import users
- ❌ export users
- ❌ reset user passwords

### 3. Employee Management (6 missing)
- ❌ import employees
- ❌ export employees
- ❌ view employee profiles
- ❌ edit employee profiles
- ❌ terminate employees
- ❌ transfer employees

### 4. Leave Management (13 missing)
- ❌ view leave requests
- ❌ create leave requests
- ❌ edit leave requests
- ❌ delete leave requests
- ❌ submit leave requests
- ❌ approve leave requests
- ❌ reject leave requests
- ❌ cancel leave requests
- ❌ view leave balances
- ❌ adjust leave balances
- ❌ view leave types
- ❌ manage leave types
- ❌ export leave reports

### 5. Payroll & Benefits (11 missing)
- ❌ view payroll
- ❌ create payroll
- ❌ edit payroll
- ❌ delete payroll
- ❌ process payroll
- ❌ approve payroll
- ❌ export payroll
- ❌ view benefits
- ❌ manage benefits
- ❌ approve benefit payouts
- ❌ export payroll reports

### 6. Performance Management (8 missing)
- ❌ view performance reviews
- ❌ create performance reviews
- ❌ edit performance reviews
- ❌ delete performance reviews
- ❌ approve performance reviews
- ❌ view KPI data
- ❌ manage KPI templates
- ❌ export performance reports

### 7. Rewards & Discipline (8 missing)
- ❌ view rewards
- ❌ create rewards
- ❌ edit rewards
- ❌ delete rewards
- ❌ approve rewards
- ❌ view disciplines
- ❌ create disciplines
- ❌ edit disciplines
- ❌ delete disciplines
- ❌ approve disciplines

### 8. Reports & Analytics (11 missing)
- ❌ view all reports
- ❌ view department reports
- ❌ view employee reports
- ❌ view contract reports
- ❌ view leave reports
- ❌ view payroll reports
- ❌ view performance reports
- ❌ export all reports
- ❌ export department reports
- ❌ schedule reports
- ❌ create custom reports

### 9. Settings & Configuration (4 missing)
- ❌ view settings
- ❌ edit settings
- ❌ manage notification templates
- ❌ manage email templates

### 10. Activity Logs (2 missing)
- ❌ view own activity logs
- ❌ export activity logs

**Tổng thiếu: ~65 permissions**

---

## 📋 Permissions cần bổ sung NGAY

### Priority 1: Modules đang dùng (24 permissions)

#### Leave Management (13)
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

#### Payroll Management (11)
```php
'view payroll',
'create payroll',
'edit payroll',
'delete payroll',
'process payroll',
'approve payroll',
'export payroll',
'view benefits',
'manage benefits',
'approve benefit payouts',
'export payroll reports',
```

### Priority 2: Modules sắp triển khai (18 permissions)

#### Performance Management (8)
```php
'view performance reviews',
'create performance reviews',
'edit performance reviews',
'delete performance reviews',
'approve performance reviews',
'view KPI data',
'manage KPI templates',
'export performance reports',
```

#### Rewards & Discipline (10)
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

### Priority 3: System Enhancement (15 permissions)

#### System Administration (3)
```php
'manage system settings',
'view system logs',
'manage integrations',
```

#### Employee Enhancement (4)
```php
'import employees',
'export employees',
'terminate employees',
'transfer employees',
```

#### Reports & Analytics (8)
```php
'view all reports',
'view department reports',
'view employee reports',
'view contract reports',
'export all reports',
'export department reports',
'schedule reports',
'create custom reports',
```

---

## 🎯 Khuyến nghị

### 1. Bổ sung permissions theo Priority
- **Priority 1**: Thêm ngay 24 permissions cho Leave & Payroll (modules đang hoạt động)
- **Priority 2**: Thêm 18 permissions cho Performance & Rewards (sắp triển khai)
- **Priority 3**: Thêm 15 permissions cho System & Reports (tối ưu)

### 2. Thiết kế Permission Groups
Nhóm permissions theo modules để dễ quản lý:
```php
[
    'System Administration' => [...],
    'User Management' => [...],
    'Organization Structure' => [...],
    'Employee Management' => [...],
    'Contract Management' => [...],
    'Leave Management' => [...],
    'Payroll & Benefits' => [...],
    'Insurance Management' => [...],
    'Performance Management' => [...],
    'Rewards & Discipline' => [...],
    'Reports & Analytics' => [...],
    'Settings & Configuration' => [...],
    'Activity Logs' => [...],
]
```

### 3. Cơ chế Dynamic Permission Management
Cần triển khai:
- ✅ Backend: PermissionController (index, store, update, delete)
- ✅ Backend: RoleController enhancement (syncPermissions)
- ✅ Frontend: Permission management UI
- ✅ Frontend: Role permission assignment UI

### 4. Migration Strategy
```bash
# Step 1: Thêm permissions mới vào seeder
php artisan db:seed --class=RolesAndPermissionsSeeder

# Step 2: Test permission assignment
# Step 3: Cập nhật UI quản lý permission
```

---

## 🔥 Action Items

1. ✅ Cập nhật RolesAndPermissionsSeeder với 57 permissions mới (Priority 1 + 2)
2. ✅ Tạo PermissionController cho CRUD permissions
3. ✅ Tạo UI Permission Management (PermissionIndex.vue)
4. ✅ Thêm chức năng Sync Permissions cho Roles (RoleShow.vue)
5. ✅ Test toàn bộ permission assignment workflow
