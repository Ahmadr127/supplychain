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

        // NOTE: User creation is now handled by OrganizationUsersSeeder
        // This seeder only creates departments structure
    }
}
