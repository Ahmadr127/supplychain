<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class WorkflowRoleUserSeeder extends Seeder
{
    /**
     * Seed the roles and users required for dynamic workflow.
     * 
     * Required roles (from DynamicWorkflowSeeder):
     * - koordinator: Maker (requester's department)
     * - manager_unit: Approver 1 - Pemilihan ID Number CapEx
     * - hospital_director (direktur): Approver - Hospital Director
     * - manager_pt: Approver - Manager PT
     * - purchasing: Approver/Releaser - Manager Pembelian
     * - manager_keuangan: Approver - Manager Keuangan (FS) - High workflows only
     * - direktur_pt: Releaser - Direktur PT - High workflows only
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('📋 Creating Workflow Roles and Users...');
        $this->command->newLine();

        // ═══════════════════════════════════════════════════════════════════════════
        // STEP 1: CREATE ROLES
        // ═══════════════════════════════════════════════════════════════════════════
        
        $roles = $this->createRoles();
        
        // ═══════════════════════════════════════════════════════════════════════════
        // STEP 2: ASSIGN PERMISSIONS TO ROLES
        // ═══════════════════════════════════════════════════════════════════════════
        
        $this->assignPermissions($roles);
        
        // ═══════════════════════════════════════════════════════════════════════════
        // STEP 3: CREATE USERS
        // ═══════════════════════════════════════════════════════════════════════════
        
        $this->createUsers($roles);

        $this->command->newLine();
        $this->command->info('✅ Workflow roles and users seeded successfully!');
        $this->command->newLine();
        
        // Show summary table
        $this->command->table(
            ['Role', 'Username', 'Email', 'Password'],
            collect($roles)->map(function ($role, $key) {
                return [
                    $role->display_name,
                    $key,
                    "{$key}@example.com",
                    'password123'
                ];
            })->toArray()
        );
    }

    /**
     * Create all required roles for workflow
     */
    private function createRoles(): array
    {
        $rolesData = [
            'koordinator' => [
                'display_name' => 'Koordinator',
                'description' => 'Koordinator unit/departemen - Maker dalam workflow approval'
            ],
            'manager_unit' => [
                'display_name' => 'Manager Unit',
                'description' => 'Manager unit/departemen - Approver 1 untuk pemilihan ID Number CapEx'
            ],
            'hospital_director' => [
                'display_name' => 'Direktur RS',
                'description' => 'Direktur Rumah Sakit - Approver dalam workflow'
            ],
            'manager_pt' => [
                'display_name' => 'Manager PT',
                'description' => 'Manager level PT - Approver dan Releaser dalam workflow'
            ],
            'purchasing' => [
                'display_name' => 'Manager Pembelian',
                'description' => 'Manager Pembelian/Purchasing - Approver dan Releaser dalam workflow'
            ],
            'manager_keuangan' => [
                'display_name' => 'Manager Keuangan',
                'description' => 'Manager Keuangan - Approver untuk FS (Feasibility Study) pada workflow High'
            ],
            'direktur_pt' => [
                'display_name' => 'Direktur PT',
                'description' => 'Direktur PT - Releaser final pada workflow High'
            ],
        ];

        $roles = [];

        foreach ($rolesData as $name => $data) {
            $role = Role::firstOrCreate(
                ['name' => $name],
                [
                    'name' => $name,
                    'display_name' => $data['display_name'],
                    'description' => $data['description']
                ]
            );
            
            $roles[$name] = $role;
            $this->command->info("  ✓ Role '{$role->display_name}' created/found");
        }

        return $roles;
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissions(array $roles): void
    {
        // Common approval permissions
        $approvalPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_my_approvals',
            'approval',
            'manage_approvals'
        ])->pluck('id')->toArray();

        // Purchasing-related permissions
        $purchasingPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_my_approvals',
            'approval',
            'manage_approvals',
            'manage_purchasing',
            'manage_capex'
        ])->pluck('id')->toArray();

        // Finance-related permissions
        $financePermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_my_approvals',
            'approval',
            'manage_approvals',
            'view_reports'
        ])->pluck('id')->toArray();

        // Director-level permissions
        $directorPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_my_approvals',
            'approval',
            'manage_approvals',
            'view_all_approvals',
            'view_reports'
        ])->pluck('id')->toArray();

        // Assign permissions
        $permissionMapping = [
            'koordinator' => $approvalPermissions,
            'manager_unit' => $approvalPermissions,
            'hospital_director' => $directorPermissions,
            'manager_pt' => $directorPermissions,
            'purchasing' => $purchasingPermissions,
            'manager_keuangan' => $financePermissions,
            'direktur_pt' => $directorPermissions,
        ];

        foreach ($permissionMapping as $roleName => $permissionIds) {
            if (isset($roles[$roleName]) && !empty($permissionIds)) {
                $roles[$roleName]->permissions()->syncWithoutDetaching($permissionIds);
            }
        }

        $this->command->info('  ✓ Permissions assigned to roles');
    }

    /**
     * Create users for each role
     */
    private function createUsers(array $roles): void
    {
        $usersData = [
            'koordinator' => [
                'name' => 'Koordinator Demo',
                'email' => 'koordinator@example.com',
            ],
            'manager_unit' => [
                'name' => 'Manager Unit Demo',
                'email' => 'manager_unit@example.com',
            ],
            'hospital_director' => [
                'name' => 'Direktur RS Demo',
                'email' => 'hospital_director@example.com',
            ],
            'manager_pt' => [
                'name' => 'Manager PT Demo',
                'email' => 'manager_pt@example.com',
            ],
            'purchasing' => [
                'name' => 'Manager Pembelian Demo',
                'email' => 'manager_pembelian@example.com',
            ],
            'manager_keuangan' => [
                'name' => 'Manager Keuangan Demo',
                'email' => 'manager_keuangan@example.com',
            ],
            'direktur_pt' => [
                'name' => 'Direktur PT Demo',
                'email' => 'direktur_pt@example.com',
            ],
        ];

        $this->command->newLine();
        $this->command->info('👤 Creating Users...');

        foreach ($usersData as $username => $data) {
            if (!isset($roles[$username])) {
                continue;
            }

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'username' => $username,
                    'email' => $data['email'],
                    'password' => Hash::make('password'),
                    'role_id' => $roles[$username]->id,
                ]
            );

            // Update role_id if user already exists but role changed
            if ($user->role_id !== $roles[$username]->id) {
                $user->update(['role_id' => $roles[$username]->id]);
            }

            $this->command->info("  ✓ User '{$user->name}' ({$user->email}) created/found");
        }
    }
}
