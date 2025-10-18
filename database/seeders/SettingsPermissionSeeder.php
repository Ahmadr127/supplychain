<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class SettingsPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permission for managing settings
        $permission = Permission::firstOrCreate(
            ['name' => 'manage_settings'],
            [
                'display_name' => 'Kelola Pengaturan',
                'description' => 'Mengelola pengaturan sistem termasuk threshold FS'
            ]
        );

        // Assign permission to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$adminRole->permissions->contains($permission->id)) {
            $adminRole->permissions()->attach($permission);
        }
    }
}
