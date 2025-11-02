<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalItemStep;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InitializeItemSteps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approval:initialize-item-steps 
                            {--request-id= : Initialize steps for specific request ID only}
                            {--force : Re-initialize steps even if they already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize approval steps for all items in approval requests';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Initializing item approval steps...');
        
        $requestId = $this->option('request-id');
        $force = $this->option('force');
        
        // Get requests to process
        $query = ApprovalRequest::with(['workflow.steps', 'items']);
        
        if ($requestId) {
            $query->where('id', $requestId);
        }
        
        $requests = $query->get();
        
        if ($requests->isEmpty()) {
            $this->warn('⚠️  No approval requests found.');
            return 0;
        }
        
        $this->info("Found {$requests->count()} approval request(s) to process.");
        
        $totalItems = 0;
        $totalSteps = 0;
        $skippedItems = 0;
        
        foreach ($requests as $request) {
            $this->line("\n📋 Processing Request #{$request->id} - {$request->request_number}");
            
            if (!$request->workflow) {
                $this->warn("  ⚠️  No workflow found for request #{$request->id}. Skipping.");
                continue;
            }
            
            $workflowSteps = $request->workflow->steps()->orderBy('step_number')->get();
            
            if ($workflowSteps->isEmpty()) {
                $this->warn("  ⚠️  No workflow steps found. Skipping.");
                continue;
            }
            
            foreach ($request->items as $item) {
                // Check if steps already exist
                $existingSteps = ApprovalItemStep::where('approval_request_id', $request->id)
                    ->where('master_item_id', $item->master_item_id)
                    ->count();
                
                if ($existingSteps > 0 && !$force) {
                    $this->line("  ⏭️  Item #{$item->id} already has steps. Skipping.");
                    $skippedItems++;
                    continue;
                }
                
                // Delete existing steps if force mode
                if ($existingSteps > 0 && $force) {
                    ApprovalItemStep::where('approval_request_id', $request->id)
                        ->where('master_item_id', $item->master_item_id)
                        ->delete();
                    $this->line("  🗑️  Deleted existing steps for item #{$item->id}");
                }
                
                // Create steps
                DB::beginTransaction();
                try {
                    foreach ($workflowSteps as $step) {
                        ApprovalItemStep::create([
                            'approval_request_id' => $request->id,
                            'master_item_id' => $item->master_item_id,
                            'step_number' => $step->step_number,
                            'step_name' => $step->step_name,
                            'approver_type' => $step->approver_type,
                            'approver_id' => $step->approver_id,
                            'approver_role_id' => $step->approver_role_id,
                            'approver_department_id' => $step->approver_department_id,
                            'status' => 'pending',
                        ]);
                        $totalSteps++;
                    }
                    
                    DB::commit();
                    $this->info("  ✅ Created {$workflowSteps->count()} steps for item #{$item->id}");
                    $totalItems++;
                    
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("  ❌ Failed to create steps for item #{$item->id}: {$e->getMessage()}");
                }
            }
        }
        
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("✅ Initialization complete!");
        $this->info("   Items processed: {$totalItems}");
        $this->info("   Items skipped: {$skippedItems}");
        $this->info("   Total steps created: {$totalSteps}");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        return 0;
    }
}
