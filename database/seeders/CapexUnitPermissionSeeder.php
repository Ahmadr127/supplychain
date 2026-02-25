<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class CapexUnitPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan permission manage_capex_unit ada.
        // Distribusi ke role sudah dikelola oleh RolePermissionSeeder:
        //   - admin           → manage_capex (full) + manage_capex_unit
        //   - manager         → manage_capex_unit only
        //   - manager_it      → manage_capex_unit only
        //   - manager_keuangan→ manage_capex_unit only
        //   - purchasing      → manage_capex_unit only
        Permission::firstOrCreate(
            ['name' => 'manage_capex_unit'],
            [
                'display_name' => 'Kelola CapEx Unit',
                'description'  => 'Melihat dan mengelola item CapEx untuk unit/departemen sendiri',
            ]
        );

        $this->command->info('manage_capex_unit permission ensured.');
    }
}
