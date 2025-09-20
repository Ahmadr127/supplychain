<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;

class TestApprovalLogic extends Command
{
    protected $signature = 'test:approval-logic';
    protected $description = 'Test the approval logic system';

    public function handle()
    {
        $this->info('Testing Approval Logic System...');
        
        // Get a test user
        $user = User::first();
        if (!$user) {
            $this->error('No users found. Please create a user first.');
            return;
        }
        
        $this->info("Testing with user: {$user->name} (ID: {$user->id})");
        
        // Get user's role
        if ($user->role) {
            $this->info("User role: {$user->role->display_name} (ID: {$user->role->id})");
        } else {
            $this->warn('User has no role assigned');
        }
        
        // Get user's departments
        $departments = $user->departments;
        $this->info("User departments: " . $departments->count());
        foreach ($departments as $dept) {
            $this->info("  - {$dept->name} (Level: {$dept->level})");
        }
        
        // Test approval steps
        $this->info("\nTesting approval steps...");
        
        $steps = ApprovalStep::where('status', 'pending')->with('request')->get();
        $this->info("Found {$steps->count()} pending approval steps");
        
        foreach ($steps as $step) {
            $this->info("\nStep: {$step->step_name}");
            $this->info("  Type: {$step->approver_type}");
            $this->info("  Approver ID: {$step->approver_id}");
            $this->info("  Approver Role ID: {$step->approver_role_id}");
            $this->info("  Approver Department ID: {$step->approver_department_id}");
            $this->info("  Approver Level: {$step->approver_level}");
            
            $canApprove = $step->canApprove($user->id);
            $this->info("  Can approve: " . ($canApprove ? 'YES' : 'NO'));
            
            if ($step->request) {
                $this->info("  Request: {$step->request->title} (Status: {$step->request->status})");
            }
        }
        
        $this->info("\nTest completed!");
    }
}
