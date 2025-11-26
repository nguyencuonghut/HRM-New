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

        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Nguyễn Thị Ngọc Lan',
            'email' => 'ns@honghafeed.com.vn',
            'password' => bcrypt('Hongha@123'),
        ]);
        $admin->assignRole('Admin');

        // Create director user
        $director = User::factory()->create([
            'name' => 'Tạ Văn Toại',
            'email' => 'gd@honghafeed.com.vn',
            'password' => bcrypt('Hongha@123'),
        ]);
        $director->assignRole('Director');

        // Create manager user
        $manager = User::factory()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => bcrypt('password'),
        ]);
        $manager->assignRole('Manager');

        // Create regular users
        foreach (range(1, 10) as $index) {
            $user = User::factory()->create();
            $user->assignRole('User');
        }

        $this->command->info('Users created and roles assigned successfully!');
    }
}

