<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class ManagePurchasingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function() {
            // Create permission if not exists
            $perm = Permission::firstOrCreate(
                ['name' => 'manage_purchasing'],
                ['display_name' => 'Manage Purchasing', 'description' => 'Manage per-item purchasing flows']
            );

            // Try attach to a Purchasing role if exists
            $attached = false;
            $purchasingRole = Role::where('name', 'purchasing')->orWhere('display_name', 'Purchasing')->first();
            if ($purchasingRole) {
                // avoid duplicate
                if (!$purchasingRole->permissions()->where('permissions.id', $perm->id)->exists()) {
                    $purchasingRole->permissions()->attach($perm->id);
                }
                $attached = true;
            }

            // Fallback: attach to Administrator role if exists
            if (!$attached) {
                $adminRole = Role::where('name', 'admin')->orWhere('name','administrator')->orWhere('display_name', 'Administrator')->first();
                if ($adminRole && !$adminRole->permissions()->where('permissions.id', $perm->id)->exists()) {
                    $adminRole->permissions()->attach($perm->id);
                }
            }
        });
    }
}
