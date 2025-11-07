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
        // SIMPLIFIED WORKFLOW STRUCTURE:
        // Step 1: Manager Unit (requester's department manager) - Input harga & approve
        // Step 2: Direktur RS - Final approval
        
        $direkturRole = Role::where('name', 'direktur')->first();
        $managerKeuanganRole = Role::where('name', 'manager_keuangan')->first(); // For quick insert template

        // 1. Workflow Barang Medis
        // Step 1: Manager Unit (input harga & approve) - DAPAT INSERT STEP KEUANGAN
        // Step 2: Direktur RS (final approval)
        if ($medisType && $direkturRole) {
            $medicalWorkflow = ApprovalWorkflow::firstOrCreate(
                [
                    'name' => 'Workflow Barang Medis',
                    'type' => 'medical'
                ],
                [
                    'description' => 'Workflow untuk barang medis: Manager Unit → Direktur RS',
                    'workflow_steps' => [
                        [
                            'name' => 'Manager',
                            'approver_type' => 'requester_department_manager',
                            'approver_role_id' => null,
                            'description' => 'Manager unit input harga dan approve',
                            'required_action' => 'input_price', // Trigger untuk input harga
                            // Manager dapat insert step Keuangan jika diperlukan
                            'can_insert_step' => true,
                            'insert_step_template' => $managerKeuanganRole ? [
                                'name' => 'Manager Keuangan',
                                'approver_type' => 'role',
                                'approver_role_id' => $managerKeuanganRole->id,
                                'required_action' => 'verify_budget', // Trigger untuk upload FS
                                'condition_value' => 90000, // Threshold FS: 90rb (untuk testing)
                                'can_insert_step' => false
                            ] : null
                        ],
                        [
                            'name' => 'Direktur RS',
                            'approver_type' => 'role',
                            'approver_role_id' => $direkturRole->id,
                            'description' => 'Direktur RS final approval',
                            'required_action' => null, // Tidak ada action khusus
                            'can_insert_step' => false
                        ]
                    ],
                    'is_active' => true,
                    'is_specific_type' => true,
                    'item_type_id' => $medisType->id
                ]
            );
        }

        // 2. Workflow Barang Non Medis
        // Step 1: Manager Unit (input harga & approve) - DAPAT INSERT STEP KEUANGAN
        // Step 2: Direktur RS (final approval)
        if ($nonMedisType && $direkturRole) {
            $nonMedicalWorkflow = ApprovalWorkflow::firstOrCreate(
                [
                    'name' => 'Workflow Barang Non Medis',
                    'type' => 'non_medical'
                ],
                [
                    'description' => 'Workflow untuk barang non medis: Manager Unit → Direktur RS',
                    'workflow_steps' => [
                        [
                            'name' => 'Manager',
                            'approver_type' => 'requester_department_manager',
                            'approver_role_id' => null,
                            'description' => 'Manager unit input harga dan approve',
                            'required_action' => 'input_price', // Trigger untuk input harga
                            // Manager dapat insert step Keuangan jika diperlukan
                            'can_insert_step' => true,
                            'insert_step_template' => $managerKeuanganRole ? [
                                'name' => 'Manager Keuangan',
                                'approver_type' => 'role',
                                'approver_role_id' => $managerKeuanganRole->id,
                                'required_action' => 'verify_budget', // Trigger untuk upload FS
                                'condition_value' => 90000, // Threshold FS: 90rb (untuk testing)
                                'can_insert_step' => false
                            ] : null
                        ],
                        [
                            'name' => 'Direktur RS',
                            'approver_type' => 'role',
                            'approver_role_id' => $direkturRole->id,
                            'description' => 'Direktur RS final approval',
                            'required_action' => null, // Tidak ada action khusus
                            'can_insert_step' => false
                        ]
                    ],
                    'is_active' => true,
                    'is_specific_type' => true,
                    'item_type_id' => $nonMedisType->id
                ]
            );
        }
        
        // 3. Default/Fallback Workflow
        // Step 1: Manager Unit (input harga & approve) - DAPAT INSERT STEP KEUANGAN
        // Step 2: Direktur RS (final approval)
        $defaultWorkflow = ApprovalWorkflow::firstOrCreate(
            [
                'name' => 'Standard Approval Workflow',
                'type' => 'standard'
            ],
            [
                'description' => 'Workflow standar: Manager Unit → Direktur RS',
                'workflow_steps' => [
                    [
                        'name' => 'Manager',
                        'approver_type' => 'requester_department_manager',
                        'approver_role_id' => null,
                        'description' => 'Manager unit input harga dan approve',
                        'required_action' => 'input_price', // Trigger untuk input harga
                        // Manager dapat insert step Keuangan jika diperlukan
                        'can_insert_step' => true,
                        'insert_step_template' => $managerKeuanganRole ? [
                            'name' => 'Manager Keuangan',
                            'approver_type' => 'role',
                            'approver_role_id' => $managerKeuanganRole->id,
                            'required_action' => 'verify_budget', // Trigger untuk upload FS
                            'condition_value' => 90000, // Threshold FS: 90rb (untuk testing)
                            'can_insert_step' => false
                        ] : null
                    ],
                    [
                        'name' => 'Direktur RS',
                        'approver_type' => 'role',
                        'approver_role_id' => $direkturRole ? $direkturRole->id : null,
                        'description' => 'Direktur RS final approval',
                        'required_action' => null, // Tidak ada action khusus
                        'can_insert_step' => false
                    ]
                ],
                'is_active' => true,
                'is_specific_type' => false,
                'item_type_id' => null
            ]
        );

        $this->command->info('✅ Approval workflows seeded successfully!');
        $this->command->info('');
        $this->command->info('📋 Simplified workflow structure (2 steps):');
        $this->command->info('  Step 1: Manager Unit (requester dept manager) - CAN INSERT STEP');
        $this->command->info('          └─ Quick insert option: Manager Keuangan - Verifikasi Budget');
        $this->command->info('  Step 2: Direktur RS (final approval)');
        $this->command->info('');
        $this->command->info('💡 Manager dapat menambahkan step Keuangan secara dinamis jika diperlukan');
    }
}