<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            ['name' => 'manage_roles', 'display_name' => 'Kelola Roles', 'description' => 'Mengelola roles dan permissions'],
            ['name' => 'manage_permissions', 'display_name' => 'Kelola Permissions', 'description' => 'Mengelola permissions'],
            ['name' => 'view_dashboard', 'display_name' => 'Lihat Dashboard', 'description' => 'Melihat halaman dashboard'],
            ['name' => 'manage_users', 'display_name' => 'Kelola Users', 'description' => 'Mengelola pengguna'],
            ['name' => 'manage_departments', 'display_name' => 'Kelola Departments', 'description' => 'Mengelola departemen'],
            ['name' => 'manage_workflows', 'display_name' => 'Kelola Workflows', 'description' => 'Mengelola approval workflows'],
            ['name' => 'view_all_approvals', 'display_name' => 'Lihat Semua Approvals', 'description' => 'Melihat semua approval requests'],
            ['name' => 'view_my_approvals', 'display_name' => 'Lihat My Requests', 'description' => 'Melihat approval requests yang dibuat sendiri'],
            ['name' => 'approval', 'display_name' => 'Approval', 'description' => 'Mengakses halaman approval untuk menyetujui atau menolak requests'],
            ['name' => 'manage_approvals', 'display_name' => 'Kelola Approvals', 'description' => 'Mengelola approval requests (create, edit, delete)'],
            ['name' => 'manage_items', 'display_name' => 'Kelola Master Barang', 'description' => 'Mengelola master barang dan data pendukungnya'],
            ['name' => 'manage_suppliers', 'display_name' => 'Kelola Supplier', 'description' => 'Mengelola data vendor/supplier'],
            ['name' => 'manage_submission_types', 'display_name' => 'Kelola Jenis Pengajuan', 'description' => 'Mengelola jenis pengajuan (Barang/Jasa/Program Kerja)'],

            ['name' => 'manage_purchasing', 'display_name' => 'Kelola Purchasing', 'description' => 'Mengelola proses purchasing per item'],
            ['name' => 'manage_capex', 'display_name' => 'Kelola CapEx', 'description' => 'Mengelola CapEx ID Numbers dan budget'],
            ['name' => 'manage_settings', 'display_name' => 'Kelola Pengaturan', 'description' => 'Mengelola pengaturan aplikasi'],
            
            // New Permissions for Release and Purchasing separation
            ['name' => 'view_release_requests', 'display_name' => 'Lihat Release Requests', 'description' => 'Melihat daftar release requests'],
            ['name' => 'view_pending_release', 'display_name' => 'Lihat Pending Release', 'description' => 'Melihat daftar pending release'],
            ['name' => 'view_process_purchasing', 'display_name' => 'Lihat Process Purchasing', 'description' => 'Melihat menu process purchasing'],
            ['name' => 'process_purchasing_item', 'display_name' => 'Proses Item Purchasing', 'description' => 'Melakukan proses purchasing pada item'],
            ['name' => 'manage_vendor', 'display_name' => 'Kelola Vendor Purchasing', 'description' => 'Mengelola benchmarking dan preferred vendor pada item purchasing'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        // Create Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin'], [
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Role dengan akses penuh ke sistem'
        ]);

        $technicalExpertRole = Role::firstOrCreate(['name' => 'technical_expert'], [
            'name' => 'technical_expert',
            'display_name' => 'Technical Expert',
            'description' => 'Ahli teknis di departemen IT'
        ]);

        $managerRole = Role::firstOrCreate(['name' => 'manager'], [
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Manager departemen'
        ]);

        $managerItRole = Role::firstOrCreate(['name' => 'manager_it'], [
            'name' => 'manager_it',
            'display_name' => 'Manager IT',
            'description' => 'Manager departemen IT'
        ]);

        $managerKeuanganRole = Role::firstOrCreate(['name' => 'manager_keuangan'], [
            'name' => 'manager_keuangan',
            'display_name' => 'Manager Keuangan',
            'description' => 'Manager departemen keuangan'
        ]);

        $direkturRole = Role::firstOrCreate(['name' => 'direktur'], [
            'name' => 'direktur',
            'display_name' => 'Direktur RS',
            'description' => 'Direktur Rumah Sakit'
        ]);

        $userRole = Role::firstOrCreate(['name' => 'user'], [
            'name' => 'user',
            'display_name' => 'Pengguna',
            'description' => 'Role untuk pengguna umum'
        ]);

        $purchasingRole = Role::firstOrCreate(['name' => 'purchasing'], [
            'name' => 'purchasing',
            'display_name' => 'Purchasing',
            'description' => 'Role untuk staff purchasing'
        ]);

        // Assign permissions to roles
        $adminRole->permissions()->sync(Permission::all()); // Admin gets all permissions
        
        $technicalExpertRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_my_approvals',
                'approval',
                'manage_approvals',
                'view_dashboard',
            ])->get()
        );
        
        $managerRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_my_approvals',
                'approval',
                'manage_approvals',
                'manage_capex',
                'view_pending_release',
                'view_dashboard',
            ])->get()
        );
        
        $managerItRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_my_approvals',
                'approval',
                'manage_approvals',
                'manage_capex',
                'view_pending_release',
                'view_dashboard',
            ])->get()
        );
        
        $managerKeuanganRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_my_approvals',
                'approval',
                'manage_approvals',
                'manage_vendor',
                'manage_capex',
                'view_release_requests',
                'view_pending_release',
                'view_process_purchasing',
                'view_dashboard',
            ])->get()
        );
        
        $direkturRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_my_approvals',
                'approval',
                'manage_approvals',
                'view_dashboard',
            ])->get()
        );
        
        $userRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_my_approvals',
                'manage_approvals',
                'view_dashboard',
            ])->get()
        );
        
        // Purchasing role permissions (aligned to purchasing features)
        $purchasingRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_my_approvals',
                'approval',
                'view_dashboard',
 
                'manage_purchasing',
                'manage_capex',
                'view_pending_release',
                'view_process_purchasing',
                'process_purchasing_item',
            ])->get()
        );
    }
}