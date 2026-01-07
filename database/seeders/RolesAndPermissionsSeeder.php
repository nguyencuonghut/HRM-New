<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions - Grouped by module for better management
        $permissions = [
            // ============================================================
            // SYSTEM ADMINISTRATION
            // ============================================================
            'manage system settings',
            'view system logs',
            'manage integrations',

            // ============================================================
            // USER MANAGEMENT
            // ============================================================
            'view users',
            'create users',
            'edit users',
            'delete users',
            'import users',
            'export users',
            'reset user passwords',

            // ============================================================
            // ROLE MANAGEMENT
            // ============================================================
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',

            // ============================================================
            // PERMISSION MANAGEMENT
            // ============================================================
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
            'assign permissions',

            // ============================================================
            // BACKUP MANAGEMENT
            // ============================================================
            'view backups',
            'create backups',
            'restore backups',
            'delete backups',
            'configure backups',

            // ============================================================
            // ACTIVITY LOG
            // ============================================================
            'view activity logs',
            'view own activity logs',
            'delete activity logs',
            'export activity logs',

            // ============================================================
            // DEPARTMENT MANAGEMENT (Organization Structure)
            // ============================================================
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',

            // ============================================================
            // EMPLOYEE MANAGEMENT
            // ============================================================
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',
            'import employees',
            'export employees',
            'view employee profiles',
            'edit employee profiles',
            'terminate employees',
            'transfer employees',

            // ============================================================
            // EMPLOYEE ASSIGNMENT
            // ============================================================
            'view employee assignments',
            'create employee assignments',
            'edit employee assignments',
            'delete employee assignments',

            // ============================================================
            // POSITION MANAGEMENT
            // ============================================================
            'view positions',
            'create positions',
            'edit positions',
            'delete positions',

            // ============================================================
            // PROVINCE MANAGEMENT
            // ============================================================
            'view provinces',
            'create provinces',
            'edit provinces',
            'delete provinces',

            // ============================================================
            // WARD MANAGEMENT
            // ============================================================
            'view wards',
            'create wards',
            'edit wards',
            'delete wards',

            // ============================================================
            // EDUCATION LEVEL MANAGEMENT
            // ============================================================
            'view education levels',
            'create education levels',
            'edit education levels',
            'delete education levels',

            // ============================================================
            // SCHOOL MANAGEMENT
            // ============================================================
            'view schools',
            'create schools',
            'edit schools',
            'delete schools',

            // ============================================================
            // SKILL MANAGEMENT
            // ============================================================
            'view skills',
            'create skills',
            'edit skills',
            'delete skills',

            // ============================================================
            // SKILL CATEGORY MANAGEMENT
            // ============================================================
            'view skill categories',
            'create skill categories',
            'edit skill categories',
            'delete skill categories',

            // ============================================================
            // CONTRACT MANAGEMENT
            // ============================================================
            'view contracts',
            'create contracts',
            'edit contracts',
            'delete contracts',
            'submit contracts',
            'approve contracts',
            'reject contracts',
            'recall contracts',
            'renew contracts',
            'terminate contracts',

            // ============================================================
            // CONTRACT TEMPLATE MANAGEMENT
            // ============================================================
            'view contract templates',
            'create contract templates',
            'edit contract templates',
            'delete contract templates',

            // ============================================================
            // CONTRACT APPENDIX MANAGEMENT
            // ============================================================
            'view appendix templates',
            'create appendix templates',
            'edit appendix templates',
            'delete appendix templates',
            'approve appendixes',
            'reject appendixes',

            // ============================================================
            // LEAVE MANAGEMENT
            // ============================================================
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

            // ============================================================
            // PAYROLL & BENEFITS MANAGEMENT
            // ============================================================
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

            // ============================================================
            // INSURANCE MANAGEMENT
            // ============================================================
            'view insurance reports',
            'create insurance reports',
            'approve insurance records',
            'reject insurance records',
            'adjust insurance records',
            'finalize insurance reports',
            'export insurance reports',
            'delete insurance reports',

            // ============================================================
            // PERFORMANCE MANAGEMENT
            // ============================================================
            'view performance reviews',
            'create performance reviews',
            'edit performance reviews',
            'delete performance reviews',
            'approve performance reviews',
            'view KPI data',
            'manage KPI templates',
            'export performance reports',

            // ============================================================
            // REWARDS & DISCIPLINE
            // ============================================================
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

            // ============================================================
            // REPORTS & ANALYTICS
            // ============================================================
            'view all reports',
            'view department reports',
            'view employee reports',
            'view contract reports',
            'view leave reports',
            'view payroll reports',
            'view performance reports',
            'export all reports',
            'export department reports',
            'schedule reports',
            'create custom reports',

            // ============================================================
            // SETTINGS & CONFIGURATION
            // ============================================================
            'view settings',
            'edit settings',
            'manage notification templates',
            'manage email templates',

            // ============================================================
            // LEGACY/BACKFILL DATA (no approval workflow)
            // ============================================================
            'import legacy data',
            'backfill employees',
            'backfill contracts',
            'backfill leave requests',
            'backfill insurance records',
            'backfill payroll records',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions
        $superAdmin = Role::create(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - has all permissions except Permission Management and Backup Management
        // Đổi tên thành HR Admin để rõ ràng hơn về nghiệp vụ
        $hrAdmin = Role::create(['name' => 'HR Admin']);
        $excludedPermissions = [
            // System admin only
            'manage system settings',
            'view system logs',
            'manage integrations',

            // Permission management (Super Admin only)
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
            'assign permissions',

            // Backup management (Super Admin only)
            'view backups',
            'create backups',
            'restore backups',
            'delete backups',
            'configure backups',
        ];
        $hrAdminPermissions = Permission::whereNotIn('name', $excludedPermissions)->pluck('name');
        $hrAdmin->givePermissionTo($hrAdminPermissions);

        // HR Staff - can manage insurance reports
        // Đổi tên thành Payroll Admin để rõ về chuyên môn lương/BHXH
        $payrollAdmin = Role::create(['name' => 'Payroll Admin']);
        $payrollAdmin->givePermissionTo([
            // View only
            'view employees',
            'view contracts',
            'view leave requests',
            'view leave balances',

            // Payroll full access
            'view payroll',
            'create payroll',
            'edit payroll',
            'delete payroll',
            'process payroll',
            'approve payroll',
            'export payroll',
            'export payroll reports',

            // Benefits management
            'view benefits',
            'manage benefits',
            'approve benefit payouts',

            // Insurance full access
            'view insurance reports',
            'create insurance reports',
            'approve insurance records',
            'reject insurance records',
            'adjust insurance records',
            'finalize insurance reports',
            'export insurance reports',
            'delete insurance reports',

            // Backfill quyền cho insurance & payroll
            'import legacy data',
            'backfill insurance records',
            'backfill payroll records',
        ]);

        // Director - can approve contracts at director level, manage users
        // Director không được quyền backfill (chỉ approval role)
        $director = Role::create(['name' => 'Director']);
        $director->givePermissionTo([
            'view users',
            'create users',
            'edit users',
            'view departments',
            'create departments',
            'edit departments',
            'view employees',
            'view employee profiles',
            'view contracts',
            'approve contracts',
            'approve appendixes',
            'reject appendixes',
            'view leave requests',
            'approve leave requests',
            'reject leave requests',
            'view payroll',
            'approve payroll',
            'view insurance reports',
            'view performance reviews',
            'approve performance reviews',
            'view rewards',
            'approve rewards',
            'view disciplines',
            'approve disciplines',
            'view all reports',
            'view department reports',
            'view employee reports',
        ]);

        // Manager - can approve contracts at manager level, manage departments
        // Đổi tên thành Department Manager để rõ ràng hơn
        // Department Manager không được quyền backfill (chỉ quản lý department hiện tại)
        $deptManager = Role::create(['name' => 'Department Manager']);
        $deptManager->givePermissionTo([
            'view users',
            'create users',
            'edit users',
            'view departments',
            'view employees',
            'create employees',
            'edit employees',
            'view employee profiles',
            'edit employee profiles',
            'transfer employees',
            'view contracts',
            'approve contracts',
            'approve appendixes',
            'reject appendixes',
            'view leave requests',
            'approve leave requests',
            'reject leave requests',
            'view leave balances',
            'view performance reviews',
            'create performance reviews',
            'edit performance reviews',
            'view KPI data',
            'view rewards',
            'create rewards',
            'view disciplines',
            'create disciplines',
            'view department reports',
            'view employee reports',
        ]);

        // Xóa role User vì không có Employee/Team Lead trong hệ thống
        // $user = Role::create(['name' => 'User']);
        // $user->givePermissionTo(['view activity logs']);

        $this->command->info('Roles and permissions created successfully!');
    }
}
