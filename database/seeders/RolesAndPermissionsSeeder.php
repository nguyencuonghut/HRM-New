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

        // Create permissions
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',

            // Role Management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',

            // Permission Management
            'view permissions',
            'assign permissions',

            // Backup Management
            'view backups',
            'create backups',
            'restore backups',
            'delete backups',
            'configure backups',

            // Activity Log
            'view activity logs',
            'delete activity logs',

            // Department Management
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',

            // Employee
            'view employees',
            'create employees',
            'edit employees',
            'delete employees',

            // Employee Assignment
            'view employee assignments',
            'create employee assignments',
            'edit employee assignments',
            'delete employee assignments',

            // Position
            'view positions',
            'create positions',
            'edit positions',
            'delete positions',

            // Province
            'view provinces',
            'create provinces',
            'edit provinces',
            'delete provinces',

            // Ward
            'view wards',
            'create wards',
            'edit wards',
            'delete wards',

            // Education Level
            'view education levels',
            'create education levels',
            'edit education levels',
            'delete education levels',

            // School
            'view schools',
            'create schools',
            'edit schools',
            'delete schools',

            //Contract
            'view contracts',
            'create contracts',
            'edit contracts',
            'delete contracts',
            'submit contracts', // Gửi phê duyệt
            'approve contracts', // Phê duyệt
            'recall contracts', // Thu hồi

            // Contract Template
            'view contract templates',
            'create contract templates',
            'edit contract templates',
            'delete contract templates',

            // Contract Appendix Template
            'view appendix templates',
            'create appendix templates',
            'edit appendix templates',
            'delete appendix templates',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions
        $superAdmin = Role::create(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - has all permissions except Permission Management and Backup Management
        $admin = Role::create(['name' => 'Admin']);
        $excludedPermissions = [
            'view permissions',
            'assign permissions',
            'view backups',
            'create backups',
            'restore backups',
            'delete backups',
            'configure backups',
        ];
        $adminPermissions = Permission::whereNotIn('name', $excludedPermissions)->pluck('name');
        $admin->givePermissionTo($adminPermissions);

        // Director - can approve contracts at director level, manage users
        $director = Role::create(['name' => 'Director']);
        $director->givePermissionTo([
            'view users',
            'create users',
            'edit users',
            'view departments',
            'create departments',
            'edit departments',
            'view contracts',
            'approve contracts', // Director có quyền approve
            'view employees',
        ]);

        // Manager - can approve contracts at manager level, manage departments
        $manager = Role::create(['name' => 'Manager']);
        $manager->givePermissionTo([
            'view users',
            'create users',
            'edit users',
            'view departments',
            'view contracts',
            'approve contracts', // Manager có quyền approve
            'view employees',
        ]);

        // User - basic permissions
        $user = Role::create(['name' => 'User']);
        $user->givePermissionTo([
            'view activity logs',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
