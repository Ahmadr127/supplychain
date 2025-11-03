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
            ],
            [
                'name' => 'Departemen Pengadaan',
                'code' => 'PGD',
                'description' => 'Departemen Pengadaan/Procurement',
                'level' => 1,
            ],
            [
                'name' => 'Departemen Keuangan',
                'code' => 'KEU',
                'description' => 'Departemen Keuangan dan Akuntansi',
                'level' => 1,
            ],
            [
                'name' => 'Departemen Keperawatan',
                'code' => 'KEP',
                'description' => 'Departemen Keperawatan',
                'level' => 1,
            ],
            
            // Level 2 - Direktur
            [
                'name' => 'Direktur',
                'code' => 'DIR',
                'description' => 'Direktur Rumah Sakit',
                'level' => 2,
            ],
        ];

        $createdDepartments = [];
        foreach ($departments as $deptData) {
            $dept = Department::firstOrCreate(['code' => $deptData['code']], $deptData);
            $createdDepartments[$dept->code] = $dept;
        }

        // Set parent-child relationships (all departments report to Direktur)
        $createdDepartments['IT']->update(['parent_id' => $createdDepartments['DIR']->id]);
        $createdDepartments['PGD']->update(['parent_id' => $createdDepartments['DIR']->id]);
        $createdDepartments['KEU']->update(['parent_id' => $createdDepartments['DIR']->id]);
        $createdDepartments['KEP']->update(['parent_id' => $createdDepartments['DIR']->id]);

        // Buat users
        $users = [
            // Administrator (Muhamad Miftahudin) - IT (Manager IT)
            [
                'name' => 'Muhamad Miftahudin',
                'username' => 'admin',
                'email' => 'admin@azra.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'admin')->first()->id,
                'department' => 'IT',
                'position' => 'Manager IT',
                'is_primary' => true,
                'is_manager' => true,
            ],
            // Direktur RS (dr. Irma Rismayanti, MM) - DIR
            [
                'name' => 'dr. Irma Rismayanti, MM',
                'username' => 'irma.rismayanti',
                'email' => 'irma@azra.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'direktur')->first()->id,
                'department' => 'DIR',
                'position' => 'Direktur RS',
                'is_primary' => true,
                'is_manager' => false,
            ],
            // Manager Keuangan (Ria Fajarrohmi) - KEU (Manager)
            [
                'name' => 'Ria Fajarrohmi',
                'username' => 'ria.fajarrohmi',
                'email' => 'ria@azra.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'manager_keuangan')->first()->id,
                'department' => 'KEU',
                'position' => 'Manager Keuangan',
                'is_primary' => true,
                'is_manager' => true,
            ],
            // Koordinator Pengadaan (Indah Triyani) - PGD (Koordinator)
            [
                'name' => 'Indah Triyani',
                'username' => 'indah.triyani',
                'email' => 'indah@azra.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'purchasing')->first()->id,
                'department' => 'PGD',
                'position' => 'Koordinator Pengadaan',
                'is_primary' => true,
                'is_manager' => true,
            ],
            // Manager Keperawatan (Seni Maulida) - KEP (Manager)
            [
                'name' => 'Seni Maulida',
                'username' => 'seni.maulida',
                'email' => 'seni@azra.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'manager')->first()->id,
                'department' => 'KEP',
                'position' => 'Manager Keperawatan',
                'is_primary' => true,
                'is_manager' => true,
            ],
            // Karu NS 3 (Eka Setia) - KEP (Kepala Ruang)
            [
                'name' => 'Eka Setia',
                'username' => 'eka.setia',
                'email' => 'eka@azra.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'user')->first()->id,
                'department' => 'KEP',
                'position' => 'Karu NS 3',
                'is_primary' => true,
                'is_manager' => false,
            ],
            // Pengguna SIMRS (Umar) - IT (Staff)
            [
                'name' => 'Umar',
                'username' => 'umar',
                'email' => 'umar@azra.com',
                'password' => bcrypt('password'),
                'role_id' => Role::where('name', 'user')->first()->id,
                'department' => 'IT',
                'position' => 'Staff SIMRS',
                'is_primary' => true,
                'is_manager' => false,
            ],
        ];

        foreach ($users as $userData) {
            $departmentCode = $userData['department'] ?? null;
            $position = $userData['position'] ?? null;
            $isPrimary = $userData['is_primary'] ?? false;
            $isManager = $userData['is_manager'] ?? false;
            unset($userData['department'], $userData['position'], $userData['is_primary'], $userData['is_manager']);

            $user = User::firstOrCreate(['email' => $userData['email']], $userData);

            if ($departmentCode && isset($createdDepartments[$departmentCode])) {
                $department = $createdDepartments[$departmentCode];
                // Attach user to department if not already attached
                if (!$user->departments()->wherePivot('department_id', $department->id)->exists()) {
                    $user->departments()->attach($department->id, [
                        'position' => $position,
                        'is_primary' => $isPrimary,
                        'is_manager' => $isManager,
                        'start_date' => now(),
                    ]);
                }
                // Set manager departemen bila is_manager = true
                if ($isManager && !$department->manager_id) {
                    $department->update(['manager_id' => $user->id]);
                }
            }
        }

        // Workflow creation is handled by ApprovalWorkflowSeeder
    }
}
