<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            ProvinceSeeder::class,
            WardSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            EmployeeSeeder::class,
            EmployeeAssignmentSeeder::class,
            RoleScopeSeeder::class,
            EducationLevelSeeder::class,
            SchoolSeeder::class,
            SkillSeeder::class,
            ContractTemplateSeeder::class,
            ContractAppendixTemplateSeeder::class,
        ]);
    }
}
