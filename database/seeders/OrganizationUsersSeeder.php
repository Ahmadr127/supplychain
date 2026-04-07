<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class OrganizationUsersSeeder extends Seeder
{
    /**
     * Extract first and last name only (without middle names and titles)
     * Takes text before comma, then extracts first and last word
     */
    private function extractNameWithoutTitle($fullName)
    {
        // Get text before the first comma
        $parts = explode(',', $fullName);
        $name = trim($parts[0]);
        
        // Split by spaces and get first and last word
        $words = array_filter(explode(' ', $name));
        $words = array_values($words); // Re-index array
        
        if (count($words) == 0) {
            return '';
        } elseif (count($words) == 1) {
            return $words[0];
        } else {
            // Return first and last name only
            return $words[0] . ' ' . $words[count($words) - 1];
        }
    }

    /**
     * Seed users based on organizational structure from image
     */
    public function run(): void
    {
        $this->command->info('📋 Creating Organization Users from Structure...');
        $this->command->newLine();

        // Create departments first
        $departments = $this->createDepartments();
        
        // Create users with their departments
        $this->createUsers($departments);

        $this->command->newLine();
        $this->command->info('✅ Organization users seeded successfully!');
    }

    private function createDepartments(): array
    {
        $departmentsData = [
            ['name' => 'MUTU', 'code' => 'MUTU', 'description' => 'Departemen MUTU'],
            ['name' => 'PENUNJANG MEDIK', 'code' => 'PENMED', 'description' => 'Departemen Penunjang Medik'],
            ['name' => 'SDM', 'code' => 'SDM', 'description' => 'Departemen SDM'],
            ['name' => 'DIREKTUR', 'code' => 'DIR', 'description' => 'Direktur'],
            ['name' => 'PT. ASP', 'code' => 'PTASP', 'description' => 'PT. ASP'],
            ['name' => 'PELAYANAN MEDIK', 'code' => 'PELMED', 'description' => 'Departemen Pelayanan Medik'],
            ['name' => 'KEUANGAN', 'code' => 'KEU', 'description' => 'Departemen Keuangan'],
            ['name' => 'IT', 'code' => 'IT', 'description' => 'Departemen IT'],
            ['name' => 'AKUNTANSI & PAJAK', 'code' => 'AKPAJ', 'description' => 'Departemen Akuntansi & Pajak'],
            ['name' => 'LEGAL', 'code' => 'LEGAL', 'description' => 'Departemen Legal'],
            ['name' => 'DIVISI KEPERAWATAN', 'code' => 'DIVKEP', 'description' => 'Divisi Keperawatan'],
            ['name' => 'SEKRETARIAT', 'code' => 'SEKR', 'description' => 'Sekretariat'],
            ['name' => 'UMUM', 'code' => 'UMUM', 'description' => 'Departemen Umum'],
            ['name' => 'MARKETING', 'code' => 'MARK', 'description' => 'Departemen Marketing'],
            // Additional departments for workflow
            ['name' => 'Departemen Pengadaan', 'code' => 'PGD', 'description' => 'Departemen Pengadaan/Procurement'],
            ['name' => 'Management PT', 'code' => 'MGT-PT', 'description' => 'Departemen Operasional Level PT'],
            ['name' => 'Direksi PT', 'code' => 'DIR-PT', 'description' => 'Jajaran Direksi PT'],
        ];

        $departments = [];
        foreach ($departmentsData as $data) {
            $dept = Department::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );
            $departments[$data['code']] = $dept;
            $this->command->info("  ✓ Department '{$dept->name}' created/found");
        }

        return $departments;
    }

    private function createUsers(array $departments): void
    {
        // Get or create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['display_name' => 'Administrator']);
        $managerRole = Role::firstOrCreate(['name' => 'manager'], ['display_name' => 'Manager']);
        $hospitalDirectorRole = Role::firstOrCreate(['name' => 'hospital_director'], ['display_name' => 'Hospital Director']);
        $presidentRole = Role::firstOrCreate(['name' => 'presiden_komisaris'], ['display_name' => 'Presiden Komisaris']);
        $headRole = Role::firstOrCreate(['name' => 'kepala'], ['display_name' => 'Kepala']);
        $staffRole = Role::firstOrCreate(['name' => 'staff'], ['display_name' => 'Staff']);
        $managerKeuanganRole = Role::firstOrCreate(['name' => 'manager_keuangan'], ['display_name' => 'Manager Keuangan']);
        $purchasingRole = Role::firstOrCreate(['name' => 'purchasing'], ['display_name' => 'Manager Pembelian']);
        $managerPtRole = Role::firstOrCreate(['name' => 'general_manager_pt'], ['display_name' => 'General Manager PT']);
        $direkturPtRole = Role::firstOrCreate(['name' => 'direktur_pt'], ['display_name' => 'Direktur PT']);

        $usersData = [
            // From image - organizational structure
            [
                'nik' => '20141969',
                'name' => 'DIENI ANANDA PUTRI, DR., MARS',
                'username' => 'dieni.putri',
                'email' => 'dieni.putri@azra.com',
                'department_code' => 'MUTU',
                'position' => 'MANAGER MUTU',
                'role' => $managerRole,
                'is_manager' => true,
            ],
            [
                'nik' => '20061105',
                'name' => 'GARCINIA SATIVA FIZRIA SETIADI, Dr, MKM',
                'username' => 'garcinia.setiadi',
                'email' => 'garcinia.setiadi@azra.com',
                'department_code' => 'PENMED',
                'position' => 'MANAGER PENUNJANG MEDIK',
                'role' => $managerRole,
                'is_manager' => true,
            ],
            [
                'nik' => '20253017',
                'name' => 'INDRA THALIB, B.SN., MM',
                'username' => 'indra.thalib',
                'email' => 'indra.thalib@azra.com',
                'department_code' => 'SDM',
                'position' => 'MANAGER SDM',
                'role' => $managerRole,
                'is_manager' => true,
            ],
            [
                'nik' => '20253030',
                'name' => 'IRMA RISMAYANTI, dr, MM',
                'username' => 'irma.rismayanti',
                'email' => 'irma.rismayanti@azra.com',
                'department_code' => 'DIR',
                'position' => 'DIREKTUR RS',
                'role' => $hospitalDirectorRole,
                'is_manager' => false,
            ],
            [
                'nik' => '19950015',
                'name' => 'LAILA AZRA, DRA.',
                'username' => 'laila.azra',
                'email' => 'laila.azra@azra.com',
                'department_code' => 'PTASP',
                'position' => 'KOMISARIS PT. ASP',
                'role' => $presidentRole,
                'is_manager' => false,
            ],
            [
                'nik' => '20253062',
                'name' => 'LILI MARLIANI, DR., MARS',
                'username' => 'lili.marliani',
                'email' => 'lili.marliani@azra.com',
                'department_code' => 'PELMED',
                'position' => 'MANAGER PELAYANAN MEDIK',
                'role' => $managerRole,
                'is_manager' => true,
            ],
            [
                'nik' => '20212767',
                'name' => 'METRI JULIANTI, SE',
                'username' => 'metri.julianti',
                'email' => 'metri.julianti@azra.com',
                'department_code' => 'KEU',
                'position' => 'MANAGER KEUANGAN',
                'role' => $managerKeuanganRole,
                'is_manager' => true,
            ],
            [
                'nik' => '20071107',
                'name' => 'M. RANGGA ADITYA',
                'username' => 'm.aditya',
                'email' => 'm.aditya@azra.com',
                'department_code' => 'PTASP',
                'position' => 'DIREKTUR PT. ASP',
                'role' => $direkturPtRole,
                'is_manager' => false,
            ],
            [
                'nik' => '20242964',
                'name' => 'MUHAMAD MIFTAHUDIN, M. KOM',
                'username' => 'muhamad.miftahudin',
                'email' => 'muhamad.miftahudin@azra.com',
                'department_code' => 'IT',
                'position' => 'MANAGER IT',
                'role' => $adminRole,
                'is_manager' => true,
            ],
            [
                'nik' => '20242967',
                'name' => 'RIA FAJARROHMI, SE',
                'username' => 'ria.fajarrohmi',
                'email' => 'ria.fajarrohmi@azra.com',
                'department_code' => 'AKPAJ',
                'position' => 'SUPERVISOR AKUNTING & PAJAK',
                'role' => $headRole,
                'is_manager' => false,
            ],
            [
                'nik' => '20111600',
                'name' => 'RIYADI MAULANA, SH., MH., CLA., CCD',
                'username' => 'riyadi.maulana',
                'email' => 'riyadi.maulana@azra.com',
                'department_code' => 'LEGAL',
                'position' => 'MANAGER LEGAL',
                'role' => $managerRole,
                'is_manager' => true,
            ],
            [
                'nik' => '19940189',
                'name' => 'SENI MAULIDA FITALOKA, S.Kep,Ns, M.Kep',
                'username' => 'seni.fitaloka',
                'email' => 'seni.fitaloka@azra.com',
                'department_code' => 'DIVKEP',
                'position' => 'MANAGER KEPERAWATAN',
                'role' => $managerRole,
                'is_manager' => true,
            ],
            [
                'nik' => '20020462',
                'name' => 'SITI KHOIRIAH',
                'username' => 'siti.khoiriah',
                'email' => 'siti.khoiriah@azra.com',
                'department_code' => 'SEKR',
                'position' => 'SEKRETARIS DIREKTUR PT. ASP',
                'role' => $staffRole,
                'is_manager' => false,
            ],
            [
                'nik' => '20253070',
                'name' => 'THORIO FARIED ISHAQ, S.I. KOM',
                'username' => 'thorio.ishaq',
                'email' => 'thorio.ishaq@azra.com',
                'department_code' => 'UMUM',
                'position' => 'MANAGER UMUM',
                'role' => $managerRole,
                'is_manager' => true,
            ],
            [
                'nik' => '20253008',
                'name' => 'TUMPAS BANGKIT PRAYUDA, SE',
                'username' => 'tumpas.prayuda',
                'email' => 'tumpas.prayuda@azra.com',
                'department_code' => 'MARK',
                'position' => 'MANAGER MARKETING',
                'role' => $managerRole,
                'is_manager' => true,
            ],
            [
                'nik' => '20242988',
                'name' => 'VERONIKA RINI HANDAYANI, A. MD',
                'username' => 'veronika.handayani',
                'email' => 'veronika.handayani@azra.com',
                'department_code' => 'SEKR',
                'position' => 'SEKRETARIS DIREKTUR PT. ASP',
                'role' => $staffRole,
                'is_manager' => false,
            ],
           
            [
                'nik' => '99999002',
                'name' => 'Admin System',
                'username' => 'admin',
                'email' => 'admin@azra.com',
                'department_code' => 'IT',
                'position' => 'System Administrator',
                'role' => $adminRole,
                'is_manager' => false,
            ],
        ];

        foreach ($usersData as $userData) {
            $departmentCode = $userData['department_code'];
            $position = $userData['position'];
            $isManager = $userData['is_manager'];
            $role = $userData['role'];
            
            unset($userData['department_code'], $userData['position'], $userData['is_manager'], $userData['role']);

            // Create or update user
            $user = User::where('nik', $userData['nik'])
                ->orWhere('username', $userData['username'])
                ->orWhere('email', $userData['email'])
                ->first();

            if ($user) {
                // Update existing user
                $user->update([
                    'nik' => $userData['nik'],
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'email' => $userData['email'],
                    'password' => Hash::make('rsazra'),
                    'role_id' => $role->id,
                ]);
            } else {
                // Create new user
                $user = User::create([
                    'nik' => $userData['nik'],
                    'name' => $userData['name'],
                    'username' => $userData['username'],
                    'email' => $userData['email'],
                    'password' => Hash::make('rsazra'),
                    'role_id' => $role->id,
                ]);
            }

            // Attach to department
            if (isset($departments[$departmentCode])) {
                $department = $departments[$departmentCode];
                
                // Detach existing relationships for this department
                $user->departments()->detach($department->id);
                
                // Attach with new data
                $user->departments()->attach($department->id, [
                    'position' => $position,
                    'is_primary' => true,
                    'is_manager' => $isManager,
                    'start_date' => now(),
                ]);

                // Set as department manager if applicable
                if ($isManager) {
                    $department->update(['manager_id' => $user->id]);
                }

                $this->command->info("  ✓ User '{$user->name}' created/updated in {$department->name}");
            }
        }
    }
}
