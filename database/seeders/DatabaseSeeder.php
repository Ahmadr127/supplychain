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
        // Call seeders in order
        $this->call([
            RolePermissionSeeder::class,
            VendorPermissionSeeder::class,
            SettingsPermissionSeeder::class,
            ManagePurchasingPermissionSeeder::class,
            DepartmentSeeder::class,
            SubmissionTypeSeeder::class,
            ProcurementTypeSeeder::class,
            MasterItemSeeder::class,
            ItemTypeCodeSeeder::class,
            WorkflowRoleUserSeeder::class,  // Create roles & users for workflow
            DynamicWorkflowSeeder::class,
            ManagerPtAndDirectorPtSeeder::class, // Helper seeder for Manager & Director PT
            //CapexSeeder::class,  // CapEx data per department
            ImportPermissionSeeder::class,
            CapexUnitPermissionSeeder::class,
            
        ]);
    }
}
