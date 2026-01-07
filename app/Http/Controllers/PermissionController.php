<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Helpers\PermissionHelper;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class PermissionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of permissions grouped by module.
     */
    public function index()
    {
        $this->authorize('view permissions');

        // Get all permissions with roles relationship
        $permissions = Permission::with('roles')->orderBy('name')->get();

        // Transform to include Vietnamese labels
        $permissionsWithLabels = PermissionHelper::transformCollection($permissions);

        // Group permissions by module with Vietnamese labels
        $grouped = [];
        foreach ($permissionsWithLabels as $permission) {
            $module = $permission['module'] ?? 'Other';
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $permission;
        }
        ksort($grouped);

        return Inertia::render('Permissions/Index', [
            'permissions' => $permissionsWithLabels,
            'groupedPermissions' => $grouped,
            'roles' => Role::with('permissions')->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created permission in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create permissions');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'guard_name' => 'nullable|string|max:255',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
            'label' => $validated['label'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Permission created successfully.');
    }

    /**
     * Update the specified permission in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        $this->authorize('edit permissions');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
            'guard_name' => 'nullable|string|max:255',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $permission->update([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
            'label' => $validated['label'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Permission updated successfully.');
    }

    /**
     * Remove the specified permission from storage.
     */
    public function destroy(Permission $permission)
    {
        $this->authorize('delete permissions');

        // Check if permission is assigned to any roles
        $rolesCount = $permission->roles()->count();

        if ($rolesCount > 0) {
            return redirect()->back()->with('error', "Cannot delete permission. It is assigned to {$rolesCount} role(s).");
        }

        $permission->delete();

        return redirect()->back()->with('success', 'Permission deleted successfully.');
    }

    /**
     * Get permissions for a specific role.
     */
    public function getByRole(Role $role)
    {
        $this->authorize('view permissions');

        $permissions = $role->permissions()->orderBy('name')->get();

        return response()->json([
            'role' => $role->name,
            'permissions' => $permissions,
            'groupedPermissions' => $this->groupPermissionsByModule($permissions),
        ]);
    }

    /**
     * Sync permissions for a role.
     */
    public function syncRolePermissions(Request $request, Role $role)
    {
        $this->authorize('assign permissions');

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->syncPermissions($validated['permissions']);

        return redirect()->back()->with('success', "Permissions updated for role: {$role->name}");
    }

    /**
     * Show permission management UI for a specific role.
     */
    public function manageRolePermissions(Role $role)
    {
        $this->authorize('assign permissions');

        $allPermissions = Permission::orderBy('name')->get();

        // Transform permissions with Vietnamese labels
        $permissionsWithLabels = PermissionHelper::transformCollection($allPermissions);
        $currentPermissionsWithLabels = PermissionHelper::transformCollection($role->permissions);

        // Group permissions by module with Vietnamese labels
        $groupedPermissions = [];
        foreach ($permissionsWithLabels as $permission) {
            $module = $permission['module'] ?? 'Other';
            if (!isset($groupedPermissions[$module])) {
                $groupedPermissions[$module] = [];
            }
            $groupedPermissions[$module][] = $permission;
        }
        ksort($groupedPermissions);

        return Inertia::render('Permissions/RolePermissions', [
            'role' => $role,
            'groupedPermissions' => $groupedPermissions,
            'allPermissions' => $permissionsWithLabels,
            'currentPermissions' => $currentPermissionsWithLabels,
        ]);
    }

    /**
     * Bulk delete permissions.
     */
    public function bulkDelete(Request $request)
    {
        $this->authorize('delete permissions');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:permissions,id'
        ]);

        // Check if any permissions are assigned to roles
        $assignedCount = Permission::whereIn('id', $request->ids)
            ->whereHas('roles')
            ->count();

        if ($assignedCount > 0) {
            return redirect()->back()->with('error', "Cannot delete {$assignedCount} permission(s). They are assigned to roles.");
        }

        Permission::whereIn('id', $request->ids)->delete();

        return redirect()->back()->with('success', 'Selected permissions deleted successfully.');
    }

    /**
     * Group permissions by module based on naming convention.
     *
     * Examples:
     * - "view employees" -> "Employee Management"
     * - "create contracts" -> "Contract Management"
     * - "manage system settings" -> "System Administration"
     */
    private function groupPermissionsByModule($permissions)
    {
        $groups = [];

        $moduleMap = [
            // System
            'system' => 'System Administration',
            'integration' => 'System Administration',

            // User & Auth
            'user' => 'User Management',
            'password' => 'User Management',

            // Roles & Permissions
            'role' => 'Role Management',
            'permission' => 'Permission Management',

            // Backup & Logs
            'backup' => 'Backup Management',
            'activity log' => 'Activity Logs',
            'log' => 'Activity Logs',

            // Organization
            'department' => 'Organization Structure',

            // Employee
            'employee' => 'Employee Management',
            'assignment' => 'Employee Assignment',
            'profile' => 'Employee Management',
            'transfer' => 'Employee Management',
            'terminate' => 'Employee Management',

            // Position & Master Data
            'position' => 'Position Management',
            'province' => 'Master Data',
            'ward' => 'Master Data',
            'education level' => 'Master Data',
            'school' => 'Master Data',
            'skill' => 'Skill Management',
            'skill categor' => 'Skill Management',

            // Contract
            'contract' => 'Contract Management',
            'appendix' => 'Contract Management',
            'template' => 'Contract Management',

            // Leave
            'leave' => 'Leave Management',

            // Payroll & Benefits
            'payroll' => 'Payroll & Benefits',
            'benefit' => 'Payroll & Benefits',

            // Insurance
            'insurance' => 'Insurance Management',

            // Performance
            'performance' => 'Performance Management',
            'kpi' => 'Performance Management',

            // Rewards & Discipline
            'reward' => 'Rewards & Discipline',
            'discipline' => 'Rewards & Discipline',

            // Reports
            'report' => 'Reports & Analytics',
            'export' => 'Reports & Analytics',
            'schedule' => 'Reports & Analytics',

            // Settings
            'setting' => 'Settings & Configuration',
            'notification' => 'Settings & Configuration',
            'email' => 'Settings & Configuration',

            // Backfill
            'backfill' => 'Legacy Data Import',
            'import legacy' => 'Legacy Data Import',
        ];

        foreach ($permissions as $permission) {
            $module = 'Other';

            // Find module by matching keywords
            foreach ($moduleMap as $keyword => $moduleName) {
                if (stripos($permission->name, $keyword) !== false) {
                    $module = $moduleName;
                    break;
                }
            }

            if (!isset($groups[$module])) {
                $groups[$module] = [];
            }

            $groups[$module][] = $permission;
        }

        // Sort modules alphabetically
        ksort($groups);

        return $groups;
    }
}

