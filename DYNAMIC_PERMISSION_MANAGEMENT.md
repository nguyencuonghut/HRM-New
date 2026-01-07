# Hệ thống Quản lý Permission Động - Hướng dẫn sử dụng

## 📋 Tổng quan

Hệ thống cho phép Super Admin quản lý permissions và phân quyền cho từng role qua giao diện web, không cần chỉnh sửa code.

---

## ✨ Tính năng đã triển khai

### 1. **Permission Management (Super Admin only)**
- ✅ Xem danh sách tất cả permissions
- ✅ Tạo permission mới
- ✅ Sửa tên permission
- ✅ Xóa permission (nếu chưa được gán cho role nào)
- ✅ View theo 2 chế độ:
  - **Flat view**: Danh sách phẳng, dễ tìm kiếm
  - **Grouped view**: Nhóm theo module (System Admin, User Management, Employee, etc.)
- ✅ Search & Filter permissions
- ✅ Bulk delete permissions

### 2. **Role Permission Assignment**
- ✅ Giao diện trực quan để assign/revoke permissions cho role
- ✅ Hiển thị permissions grouped by module
- ✅ Checkbox multi-select với toggle module (chọn/bỏ chọn toàn bộ module)
- ✅ Thống kê real-time: Tổng permissions, Đã chọn, Chưa chọn, Tỷ lệ %
- ✅ Search permissions theo tên
- ✅ Filter: Tất cả / Đã chọn / Chưa chọn
- ✅ Floating save button khi có thay đổi
- ✅ Activity log cho mọi thay đổi permission

### 3. **Backend API**
- ✅ PermissionController với CRUD permissions
- ✅ RoleController mở rộng với syncPermissions(), getPermissions(), getUsersWithRole()
- ✅ Authorization gates (Super Admin only)
- ✅ Validation & error handling
- ✅ Activity logging

### 4. **Permission System**
- ✅ 146 permissions (tăng từ 89 lên 146)
- ✅ Grouped thành 20 modules:
  - System Administration (3)
  - User Management (7)
  - Role Management (4)
  - Permission Management (5)
  - Backup Management (5)
  - Activity Logs (4)
  - Organization Structure (4)
  - Employee Management (10)
  - Employee Assignment (4)
  - Position Management (4)
  - Master Data (12)
  - Skill Management (8)
  - Contract Management (10)
  - Leave Management (13)
  - Payroll & Benefits (11)
  - Insurance Management (8)
  - Performance Management (8)
  - Rewards & Discipline (10)
  - Reports & Analytics (11)
  - Settings & Configuration (4)
  - Legacy Data Import (6)

---

## 🎯 Cách sử dụng

### A. Quản lý Permissions (Super Admin)

#### 1. Xem danh sách permissions
```
Sidebar → System → Quản lý quyền
```

**Chế độ xem:**
- **Grouped view** (mặc định): Permissions grouped theo module, dễ overview
- **Flat view**: Bảng phẳng với search/filter, dễ tìm kiếm cụ thể

**Features:**
- Search global: Tìm theo tên permission hoặc module
- Sort: Click header để sort theo tên, số roles, guard name
- Pagination: 25/50/100 items per page

#### 2. Tạo permission mới
```
Button "Thêm quyền" → Nhập:
- Tên quyền (required): vd "approve payroll", "export reports"
- Guard name (optional): Mặc định "web"
→ Lưu
```

**Quy tắc đặt tên:**
- Sử dụng lowercase, space-separated hoặc snake_case
- Format: `<action> <resource>`
  - view employees
  - create contracts
  - approve payroll
  - manage system settings

#### 3. Sửa permission
```
Click icon Pencil → Sửa tên → Lưu
```

#### 4. Xóa permission
```
Click icon Trash → Confirm
```

**Lưu ý:** 
- Không thể xóa permission đã được gán cho role
- Hiển thị warning nếu permission đang được sử dụng

#### 5. Xóa nhiều permissions
```
Checkbox chọn permissions → Button "Xóa" → Confirm
```

---

### B. Phân quyền cho Role (Super Admin)

#### 1. Truy cập trang phân quyền
**Cách 1:**
```
Sidebar → System → Quản lý vai trò → Click icon "Shield" → "Phân quyền"
```

**Cách 2:**
```
Sidebar → System → Quản lý quyền → Chọn role từ dropdown
```

