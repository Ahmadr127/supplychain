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
            ['name' => 'view_pending_approvals', 'display_name' => 'Lihat Pending Approvals', 'description' => 'Melihat approval requests yang menunggu persetujuan'],
            ['name' => 'manage_approvals', 'display_name' => 'Kelola Approvals', 'description' => 'Mengelola approval requests (create, edit, delete)'],
            ['name' => 'manage_items', 'display_name' => 'Kelola Master Barang', 'description' => 'Mengelola master barang dan data pendukungnya'],
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

        $managerPemintaRole = Role::firstOrCreate(['name' => 'manager_peminta'], [
            'name' => 'manager_peminta',
            'display_name' => 'Manager Peminta',
            'description' => 'Manager yang dapat meminta approval'
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

        // Assign permissions to roles
        $adminRole->permissions()->sync(Permission::all()); // Admin gets all permissions
        
        $technicalExpertRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_dashboard',
                'view_my_approvals',
                'view_pending_approvals',
                'manage_approvals',
                'manage_items'
            ])->get()
        );
        
        $managerPemintaRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_dashboard',
                'view_my_approvals',
                'view_pending_approvals',
                'manage_approvals',
                'manage_items'
            ])->get()
        );
        
        $managerItRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_dashboard',
                'view_my_approvals',
                'view_pending_approvals',
                'manage_approvals',
                'manage_items'
            ])->get()
        );
        
        $managerKeuanganRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_dashboard',
                'view_my_approvals',
                'view_pending_approvals',
                'manage_approvals',
                'manage_items'
            ])->get()
        );
        
        $direkturRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_dashboard',
                'view_my_approvals',
                'view_pending_approvals',
                'manage_approvals',
                'manage_items'
            ])->get()
        );
        
        $userRole->permissions()->sync(
            Permission::whereIn('name', [
                'view_dashboard',
                'view_my_approvals',
                'view_pending_approvals',
                'manage_approvals'
            ])->get()
        );
    }
}
