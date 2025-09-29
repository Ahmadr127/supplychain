<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\User;
use App\Models\Role;
use App\Models\ApprovalWorkflow;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // Roles are now created in RolePermissionSeeder

        // Buat struktur departemen Rumah Sakit
        $departments = [
            // Level 1 - Unit/Departemen
            [
                'name' => 'Departemen IT',
                'code' => 'IT',
                'description' => 'Departemen Teknologi Informasi',
                'level' => 1,
                'approval_level' => 1,
            ],
            [
                'name' => 'Departemen Pemasaran',
                'code' => 'PEM',
                'description' => 'Departemen Pemasaran dan Marketing',
                'level' => 1,
                'approval_level' => 1,
            ],
            [
                'name' => 'Departemen Keuangan',
                'code' => 'KEU',
                'description' => 'Departemen Keuangan dan Akuntansi',
                'level' => 1,
                'approval_level' => 1,
            ],
            
            // Level 2 - Direktur
            [
                'name' => 'Direktur',
                'code' => 'DIR',
                'description' => 'Direktur Rumah Sakit',
                'level' => 2,
                'approval_level' => 2,
            ],
        ];

        $createdDepartments = [];
        foreach ($departments as $deptData) {
            $dept = Department::firstOrCreate(['code' => $deptData['code']], $deptData);
            $createdDepartments[$dept->code] = $dept;
        }

        // Set parent-child relationships (all departments report to Direktur)
        $createdDepartments['IT']->update(['parent_id' => $createdDepartments['DIR']->id]);
        $createdDepartments['PEM']->update(['parent_id' => $createdDepartments['DIR']->id]);
        $createdDepartments['KEU']->update(['parent_id' => $createdDepartments['DIR']->id]);

        // Buat sample users untuk struktur Rumah Sakit
        $users = [
            // Administrator
            [
                'name' => 'Dr. Admin Sistem',
                'username' => 'admin',
                'email' => 'admin@rs.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'admin')->first()->id,
                'department' => 'IT',
                'position' => 'System Administrator',
                'is_primary' => true,
                'is_manager' => false,
            ],
            // Technical Expert - IT
            [
                'name' => 'Budi Santoso, S.Kom',
                'username' => 'budi.santoso',
                'email' => 'budi.santoso@rs.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'technical_expert')->first()->id,
                'department' => 'IT',
                'position' => 'Technical Expert IT',
                'is_primary' => true,
                'is_manager' => false,
            ],
            // Manager IT
            [
                'name' => 'Dr. Andi Pratama, S.Kom, M.T',
                'username' => 'andi.pratama',
                'email' => 'andi.pratama@rs.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'manager_it')->first()->id,
                'department' => 'IT',
                'position' => 'Manager IT',
                'is_primary' => true,
                'is_manager' => true,
            ],
            // Manager Peminta - Pemasaran
            [
                'name' => 'Siti Nurhaliza, S.E',
                'username' => 'siti.nurhaliza',
                'email' => 'siti.nurhaliza@rs.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'manager_peminta')->first()->id,
                'department' => 'PEM',
                'position' => 'Manager Pemasaran',
                'is_primary' => true,
                'is_manager' => true,
            ],
            // Manager Keuangan
            [
                'name' => 'Ahmad Wijaya, S.E, M.Ak',
                'username' => 'ahmad.wijaya',
                'email' => 'ahmad.wijaya@rs.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'manager_keuangan')->first()->id,
                'department' => 'KEU',
                'position' => 'Manager Keuangan',
                'is_primary' => true,
                'is_manager' => true,
            ],
            // Direktur RS
            [
                'name' => 'Dr. Prof. H. Muhammad Rizki, Sp.PD',
                'username' => 'direktur',
                'email' => 'direktur@rs.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'direktur')->first()->id,
                'department' => 'DIR',
                'position' => 'Direktur Rumah Sakit',
                'is_primary' => true,
                'is_manager' => true,
            ],
            // Additional users for testing
            [
                'name' => 'Sarah Putri, S.Kom',
                'username' => 'sarah.putri',
                'email' => 'sarah.putri@rs.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'technical_expert')->first()->id,
                'department' => 'IT',
                'position' => 'IT Support',
                'is_primary' => false,
                'is_manager' => false,
            ],
            [
                'name' => 'Rudi Hartono, S.E',
                'username' => 'rudi.hartono',
                'email' => 'rudi.hartono@rs.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'user')->first()->id,
                'department' => 'PEM',
                'position' => 'Staff Pemasaran',
                'is_primary' => true,
                'is_manager' => false,
            ],
        ];

        foreach ($users as $userData) {
            $department = $createdDepartments[$userData['department']];
            $position = $userData['position'];
            $isPrimary = $userData['is_primary'];
            $isManager = $userData['is_manager'];
            unset($userData['department'], $userData['position'], $userData['is_primary'], $userData['is_manager']);
            
            $user = User::firstOrCreate(['email' => $userData['email']], $userData);
            
            // Attach user to department if not already attached
            if (!$user->departments()->wherePivot('department_id', $department->id)->exists()) {
                $user->departments()->attach($department->id, [
                    'position' => $position,
                    'is_primary' => $isPrimary,
                    'is_manager' => $isManager,
                    'start_date' => now(),
                ]);
            }
            
            // Set as manager if needed
            if ($isManager && !$department->manager_id) {
                $department->update(['manager_id' => $user->id]);
            }
        }

        // Buat single approval workflow untuk Rumah Sakit
        $workflow = [
            'name' => 'Standard Approval Workflow',
            'type' => 'standard',
            'description' => 'Workflow standar untuk semua permintaan approval',
            'workflow_steps' => [
                [
                    'name' => 'Technical Expert',
                    'approver_type' => 'role',
                    'approver_role_id' => Role::where('name', 'technical_expert')->first()->id,
                ],
                [
                    'name' => 'Manager IT',
                    'approver_type' => 'role',
                    'approver_role_id' => Role::where('name', 'manager_it')->first()->id,
                ],
                [
                    'name' => 'Manager Keuangan',
                    'approver_type' => 'role',
                    'approver_role_id' => Role::where('name', 'manager_keuangan')->first()->id,
                ],
                [
                    'name' => 'Direktur',
                    'approver_type' => 'role',
                    'approver_role_id' => Role::where('name', 'direktur')->first()->id,
                ],
            ],
        ];

        ApprovalWorkflow::firstOrCreate(['name' => $workflow['name']], $workflow);
    }
}
