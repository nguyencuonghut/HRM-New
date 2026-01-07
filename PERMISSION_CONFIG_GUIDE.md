# Permission Configuration System - Hướng dẫn

## 📋 Tổng quan

Hệ thống quản lý permissions qua config file giúp:
- ✅ **Single Source of Truth**: Tất cả permissions được định nghĩa tại một chỗ
- ✅ **Vietnamese Labels**: Hiển thị tiếng Việt thân thiện cho người dùng
- ✅ **Easy Maintenance**: Thêm/sửa/xóa permission không ảnh hưởng code logic
- ✅ **Grouped by Module**: Tổ chức permissions theo modules rõ ràng
- ✅ **Description Support**: Mỗi permission có description giải thích

---

## 🗂️ Cấu trúc file

### 1. Config File: `config/permissions.php`

```php
return [
    'modules' => [
        'module_key' => [
            'label' => 'Tên module tiếng Việt',
            'icon' => 'pi-icon-name',
            'permissions' => [
                'permission name' => [
                    'label' => 'Label tiếng Việt',
                    'description' => 'Mô tả chi tiết'
                ],
                // ...
            ]
        ],
        // ...
    ]
];
```

**Ví dụ:**
```php
'employee_management' => [
    'label' => 'Quản lý nhân viên',
    'icon' => 'pi-id-card',
    'permissions' => [
        'view employees' => [
            'label' => 'Xem nhân viên',
            'description' => 'Truy cập danh sách nhân viên'
        ],
        'create employees' => [
            'label' => 'Tạo nhân viên',
            'description' => 'Thêm nhân viên mới'
        ],
    ]
],
```

### 2. Helper Class: `app/Helpers/PermissionHelper.php`

Cung cấp methods tiện ích:
- `getLabel($permissionName)` - Lấy label tiếng Việt
- `getDescription($permissionName)` - Lấy mô tả
- `getModule($permissionName)` - Lấy tên module
- `getModuleIcon($permissionName)` - Lấy icon của module
- `getAllWithLabels()` - Lấy tất cả permissions với labels
- `getAllGrouped()` - Lấy permissions grouped by module
- `transformCollection($permissions)` - Transform Eloquent collection

---

## 💡 Cách sử dụng

### A. Backend (Controller/Service)

#### 1. Lấy label tiếng Việt cho permission
```php
use App\Helpers\PermissionHelper;

$label = PermissionHelper::getLabel('view employees');
// Output: "Xem nhân viên"

$description = PermissionHelper::getDescription('view employees');
// Output: "Truy cập danh sách nhân viên"
```

#### 2. Transform Eloquent Collection
```php
use App\Helpers\PermissionHelper;
use App\Models\Permission;

$permissions = Permission::all();
$withLabels = PermissionHelper::transformCollection($permissions);

// Result:
[
    [
        'id' => 1,
        'name' => 'view employees',
        'label' => 'Xem nhân viên',
        'description' => 'Truy cập danh sách nhân viên',
        'module' => 'Quản lý nhân viên',
        'module_icon' => 'pi-id-card',
        'guard_name' => 'web',
        // ...
    ],
    // ...
]
```

#### 3. Lấy tất cả permissions grouped
```php
$grouped = PermissionHelper::getAllGrouped();

// Result:
[
    'Quản lý nhân viên' => [
        'label' => 'Quản lý nhân viên',
        'icon' => 'pi-id-card',
        'permissions' => [
            ['name' => 'view employees', 'label' => 'Xem nhân viên', ...],
            ['name' => 'create employees', 'label' => 'Tạo nhân viên', ...],
        ]
    ],
    // ...
]
```

#### 4. Trong Controller
```php
class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::orderBy('name')->get();
        
        // Transform với labels tiếng Việt
        $permissionsWithLabels = PermissionHelper::transformCollection($permissions);

        return Inertia::render('Permissions/Index', [
            'permissions' => $permissionsWithLabels
        ]);
    }
}
```

### B. Frontend (Vue/JavaScript)

