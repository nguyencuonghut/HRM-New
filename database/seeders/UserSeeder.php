<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin user
        $superAdmin = User::factory()->create([
            'name' => 'Tony Nguyen',
            'email' => 'nguyenvancuong@honghafeed.com.vn',
            'password' => bcrypt('Hongha@123'),
        ]);
        $superAdmin->assignRole('Super Admin');

        // Create HR Admin user
        $hrAdmin = User::factory()->create([
            'name' => 'Nguyễn Thị Ngọc Lan',
            'email' => 'ns@honghafeed.com.vn',
            'password' => bcrypt('Hongha@123'),
        ]);
        $hrAdmin->assignRole('HR Admin');

        // Create director user
        $director = User::factory()->create([
            'name' => 'Tạ Văn Toại',
            'email' => 'gd@honghafeed.com.vn',
            'password' => bcrypt('Hongha@123'),
        ]);
        $director->assignRole('Director');

        // Create department manager user
        $deptManager = User::factory()->create([
            'name' => 'Department Manager User',
            'email' => 'dept-manager@example.com',
            'password' => bcrypt('password'),
        ]);
        $deptManager->assignRole('Department Manager');

        // Create payroll admin user
        $payrollAdmin = User::factory()->create([
            'name' => 'Payroll Admin User',
            'email' => 'payroll@example.com',
            'password' => bcrypt('password'),
        ]);
        $payrollAdmin->assignRole('Payroll Admin');

        $this->command->info('Users created and roles assigned successfully!');
    }
}

