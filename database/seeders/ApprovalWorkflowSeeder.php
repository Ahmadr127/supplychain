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
        
        // Get roles for workflow steps (sesuai dengan user yang ada di DepartmentSeeder)
        // Manager IT: Muhamad Miftahudin (admin role, tapi sebagai Manager IT)
        // Manager Keuangan: Ria Fajarrohmi (manager_keuangan role)
        // Direktur: dr. Irma Rismayanti, MM (direktur role)
        $managerItRole = Role::where('name', 'admin')->first(); // Miftah menggunakan admin role
        $managerKeuanganRole = Role::where('name', 'manager_keuangan')->first();
        $direkturRole = Role::where('name', 'direktur')->first();

        // 1. Workflow Barang Medis (3 langkah: Manager IT -> Manager Keuangan -> Direktur)
        if ($medisType && $managerItRole && $managerKeuanganRole && $direkturRole) {
            $medicalWorkflow = ApprovalWorkflow::firstOrCreate(
                [
                    'name' => 'Workflow Barang Medis',
                    'type' => 'medical'
                ],
                [
                    'description' => 'Workflow untuk barang medis dan farmasi (3 langkah approval)',
                    'workflow_steps' => [
                        [
                            'name' => 'Manager IT',
                            'approver_type' => 'role',
                            'approver_role_id' => $managerItRole->id,
                        ],
                        [
                            'name' => 'Manager Keuangan',
                            'approver_type' => 'role',
                            'approver_role_id' => $managerKeuanganRole->id,
                        ],
                        [
                            'name' => 'Direktur RS',
                            'approver_type' => 'role',
                            'approver_role_id' => $direkturRole->id,
                        ]
                    ],
                    'is_active' => true,
                    'is_specific_type' => true,
                    'item_type_id' => $medisType->id
                ]
            );
        }

        // 2. Workflow Barang Non Medis (2 langkah: Manager IT -> Manager Keuangan)
        if ($nonMedisType && $managerItRole && $managerKeuanganRole) {
            $nonMedicalWorkflow = ApprovalWorkflow::firstOrCreate(
                [
                    'name' => 'Workflow Barang Non Medis',
                    'type' => 'non_medical'
                ],
                [
                    'description' => 'Workflow untuk barang non medis dan umum (2 langkah approval)',
                    'workflow_steps' => [
                        [
                            'name' => 'Manager IT',
                            'approver_type' => 'role',
                            'approver_role_id' => $managerItRole->id,
                        ],
                        [
                            'name' => 'Manager Keuangan',
                            'approver_type' => 'role',
                            'approver_role_id' => $managerKeuanganRole->id,
                        ]
                    ],
                    'is_active' => true,
                    'is_specific_type' => true,
                    'item_type_id' => $nonMedisType->id
                ]
            );
        }
        
        // 3. Default/Fallback Workflow (jika ada submission type lain atau item type yang tidak terdefinisi)
        $defaultWorkflow = ApprovalWorkflow::firstOrCreate(
            [
                'name' => 'Standard Approval Workflow',
                'type' => 'standard'
            ],
            [
                'description' => 'Workflow standar untuk permintaan approval umum',
                'workflow_steps' => [
                    [
                        'name' => 'Manager IT',
                        'approver_type' => 'role',
                        'approver_role_id' => $managerItRole ? $managerItRole->id : null,
                    ],
                    [
                        'name' => 'Manager Keuangan',
                        'approver_type' => 'role',
                        'approver_role_id' => $managerKeuanganRole ? $managerKeuanganRole->id : null,
                    ]
                ],
                'is_active' => true,
                'is_specific_type' => false,
                'item_type_id' => null
            ]
        );

        $this->command->info('Approval workflows seeded successfully!');
    }
}