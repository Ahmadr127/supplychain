<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApprovalItemStep;
use App\Models\ApprovalRequestItem;
use App\Models\PurchasingItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealWorkflowStates extends Command
{
    protected $signature = 'approval:heal-states';
    protected $description = 'Heal older workflow states by backfilling step_phase and step_type, and resolving stuck purchasing items.';

    public function handle()
    {
        $this->info("🚀 Starting workflow states healing process...");

        // Known purchasing and release steps
        $purchasingSteps = [
            'benchmarking vendor',
            'trial vendor',
            'pilih preferred vendor',
            'input po',
            'final approver', // Sering dimasukkan di area purchasing
            'penerimaan (grn)',
            'grn'
        ];

        // 1. Backfill ApprovalItemStep
        $this->info("\n1. Fixing ApprovalItemStep records without step_phase...");
        
        $stepsToFix = ApprovalItemStep::whereNull('step_phase')->orWhereNull('step_type')->get();
        $fixedStepsCount = 0;

        foreach ($stepsToFix as $step) {
            $nameLower = strtolower(trim($step->step_name));
            
            // Default ke approval
            $newPhase = $step->step_phase ?? 'approval';
            $newType = $step->step_type ?? 'approver';

            if ($nameLower === 'releaser') {
                $newPhase = 'release';
                $newType = 'releaser';
            } elseif (in_array($nameLower, $purchasingSteps) || str_contains($nameLower, 'purchasing') || str_contains($nameLower, 'vendor')) {
                $newPhase = 'purchasing';
                $newType = 'purchasing';
            }

            $step->update([
                'step_phase' => $newPhase,
                'step_type' => $newType,
            ]);

            $fixedStepsCount++;
        }
        $this->info("✅ Fixed {$fixedStepsCount} step records.");

        // 2. Find and fix stuck items (on progress -> in_purchasing)
        $this->info("\n2. Finding stuck items that should be in purchasing phase...");
        
        $stuckItems = ApprovalRequestItem::where('status', 'on progress')->get();
        $fixedItemsCount = 0;

        foreach ($stuckItems as $item) {
            $currentStep = $item->getCurrentPendingStep();
            
            if ($currentStep && $currentStep->step_phase === 'purchasing') {
                DB::beginTransaction();
                try {
                    // Update item status to trigger purchasing readiness
                    $item->update(['status' => 'in_purchasing']);
                    
                    // Create purchasing item if not exists
                    PurchasingItem::firstOrCreate(
                        [
                            'approval_request_id' => $item->approval_request_id,
                            'master_item_id' => $item->master_item_id,
                        ],
                        [
                            'quantity' => $item->quantity,
                            'status' => 'unprocessed',
                        ]
                    );

                    // Re-aggregate request status
                    if ($item->approvalRequest) {
                        $item->approvalRequest->refreshStatus();
                    }

                    DB::commit();
                    $fixedItemsCount++;
                    $reqNumber = optional($item->approvalRequest)->request_number ?? 'Unknown';
                    $this->line("   - Fixed Item ID {$item->id} (Request: {$reqNumber}) - Status updated to in_purchasing");
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("   ❌ Failed to fix Item ID {$item->id}: " . $e->getMessage());
                }
            }
        }
        $this->info("✅ Fixed {$fixedItemsCount} stuck items.");
        
        $this->newLine();
        $this->info("🎉 Healing process completed successfully!");
        return Command::SUCCESS;
    }
}
