<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class ImportPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create manage_import permission
        $permission = Permission::firstOrCreate(
            ['name' => 'manage_import'],
            [
                'display_name' => 'Kelola Import Data',
                'description'  => 'Dapat mengupload dan mengimport data dari file Excel/CSV ke sistem',
            ]
        );

        // Assign to admin role by default
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$adminRole->permissions->contains($permission->id)) {
            $adminRole->permissions()->attach($permission);
        }

        // You can add more roles here if needed:
        // $otherRole = Role::where('name', 'your_role')->first();
        // if ($otherRole && !$otherRole->permissions->contains($permission->id)) {
        //     $otherRole->permissions()->attach($permission);
        // }

        $this->command->info('manage_import permission seeded and assigned to admin.');
    }
}
