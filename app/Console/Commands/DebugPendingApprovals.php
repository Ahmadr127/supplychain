<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ApprovalStep;
use App\Models\ApprovalRequest;
use App\Models\Department;

class DebugPendingApprovals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:pending-approvals {user_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug pending approvals for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id') ?? 1;
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return;
        }

        $this->info("Debugging pending approvals for: {$user->name} ({$user->email})");
        $this->line("");

        // Check user's departments
        $this->info("1. User Departments:");
        $userDepartments = $user->departments;
        if ($userDepartments->count() > 0) {
            foreach ($userDepartments as $dept) {
                $this->line("   - {$dept->name} (ID: {$dept->id}) - Manager: " . ($dept->manager_id == $user->id ? 'YES' : 'NO'));
            }
        } else {
            $this->line("   No departments assigned");
        }

        // Check user's role
        $this->info("2. User Role:");
        if ($user->role) {
            $this->line("   - {$user->role->display_name} (ID: {$user->role->id})");
        } else {
            $this->line("   No role assigned");
        }

        // Check all pending approval steps
        $this->info("3. All Pending Approval Steps:");
        $allPendingSteps = ApprovalStep::where('status', 'pending')
            ->with(['request.requester', 'request.workflow'])
            ->get();

        if ($allPendingSteps->count() > 0) {
            foreach ($allPendingSteps as $step) {
                $this->line("   - Step {$step->step_number}: {$step->step_name}");
                $this->line("     Request: {$step->request->title} ({$step->request->request_number})");
                $this->line("     Requester: {$step->request->requester->name}");
                $this->line("     Approver Type: {$step->approver_type}");
                $this->line("     Approver ID: {$step->approver_id}");
                $this->line("     Approver Role ID: {$step->approver_role_id}");
                $this->line("     Approver Department ID: {$step->approver_department_id}");
                $this->line("     Approver Level: {$step->approver_level}");
                $this->line("");
            }
        } else {
            $this->line("   No pending approval steps found");
        }

        // Check what user can approve
        $this->info("4. Steps User Can Approve:");
        $userDepartments = $user->departments()->pluck('departments.id');
        $userRoles = $user->role ? [$user->role->id] : [];

        $canApprove = ApprovalStep::where('status', 'pending')
            ->whereHas('request', function($q) {
                $q->where('status', 'pending');
            })
            ->where(function($q) use ($userDepartments, $userRoles, $user) {
                $q->where('approver_id', $user->id)
                  ->orWhereIn('approver_role_id', $userRoles)
                  ->orWhereIn('approver_department_id', $userDepartments)
                  ->orWhereHas('approverDepartment', function($deptQuery) use ($user) {
                      $deptQuery->where('manager_id', $user->id);
                  });
            })
            ->with(['request.requester', 'request.workflow'])
            ->get();

        if ($canApprove->count() > 0) {
            foreach ($canApprove as $step) {
                $this->line("   ✓ Can approve: {$step->step_name}");
                $this->line("     Request: {$step->request->title}");
                $this->line("     Reason: " . $this->getApprovalReason($step, $user, $userDepartments, $userRoles));
                $this->line("");
            }
        } else {
            $this->line("   No steps user can approve");
        }

        // Check all approval requests
        $this->info("5. All Approval Requests:");
        $allRequests = ApprovalRequest::with(['workflow', 'requester', 'steps'])->get();
        foreach ($allRequests as $request) {
            $this->line("   - {$request->request_number}: {$request->title}");
            $this->line("     Status: {$request->status}");
            $this->line("     Current Step: {$request->current_step}/{$request->total_steps}");
            $this->line("     Steps:");
            foreach ($request->steps as $step) {
                $this->line("       - Step {$step->step_number}: {$step->step_name} ({$step->status})");
            }
            $this->line("");
        }
    }

    private function getApprovalReason($step, $user, $userDepartments, $userRoles)
    {
        if ($step->approver_id == $user->id) {
            return "Direct user assignment";
        }
        if (in_array($step->approver_role_id, $userRoles)) {
            return "User has required role";
        }
        if (in_array($step->approver_department_id, $userDepartments)) {
            return "User is in required department";
        }
        if ($step->approverDepartment && $step->approverDepartment->manager_id == $user->id) {
            return "User is manager of required department";
        }
        return "Unknown reason";
    }
}
