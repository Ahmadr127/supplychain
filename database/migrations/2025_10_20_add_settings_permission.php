<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create permission for managing settings if not exists
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
        
        // Also assign to manager roles who might need to configure settings
        $managerRoles = Role::whereIn('name', ['manager_it', 'manager_keuangan'])->get();
        foreach ($managerRoles as $role) {
            if (!$role->permissions->contains($permission->id)) {
                $role->permissions()->attach($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the permission
        $permission = Permission::where('name', 'manage_settings')->first();
        if ($permission) {
            // Detach from all roles first
            $permission->roles()->detach();
            // Then delete the permission
            $permission->delete();
        }
    }
};
