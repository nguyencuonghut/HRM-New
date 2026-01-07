# Permission Usage Guide - HongHa HRM System

Complete guide for implementing and using the role-based permission system in HongHa HRM.

## Table of Contents
1. [Overview](#overview)
2. [Permission Configuration](#permission-configuration)
3. [Backend Implementation](#backend-implementation)
4. [Frontend Implementation](#frontend-implementation)
5. [Middleware Usage](#middleware-usage)
6. [Testing Guide](#testing-guide)
7. [Best Practices](#best-practices)
8. [Troubleshooting](#troubleshooting)

---

## Overview

The HongHa HRM system uses **Spatie Laravel Permission** package with a centralized configuration approach:

- **146 permissions** organized into **17 modules**
- Vietnamese labels for UI display
- English names for code references
- Automatic policy discovery (Laravel 11)
- Frontend permission composable
- Route-level and controller-level authorization

### Key Components

1. **config/permissions.php** - Single source of truth for all permissions
2. **PermissionHelper** - Transforms permissions with Vietnamese labels
3. **usePermissions.js** - Frontend composable for permission checks
4. **Policies** - Authorization logic for each model
5. **PermissionMiddleware** - Route-level protection

---

## Permission Configuration

### Structure

```php
// config/permissions.php
return [
    'modules' => [
        'employees' => [
            'label' => 'Quản lý nhân viên',
            'icon' => 'pi pi-users',
            'permissions' => [
                'view employees' => [
                    'label' => 'Xem danh sách nhân viên',
                    'description' => 'Cho phép xem danh sách tất cả nhân viên trong hệ thống',
                ],
                // ...
            ],
        ],
    ],
];
```

### Adding New Permissions

1. **Add to config/permissions.php**:

```php
'contracts' => [
    'label' => 'Quản lý hợp đồng',
    'icon' => 'pi pi-file-edit',
    'permissions' => [
        'renew contracts' => [
            'label' => 'Gia hạn hợp đồng',
            'description' => 'Cho phép gia hạn hợp đồng lao động',
        ],
    ],
],
```

2. **Sync to database** (run in tinker or seeder):

```php
use Spatie\Permission\Models\Permission;

Permission::firstOrCreate(['name' => 'renew contracts']);
```

3. **Assign to roles**:

```php
$role = Role::findByName('HR Admin');
$role->givePermissionTo('renew contracts');
```

---

## Backend Implementation

### 1. Controller Authorization

#### Standard CRUD Pattern

```php
<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmployeeController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Employee::class);
        
        // Your code...
    }

    public function show(Employee $employee)
    {
        $this->authorize('view', $employee);
        
        // Your code...
    }

    public function store(Request $request)
    {
        $this->authorize('create', Employee::class);
        
        // Your code...
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorize('update', $employee);
        
        // Your code...
    }

    public function destroy(Employee $employee)
    {
        $this->authorize('delete', $employee);
        
        // Your code...
    }
}
```

#### Custom Actions

```php
public function approve(Contract $contract)
{
    $this->authorize('approve', $contract);
    
    // Your code...
}

public function export()
{
    $this->authorize('export', Employee::class);
    
    // Your code...
}
```

### 2. Policy Implementation

#### Create Policy

```bash
php artisan make:policy EmployeePolicy --model=Employee
```

#### Policy Structure

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;

class EmployeePolicy
{
    /**
     * Determine if user can view any employees
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view employees');
    }

    /**
     * Determine if user can view specific employee
     */
    public function view(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('view employees');
    }

    /**
     * Determine if user can create employees
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create employees');
    }

    /**
     * Determine if user can update employee
     */
    public function update(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('edit employees');
    }

    /**
     * Determine if user can delete employee
     */
    public function delete(User $user, Employee $employee): bool
    {
        return $user->hasPermissionTo('delete employees');
    }

    /**
     * Determine if user can view employee profile
     */
    public function viewProfile(User $user, Employee $employee): bool
    {
        // User can view their own profile or has permission
        return $user->employee_id === $employee->id 
            || $user->hasPermissionTo('view employee profiles');
    }

    /**
     * Determine if user can edit employee profile
     */
    public function editProfile(User $user, Employee $employee): bool
    {
        // User can edit their own profile or has permission
        return $user->employee_id === $employee->id 
            || $user->hasPermissionTo('edit employee profiles');
    }
}
```

#### Complex Policy Logic

```php
public function approve(User $user, Contract $contract): bool
{
    // Must have permission
    if (!$user->hasPermissionTo('approve contracts')) {
        return false;
    }

    // Cannot approve own contract
    if ($user->employee_id === $contract->employee_id) {
        return false;
    }

    // Contract must be pending approval
    if ($contract->status !== ContractStatus::PENDING_APPROVAL) {
        return false;
    }

    return true;
}
```

### 3. Policy Registration

**Laravel 11 auto-discovers policies** by convention:
- `App\Models\Employee` → `App\Policies\EmployeePolicy`
- `App\Models\Contract` → `App\Policies\ContractPolicy`

No manual registration needed in `AuthServiceProvider`.

---

## Frontend Implementation

### 1. Using the Permission Composable

#### Import and Setup

```vue
<script setup>
import { usePermissions } from '@/Composables/usePermissions';

const { can, canAny, canAll, hasRole, isSuperAdmin } = usePermissions();
</script>
```

#### Available Functions

```javascript
// Check single permission
can('view employees') // boolean

// Check if user has ANY of the permissions (OR)
canAny('edit employees', 'delete employees') // boolean

// Check if user has ALL permissions (AND)
canAll('view employees', 'edit employees') // boolean

// Check single role
hasRole('HR Admin') // boolean

// Check if user has ANY role (OR)
hasAnyRole('Admin', 'HR Admin') // boolean

// Check if user has ALL roles (AND)
hasAllRoles('Admin', 'Super Admin') // boolean

// Check if Super Admin (bypasses all checks)
isSuperAdmin() // boolean
```

### 2. Conditional Rendering

#### Hide/Show Buttons

```vue
<template>
  <!-- Create button -->
  <Button 
    v-if="can('create employees')"
    label="Thêm mới" 
    icon="pi pi-plus"
    @click="openCreateDialog" 
  />

  <!-- Edit button -->
  <Button
    v-if="can('edit employees')"
    icon="pi pi-pencil"
    @click="editEmployee(employee)"
  />

  <!-- Delete button -->
  <Button
    v-if="can('delete employees')"
    icon="pi pi-trash"
    severity="danger"
    @click="deleteEmployee(employee)"
  />

  <!-- Export button (Super Admin only) -->
  <Button
    v-if="isSuperAdmin()"
    label="Xuất dữ liệu"
    icon="pi pi-download"
    @click="exportData"
  />
</template>
```

#### Conditional Columns in DataTable

```vue
<template>
  <DataTable :value="employees">
    <Column field="employee_code" header="Mã NV" />
    <Column field="full_name" header="Họ và tên" />
    
    <!-- Actions column - only show if user can edit OR delete -->
    <Column 
      v-if="canAny('edit employees', 'delete employees')"
      header="Hành động"
    >
      <template #body="{ data }">
        <Button
          v-if="can('edit employees')"
          icon="pi pi-pencil"
          @click="editEmployee(data)"
        />
        <Button
          v-if="can('delete employees')"
          icon="pi pi-trash"
          @click="deleteEmployee(data)"
        />
      </template>
    </Column>
  </DataTable>
</template>
```

#### Complex Conditional Logic

```vue
<template>
  <!-- Contract actions based on status -->
  <div v-if="contract.status === 'DRAFT'">
    <Button
      v-if="can('edit contracts')"
      label="Chỉnh sửa"
      @click="editContract"
    />
    <Button
      v-if="can('submit contracts')"
      label="Gửi phê duyệt"
      @click="submitForApproval"
    />
  </div>

  <div v-else-if="contract.status === 'PENDING_APPROVAL'">
    <Button
      v-if="can('approve contracts')"
      label="Phê duyệt"
      @click="approveContract"
    />
    <Button
      v-if="can('approve contracts')"
      label="Từ chối"
      severity="danger"
      @click="rejectContract"
    />
  </div>

  <div v-else-if="contract.status === 'ACTIVE'">
    <Button
      v-if="can('edit contracts')"
      label="Gia hạn"
      @click="renewContract"
    />
    <Button
      v-if="can('terminate contracts')"
      label="Chấm dứt"
      severity="danger"
      @click="terminateContract"
    />
  </div>
</template>
```

### 3. Selection Columns

```vue
<template>
  <DataTable :value="items" v-model:selection="selectedItems">
    <!-- Show selection column only if user can delete -->
    <Column 
      v-if="can('delete employees')"
      selectionMode="multiple" 
    />
    
    <!-- Other columns... -->
  </DataTable>

  <!-- Bulk delete button -->
  <Button
    v-if="can('delete employees') && selectedItems.length > 0"
    label="Xóa đã chọn"
    severity="danger"
    @click="bulkDelete"
  />
</template>
```

---

## Middleware Usage

### Route Protection

#### In routes/web.php

```php
use Illuminate\Support\Facades\Route;

// Single permission
Route::get('/employees', [EmployeeController::class, 'index'])
    ->middleware('permission:view employees');

// Multiple permissions with OR logic (|)
Route::get('/contracts', [ContractController::class, 'index'])
    ->middleware('permission:view contracts|edit contracts');

// Multiple permissions with AND logic (&)
Route::post('/contracts/approve', [ContractController::class, 'approve'])
    ->middleware('permission:view contracts&approve contracts');

// Group with middleware
Route::middleware(['permission:manage benefits'])->group(function () {
    Route::get('/benefits', [BenefitController::class, 'index']);
    Route::post('/benefits', [BenefitController::class, 'store']);
    Route::put('/benefits/{benefit}', [BenefitController::class, 'update']);
    Route::delete('/benefits/{benefit}', [BenefitController::class, 'destroy']);
});

// Multiple middleware
Route::get('/admin/settings', [SettingsController::class, 'index'])
    ->middleware(['auth', 'permission:manage settings']);
```

### Middleware Logic

The `PermissionMiddleware` supports:

1. **Single permission**: `permission:view employees`
2. **OR logic** (any permission): `permission:edit employees|delete employees`
3. **AND logic** (all permissions): `permission:view employees&edit employees`
4. **Super Admin bypass**: Super Admin role bypasses all permission checks

---

## Testing Guide

### 1. Test with Different Roles

#### Super Admin (Full Access)

```php
$user = User::factory()->create();
$user->assignRole('Super Admin');
$this->actingAs($user);

// Should access everything
$response = $this->get('/employees');
$response->assertStatus(200);

$response = $this->post('/employees', $data);
$response->assertStatus(302);
```

#### HR Admin (Limited Access)

```php
$user = User::factory()->create();
$role = Role::create(['name' => 'HR Admin']);
$role->givePermissionTo(['view employees', 'edit employees']);
$user->assignRole('HR Admin');
$this->actingAs($user);

// Can view
$response = $this->get('/employees');
$response->assertStatus(200);

// Can edit
$response = $this->put("/employees/{$employee->id}", $data);
$response->assertStatus(302);

// Cannot delete
$response = $this->delete("/employees/{$employee->id}");
$response->assertStatus(403);
```

#### Employee (Self-Only Access)

```php
$employee = Employee::factory()->create();
$user = User::factory()->create(['employee_id' => $employee->id]);
$user->assignRole('Employee');
$this->actingAs($user);

// Can view own profile
$response = $this->get("/employees/{$employee->id}/profile");
$response->assertStatus(200);

// Cannot view other profiles
$otherEmployee = Employee::factory()->create();
$response = $this->get("/employees/{$otherEmployee->id}/profile");
$response->assertStatus(403);
```

### 2. Frontend Testing

#### Test with Vue Testing Library

```javascript
import { mount } from '@vue/test-utils';
import { usePermissions } from '@/Composables/usePermissions';
import EmployeeIndex from '@/Pages/EmployeeIndex.vue';

// Mock permissions
vi.mock('@/Composables/usePermissions', () => ({
  usePermissions: () => ({
    can: vi.fn((permission) => {
      if (permission === 'create employees') return true;
      if (permission === 'delete employees') return false;
      return true;
    }),
    canAny: vi.fn(() => true),
  }),
}));

test('shows create button when user has permission', () => {
  const wrapper = mount(EmployeeIndex);
  
  expect(wrapper.find('[data-test="create-button"]').exists()).toBe(true);
});

test('hides delete button when user lacks permission', () => {
  const wrapper = mount(EmployeeIndex);
  
  expect(wrapper.find('[data-test="delete-button"]').exists()).toBe(false);
});
```

### 3. Manual Testing Checklist

For each role, test:

- [ ] **Navigation** - Can only see menu items for permitted modules
- [ ] **List Pages** - Can view lists if has "view" permission
- [ ] **Create Buttons** - Only visible with "create" permission
- [ ] **Edit Buttons** - Only visible with "edit" permission
- [ ] **Delete Buttons** - Only visible with "delete" permission
- [ ] **Action Columns** - Hidden if no edit/delete permissions
- [ ] **Direct URL Access** - Blocked by middleware if no permission
- [ ] **API Endpoints** - Return 403 if no permission
- [ ] **Bulk Actions** - Disabled if no permission

---

## Best Practices

### 1. Controller-Level vs Route-Level

**Use Controller Authorization for:**
- Fine-grained control
- Model-specific checks
- Custom authorization logic

```php
public function update(Request $request, Employee $employee)
{
    // Check permission + ownership
    $this->authorize('update', $employee);
}
```

**Use Route Middleware for:**
- Simple permission checks
- Group protection
- Module-level access

```php
Route::middleware('permission:view reports')->group(function () {
    // All report routes
});
```

### 2. Permission Naming Convention

- Use **lowercase** with **spaces**: `view employees`
- Use **verb + noun** pattern: `create contracts`, `approve leave requests`
- Be **specific**: Instead of `manage employees`, use `edit employees`, `delete employees`
- Use **plural nouns**: `employees`, `contracts` (not `employee`, `contract`)

### 3. Frontend Best Practices

```vue
<!-- ✅ Good: Clear permission checks -->
<Button 
  v-if="can('create employees')"
  label="Thêm mới"
/>

<!-- ❌ Bad: Hidden with CSS -->
<Button 
  :style="{ display: can('create employees') ? 'block' : 'none' }"
  label="Thêm mới"
/>

<!-- ✅ Good: Conditional column -->
<Column v-if="canAny('edit employees', 'delete employees')">

<!-- ❌ Bad: Empty column -->
<Column>
  <Button v-if="can('edit employees')" />
</Column>
```

### 4. Policy Best Practices

```php
// ✅ Good: Check permission first, then business logic
public function approve(User $user, Contract $contract): bool
{
    if (!$user->hasPermissionTo('approve contracts')) {
        return false;
    }

    return $contract->status === ContractStatus::PENDING_APPROVAL
        && $user->employee_id !== $contract->employee_id;
}

// ❌ Bad: Complex logic without permission check
public function approve(User $user, Contract $contract): bool
{
    return $user->hasRole('Admin') 
        && $contract->status === ContractStatus::PENDING_APPROVAL;
}
```

### 5. Super Admin Handling

```php
// Super Admin bypasses all checks automatically in:
// - PermissionMiddleware
// - Policies (via Gate::before in AuthServiceProvider if needed)

// Explicit check if needed:
if ($user->hasRole('Super Admin')) {
    return true;
}
```

---

## Troubleshooting

### Issue: "User does not have the right permissions"

**Cause**: User's role doesn't have the required permission.

**Solution**:
```php
// Check user's permissions
$user->getAllPermissions()->pluck('name');

// Check role's permissions
$role = Role::findByName('HR Admin');
$role->permissions->pluck('name');

// Assign missing permission
$role->givePermissionTo('view employees');
```

### Issue: "Policy not found"

**Cause**: Policy file doesn't follow naming convention.

**Solution**:
- Model: `App\Models\Employee`
- Policy: `App\Policies\EmployeePolicy`

```bash
php artisan make:policy EmployeePolicy --model=Employee
```

### Issue: Frontend permission check not working

**Cause**: Permissions not shared via Inertia.

**Check**:
```javascript
// In Vue component
import { usePage } from '@inertiajs/vue3';

const page = usePage();
console.log(page.props.auth.permissions); // Should show permissions array
```

**Solution**: Ensure `HandleInertiaRequests` middleware shares permissions:
```php
public function share(Request $request): array
{
    return [
        'auth' => [
            'user' => $request->user(),
            'permissions' => $request->user() 
                ? PermissionHelper::transformCollection($request->user()->getAllPermissions())
                : [],
        ],
    ];
}
```

### Issue: 403 error even with correct permission

**Cause**: Cache issue or permission not synced.

**Solution**:
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan permission:cache-reset

# Re-sync permissions
php artisan db:seed --class=PermissionSeeder
```

### Issue: Permission middleware not working

**Cause**: Middleware not registered.

**Solution**: Check `bootstrap/app.php`:
```php
$middleware->alias([
    'permission' => PermissionMiddleware::class,
]);
```

---

## Permission Reference

### Complete Permission List (146 permissions)

See `config/permissions.php` for the complete list organized by module:

1. **Employees** (10 permissions)
2. **Contracts** (12 permissions)
3. **Departments** (4 permissions)
4. **Positions** (4 permissions)
5. **Leave Management** (15 permissions)
6. **Insurance** (10 permissions)
7. **Benefits** (5 permissions)
8. **Payroll** (8 permissions)
9. **Performance** (12 permissions)
10. **Skills** (8 permissions)
11. **System Settings** (12 permissions)
12. **Users & Roles** (10 permissions)
13. **Reports** (8 permissions)
14. **Activities** (2 permissions)
15. **Configuration** (10 permissions)
16. **Master Data** (8 permissions)
17. **Contract Templates** (8 permissions)

---

## Additional Resources

- [Spatie Permission Documentation](https://spatie.be/docs/laravel-permission)
- [Laravel Authorization](https://laravel.com/docs/11.x/authorization)
- [Inertia.js Shared Data](https://inertiajs.com/shared-data)
- Project Files:
  - `config/permissions.php` - Permission configuration
  - `app/Helpers/PermissionHelper.php` - Helper functions
  - `resources/js/Composables/usePermissions.js` - Frontend composable
  - `app/Http/Middleware/PermissionMiddleware.php` - Route middleware
  - `ROLE_PERMISSION_IMPLEMENTATION_PLAN.md` - Implementation plan

---

## Support

For questions or issues, contact the development team or refer to the project documentation.

**Last Updated**: {{ current_date }}
**Version**: 1.0.0
