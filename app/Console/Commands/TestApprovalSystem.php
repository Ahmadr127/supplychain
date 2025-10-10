<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Department;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalRequest;
use App\Models\SubmissionType;

class TestApprovalSystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:approval-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the approval system with sample data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Approval System...');
        
        // Test 1: Check if departments exist
        $this->info('1. Checking departments...');
        $departments = Department::count();
        $this->line("   Found {$departments} departments");
        
        // Test 2: Check if users exist
        $this->info('2. Checking users...');
        $users = User::count();
        $this->line("   Found {$users} users");
        
        // Test 3: Check if workflows exist
        $this->info('3. Checking workflows...');
        $workflows = ApprovalWorkflow::count();
        $this->line("   Found {$workflows} workflows");
        
        // Test 4: Test user-department relationships
        $this->info('4. Testing user-department relationships...');
        $userWithDepartments = User::with('departments')->first();
        if ($userWithDepartments) {
            $this->line("   User '{$userWithDepartments->name}' has {$userWithDepartments->departments->count()} departments");
        }
        
        // Test 5: Test approval workflow
        $this->info('5. Testing approval workflow...');
        $workflow = ApprovalWorkflow::first();
        if ($workflow) {
            $this->line("   Workflow '{$workflow->name}' has " . count($workflow->workflow_steps) . " steps");
        }
        
        // Test 6: Create a sample approval request
        $this->info('6. Creating sample approval request...');
        $requester = User::first();
        $workflow = ApprovalWorkflow::first();
        
        if ($requester && $workflow) {
            try {
                $stype = SubmissionType::firstOrCreate(['code' => 'BRG'], ['name' => 'Barang', 'description' => 'Barang', 'is_active' => true]);
                $request = $workflow->createRequest(
                    requesterId: $requester->id,
                    submissionTypeId: $stype->id,
                    description: 'This is a test request created by the command'
                );
                
                $this->line("   Created request: {$request->request_number}");
                $this->line("   Status: {$request->status}");
                $this->line("   Current step: {$request->current_step}/{$request->total_steps}");
                
            } catch (\Exception $e) {
                $this->error("   Failed to create request: " . $e->getMessage());
            }
        }
        
        $this->info('Approval system test completed!');
    }
}