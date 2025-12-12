<?php

namespace Database\Seeders;

use App\Models\Contract;
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
            SkillCategorySeeder::class,
            SkillSeeder::class,
            ContractDocxTemplateSeeder::class,
            ContractTemplatePlaceholderMappingSeeder::class,
            ContractAppendixTemplateSeeder::class,
            ContractAppendixTemplatePlaceholderMappingSeeder::class,
            ContractApprovalSeeder::class, // Setup HR Head cho phê duyệt hợp đồng
            LeaveTypeSeeder::class, // Leave types with colors and configurations
            ContractSeeder::class, // Test contracts for leave balance system
            ContractAppendixSeeder::class, // Test appendices for contract modifications
            //InsuranceTestDataSeeder::class, // Test data for insurance workflow
            MigrateExistingEmployeesToEmploymentSeeder::class, // Migrate existing insurance data
        ]);
    }
}
