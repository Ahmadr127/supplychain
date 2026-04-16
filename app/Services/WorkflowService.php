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
     * Re-evaluate workflow steps for ONE item based on its updated price.
     *
     * KEY FIX: All queries now filter by `approval_request_item_id` (the item's PK in
     * approval_request_items), NOT by `master_item_id`. This prevents cross-item
     * contamination when a request contains multiple items with the same master_item_id
     * or different nominal ranges.
     *
     * Also: the decision "does workflow need to change?" is now determined per-item by
     * comparing the TARGET workflow against the steps that already exist for THIS specific
     * item — NOT by comparing against `request->workflow_id` (which is a shared field).
     */
    public function reevaluateWorkflow(ApprovalRequestItem $item): void
    {
        Log::info('🔄 WorkflowService::reevaluateWorkflow CALLED', [
            'item_id'     => $item->id,
            'total_price' => $item->total_price,
        ]);

        $request = $item->approvalRequest;
        if (!$request) return;

        $procurementType = ProcurementType::find($request->procurement_type_id);
        if (!$procurementType) {
            Log::warning('⚠️ Procurement type not found', [
                'procurement_type_id' => $request->procurement_type_id,
            ]);
            return;
        }

        $totalPrice   = (float) ($item->total_price ?? 0);
        $nominalRange = $this->getNominalRange($totalPrice);

        Log::info('📊 Determined nominal range', [
            'item_id'          => $item->id,
            'total_price'      => $totalPrice,
            'range'            => $nominalRange,
            'procurement_type' => $procurementType->code,
        ]);

        // Find the target workflow for THIS item based on its own price range
        $targetWorkflow = ApprovalWorkflow::where('procurement_type_id', $procurementType->id)
            ->where('nominal_range', $nominalRange)
            ->where('is_active', true)
            ->first();

        if (!$targetWorkflow) {
            Log::warning('⚠️ No matching workflow found for re-evaluation', [
                'item_id'          => $item->id,
                'procurement_type' => $procurementType->id,
                'nominal_range'    => $nominalRange,
            ]);
            return;
        }

        Log::info('🎯 Target workflow found', [
            'item_id'     => $item->id,
            'workflow_id' => $targetWorkflow->id,
            'name'        => $targetWorkflow->name,
        ]);

        // ── FIX: Determine whether THIS item's steps need to change ──────────
        // We compare against the item's own existing step config, NOT request->workflow_id.
        // Find the last approved step for this specific item.
        $lastApprovedStep = ApprovalItemStep::where('approval_request_id', $request->id)
            ->where('approval_request_item_id', $item->id)   // ← FIX: use item PK
            ->where('status', 'approved')
            ->orderBy('step_number', 'desc')
            ->first();

        $currentStepNumber = $lastApprovedStep ? (int) $lastApprovedStep->step_number : 0;

        // Count how many pending future steps already exist for this item
        $existingFutureSteps = ApprovalItemStep::where('approval_request_id', $request->id)
            ->where('approval_request_item_id', $item->id)   // ← FIX: use item PK
            ->where('step_number', '>', $currentStepNumber)
            ->get();

        // Steps from the target workflow that are after currentStepNumber
        $targetFutureSteps = collect($targetWorkflow->steps ?? [])
            ->filter(fn($s) => (int)($s->step_number ?? 0) > $currentStepNumber)
            ->values();

        // Check if the existing future steps already match the target workflow.
        // Simple check: same count AND same approver_type sequence is enough for most cases.
        $alreadyMatches = $this->stepsMatchWorkflow($existingFutureSteps, $targetFutureSteps);

        if ($alreadyMatches) {
            Log::info('✅ Item workflow steps already match target — no regeneration needed', [
                'item_id'     => $item->id,
                'workflow_id' => $targetWorkflow->id,
            ]);
            // Still update request->workflow_id for bookkeeping (does NOT affect other items' steps)
            if ($request->workflow_id !== $targetWorkflow->id) {
                $request->update(['workflow_id' => $targetWorkflow->id]);
            }
            return;
        }

        // ── Workflow IS different for this item → regenerate its future steps ─
        Log::info('🔁 Workflow change detected for item — regenerating steps', [
            'item_id'          => $item->id,
            'existing_count'   => $existingFutureSteps->count(),
            'target_count'     => $targetFutureSteps->count(),
            'workflow_id'      => $targetWorkflow->id,
        ]);

        DB::transaction(function () use (
            $request, $item, $targetWorkflow, $lastApprovedStep, $currentStepNumber, $targetFutureSteps
        ) {
            // Update request->workflow_id for bookkeeping
            $request->update(['workflow_id' => $targetWorkflow->id]);

            // 1) Align the approved step (step 1) name/metadata with target workflow step 1
            if ($lastApprovedStep) {
                $targetStep1 = $targetFutureSteps->isEmpty()
                    ? collect($targetWorkflow->steps ?? [])->firstWhere('step_number', 1)
                    : null;
                // Always try to get step 1 from target workflow
                $targetStep1 = collect($targetWorkflow->steps ?? [])->firstWhere('step_number', 1);

                $updates = [
                    'step_number' => 1,
                    'step_phase'  => 'approval',
                ];

                if ($targetStep1) {
                    $updates['step_name']             = $targetStep1->step_name             ?? $lastApprovedStep->step_name;
                    $updates['approver_type']          = $targetStep1->approver_type          ?? $lastApprovedStep->approver_type;
                    $updates['approver_id']            = $targetStep1->approver_id            ?? $lastApprovedStep->approver_id;
                    $updates['approver_role_id']       = $targetStep1->approver_role_id       ?? $lastApprovedStep->approver_role_id;
                    $updates['approver_department_id'] = $targetStep1->approver_department_id ?? $lastApprovedStep->approver_department_id;
                    $updates['required_action']        = $targetStep1->required_action        ?? $lastApprovedStep->required_action;
                    $updates['is_conditional']         = $targetStep1->is_conditional         ?? $lastApprovedStep->is_conditional;
                    $updates['condition_type']         = $targetStep1->condition_type         ?? $lastApprovedStep->condition_type;
                    $updates['condition_value']        = $targetStep1->condition_value        ?? $lastApprovedStep->condition_value;
                    $updates['step_type']              = $targetStep1->step_type              ?? $lastApprovedStep->step_type;
                    $updates['scope_process']          = $targetStep1->scope_process          ?? $lastApprovedStep->scope_process;
                    $updates['can_insert_step']        = $targetStep1->can_insert_step        ?? $lastApprovedStep->can_insert_step;
                    $updates['insert_step_template']   = $targetStep1->insert_step_template   ?? $lastApprovedStep->insert_step_template;
                }

                $lastApprovedStep->update($updates);
            }

            // 2) Delete ALL future steps for THIS item only (after step 1)
            //    ← FIX: was filtering by master_item_id, now filters by approval_request_item_id
            $deleted = ApprovalItemStep::where('approval_request_id', $request->id)
                ->where('approval_request_item_id', $item->id)   // ← FIX
                ->where('step_number', '>', $currentStepNumber > 0 ? $currentStepNumber : 1)
                ->delete();

            Log::info('🗑️ Deleted future steps for item', [
                'item_id' => $item->id,
                'deleted' => $deleted,
            ]);

            // 3) Insert new future steps from target workflow (skip step 1 if already kept)
            $count = 0;
            foreach ($targetWorkflow->steps as $step) {
                $stepNum = (int) ($step->step_number ?? 0);
                // Skip step 1 if we already have an approved step at position 1
                if ($lastApprovedStep && $stepNum <= 1) continue;
                // Skip steps already covered by currentStepNumber
                if ($stepNum <= $currentStepNumber) continue;

                $stepPhase     = $step->step_phase ?? 'approval';
                $initialStatus = ($stepPhase === 'release') ? 'pending_purchase' : 'pending';

                ApprovalItemStep::create([
                    'approval_request_id'      => $request->id,
                    'approval_request_item_id' => $item->id,
                    'master_item_id'           => $item->master_item_id,
                    'step_number'              => $step->step_number,
                    'step_name'                => $step->step_name,
                    'approver_type'            => $step->approver_type,
                    'approver_id'              => $step->approver_id,
                    'approver_role_id'         => $step->approver_role_id,
                    'approver_department_id'   => $step->approver_department_id,
                    'status'                   => $initialStatus,
                    'can_insert_step'          => $step->can_insert_step ?? false,
                    'insert_step_template'     => $step->insert_step_template ?? null,
                    'required_action'          => $step->required_action ?? null,
                    'is_conditional'           => $step->is_conditional ?? false,
                    'condition_type'           => $step->condition_type ?? null,
                    'condition_value'          => $step->condition_value ?? null,
                    'step_type'                => $step->step_type ?? 'approver',
                    'step_phase'               => $stepPhase,
                    'scope_process'            => $step->scope_process ?? null,
                ]);
                $count++;
            }

            Log::info('✨ Regenerated steps for item (kept approved step as #1)', [
                'item_id'        => $item->id,
                'inserted_count' => $count,
                'kept_step_id'   => $lastApprovedStep?->id,
            ]);
        });
    }

    /**
     * Check whether the existing pending future steps already match the target workflow steps.
     * Matching criteria: same count AND same approver_type sequence.
     */
    private function stepsMatchWorkflow($existingSteps, $targetSteps): bool
    {
        if ($existingSteps->count() !== $targetSteps->count()) {
            return false;
        }

        // Sort both by step_number
        $existing = $existingSteps->sortBy('step_number')->values();
        $target   = $targetSteps->sortBy('step_number')->values();

        foreach ($target as $i => $ts) {
            $es = $existing[$i] ?? null;
            if (!$es) return false;
            // Check approver_type matches (core structural check)
            if (($es->approver_type ?? '') !== ($ts->approver_type ?? '')) {
                return false;
            }
            // Also check step_name to catch workflow label changes
            if (($es->step_name ?? '') !== ($ts->step_name ?? '')) {
                return false;
            }
        }

        return true;
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
