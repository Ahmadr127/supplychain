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
        
        // Get roles and departments for workflow steps (matching the actual data from other seeders)
        $technicalExpertRole = Role::where('name', 'technical_expert')->first();
        $managerItRole = Role::where('name', 'manager_it')->first();
        $managerKeuanganRole = Role::where('name', 'manager_keuangan')->first();
        $direkturRole = Role::where('name', 'direktur')->first();

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
                            'name' => 'Technical Expert Review',
                            'approver_type' => 'role',
                            'approver_role_id' => $technicalExpertRole ? $technicalExpertRole->id : null,
                        ],
                        [
                            'name' => 'Manager IT Approval',
                            'approver_type' => 'role',
                            'approver_role_id' => $managerItRole ? $managerItRole->id : null,
                        ],
                        [
                            'name' => 'Manager Keuangan Approval',
                            'approver_type' => 'role',
                            'approver_role_id' => $managerKeuanganRole ? $managerKeuanganRole->id : null,
                        ],
                        [
                            'name' => 'Final Approval Direktur',
                            'approver_type' => 'role',
                            'approver_role_id' => $direkturRole ? $direkturRole->id : null,
                        ]
                    ],
                    'is_active' => true,
                    'is_specific_type' => true,
                    'item_type_id' => $medisType->id
                ]
            );
        }

        // 2. Non-Medical Items Workflow (simplified - only 2 steps like standard approval)
        if ($nonMedisType) {
            $nonMedicalWorkflow = ApprovalWorkflow::firstOrCreate(
                [
                    'name' => 'Workflow Barang Non Medis',
                    'type' => 'non_medical'
                ],
                [
                    'description' => 'Workflow sederhana untuk barang non medis dan umum (2 langkah approval)',
                    'workflow_steps' => [
                        [
                            'name' => 'Manager IT Approval',
                            'approver_type' => 'role',
                            'approver_role_id' => $managerItRole ? $managerItRole->id : null,
                        ],
                        [
                            'name' => 'Manager Keuangan Approval',
                            'approver_type' => 'role',
                            'approver_role_id' => $managerKeuanganRole ? $managerKeuanganRole->id : null,
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