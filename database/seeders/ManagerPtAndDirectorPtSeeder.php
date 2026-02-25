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

        // 4. Create Users
        $usersData = [
            [
                'name' => 'Budi Manager PT',
                'email' => 'manager.pt@example.com',
                'username' => 'manager.pt',
                // 'phone' => '081234567890',
                'role_key' => 'manager_pt',
                'department_key' => 'management_pt'
            ],
            [
                'name' => 'Siti Direktur PT',
                'email' => 'direktur.pt@example.com',
                'username' => 'direktur.pt',
                // 'phone' => '081234567891',
                'role_key' => 'direktur_pt',
                'department_key' => 'direksi_pt'
            ]
        ];

        foreach ($usersData as $userData) {
            $role = $roles[$userData['role_key']];
            $department = $departments[$userData['department_key']];

            $user = User::where('email', $userData['email'])->first();
            
            // if ($user && $user->trashed()) {
            //     $user->restore();
            //     $this->command->info("  ! User restored: {$userData['name']}");
            // }
            
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    // 'phone' => $userData['phone'],
                    'password' => Hash::make('password'), // Default password
                    'role_id' => $role->id,
                    'email_verified_at' => now(),
                ]
            );

            // Assign Department
            // Check if pivot exists, if not attach (assume primary and manager for simplicity in this seeder)
            if (!$user->departments()->where('department_id', $department->id)->exists()) {
                $user->departments()->attach($department->id, [
                    'is_primary' => true,
                    'is_manager' => true // They are managers of their own special departments
                ]);
            }

            // Update Department Manager ID
            $department->manager_id = $user->id;
            $department->save();

            $this->command->info("  👤 User created/updated: {$userData['name']} ({$role->display_name})");
        }

        $this->command->info('✅ Manager PT & Direktur PT seeded successfully!');
    }
}