#### 1. Hiển thị permission với label tiếng Việt
```vue
<template>
    <div v-for="permission in permissions" :key="permission.id">
        <!-- Hiển thị label tiếng Việt -->
        <span>{{ permission.label }}</span>
        
        <!-- Hiển thị description -->
        <small>{{ permission.description }}</small>
        
        <!-- Hiển thị module -->
        <Badge :value="permission.module" />
        
        <!-- Hiển thị icon module -->
        <i :class="`pi ${permission.module_icon}`"></i>
    </div>
</template>

<script setup>
const props = defineProps({
    permissions: Array // Đã được transform từ backend
});
</script>
```

#### 2. DataTable với label tiếng Việt
```vue
<DataTable :value="permissions">
    <Column field="label" header="Quyền" sortable>
        <template #body="slotProps">
            <div class="flex items-center gap-2">
                <i :class="`pi ${slotProps.data.module_icon}`"></i>
                <span>{{ slotProps.data.label }}</span>
            </div>
        </template>
    </Column>
    
    <Column field="description" header="Mô tả" />
    
    <Column field="module" header="Module">
        <template #body="slotProps">
            <Badge :value="slotProps.data.module" severity="info" />
        </template>
    </Column>
</DataTable>
```

---

## ➕ Thêm Permission Mới

### Bước 1: Thêm vào config/permissions.php

```php
'employee_management' => [
    'label' => 'Quản lý nhân viên',
    'icon' => 'pi-id-card',
    'permissions' => [
        // Existing permissions...
        
        // ✅ Permission mới
        'suspend employees' => [
            'label' => 'Tạm đình chỉ nhân viên',
            'description' => 'Đình chỉ công tác tạm thời'
        ],
    ]
],
```

### Bước 2: Tạo permission trong database

**Option 1: Qua UI (Super Admin)**
```
Sidebar → System → Quản lý quyền → "Thêm quyền"
Name: suspend employees
→ Lưu
```

**Option 2: Qua seeder**
```php
// database/seeders/RolesAndPermissionsSeeder.php
$permissions = [
    // ...existing permissions
    'suspend employees',
];
```

**Option 3: Qua Artisan**
```bash
php artisan tinker
>>> Permission::create(['name' => 'suspend employees']);
```

### Bước 3: Assign vào roles (nếu cần)
```
Sidebar → System → Quản lý vai trò → HR Admin → "Phân quyền"
→ Search "suspend employees" → Check → Lưu
```

**Xong!** Frontend tự động hiển thị label "Tạm đình chỉ nhân viên" mà không cần sửa code.

---

## ✏️ Sửa Permission Label

Chỉ cần sửa trong `config/permissions.php`:

```php
'view employees' => [
    'label' => 'Xem danh sách nhân viên',  // ✅ Sửa label
    'description' => 'Xem thông tin nhân viên trong hệ thống'  // ✅ Sửa description
],
```

**Lưu ý:** 
- ⚠️ **KHÔNG sửa key** `'view employees'` (đây là permission name trong database)
- ✅ Chỉ sửa `label` và `description`
- Không cần restart server (Laravel tự reload config)

---

## 🗑️ Xóa Permission

### Bước 1: Xóa khỏi config/permissions.php
```php
'permissions' => [
    // ❌ Xóa dòng này
    // 'old permission' => ['label' => '...', 'description' => '...'],
],
```

### Bước 2: Revoke khỏi tất cả roles
```
Sidebar → System → Quản lý quyền → Xem "old permission"
→ Check "Được gán cho X roles" → Revoke từng role
```

### Bước 3: Xóa khỏi database
```
Sidebar → System → Quản lý quyền → Click trash icon → Xác nhận
```

---

## 🌍 Thêm Module Mới

Ví dụ thêm module "Attendance Management":

```php
// config/permissions.php
'modules' => [
    // ...existing modules
    
    'attendance_management' => [
        'label' => 'Quản lý chấm công',
        'icon' => 'pi-clock',
        'permissions' => [
            'view attendance' => [
                'label' => 'Xem chấm công',
                'description' => 'Xem lịch sử chấm công'
            ],
            'create attendance' => [
                'label' => 'Tạo chấm công',
                'description' => 'Chấm công thủ công'
            ],
            'approve attendance' => [
                'label' => 'Duyệt chấm công',
                'description' => 'Phê duyệt bản ghi chấm công'
            ],
            'export attendance reports' => [
                'label' => 'Xuất báo cáo chấm công',
                'description' => 'Export báo cáo'
            ],
        ]
    ],
],
```

Sau đó tạo permissions trong database (qua UI hoặc seeder).

---

