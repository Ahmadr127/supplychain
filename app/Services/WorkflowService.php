<?php

namespace App\Services;

use App\Models\ApprovalRequestItem;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalItemStep;
use App\Models\ProcurementType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    /**
     * Re-evaluate workflow steps for an item based on its updated price.
     * This is called when the Manager (Approver 1/2) updates the price.
     */
    public function reevaluateWorkflow(ApprovalRequestItem $item): void
    {
        Log::info('🔄 Re-evaluating workflow for item', ['item_id' => $item->id, 'total_price' => $item->total_price]);

        $request = $item->approvalRequest;
        if (!$request) return;

        $procurementType = ProcurementType::find($request->procurement_type_id);
        if (!$procurementType) {
            Log::warning('⚠️ Procurement type not found', ['procurement_type_id' => $request->procurement_type_id]);
            return;
        }

        $totalPrice = $item->total_price;
        $nominalRange = $this->getNominalRange($totalPrice);

        Log::info('📊 Determined nominal range', ['range' => $nominalRange, 'procurement_type' => $procurementType->code]);

        // Find the matching workflow
        $targetWorkflow = ApprovalWorkflow::where('procurement_type_id', $procurementType->id)
            ->where('nominal_range', $nominalRange)
            ->where('is_active', true)
            ->first();

        if (!$targetWorkflow) {
            Log::warning('⚠️ No matching workflow found for re-evaluation');
            return;
        }

        Log::info('🎯 Target workflow found', ['workflow_id' => $targetWorkflow->id, 'name' => $targetWorkflow->name]);

        // Check if workflow needs to change
        if ($request->workflow_id !== $targetWorkflow->id) {
            $oldWorkflowId = $request->workflow_id;
            $request->update(['workflow_id' => $targetWorkflow->id]);
            Log::info('✅ Updated request workflow_id', ['old' => $oldWorkflowId, 'new' => $targetWorkflow->id]);

            // WORKFLOW SWITCH LOGIC
            // IMPORTANT:
            // When switching from the default initial workflow, the Manager/Requester-Dept-Manager step
            // (price input/verification) that just got approved must become STEP 1 in the new workflow,
            // not archived to step 0 and not duplicated. This preserves history and prevents re-input.
            
            $lastApprovedStep = ApprovalItemStep::where('approval_request_id', $request->id)
                ->where('master_item_id', $item->master_item_id)
                ->where('status', 'approved')
                ->orderBy('step_number', 'desc')
                ->first();

            // To be safe, do the step regeneration atomically.
            DB::transaction(function () use ($request, $item, $targetWorkflow, $lastApprovedStep) {
                // 1) Ensure the approved manager step becomes step 1 (kept as the same DB row / same ID)
                if ($lastApprovedStep) {
                    // Try to align name/scope with the new workflow's step 1 (if present),
                    // but keep the approved history intact.
                    $targetStep1 = collect($targetWorkflow->steps ?? [])->firstWhere('step_number', 1);

                    $updates = [
                        'step_number' => 1,
                        'step_phase' => 'approval',
                    ];

                    if ($targetStep1) {
                        $updates['step_name'] = $targetStep1->step_name ?? $lastApprovedStep->step_name;
                        $updates['approver_type'] = $targetStep1->approver_type ?? $lastApprovedStep->approver_type;
                        $updates['approver_id'] = $targetStep1->approver_id ?? $lastApprovedStep->approver_id;
                        $updates['approver_role_id'] = $targetStep1->approver_role_id ?? $lastApprovedStep->approver_role_id;
                        $updates['approver_department_id'] = $targetStep1->approver_department_id ?? $lastApprovedStep->approver_department_id;
                        $updates['required_action'] = $targetStep1->required_action ?? $lastApprovedStep->required_action;
                        $updates['is_conditional'] = $targetStep1->is_conditional ?? $lastApprovedStep->is_conditional;
                        $updates['condition_type'] = $targetStep1->condition_type ?? $lastApprovedStep->condition_type;
                        $updates['condition_value'] = $targetStep1->condition_value ?? $lastApprovedStep->condition_value;
                        $updates['step_type'] = $targetStep1->step_type ?? $lastApprovedStep->step_type;
                        $updates['scope_process'] = $targetStep1->scope_process ?? $lastApprovedStep->scope_process;
                        $updates['can_insert_step'] = $targetStep1->can_insert_step ?? $lastApprovedStep->can_insert_step;
                        $updates['insert_step_template'] = $targetStep1->insert_step_template ?? $lastApprovedStep->insert_step_template;
                    }

                    $lastApprovedStep->update($updates);
                }

                // 2) Delete all steps AFTER step 1 (future/pending/old)
                ApprovalItemStep::where('approval_request_id', $request->id)
                    ->where('master_item_id', $item->master_item_id)
                    ->where('step_number', '>', 1)
                    ->delete();

                // 3) Insert new steps from target workflow EXCEPT step 1 (already satisfied by approved step)
                $count = 0;
                foreach ($targetWorkflow->steps as $step) {
                    if ((int)($step->step_number ?? 0) <= 1) continue;

                    $stepPhase = $step->step_phase ?? 'approval';
                    $initialStatus = ($stepPhase === 'release') ? 'pending_purchase' : 'pending';

                    ApprovalItemStep::create([
                        'approval_request_id' => $request->id,
                        'master_item_id' => $item->master_item_id,
                        'step_number' => $step->step_number,
                        'step_name' => $step->step_name,
                        'approver_type' => $step->approver_type,
                        'approver_id' => $step->approver_id,
                        'approver_role_id' => $step->approver_role_id,
                        'approver_department_id' => $step->approver_department_id,
                        'status' => $initialStatus,
                        'can_insert_step' => $step->can_insert_step ?? false,
                        'insert_step_template' => $step->insert_step_template ?? null,
                        'required_action' => $step->required_action ?? null,
                        'is_conditional' => $step->is_conditional ?? false,
                        'condition_type' => $step->condition_type ?? null,
                        'condition_value' => $step->condition_value ?? null,
                        'step_type' => $step->step_type ?? 'approver',
                        'step_phase' => $stepPhase,
                        'scope_process' => $step->scope_process ?? null,
                    ]);
                    $count++;
                }

                Log::info('✨ Regenerated steps for new workflow (kept approved step as #1)', [
                    'inserted_count' => $count,
                    'kept_step_id' => $lastApprovedStep?->id,
                ]);
            });

        } else {
            // WORKFLOW SAME - UPDATE FUTURE STEPS ONLY
            // We assume the current step is the Manager step (Step 2 or whatever)
            // So we replace steps > currentStepNumber
            
            $currentStep = $item->getCurrentPendingStep();
            // If just approved, currentPendingStep is null. We need the last approved step.
            if (!$currentStep) {
                 $currentStep = ApprovalItemStep::where('approval_request_id', $request->id)
                    ->where('master_item_id', $item->master_item_id)
                    ->where('status', 'approved')
                    ->orderBy('step_number', 'desc')
                    ->first();
            }
            
            $currentStepNumber = $currentStep ? $currentStep->step_number : 0;

            // Delete future steps
            $deleted = ApprovalItemStep::where('approval_request_id', $request->id)
                ->where('master_item_id', $item->master_item_id)
                ->where('step_number', '>', $currentStepNumber)
                ->delete();

            Log::info('🗑️ Deleted future steps', ['count' => $deleted]);

            // Insert new future steps
            $count = 0;
            foreach ($targetWorkflow->steps as $step) {
                if ($step->step_number <= $currentStepNumber) continue;

                $stepPhase = $step->step_phase ?? 'approval';
                $initialStatus = ($stepPhase === 'release') ? 'pending_purchase' : 'pending';

                ApprovalItemStep::create([
                    'approval_request_id' => $request->id,
                    'master_item_id' => $item->master_item_id,
                    'step_number' => $step->step_number,
                    'step_name' => $step->step_name,
                    'approver_type' => $step->approver_type,
                    'approver_id' => $step->approver_id,
                    'approver_role_id' => $step->approver_role_id,
                    'approver_department_id' => $step->approver_department_id,
                    'status' => $initialStatus,
                    'can_insert_step' => $step->can_insert_step ?? false,
                    'insert_step_template' => $step->insert_step_template ?? null,
                    'required_action' => $step->required_action ?? null,
                    'is_conditional' => $step->is_conditional ?? false,
                    'condition_type' => $step->condition_type ?? null,
                    'condition_value' => $step->condition_value ?? null,
                    'step_type' => $step->step_type ?? 'approver',
                    'step_phase' => $stepPhase,
                    'scope_process' => $step->scope_process ?? null,
                ]);
                $count++;
            }
            Log::info('✨ Inserted new future steps', ['count' => $count]);
        }
    }

    private function getNominalRange(float $price): string
    {
        if ($price <= 10000000) {
            return 'low';
        } elseif ($price <= 50000000) {
            return 'medium';
        } else {
            return 'high';
        }
    }
}
