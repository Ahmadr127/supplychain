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
     * - general_manager_pt: Approver - General Manager PT
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
        $this->command->info('✅ Workflow roles and permissions seeded successfully!');
        $this->command->info('   Note: Demo users are NOT created. Use DepartmentSeeder or create users manually.');
        $this->command->newLine();
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
            'general_manager_pt' => [
                'display_name' => 'General Manager PT',
                'description' => 'General Manager PT - Approver dan Releaser dalam workflow'
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
        // Common approval permissions (removed manage_approvals)
        $approvalPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_my_approvals',
            'approval'
        ])->pluck('id')->toArray();

        // Manager permissions (Manager Unit) - includes CapEx Unit and Pending Release (removed manage_approvals)
        $managerPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_my_approvals',
            'approval',
            'manage_capex_unit',
            'view_pending_release'
        ])->pluck('id')->toArray();

        // Purchasing-related permissions (removed manage_approvals and release permission)
        $purchasingPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_my_approvals',
            'approval',
            'manage_purchasing',
            'manage_capex_unit',
            'view_pending_release'
        ])->pluck('id')->toArray();

        // Finance-related permissions (removed manage_approvals, added manage_vendor for Kelola Vendor Purchasing)
        $financePermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_my_approvals',
            'approval',
            'view_reports',
            'manage_vendor',
            'manage_capex_unit',
            'view_pending_release'
        ])->pluck('id')->toArray();

        // Director-level permissions - includes CapEx Unit and Pending Release (removed manage_approvals)
        $directorPermissions = Permission::whereIn('name', [
            'view_dashboard',
            'view_my_approvals',
            'approval',
            'view_all_approvals',
            'view_reports',
            'manage_capex_unit',
            'view_pending_release'
        ])->pluck('id')->toArray();

        // Assign permissions
        $permissionMapping = [
            'koordinator' => $approvalPermissions,
            'manager_unit' => $managerPermissions,
            'hospital_director' => $directorPermissions,
            'general_manager_pt' => $directorPermissions,
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
     * NOTE: User creation is now handled by OrganizationUsersSeeder
     */
    private function createUsers(array $roles): void
    {
        $this->command->newLine();
        $this->command->info('👤 User creation is now handled by OrganizationUsersSeeder');
    }
}
