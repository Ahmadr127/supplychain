<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class TechnicalSupportPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'access_technical_support'
        ], [
            'display_name' => 'Access Technical Support Menu',
            'description' => 'Can access the Technical Support queue and provide specifications'
        ]);

        $adminRole = Role::find(1);
        if ($adminRole && !$adminRole->permissions->contains($permission->id)) {
            $adminRole->permissions()->attach($permission->id);
        }
    }
}

