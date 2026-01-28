<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class ExtendedRoleSeeder extends Seeder
{
    /**
     * Seed additional roles required for the new workflow system.
     * Based on the workflow diagram:
     * - Maker: Koordinator, Kepala, Supervisor, Manager
     * - Approvers: Manager Unit, Hospital Director, Manager Keuangan, Manager PT, Manager Pembelian
     * - Procurement: SPH
     * - Releasers: Manager Pembelian, Manager PT, Direktur PT
     */
    public function run(): void
    {
        // Get approval permission
        $approvalPerm = Permission::where('name', 'approval')->first();
        $viewMyApprovals = Permission::where('name', 'view_my_approvals')->first();
        $manageApprovals = Permission::where('name', 'manage_approvals')->first();
        $viewReports = Permission::where('name', 'view_reports')->first();
        $managePurchasing = Permission::where('name', 'manage_purchasing')->first();

        $newRoles = [
            // Maker roles
            [
                'name' => 'koordinator',
                'display_name' => 'Koordinator',
                'description' => 'Koordinator unit - dapat membuat request dan menjadi Maker',
                'permissions' => [$viewMyApprovals, $manageApprovals],
            ],
            [
                'name' => 'kepala',
                'display_name' => 'Kepala',
                'description' => 'Kepala unit/bagian - dapat membuat request dan menjadi Maker',
                'permissions' => [$viewMyApprovals, $manageApprovals],
            ],
            [
                'name' => 'supervisor',
                'display_name' => 'Supervisor',
                'description' => 'Supervisor - dapat membuat request dan menjadi Maker',
                'permissions' => [$viewMyApprovals, $manageApprovals],
            ],
            
            // Approver roles
            [
                'name' => 'manager_unit',
                'display_name' => 'Manager Unit',
                'description' => 'Manager Unit - Approver 1, bertanggung jawab memilih CapEx ID Number',
                'permissions' => [$viewMyApprovals, $manageApprovals, $approvalPerm],
            ],
            [
                'name' => 'hospital_director',
                'display_name' => 'Hospital Director',
                'description' => 'Direktur Rumah Sakit - Approver level senior',
                'permissions' => [$viewMyApprovals, $manageApprovals, $approvalPerm, $viewReports],
            ],
            [
                'name' => 'manager_pt',
                'display_name' => 'Manager PT',
                'description' => 'Manager PT - Approver dan Releaser',
                'permissions' => [$viewMyApprovals, $manageApprovals, $approvalPerm, $viewReports],
            ],
            [
                'name' => 'purchasing',
                'display_name' => 'Manager Pembelian',
                'description' => 'Manager Pembelian/Purchasing - Approver dan Releaser',
                'permissions' => [$viewMyApprovals, $manageApprovals, $approvalPerm, $managePurchasing, $viewReports],
            ],
            [
                'name' => 'direktur_pt',
                'display_name' => 'Direktur PT',
                'description' => 'Direktur PT - Final Releaser untuk nominal tinggi (> 50 Juta)',
                'permissions' => [$viewMyApprovals, $manageApprovals, $approvalPerm, $viewReports],
            ],
            
            // Note: SPH role is part of Purchasing flow, not approval workflow
            // SPH users should use the existing 'purchasing' role
        ];

        foreach ($newRoles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);
            
            $role = Role::updateOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
            
            // Attach permissions if they exist
            $permIds = collect($permissions)->filter()->pluck('id');
            if ($permIds->isNotEmpty()) {
                $role->permissions()->syncWithoutDetaching($permIds);
            }
            
            $this->command->info("✅ Role created/updated: {$role->display_name}");
        }

        $this->command->info('');
        $this->command->info('📋 Extended roles seeded successfully!');
        $this->command->info('   - Maker roles: koordinator, kepala, supervisor');
        $this->command->info('   - Approver roles: manager_unit, hospital_director, manager_pt, manager_pembelian');
        $this->command->info('   - Releaser roles: manager_pembelian, manager_pt, direktur_pt');
        $this->command->info('');
        $this->command->info('   Note: SPH/Procurement uses existing "purchasing" role');
    }
}
