<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates roles and assigns permissions from config/permissions.php
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions from config/permissions.php
        $this->createPermissionsFromConfig();

        // Create roles and assign permissions
        $this->createRoles();

        $this->command->info('Roles and permissions created successfully from config!');
    }

    /**
     * Create all permissions from config/permissions.php
     */
    private function createPermissionsFromConfig(): void
    {
        $config = config('permissions.modules', []);
        $permissionCount = 0;

        foreach ($config as $moduleKey => $module) {
            if (!isset($module['permissions'])) {
                continue;
            }

            foreach ($module['permissions'] as $permissionName => $permissionData) {
                Permission::firstOrCreate(['name' => $permissionName]);
                $permissionCount++;
            }
        }

        $this->command->info("Created {$permissionCount} permissions from config.");
    }

    /**
     * Create roles and assign permissions
     */
    private function createRoles(): void
    {
        // ============================================================
        // 1. SUPER ADMIN - Full system access
        // ============================================================
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());
        $this->command->info('✓ Super Admin created with all permissions');

        // ============================================================
        // 2. HR ADMIN - Full HR operations
        // ============================================================
        $hrAdmin = Role::firstOrCreate(['name' => 'HR Admin']);

        $hrAdminPermissions = [
            // Employees - Full access
            'view employees', 'create employees', 'edit employees', 'delete employees',
            'import employees', 'export employees',
            'view employee profiles', 'edit employee profiles', 'terminate employees',

            // Employee Assignment - Full access
            'view employee assignments', 'create employee assignments',
            'edit employee assignments', 'delete employee assignments',

            // Contracts - Full access (all levels approval)
            'view contracts', 'create contracts', 'edit contracts', 'delete contracts',
            'submit contracts', 'approve contracts', 'reject contracts', 'recall contracts',
            'renew contracts', 'terminate contracts',

            // Departments & Positions - Full access
            'view departments', 'create departments', 'edit departments', 'delete departments',
            'view positions', 'create positions', 'edit positions', 'delete positions',

            // Leave Management - Full access (all levels approval)
            'view leave requests', 'create leave requests', 'edit leave requests', 'delete leave requests',
            'submit leave requests', 'approve leave requests', 'reject leave requests', 'cancel leave requests',
            'view leave balances', 'adjust leave balances',
            'view leave types', 'manage leave types', 'export leave reports',

            // Insurance - View only (Payroll Admin handles)
            'view insurance reports',

            // Benefits - View only (Payroll Admin handles)
            'view benefits',

            // Payroll - View only (Payroll Admin handles)
            'view payroll', 'view payroll reports',

            // Performance - Full access including approvals
            'view performance reviews', 'create performance reviews', 'edit performance reviews',
            'delete performance reviews', 'approve performance reviews',
            'view KPI data', 'manage KPI templates', 'export performance reports',

            // Rewards & Disciplines - Full access
            'view rewards', 'create rewards', 'edit rewards', 'delete rewards', 'approve rewards',
            'view disciplines', 'create disciplines', 'edit disciplines', 'delete disciplines', 'approve disciplines',

            // Skills - Full access
            'view skills', 'create skills', 'edit skills', 'delete skills',
            'view skill categories', 'create skill categories', 'edit skill categories', 'delete skill categories',

            // Contract Templates - Full access
            'view contract templates', 'create contract templates', 'edit contract templates', 'delete contract templates',
            'view appendix templates', 'create appendix templates', 'edit appendix templates', 'delete appendix templates',

            // Reports - Full HR reports access
            'view all reports', 'view employee reports', 'view department reports',
            'view contract reports', 'view leave reports', 'view performance reports',
            'export all reports',

            // Master Data - Full access
            'view provinces', 'create provinces', 'edit provinces', 'delete provinces',
            'view wards', 'create wards', 'edit wards', 'delete wards',
            'view education levels', 'create education levels', 'edit education levels', 'delete education levels',
            'view schools', 'create schools', 'edit schools', 'delete schools',

            // Activity Logs - View all
            'view activity logs',
        ];

        $hrAdmin->syncPermissions($hrAdminPermissions);
        $this->command->info('✓ HR Admin created with full HR permissions');

        // ============================================================
        // 3. PAYROLL ADMIN - Payroll, Insurance, Benefits specialist
        // ============================================================
        $payrollAdmin = Role::firstOrCreate(['name' => 'Payroll Admin']);

        $payrollAdminPermissions = [
            // Employees - View only
            'view employees', 'view employee profiles',

            // Contracts - View only
            'view contracts',

            // Leave - View only (needed for payroll calculations)
            'view leave requests', 'view leave balances',

            // Insurance - Full access
            'view insurance reports', 'create insurance reports', 'delete insurance reports',
            'approve insurance records', 'reject insurance records', 'adjust insurance records',
            'finalize insurance reports', 'export insurance reports',
            'manage insurance components',

            // Benefits - Full access
            'view benefits', 'manage benefits',

            // Payroll - Full access
            'view payroll', 'create payroll', 'edit payroll', 'delete payroll',
            'process payroll', 'approve payroll', 'export payroll',
            'view payroll reports', 'export payroll reports',

            // Reports - Payroll related only
            'view all reports', 'view employee reports',
            'export all reports',

            // Activity Logs - View own
            'view own activity logs',
        ];

        $payrollAdmin->syncPermissions($payrollAdminPermissions);
        $this->command->info('✓ Payroll Admin created with payroll permissions');

        // ============================================================
        // 4. DIRECTOR - Approval authority
        // ============================================================
        $director = Role::firstOrCreate(['name' => 'Director']);

        $directorPermissions = [
            // Employees - View only
            'view employees', 'view employee profiles',

            // Departments & Positions - View + Edit
            'view departments', 'create departments', 'edit departments',
            'view positions',

            // Contracts - View + Approve
            'view contracts', 'approve contracts',

            // Leave - View + Approve
            'view leave requests', 'approve leave requests', 'reject leave requests',
            'view leave balances',

            // Insurance - View only
            'view insurance reports',

            // Payroll - View + Approve
            'view payroll', 'approve payroll', 'view payroll reports',

            // Performance - View + Approve
            'view performance reviews', 'approve performance reviews',
            'view KPI data',

            // Rewards & Disciplines - Approve
            'view rewards', 'approve rewards',
            'view disciplines', 'approve disciplines',

            // Reports - View all
            'view all reports', 'view employee reports', 'view department reports',
            'view contract reports', 'view leave reports', 'view performance reports',

            // Activity Logs - View all
            'view activity logs',
        ];

        $director->syncPermissions($directorPermissions);
        $this->command->info('✓ Director created with approval permissions');

        // ============================================================
        // 5. DEPARTMENT MANAGER - Department-level management
        // ============================================================
        $deptManager = Role::firstOrCreate(['name' => 'Department Manager']);

        $deptManagerPermissions = [
            // Employees - View + Edit (own department only)
            'view employees', 'view employee profiles', 'edit employee profiles',

            // Employee Assignment - Manage own department
            'view employee assignments', 'create employee assignments',
            'edit employee assignments', 'delete employee assignments',

            // Departments & Positions - View only
            'view departments', 'view positions',

            // Contracts - Create + Submit for approval (department level)
            'view contracts', 'create contracts', 'submit contracts',

            // Leave - Approve (own department only)
            'view leave requests', 'create leave requests',
            'approve leave requests', 'reject leave requests',
            'view leave balances',

            // Performance - Approve reviews (own department only)
            'view performance reviews', 'create performance reviews',
            'edit performance reviews', 'approve performance reviews',
            'view KPI data', 'manage KPI templates',

            // Rewards & Disciplines - Create (pending higher approval)
            'view rewards', 'create rewards',
            'view disciplines', 'create disciplines',

            // Skills - View only
            'view skills', 'view skill categories',

            // Reports - Department & Employee reports
            'view department reports', 'view employee reports',

            // Activity Logs - View own department
            'view own activity logs',
        ];

        $deptManager->syncPermissions($deptManagerPermissions);
        $this->command->info('✓ Department Manager created with department-level permissions');
    }
}
