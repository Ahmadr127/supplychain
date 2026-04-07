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
        $this->command->info('🟢 Starting Helper Seeder: General Manager PT & Direktur PT...');

        // 1. Ensure Roles Exist
        $rolesData = [
            'general_manager_pt' => [
                'display_name' => 'General Manager PT',
                'description' => 'General Manager PT - Approver dan Releaser'
            ],
            'direktur_pt' => [
                'display_name' => 'Direktur PT',
                'description' => 'Direktur PT - Final Releaser'
            ],
            'manager_fatp' => [
                'display_name' => 'Manager FATP',
                'description' => 'Manager FATP - Approver dan Releaser'
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
        // general_manager_pt: hanya approval & release, TIDAK punya view_all_approvals / manage_capex
        $managerPtPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_my_approvals',
            'approval',
            'view_pending_release',
            'manage_capex_unit',
        ])->pluck('id');

        // direktur_pt: hanya approval & release, TIDAK punya view_all_approvals / manage_capex
        $direkturPtPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_my_approvals',
            'approval',
            'view_pending_release',
            'manage_capex_unit',
        ])->pluck('id');

        $roles['general_manager_pt']->permissions()->sync($managerPtPermissions);
        $roles['direktur_pt']->permissions()->sync($direkturPtPermissions);
        $roles['manager_fatp']->permissions()->sync($managerPtPermissions);
        $this->command->info('  ✓ Permissions synced (explicit, no view_all_approvals / manage_capex)');

        // 3. Create Departments (if not exist)
        $deptsData = [
            'management_pt' => [
                'name' => 'General Management PT',
                'code' => 'GM-PT',
                'description' => 'Departemen Operasional Level PT',
                'manager_role' => 'general_manager_pt'
            ],
            'direksi_pt' => [
                'name' => 'Direksi PT',
                'code' => 'DIR-PT',
                'description' => 'Jajaran Direksi PT',
                'manager_role' => 'direktur_pt'
            ],
            'fatp' => [
                'name' => 'Departemen FATP',
                'code' => 'FATP',
                'description' => 'Finance Accounting & Tax',
                'manager_role' => 'manager_fatp'
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

        // 4. Create Dummy Users for Testing
        $this->createDummyUsers($roles, $departments);

        $this->command->info('✅ General Manager PT & Direktur PT roles and departments seeded successfully!');
    }

    private function createDummyUsers(array $roles, array $departments): void
    {
        $this->command->info('');
        $this->command->info('📝 Creating Dummy General Manager PT User...');

        // Dummy General Manager PT User
        $managerPtUser = User::updateOrCreate(
            ['nik' => '32010199990001'],
            [
                'username' => 'general.manager.pt',
                'name' => 'Budi General Manager PT',
                'email' => 'general.manager.pt@azra.com',
                'password' => Hash::make('rsazra'),
                'role_id' => $roles['general_manager_pt']->id,
            ]
        );

        // Attach to Management PT department
        if (isset($departments['management_pt'])) {
            $managerPtUser->departments()->syncWithoutDetaching([
                $departments['management_pt']->id => [
                    'position' => 'General Manager PT',
                    'is_primary' => true,
                    'is_manager' => false,
                    'start_date' => now(),
                ]
            ]);
        }

        $this->command->info("  ✓ Dummy User: {$managerPtUser->name} (general_manager_pt)");
        $this->command->info('');
        $this->command->info('💡 Dummy User Credentials:');
        $this->command->info('   Username: general.manager.pt');
        $this->command->info('   Password: rsazra');

        $this->command->info('');
        $this->command->info('📝 Creating Dummy Manager FATP User...');

        // Dummy Manager FATP User
        $managerFatpUser = User::updateOrCreate(
            ['nik' => '32010199990002'],
            [
                'username' => 'manager.fatp',
                'name' => 'Andi Manager FATP',
                'email' => 'manager.fatp@azra.com',
                'password' => Hash::make('rsazra'),
                'role_id' => $roles['manager_fatp']->id,
            ]
        );

        // Attach to FATP department
        if (isset($departments['fatp'])) {
            $managerFatpUser->departments()->syncWithoutDetaching([
                $departments['fatp']->id => [
                    'position' => 'Manager FATP',
                    'is_primary' => true,
                    'is_manager' => true,
                    'start_date' => now(),
                ]
            ]);
        }

        $this->command->info("  ✓ Dummy User: {$managerFatpUser->name} (manager_fatp)");
        $this->command->info('');
        $this->command->info('💡 Dummy User Credentials:');
        $this->command->info('   Username: manager.fatp');
        $this->command->info('   Password: rsazra');
    }
}
