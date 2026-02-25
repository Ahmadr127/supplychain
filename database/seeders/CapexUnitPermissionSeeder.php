<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class CapexUnitPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'manage_capex_unit'],
            ['display_name' => 'Kelola CapEx Unit', 'description' => 'Dapat melihat dan mengelola item CapEx untuk unit/departemen sendiri']
        );

        // Assign manage_capex_unit to manager-level roles by default
        $managerRoles = \App\Models\Role::whereIn('name', ['manager', 'manager_it', 'manager_keuangan'])->get();
        foreach ($managerRoles as $role) {
            if (!$role->permissions->contains($permission->id)) {
                $role->permissions()->attach($permission);
            }
        }

        // Ensure admin has BOTH manage_capex and manage_capex_unit
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            if (!$adminRole->permissions->contains($permission->id)) {
                $adminRole->permissions()->attach($permission);
            }

            $capexAdminPerm = Permission::where('name', 'manage_capex')->first();
            if ($capexAdminPerm && !$adminRole->permissions->contains($capexAdminPerm->id)) {
                $adminRole->permissions()->attach($capexAdminPerm);
            }
        }
    }
}