## 🎯 Best Practices

### 1. Naming Convention cho Permissions
```
<action> <resource> [<optional context>]

✅ Good:
- view employees
- create contracts
- approve leave requests
- export payroll reports
- manage system settings

❌ Bad:
- employees.view (dùng dot notation)
- ViewEmployees (PascalCase)
- xem-nhan-vien (tiếng Việt)
```

### 2. Naming Convention cho Labels
```
✅ Good:
- "Xem nhân viên" (ngắn gọn)
- "Tạo hợp đồng" (rõ ràng)
- "Duyệt đơn nghỉ phép" (cụ thể)

❌ Bad:
- "Xem" (quá chung chung)
- "Có thể xem được danh sách nhân viên" (quá dài)
```

### 3. Description Guidelines
```
✅ Good:
- "Truy cập danh sách nhân viên trong hệ thống"
- "Tạo hợp đồng lao động mới"
- "Phê duyệt đơn xin nghỉ phép của nhân viên"

❌ Bad:
- "View employees" (dùng tiếng Anh)
- "" (để trống)
- "Xem nhân viên" (trùng với label)
```

### 4. Module Organization
- Group permissions theo business domain (Employee, Contract, Leave...)
- Mỗi module nên có 4-15 permissions (không quá nhiều)
- Permissions trong module nên follow CRUD order: view → create → edit → delete → others

### 5. Icons
Sử dụng PrimeIcons:
- `pi-id-card` - Employee
- `pi-file` - Contract
- `pi-calendar` - Leave
- `pi-money-bill` - Payroll
- `pi-shield` - Insurance, Backup, Roles
- `pi-chart-bar` - Reports
- `pi-cog` - Settings
- Xem full list: https://primevue.org/icons

---

## 🧪 Testing

### Test transform collection
```php
use App\Helpers\PermissionHelper;
use App\Models\Permission;

// Test single permission
$permission = Permission::where('name', 'view employees')->first();
$label = PermissionHelper::getLabel($permission->name);
assert($label === 'Xem nhân viên');

// Test collection transform
$permissions = Permission::take(5)->get();
$transformed = PermissionHelper::transformCollection($permissions);
assert(isset($transformed[0]['label']));
assert(isset($transformed[0]['module']));
```

### Test frontend display
```bash
# 1. Login as Super Admin
# 2. Sidebar → System → Quản lý quyền
# 3. Verify:
#    - Permissions hiển thị label tiếng Việt
#    - Module names hiển thị tiếng Việt
#    - Icons hiển thị đúng
#    - Search hoạt động
```

---

## 🚀 Migration từ hệ thống cũ

Nếu bạn đã có permissions trong database (tiếng Anh), chỉ cần:

1. **Tạo config/permissions.php** với mapping đầy đủ
2. **Transform khi pass qua Inertia** (đã làm trong PermissionController)
3. **Frontend tự động nhận labels tiếng Việt**

Không cần sửa database, không cần migration!

---

## 📝 Checklist khi thêm permission mới

- [ ] Thêm vào `config/permissions.php` với label & description tiếng Việt
- [ ] Đặt tên permission theo convention (lowercase, space-separated)
- [ ] Chọn module phù hợp hoặc tạo module mới
- [ ] Chọn icon phù hợp cho module
- [ ] Tạo permission trong database (UI/seeder/tinker)
- [ ] Assign vào roles phù hợp
- [ ] Test hiển thị trong UI
- [ ] Update documentation nếu cần

---

## ❓ FAQs

**Q: Có cần restart server khi sửa config không?**  
A: Không cần trong development (Laravel auto reload). Production thì nên clear cache:
```bash
php artisan config:cache
```

**Q: Nếu permission không có trong config thì sao?**  
A: `PermissionHelper::getLabel()` sẽ fallback về tên tiếng Anh gốc.

**Q: Có thể dùng cho API không?**  
A: Có, transform permission trước khi return JSON:
```php
$permissions = PermissionHelper::transformCollection(Permission::all());
return response()->json($permissions);
```

**Q: Performance có bị ảnh hưởng không?**  
A: Không đáng kể. Config được cached, lookup O(n) trong vài trăm items. Có thể optimize bằng cache nếu cần.

---

**Author:** HRM Development Team  
**Version:** 1.0.0  
**Last Updated:** 2026-01-07
