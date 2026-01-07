# Kế Hoạch Triển Khai Role & Permission

## 📊 Phân Tích Hiện Trạng

### ✅ Đã Có
1. **Config System**: `config/permissions.php` - 146 permissions trong 17 modules
2. **Helper Class**: `PermissionHelper` với Vietnamese labels
3. **Database**: Bảng permissions/roles từ Spatie (đã có columns label/description)
4. **Backend Protection (Partial)**:
   - ✅ Permission-related: PermissionController, RoleController
   - ✅ EmployeeAssignmentController 
   - ✅ EmployeeEducationController
   - ✅ SchoolController
   - ✅ ActivityLogController
   - ✅ EmployeeSkillController
5. **Frontend Permission Checks (Partial)**:
   - ✅ Permissions/Index.vue
   - ✅ BackupIndex.vue
   - ✅ DepartmentIndex.vue
   - ✅ ContractTemplateIndex.vue
   - ✅ UserIndex.vue

### ❌ Chưa Có
1. **Policies**: Chỉ có 17/46 controllers có Policy
2. **Backend Authorization**: Nhiều controllers chưa có `authorize()` 
3. **Frontend Permission Checks**: Nhiều Vue pages chưa check permissions
4. **Middleware**: Chưa có middleware tự động check permissions cho routes

---

## 🎯 Mục Tiêu

1. **Backend**: 100% controllers có authorization checks
2. **Frontend**: 100% UI elements (buttons, actions) dựa trên permissions
3. **Policies**: Tạo đầy đủ policies cho tất cả resources
4. **Middleware**: Route-level permission protection
5. **Documentation**: Hướng dẫn đầy đủ cho developers

---

## 📋 Danh Sách Controllers Cần Xử Lý

### 🔴 Priority 1 - Core Features (Chưa có Authorization)
1. **EmployeeController** - 10 permissions cần protect
2. **ContractController** - 11 permissions  
3. **LeaveBalanceController** - 4 permissions
4. **LeaveApprovalController** - 3 permissions
5. **UserController** - 7 permissions
6. **PositionController** - 4 permissions

### 🟡 Priority 2 - Supporting Features
7. **InsuranceReportController**
8. **BenefitTypeController**
9. **SkillController**
10. **SkillCategoryController**
11. **ProvinceController**
12. **WardController**
13. **EducationLevelController**

### 🟢 Priority 3 - Additional Features
14. **EmployeeKpiMonthController**
15. **EmployeeRewardDisciplineController**
16. **EmployeeRelativeController**
17. **EmployeeExperienceController**
18. **ContractAppendixController**
19. **ContractTemplateController**
20. **ContractAppendixTemplateController**

---

## 🚀 Kế Hoạch Thực Hiện (10 Bước)

### **BƯỚC 1: Tạo Policies Còn Thiếu** (30 phút)
**Mục tiêu**: Tạo 29 policy files còn thiếu

**Actions**:
```bash
# Generate policies
php artisan make:policy EmployeePolicy --model=Employee
php artisan make:policy ContractPolicy --model=Contract
php artisan make:policy LeaveRequestPolicy --model=LeaveRequest
php artisan make:policy LeaveBalancePolicy --model=LeaveBalance
php artisan make:policy PositionPolicy --model=Position
php artisan make:policy BenefitTypePolicy --model=BenefitType
php artisan make:policy SkillPolicy --model=Skill
php artisan make:policy ProvincePolicy --model=Province
php artisan make:policy WardPolicy --model=Ward
# ... tiếp tục với các models khác
```

**Template mẫu cho Policy**:
```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view employees');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('view employees');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create employees');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('edit employees');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('delete employees');
    }

    public function bulkDelete(User $user): bool
    {
        return $user->hasPermissionTo('delete employees');
    }
}
```

---

### **BƯỚC 2: Register Policies** (5 phút)
**File**: `app/Providers/AuthServiceProvider.php`

Thêm vào array `$policies`:
```php
protected $policies = [
    Employee::class => EmployeePolicy::class,
    Contract::class => ContractPolicy::class,
    LeaveRequest::class => LeaveRequestPolicy::class,
    // ... tất cả policies
];
```

---

### **BƯỚC 3: Thêm Authorization vào Controllers - Priority 1** (1 giờ)

#### **EmployeeController**
```php
public function index()
{
    $this->authorize('viewAny', Employee::class);
    // ...
}

public function store(Request $request)
{
    $this->authorize('create', Employee::class);
    // ...
}

public function update(Request $request, Employee $employee)
{
    $this->authorize('update', $employee);
    // ...
}

public function destroy(Employee $employee)
{
    $this->authorize('delete', $employee);
    // ...
}
```

#### Áp dụng tương tự cho:
- ContractController
- LeaveBalanceController
- LeaveApprovalController
- UserController  
- PositionController

---

### **BƯỚC 4: Thêm Authorization vào Controllers - Priority 2 & 3** (1 giờ)
Áp dụng pattern giống Priority 1 cho các controllers còn lại

---

### **BƯỚC 5: Tạo Helper Functions cho Frontend** (15 phút)
**File**: `resources/js/Composables/usePermissions.js`

```javascript
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();
    
    const userPermissions = computed(() => {
        return page.props.auth?.user?.permissions || [];
    });

    const can = (permissionName) => {
        return userPermissions.value.some(p => p.name === permissionName);
    };

    const canAny = (...permissionNames) => {
        return permissionNames.some(name => can(name));
    };

    const canAll = (...permissionNames) => {
        return permissionNames.every(name => can(name));
    };

    return { can, canAny, canAll, userPermissions };
}
```