#### 2. Assign permissions cho role
**Giao diện:**
- Header: Tên role, Statistics cards (Tổng, Đã chọn, Chưa chọn, Tỷ lệ %)
- Toolbar: Search, Filter (Tất cả/Đã chọn/Chưa chọn), Select all/Deselect all
- Body: Accordion modules với checkboxes

**Thao tác:**
1. **Chọn từng permission:** Click checkbox hoặc click vào card
2. **Chọn toàn bộ module:** Click "Chọn tất cả" ở header module
3. **Search:** Gõ tên permission → Chỉ hiển thị matching permissions
4. **Filter:** 
   - "Tất cả": Hiển thị tất cả
   - "Đã chọn": Chỉ hiển thị permissions đã chọn
   - "Chưa chọn": Chỉ hiển thị permissions chưa chọn
5. **Save:** Click "Lưu thay đổi" (floating button xuất hiện khi có thay đổi)

#### 3. Xem permissions của role
```
Sidebar → System → Quản lý vai trò → Click vào role name
→ Tab "Permissions"
```

---

## 🔐 Authorization Matrix

| Action | Super Admin | HR Admin | Other Roles |
|--------|-------------|----------|-------------|
| View permissions | ✅ | ❌ | ❌ |
| Create permissions | ✅ | ❌ | ❌ |
| Edit permissions | ✅ | ❌ | ❌ |
| Delete permissions | ✅ | ❌ | ❌ |
| Assign permissions to roles | ✅ | ❌ | ❌ |
| View own permissions | ✅ | ✅ | ✅ |

---

## 📊 Workflow Examples

### Example 1: Thêm permission mới và gán cho role

```bash
# Bước 1: Tạo permission mới
Sidebar → System → Quản lý quyền → "Thêm quyền"
Name: "export insurance reports"
Guard: "web"
→ Lưu

# Bước 2: Gán cho role "Payroll Admin"
Click "Payroll Admin" → Tab Permissions
→ Search "export insurance"
→ Check checkbox "export insurance reports"
→ "Lưu thay đổi"

# Bước 3: Verify
Login as user với role "Payroll Admin"
→ Check nếu button "Export" xuất hiện trên Insurance Reports page
```

### Example 2: Clone permissions từ role này sang role khác

```bash
# Bước 1: Xem permissions của HR Admin
Sidebar → Vai trò → HR Admin → "Phân quyền"
→ Filter "Đã chọn" → Copy list

# Bước 2: Assign vào Department Manager
→ Quay về Vai trò → Department Manager → "Phân quyền"
→ Chọn các permissions tương ứng
→ Lưu
```

### Example 3: Remove permission khỏi tất cả roles

```bash
# Bước 1: Xem permission được gán cho roles nào
Sidebar → Quản lý quyền → Flat view
→ Column "Được gán cho" → Xem "X roles"

# Bước 2: Remove từng role
Click vào từng role → Phân quyền
→ Uncheck permission đó → Lưu

# Bước 3: Xóa permission
Quay về Quản lý quyền → Xóa permission
```

---

## 🛠️ Testing Checklist

### Permission Management
- [ ] Create new permission
- [ ] Edit permission name
- [ ] Delete unused permission
- [ ] Cannot delete permission assigned to roles
- [ ] Bulk delete permissions
- [ ] Search permissions (flat view)
- [ ] Filter by module (grouped view)
- [ ] View permissions of specific role

### Role Permission Assignment
- [ ] View all permissions for a role
- [ ] Toggle individual permission
- [ ] Select all module permissions
- [ ] Deselect all module permissions
- [ ] Search permissions
- [ ] Filter: All / Selected / Unselected
- [ ] Statistics update real-time
- [ ] Floating save button appears on change
- [ ] Save permissions successfully
- [ ] Activity log created after save
- [ ] Verify user permissions updated immediately

### Authorization
- [ ] Super Admin can access all features
- [ ] HR Admin cannot access Permission Management
- [ ] Other roles cannot access Permission Management
- [ ] Middleware blocks unauthorized access

---

## 🎨 UI Screenshots Reference

### 1. Permission Index - Grouped View
```
┌─────────────────────────────────────────────────────────┐
│ Toolbar: [Thêm quyền] [Xóa]          [Danh sách][Module]│
├─────────────────────────────────────────────────────────┤
│ ▼ System Administration (3)                        [3] │
│   ├─ manage system settings                         ✎ 🗑│
│   ├─ view system logs                               ✎ 🗑│
│   └─ manage integrations                            ✎ 🗑│
├─────────────────────────────────────────────────────────┤
│ ▼ User Management (7)                              [7] │
│   ├─ view users           ├─ import users               │
│   ├─ create users         ├─ export users               │
│   └─ ...                                                │
└─────────────────────────────────────────────────────────┘
```

