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
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin - has all permissions
        $superAdmin = Role::create(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Admin - has most permissions except some critical ones
        $admin = Role::create(['name' => 'Admin']);
        $admin->givePermissionTo([
            'view users',
            'create users',
            'edit users',
            'view roles',
            'view permissions',
            'view backups',
            'create backups',
            'view activity logs',
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',
        ]);

        // Director - can manage users and backups
        $director = Role::create(['name' => 'Director']);
        $director->givePermissionTo([
            'view users',
            'create users',
            'edit users',
            'view departments',
            'create departments',
            'edit departments',
        ]);

        // Manager - can manage users and backups
        $manager = Role::create(['name' => 'Manager']);
        $manager->givePermissionTo([
            'view users',
            'create users',
            'edit users',
            'view departments',
        ]);

        // User - basic permissions
        $user = Role::create(['name' => 'User']);
        $user->givePermissionTo([
            'view activity logs',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