---

### **BƯỚC 6: Cập Nhật Inertia Middleware** (10 phút)
**File**: `app/Http/Middleware/HandleInertiaRequests.php`

```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'auth' => [
            'user' => $request->user() ? [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'roles' => $request->user()->roles->pluck('name'),
                'permissions' => $request->user()->getAllPermissions()->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'label' => \App\Helpers\PermissionHelper::getLabel($p->name),
                ]),
            ] : null,
        ],
    ];
}
```

---

### **BƯỚC 7: Cập Nhật Frontend - Priority 1 Pages** (2 giờ)

#### **EmployeeIndex.vue**
```vue
<script setup>
import { usePermissions } from '@/Composables/usePermissions';

const { can } = usePermissions();
</script>

<template>
    <Toolbar>
        <template #start>
            <Button 
                v-if="can('create employees')" 
                label="Thêm nhân viên" 
                icon="pi pi-plus" 
                @click="openNew" 
            />
            <Button 
                v-if="can('delete employees')" 
                label="Xóa" 
                icon="pi pi-trash" 
                severity="danger" 
            />
        </template>
    </Toolbar>

    <DataTable>
        <!-- ... -->
        <Column v-if="can('edit employees') || can('delete employees')">
            <template #body="slotProps">
                <Button 
                    v-if="can('edit employees')" 
                    icon="pi pi-pencil" 
                    @click="edit(slotProps.data)" 
                />
                <Button 
                    v-if="can('delete employees')" 
                    icon="pi pi-trash" 
                    @click="confirmDelete(slotProps.data)" 
                />
            </template>
        </Column>
    </DataTable>
</template>
```

#### Áp dụng cho các pages:
- ContractIndex.vue
- LeaveBalances/Index.vue
- UserIndex.vue
- PositionIndex.vue

---

### **BƯỚC 8: Cập Nhật Frontend - Priority 2 & 3 Pages** (2 giờ)
Áp dụng pattern tương tự cho các pages còn lại

---

### **BƯỚC 9: Tạo Route Middleware** (30 phút)
**File**: `app/Http/Middleware/CheckPermission.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user()?->hasPermissionTo($permission)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
```

**Register middleware** trong `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'permission' => \App\Http\Middleware\CheckPermission::class,
    ]);
})
```

**Áp dụng trong routes**:
```php
Route::middleware(['auth', 'permission:view employees'])->group(function () {
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
});
```

---

### **BƯỚC 10: Testing & Documentation** (1 giờ)

#### **Testing Checklist**:
- [ ] Super Admin có full access
- [ ] HR Admin có access các modules HR
- [ ] HR Staff có limited access
- [ ] Employee chỉ xem được data của mình
- [ ] Buttons ẩn/hiện đúng theo permissions
- [ ] API calls bị block khi không có permission

#### **Documentation**:
Tạo file `PERMISSION_USAGE_GUIDE.md`:
- Cách check permissions trong Controller
- Cách check permissions trong Vue
- Cách tạo Policy mới
- Cách thêm permission mới
- Common patterns & best practices

---

## 📊 Tổng Quan Timeline

| Bước | Thời Gian | Độ Ưu Tiên |
|------|-----------|------------|
| 1. Tạo Policies | 30 phút | 🔴 |
| 2. Register Policies | 5 phút | 🔴 |
| 3. Authorization P1 | 1 giờ | 🔴 |
| 4. Authorization P2&3 | 1 giờ | 🟡 |
| 5. Frontend Helpers | 15 phút | 🔴 |
| 6. Inertia Middleware | 10 phút | 🔴 |
| 7. Frontend P1 | 2 giờ | 🔴 |
| 8. Frontend P2&3 | 2 giờ | 🟡 |
| 9. Route Middleware | 30 phút | 🟢 |
| 10. Testing & Docs | 1 giờ | 🟢 |
| **TỔNG** | **~8.5 giờ** | |

---

## 🎯 Quick Wins (Có thể làm ngay)

1. **Tạo usePermissions composable** (15 phút) → Dùng ngay được
2. **Cập nhật HandleInertiaRequests** (10 phút) → Share permissions to frontend
3. **Tạo 5-10 policies quan trọng nhất** (30 phút) → Protect core features

---

## 📝 Notes

### Best Practices
1. **Luôn check permissions ở cả BE và FE**
2. **Policies nên đơn giản, chỉ check hasPermissionTo()**
3. **Frontend composable giúp code DRY**
4. **Không hardcode permission names - dùng constants**
5. **Test với multiple roles để đảm bảo logic đúng**

### Common Pitfalls
1. ❌ Chỉ check FE không check BE → Security risk
2. ❌ Forget to register Policy → Authorization không work
3. ❌ Không share permissions qua Inertia → FE không có data
4. ❌ Typo trong permission names → Logic sai

---

## 🚦 Bắt Đầu Từ Đây

Bạn muốn bắt đầu từ bước nào? Đề xuất:
1. **Quick Win**: Bước 5 + 6 (25 phút) - Enable FE permission checks ngay
2. **Core Protection**: Bước 1 + 2 + 3 (1.5 giờ) - Protect quan trọng nhất
3. **Full Implementation**: Làm tuần tự từ Bước 1 → 10
