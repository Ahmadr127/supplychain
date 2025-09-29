<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApprovalWorkflow;
use App\Models\ItemType;
use App\Models\Role;
use App\Models\Department;

class ApprovalWorkflowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get item types
        $medisType = ItemType::where('name', 'Medis')->first();
        $nonMedisType = ItemType::where('name', 'Non Medis')->first();
        
        // Get roles and departments for workflow steps
        $adminRole = Role::where('name', 'Admin')->first();
        $managerRole = Role::where('name', 'Manager')->first();
        $supervisorRole = Role::where('name', 'Supervisor')->first();
        
        $farmasiDept = Department::where('name', 'Farmasi')->first();
        $gudangDept = Department::where('name', 'Gudang')->first();
        $keuanganDept = Department::where('name', 'Keuangan')->first();

        // 1. Medical Items Workflow (specific for medical items)
        if ($medisType) {
            $medicalWorkflow = ApprovalWorkflow::firstOrCreate(
                [
                    'name' => 'Workflow Barang Medis',
                    'type' => 'medical'
                ],
                [
                    'description' => 'Workflow khusus untuk barang medis dan farmasi',
                    'workflow_steps' => [
                        [
                            'name' => 'Approval Farmasi',
                            'approver_type' => 'department_manager',
                            'approver_department_id' => $farmasiDept ? $farmasiDept->id : null,
                        ],
                        [
                            'name' => 'Approval Gudang Medis',
                            'approver_type' => 'department_manager',
                            'approver_department_id' => $gudangDept ? $gudangDept->id : null,
                        ],
                        [
                            'name' => 'Approval Keuangan',
                            'approver_type' => 'department_manager',
                            'approver_department_id' => $keuanganDept ? $keuanganDept->id : null,
                        ],
                        [
                            'name' => 'Final Approval Manager',
                            'approver_type' => 'role',
                            'approver_role_id' => $managerRole ? $managerRole->id : null,
                        ]
                    ],
                    'is_active' => true,
                    'is_specific_type' => true,
                    'item_type_id' => $medisType->id
                ]
            );
        }

        // 2. Non-Medical Items Workflow (specific for non-medical items)
        if ($nonMedisType) {
            $nonMedicalWorkflow = ApprovalWorkflow::firstOrCreate(
                [
                    'name' => 'Workflow Barang Non Medis',
                    'type' => 'non_medical'
                ],
                [
                    'description' => 'Workflow khusus untuk barang non medis dan umum',
                    'workflow_steps' => [
                        [
                            'name' => 'Approval Supervisor',
                            'approver_type' => 'role',
                            'approver_role_id' => $supervisorRole ? $supervisorRole->id : null,
                        ],
                        [
                            'name' => 'Approval Gudang',
                            'approver_type' => 'department_manager',
                            'approver_department_id' => $gudangDept ? $gudangDept->id : null,
                        ],
                        [
                            'name' => 'Final Approval Manager',
                            'approver_type' => 'role',
                            'approver_role_id' => $managerRole ? $managerRole->id : null,
                        ]
                    ],
                    'is_active' => true,
                    'is_specific_type' => true,
                    'item_type_id' => $nonMedisType->id
                ]
            );
        }

        $this->command->info('Approval workflows seeded successfully!');
    }
}