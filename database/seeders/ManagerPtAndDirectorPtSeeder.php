<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;
use App\Models\Permission;

class ManagerPtAndDirectorPtSeeder extends Seeder
{
    /**
     * Seed Manager PT and Direktur PT users, roles, and departments.
     */
    public function run(): void
    {
        $this->command->info('🟢 Starting Helper Seeder: Manager PT & Direktur PT...');

        // 1. Ensure Roles Exist
        $rolesData = [
            'manager_pt' => [
                'display_name' => 'Manager PT',
                'description' => 'Manager PT - Approver dan Releaser'
            ],
            'direktur_pt' => [
                'display_name' => 'Direktur PT',
                'description' => 'Direktur PT - Final Releaser'
            ]
        ];

        $roles = [];
        foreach ($rolesData as $name => $details) {
            $roles[$name] = Role::firstOrCreate(
                ['name' => $name],
                $details
            );
            $this->command->info("  ✓ Role: {$details['display_name']}");
        }

        // 2. Assign Permissions — gunakan sync (bukan syncWithoutDetaching) agar eksplisit
        // manager_pt: hanya approval & release, TIDAK punya view_all_approvals / manage_capex
        $managerPtPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_my_approvals',
            'approval',
            'manage_approvals',
            'view_pending_release',
            'manage_capex_unit',
        ])->pluck('id');

        // direktur_pt: hanya approval & release, TIDAK punya view_all_approvals / manage_capex
        $direkturPtPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_my_approvals',
            'approval',
            'manage_approvals',
            'view_pending_release',
            'manage_capex_unit',
        ])->pluck('id');

        $roles['manager_pt']->permissions()->sync($managerPtPermissions);
        $roles['direktur_pt']->permissions()->sync($direkturPtPermissions);
        $this->command->info('  ✓ Permissions synced (explicit, no view_all_approvals / manage_capex)');

        // 3. Create Departments (if not exist)
        $deptsData = [
            'management_pt' => [
                'name' => 'Management PT',
                'code' => 'MGT-PT',
                'description' => 'Departemen Operasional Level PT',
                'manager_role' => 'manager_pt'
            ],
            'direksi_pt' => [
                'name' => 'Direksi PT',
                'code' => 'DIR-PT',
                'description' => 'Jajaran Direksi PT',
                'manager_role' => 'direktur_pt'
            ]
        ];

        $departments = [];
        foreach ($deptsData as $key => $data) {
            $dept = Department::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'description' => $data['description']
                ]
            );
            $departments[$key] = $dept;
            $this->command->info("  ✓ Department: {$data['name']}");
        }

        $this->command->info('✅ Manager PT & Direktur PT roles and departments seeded successfully!');
        $this->command->info('NOTE: User creation is now handled by OrganizationUsersSeeder');
    }
}