### 2. Role Permissions Management
```
┌─────────────────────────────────────────────────────────┐
│ [← Quay lại]                                            │
│                                                          │
│ Phân Quyền cho Role         [HR Admin]  [45 / 146]     │
│                                                          │
│ [🔍 Search...]  [Tất cả][Đã chọn][Chưa chọn]           │
│ [Chọn tất cả] [Bỏ chọn tất cả]                         │
├─────────────────────────────────────────────────────────┤
│ 📊 Statistics:                                          │
│ [Tổng: 146]  [Đã chọn: 45]  [Chưa chọn: 101]  [31%]   │
├─────────────────────────────────────────────────────────┤
│ ▼ Employee Management    [8 / 10]       [Chọn tất cả] │
│   ☑ view employees        ☑ import employees           │
│   ☑ create employees      ☑ export employees           │
│   ☑ edit employees        ☐ terminate employees        │
│   ☑ delete employees      ☐ transfer employees         │
│   ☑ view employee profiles                             │
│   ☑ edit employee profiles                             │
└─────────────────────────────────────────────────────────┘
                                    [💾 Lưu thay đổi]
```

---

## 🚀 Next Steps (Future Enhancements)

### Phase 2: Role Templates
- [ ] Tạo role templates (vd: "HR Staff Template")
- [ ] Clone role với permissions
- [ ] Import/Export role definitions (JSON)

### Phase 3: Permission Groups
- [ ] Tạo permission groups (vd: "Employee Full Access" = view+create+edit+delete employees)
- [ ] Assign group to role thay vì từng permission

### Phase 4: Time-based Permissions
- [ ] Temporary permissions (có expiration date)
- [ ] Schedule permission changes

### Phase 5: Audit & Compliance
- [ ] Permission usage analytics
- [ ] Unused permissions detection
- [ ] Role permission diff/comparison tool
- [ ] Compliance reports (ai có quyền gì, khi nào)

---

## ❗ Important Notes

### Seeder vs Dynamic Management
- **Seeder**: Chỉ chạy khi setup DB mới hoặc reset
- **Dynamic Management**: Production environment - thay đổi permission qua UI
- **Best Practice**: 
  - Development: Dùng seeder để sync permissions
  - Production: Dùng UI để thêm/sửa permissions

### Performance Considerations
- Spatie Permission package cache permissions
- Clear cache sau khi thay đổi permission:
  ```bash
  php artisan permission:cache-reset
  ```
- Cache tự động clear khi save qua UI

### Security Best Practices
- ✅ Chỉ Super Admin có quyền quản lý permissions
- ✅ Activity log mọi thay đổi permission
- ✅ Validate permission names (không trùng)
- ✅ Không cho xóa permission đang được sử dụng
- ✅ Không cho xóa system roles (Super Admin)

---

## 📝 Database Schema

### Spatie Permission Tables
```sql
-- Roles table
roles (id, name, guard_name, created_at, updated_at)

-- Permissions table
permissions (id, name, guard_name, created_at, updated_at)

-- Role has permissions (pivot)
role_has_permissions (permission_id, role_id)

-- User has roles (pivot)
model_has_roles (role_id, model_type, model_id)

-- User has permissions (pivot - direct assignment)
model_has_permissions (permission_id, model_type, model_id)
```

---

## 🔧 Troubleshooting

### Issue: Permission không apply ngay sau khi save
**Solution:** Clear cache
```bash
php artisan permission:cache-reset
```

### Issue: User vẫn thấy menu/button dù đã revoke permission
**Solution:** 
1. Logout và login lại
2. Check blade/vue components có dùng đúng `@can()` hoặc `can()` helper không

### Issue: Không thể xóa permission
**Solution:** Check permission có đang được assign cho role nào không
```php
$permission = Permission::find($id);
$roles = $permission->roles; // Nếu > 0, phải revoke trước khi xóa
```

---

## 📞 Support

Nếu có vấn đề, check:
1. **Logs:** `storage/logs/laravel.log`
2. **Activity Logs:** System → Activity Logs
3. **Database:** Check `role_has_permissions` table
4. **Cache:** Clear permission cache

---

**Ngày cập nhật:** 2026-01-07  
**Version:** 1.0.0  
**Author:** HRM Development Team
