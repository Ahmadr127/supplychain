<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class VendorPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create or get the manage_vendor permission
        $perm = Permission::firstOrCreate(
            ['name' => 'manage_vendor'],
            [
                'display_name' => 'Kelola Vendor Purchasing',
                'description' => 'Mengelola benchmarking dan preferred vendor pada item purchasing'
            ]
        );

        // Attach to admin role by default
        $admin = Role::where('name', 'admin')->first();
        if ($admin && !$admin->permissions()->where('permissions.id', $perm->id)->exists()) {
            $admin->permissions()->attach($perm->id);
        }
    }
}
