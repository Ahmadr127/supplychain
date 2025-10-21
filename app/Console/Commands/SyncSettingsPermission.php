<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\Role;

class SyncSettingsPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:sync-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync settings permission to appropriate roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Syncing settings permission...');
        
        // Create permission for managing settings if not exists
        $permission = Permission::firstOrCreate(
            ['name' => 'manage_settings'],
            [
                'display_name' => 'Kelola Pengaturan',
                'description' => 'Mengelola pengaturan sistem termasuk threshold FS'
            ]
        );
        
        $this->info('Permission "manage_settings" created/found.');
        
        // Assign permission to admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            if (!$adminRole->permissions->contains($permission->id)) {
                $adminRole->permissions()->attach($permission);
                $this->info('Permission assigned to admin role.');
            } else {
                $this->info('Admin role already has the permission.');
            }
        } else {
            $this->warn('Admin role not found!');
        }
        
        // Also assign to manager roles who might need to configure settings
        $managerRoles = Role::whereIn('name', ['manager_it', 'manager_keuangan'])->get();
        foreach ($managerRoles as $role) {
            if (!$role->permissions->contains($permission->id)) {
                $role->permissions()->attach($permission);
                $this->info("Permission assigned to {$role->name} role.");
            } else {
                $this->info("{$role->name} role already has the permission.");
            }
        }
        
        $this->info('Settings permission sync completed!');
        
        // Show which users now have access
        $this->info("\nUsers with settings access:");
        $users = \App\Models\User::whereHas('roles.permissions', function($q) {
            $q->where('name', 'manage_settings');
        })->get();
        
        if ($users->count() > 0) {
            foreach ($users as $user) {
                $roles = $user->roles->pluck('display_name')->join(', ');
                $this->line("- {$user->name} ({$user->email}) - Roles: {$roles}");
            }
        } else {
            $this->warn('No users found with settings permission!');
        }
        
        return Command::SUCCESS;
    }
}
