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
        
        // Get roles for workflow steps
        // NEW WORKFLOW STRUCTURE:
        // Step 1: Manager Unit (requester's department manager) - Input harga
        // Step 2: Keuangan (conditional, if total >= 100jt) - Upload FS
        // Step 3: Direktur RS - Final approval
        
        $managerKeuanganRole = Role::where('name', 'manager_keuangan')->first();
        $direkturRole = Role::where('name', 'direktur')->first();

        // 1. Workflow Barang Medis
        // Step 1: Manager Unit (input harga)
        // Step 2: Keuangan (upload FS, conditional)
        // Step 3: Direktur RS (final approval)
        if ($medisType && $managerKeuanganRole && $direkturRole) {
            $medicalWorkflow = ApprovalWorkflow::firstOrCreate(
                [
                    'name' => 'Workflow Barang Medis',
                    'type' => 'medical'
                ],
                [
                    'description' => 'Workflow untuk barang medis: Manager Unit → Keuangan (conditional) → Direktur RS',
                    'workflow_steps' => [
                        [
                            'name' => 'Manager',
                            'approver_type' => 'requester_department_manager',
                            'approver_role_id' => null,
                            'description' => 'Manager unit input harga dan approve'
                        ],
                        [
                            'name' => 'Keuangan',
                            'approver_type' => 'role',
                            'approver_role_id' => $managerKeuanganRole->id,
                            'description' => 'Keuangan upload FS jika total >= 100jt',
                            'is_conditional' => true,
                            'condition_type' => 'total_price',
                            'condition_value' => 100000000
                        ],
                        [
                            'name' => 'Direktur RS',
                            'approver_type' => 'role',
                            'approver_role_id' => $direkturRole->id,
                            'description' => 'Direktur RS final approval'
                        ]
                    ],
                    'is_active' => true,
                    'is_specific_type' => true,
                    'item_type_id' => $medisType->id
                ]
            );
        }

        // 2. Workflow Barang Non Medis
        // Step 1: Manager Unit (input harga)
        // Step 2: Keuangan (upload FS, conditional)
        // Step 3: Direktur RS (final approval)
        if ($nonMedisType && $managerKeuanganRole && $direkturRole) {
            $nonMedicalWorkflow = ApprovalWorkflow::firstOrCreate(
                [
                    'name' => 'Workflow Barang Non Medis',
                    'type' => 'non_medical'
                ],
                [
                    'description' => 'Workflow untuk barang non medis: Manager Unit → Keuangan (conditional) → Direktur RS',
                    'workflow_steps' => [
                        [
                            'name' => 'Manager',
                            'approver_type' => 'requester_department_manager',
                            'approver_role_id' => null,
                            'description' => 'Manager unit input harga dan approve'
                        ],
                        [
                            'name' => 'Keuangan',
                            'approver_type' => 'role',
                            'approver_role_id' => $managerKeuanganRole->id,
                            'description' => 'Keuangan upload FS jika total >= 100jt',
                            'is_conditional' => true,
                            'condition_type' => 'total_price',
                            'condition_value' => 100000000
                        ],
                        [
                            'name' => 'Direktur RS',
                            'approver_type' => 'role',
                            'approver_role_id' => $direkturRole->id,
                            'description' => 'Direktur RS final approval'
                        ]
                    ],
                    'is_active' => true,
                    'is_specific_type' => true,
                    'item_type_id' => $nonMedisType->id
                ]
            );
        }
        
        // 3. Default/Fallback Workflow
        // Step 1: Manager Unit (input harga)
        // Step 2: Keuangan (upload FS, conditional)
        // Step 3: Direktur RS (final approval)
        $defaultWorkflow = ApprovalWorkflow::firstOrCreate(
            [
                'name' => 'Standard Approval Workflow',
                'type' => 'standard'
            ],
            [
                'description' => 'Workflow standar: Manager Unit → Keuangan (conditional) → Direktur RS',
                'workflow_steps' => [
                    [
                        'name' => 'Manager',
                        'approver_type' => 'requester_department_manager',
                        'approver_role_id' => null,
                        'description' => 'Manager unit input harga dan approve'
                    ],
                    [
                        'name' => 'Keuangan',
                        'approver_type' => 'role',
                        'approver_role_id' => $managerKeuanganRole ? $managerKeuanganRole->id : null,
                        'description' => 'Keuangan upload FS jika total >= 100jt',
                        'is_conditional' => true,
                        'condition_type' => 'total_price',
                        'condition_value' => 100000000
                    ],
                    [
                        'name' => 'Direktur RS',
                        'approver_type' => 'role',
                        'approver_role_id' => $direkturRole ? $direkturRole->id : null,
                        'description' => 'Direktur RS final approval'
                    ]
                ],
                'is_active' => true,
                'is_specific_type' => false,
                'item_type_id' => null
            ]
        );

        $this->command->info('Approval workflows seeded successfully!');
        $this->command->info('New workflow structure:');
        $this->command->info('  Step 1: Manager Unit (input harga)');
        $this->command->info('  Step 2: Keuangan (upload FS, conditional if total >= 100jt)');
        $this->command->info('  Step 3: Direktur RS (final approval)');
    }
}