<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalRequest;
use App\Models\ApprovalStep;

class TestApprovalProcess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:approval-process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the complete approval process';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Complete Approval Process...');
        $this->line('');

        // 1. Create a test user who can approve
        $this->info('1. Creating test approver user...');
        $approver = User::firstOrCreate(['email' => 'approver@test.com'], [
            'name' => 'Test Approver',
            'username' => 'approver',
            'email' => 'approver@test.com',
            'password' => bcrypt('password'),
            'role_id' => 4, // Staff Unit role
        ]);
        $this->line("   Created approver: {$approver->name}");

        // 2. Assign approver to IT Unit (Level 1)
        $itUnit = \App\Models\Department::where('code', 'IT')->first();
        if ($itUnit && !$approver->departments()->wherePivot('department_id', $itUnit->id)->exists()) {
            $approver->departments()->attach($itUnit->id, [
                'position' => 'IT Manager',
                'is_primary' => true,
                'is_manager' => true,
                'start_date' => now(),
            ]);
            $this->line("   Assigned approver to IT Unit as manager");
        }

        // 3. Create a test approval request
        $this->info('2. Creating test approval request...');
        $workflow = ApprovalWorkflow::where('type', 'purchase')->first();
        $requester = User::where('email', 'admin@example.com')->first();
        
        if ($workflow && $requester) {
            $request = $workflow->createRequest(
                requesterId: $requester->id,
                title: 'Test Approval Process',
                description: 'Testing the complete approval process',
                data: [
                    'amount' => 5000000,
                    'items' => ['Test Item 1', 'Test Item 2']
                ]
            );
            $this->line("   Created request: {$request->request_number}");
            $this->line("   Status: {$request->status}");
            $this->line("   Current step: {$request->current_step}/{$request->total_steps}");
        }

        // 4. Check what the approver can approve
        $this->info('3. Checking what approver can approve...');
        $userDepartments = $approver->departments()->pluck('departments.id');
        $userRoles = $approver->role ? [$approver->role->id] : [];

        $canApprove = ApprovalStep::where('status', 'pending')
            ->whereHas('request', function($q) {
                $q->where('status', 'pending');
            })
            ->where(function($q) use ($userDepartments, $userRoles, $approver) {
                $q->where('approver_id', $approver->id)
                  ->orWhereIn('approver_role_id', $userRoles)
                  ->orWhereIn('approver_department_id', $userDepartments)
                  ->orWhereHas('approverDepartment', function($deptQuery) use ($approver) {
                      $deptQuery->where('manager_id', $approver->id);
                  })
                  ->orWhere(function($levelQuery) use ($userDepartments) {
                      $levelQuery->where('approver_type', 'department_level')
                                ->whereExists(function($existsQuery) use ($userDepartments) {
                                    $existsQuery->select(\DB::raw(1))
                                              ->from('departments')
                                              ->whereIn('id', $userDepartments)
                                              ->where('level', '>=', \DB::raw('approval_steps.approver_level'));
                                });
                  });
            })
            ->with(['request.requester', 'request.workflow'])
            ->get();

        if ($canApprove->count() > 0) {
            $this->line("   Approver can approve {$canApprove->count()} steps:");
            foreach ($canApprove as $step) {
                $this->line("   - Step {$step->step_number}: {$step->step_name}");
                $this->line("     Request: {$step->request->title}");
            }
        } else {
            $this->line("   No steps approver can approve");
        }

        // 5. Test approval process
        if ($canApprove->count() > 0) {
            $this->info('4. Testing approval process...');
            $firstStep = $canApprove->first();
            $request = $firstStep->request;
            
            $this->line("   Approving step: {$firstStep->step_name}");
            $success = $request->approve($approver->id, 'Test approval by command');
            
            if ($success) {
                $this->line("   ✓ Approval successful!");
                $this->line("   Request status: {$request->fresh()->status}");
                $this->line("   Current step: {$request->fresh()->current_step}/{$request->fresh()->total_steps}");
            } else {
                $this->line("   ✗ Approval failed!");
            }
        }

        $this->line('');
        $this->info('Approval process test completed!');
        $this->line('');
        $this->line('You can now test the system by:');
        $this->line('1. Login as approver@test.com / password');
        $this->line('2. Go to /pending-approvals');
        $this->line('3. Click Review on any pending request');
        $this->line('4. Approve or reject the request');
    }
}
