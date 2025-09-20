<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApprovalWorkflow;
use App\Models\Role;
use App\Models\Department;
use App\Models\User;

class TestWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:workflow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test workflow functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Workflow Functionality...');
        $this->line('');

        // Test 1: Check if all required models are available
        $this->info('1. Checking model availability...');
        
        try {
            $workflows = ApprovalWorkflow::count();
            $this->line("   ✓ ApprovalWorkflow: {$workflows} records");
        } catch (\Exception $e) {
            $this->error("   ✗ ApprovalWorkflow: " . $e->getMessage());
        }

        try {
            $roles = Role::count();
            $this->line("   ✓ Role: {$roles} records");
        } catch (\Exception $e) {
            $this->error("   ✗ Role: " . $e->getMessage());
        }

        try {
            $departments = Department::count();
            $this->line("   ✓ Department: {$departments} records");
        } catch (\Exception $e) {
            $this->error("   ✗ Department: " . $e->getMessage());
        }

        try {
            $users = User::count();
            $this->line("   ✓ User: {$users} records");
        } catch (\Exception $e) {
            $this->error("   ✗ User: " . $e->getMessage());
        }

        // Test 2: Test workflow creation
        $this->info('2. Testing workflow creation...');
        try {
            $workflow = ApprovalWorkflow::create([
                'name' => 'Test Workflow',
                'type' => 'test',
                'description' => 'Test workflow for debugging',
                'workflow_steps' => [
                    [
                        'name' => 'Test Step 1',
                        'approver_type' => 'user',
                        'approver_id' => 1
                    ],
                    [
                        'name' => 'Test Step 2',
                        'approver_type' => 'role',
                        'approver_role_id' => 1
                    ]
                ],
                'is_active' => true
            ]);
            $this->line("   ✓ Created workflow: {$workflow->name} (ID: {$workflow->id})");
            
            // Clean up
            $workflow->delete();
            $this->line("   ✓ Cleaned up test workflow");
        } catch (\Exception $e) {
            $this->error("   ✗ Workflow creation failed: " . $e->getMessage());
        }

        // Test 3: Test workflow edit data
        $this->info('3. Testing workflow edit data...');
        try {
            $workflow = ApprovalWorkflow::first();
            if ($workflow) {
                $roles = Role::all();
                $departments = Department::where('is_active', true)->get();
                $users = User::with('role')->get();
                
                $this->line("   ✓ Workflow: {$workflow->name}");
                $this->line("   ✓ Roles available: {$roles->count()}");
                $this->line("   ✓ Departments available: {$departments->count()}");
                $this->line("   ✓ Users available: {$users->count()}");
                
                if ($workflow->workflow_steps) {
                    $this->line("   ✓ Workflow steps: " . count($workflow->workflow_steps));
                }
            } else {
                $this->line("   ! No workflows found");
            }
        } catch (\Exception $e) {
            $this->error("   ✗ Workflow edit data failed: " . $e->getMessage());
        }

        $this->line('');
        $this->info('Workflow test completed!');
    }
}

