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

        // Known purchasing steps and their required_actions
        $purchasingMapping = [
            'benchmarking vendor' => 'purchasing_receive_doc_benchmark',
            'trial vendor' => 'purchasing_trial',
            'pilih preferred vendor' => 'purchasing_preferred_vendor',
            'input po' => 'purchasing_po',
            'penerimaan (grn)' => 'purchasing_invoice_grn_done',
            'grn' => 'purchasing_invoice_grn_done',
        ];

        // 1. Force Backfill ApprovalItemStep based on step_name
        $this->info("\n1. Fixing ApprovalItemStep records based on step_name...");
        
        $steps = ApprovalItemStep::all();
        $fixedStepsCount = 0;

        foreach ($steps as $step) {
            $nameLower = strtolower(trim($step->step_name));
            $updated = false;
            $changes = [];

            if ($nameLower === 'releaser') {
                if ($step->step_phase !== 'release' || $step->step_type !== 'releaser') {
                    $changes['step_phase'] = 'release';
                    $changes['step_type'] = 'releaser';
                    $updated = true;
                }
            } elseif (array_key_exists($nameLower, $purchasingMapping) || str_contains($nameLower, 'purchasing')) {
                if ($step->step_phase !== 'purchasing' || $step->step_type !== 'purchasing') {
                    $changes['step_phase'] = 'purchasing';
                    $changes['step_type'] = 'purchasing';
                    $updated = true;
                }
                
                // Map required_action if empty or wrong
                $expectedAction = $purchasingMapping[$nameLower] ?? null;
                if ($expectedAction && $step->required_action !== $expectedAction) {
                    $changes['required_action'] = $expectedAction;
                    $updated = true;
                }
            }

            // Also check if any older steps didn't have phase at all but aren't purchasing
            if (!$updated && empty($step->step_phase)) {
                $changes['step_phase'] = 'approval';
                $changes['step_type'] = 'approver';
                $updated = true;
            }

            if ($updated) {
                $step->update($changes);
                $fixedStepsCount++;
            }
        }
        $this->info("✅ Fixed {$fixedStepsCount} step records.");

        // 1b. Legacy bug: approval-phase steps were given pending_purchase if any release
        //     existed earlier in the workflow template. Those steps never become
        //     visible to getCurrentPendingStep() (pending only). Reset to pending.
        $resetApprovalStuck = ApprovalItemStep::query()
            ->where('status', 'pending_purchase')
            ->where(function ($q) {
                $q->whereNull('step_phase')->orWhere('step_phase', 'approval');
            })
            ->update(['status' => 'pending']);
        $this->info("\n1b. Approval steps reset from pending_purchase → pending: {$resetApprovalStuck} row(s).");

        // 1c. Release steps were incorrectly initialized as pending_purchase; that status
        //     is not used by getCurrentPendingStep() and activateReleaseSteps was never
        //     wired globally — unblock releasers.
        $resetReleaseStuck = ApprovalItemStep::query()
            ->where('step_phase', 'release')
            ->where('status', 'pending_purchase')
            ->update(['status' => 'pending']);
        $this->info("\n1c. Release steps reset from pending_purchase → pending: {$resetReleaseStuck} row(s).");

        // 2. Find and fix stuck items (on progress -> in_purchasing)
        $this->info("\n2. Finding stuck items that should be in purchasing phase...");
        
        $stuckItems = ApprovalRequestItem::whereIn('status', ['on progress', 'pending'])->get();
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
