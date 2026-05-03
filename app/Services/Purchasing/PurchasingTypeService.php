<?php

namespace App\Services\Purchasing;

use App\Models\ApprovalItemStep;
use App\Models\ApprovalWorkflow;
use App\Models\PurchasingItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * PurchasingTypeService
 *
 * Single source of truth for resolving which purchasing steps should be shown
 * for a given PurchasingItem. Both the web controller and the API controller
 * delegate to this service — no duplication of step-logic.
 *
 * Step resolution order:
 *  1. Load the ApprovalWorkflow linked to the ApprovalRequest.
 *  2. Read `purchasing_step_config` (null = all 5 default steps enabled).
 *  3. For each enabled step, compute: done?, active?, locked?
 *     - Conditional skip: a step is treated as "effectively done" when it is
 *       either truly done OR disabled/skippable in config.
 *  4. Return a flat Collection of step-objects ready for view/API rendering.
 */
class PurchasingTypeService
{
    // ────────────────────────────────────────────────────────────────────────
    // PUBLIC API
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Resolve the purchasing steps that should be displayed / acted upon.
     *
     * @param  PurchasingItem    $item              The item being processed.
     * @param  bool              $canPurchasing     User has manage_purchasing / process_purchasing_item.
     * @param  bool              $canVendor         User has manage_vendor.
     * @param  Collection        $purchasingItemSteps  ApprovalItemStep records for phase='purchasing'.
     * @param  Collection        $releaseItemSteps     ApprovalItemStep records for phase='release'.
     * @return Collection  Each element is a stdClass with:
     *         step_key, label, enabled, order, allow_skip,
     *         done, active, locked, form (blade form key),
     *         is_skipped (step disabled in config but still tracked).
     */
    public function resolvePurchasingSteps(
        PurchasingItem $item,
        bool $canPurchasing,
        bool $canVendor,
        Collection $purchasingItemSteps,
        Collection $releaseItemSteps
    ): Collection {
        $workflow = $this->resolveWorkflow($item);
        $enabledSteps = $workflow
            ? $workflow->getEnabledPurchasingSteps()
            : $this->buildDefaultStepConfig();

        // ── Compute primitives from data ──────────────────────────────────
        $benchmarkingDone  = !empty($item->approvalRequest?->received_at) && $item->vendors()->exists();
        $trialDone         = $this->isTrialDone($purchasingItemSteps);
        $hasTrial          = $enabledSteps->contains('step_key', 'trial');
        $trialSkippable    = $workflow ? $workflow->isPurchasingStepSkippable('trial') : true;

        // Trial is "effectively done" when:
        //  (a) it's disabled in config, OR
        //  (b) the trial step is actually approved/skipped in item_steps, OR
        //  (c) there's no trial item_step record at all AND it's skippable
        $trialEffectiveDone = !$hasTrial || $trialDone
            || ($trialSkippable && !$purchasingItemSteps->contains(fn($s) => stripos($s->step_name, 'Trial') !== false));

        $preferredVendorDone = !empty($item->preferred_vendor_id);
        $poDone              = !empty($item->po_number);
        $invoiceDone         = !empty($item->invoice_number);

        // Release finished?
        $releaseTotal    = $releaseItemSteps->count();
        $releaseApproved = $releaseItemSteps->where('status', 'approved')->count();
        $isReleaseFinished = $releaseTotal === 0
            || ($releaseApproved === $releaseTotal && $releaseTotal > 0);

        // Build lookup: step_key → done/active
        $stepState = [
            'benchmarking' => [
                'done'   => $benchmarkingDone,
                'active' => $canPurchasing,
                'prev_done' => true, // first step — always reachable
            ],
            'trial' => [
                'done'   => $trialDone,
                'active' => $canPurchasing && $benchmarkingDone,
                'prev_done' => $benchmarkingDone,
            ],
            'preferred_vendor' => [
                'done'   => $preferredVendorDone,
                'active' => $canVendor && $benchmarkingDone && $trialEffectiveDone,
                'prev_done' => $benchmarkingDone && $trialEffectiveDone,
            ],
            'po' => [
                'done'   => $poDone,
                'active' => $canPurchasing && $preferredVendorDone,
                'prev_done' => $preferredVendorDone,
            ],
            'invoice_grn_done' => [
                'done'   => $invoiceDone,
                'active' => $canPurchasing && $poDone && $isReleaseFinished,
                'prev_done' => $poDone && $isReleaseFinished,
            ],
        ];

        return $enabledSteps->map(function (object $stepDef) use ($stepState, $isReleaseFinished) {
            $key   = $stepDef->step_key;
            $state = $stepState[$key] ?? ['done' => false, 'active' => false, 'prev_done' => false];

            $done   = $state['done'];
            $active = !$done && $state['active'] && $state['prev_done'];
            $locked = !$done && !$active;

            return (object) [
                'step_key'           => $key,
                'label'              => $stepDef->label,
                'enabled'            => $stepDef->enabled,
                'order'              => $stepDef->order,
                'allow_skip'         => $stepDef->allow_skip,
                'done'               => $done,
                'active'             => $active || $done, // blade uses active as "can interact"
                'locked'             => $locked,
                'form'               => $this->formKeyForStep($key),
                'is_release_finished'=> $isReleaseFinished,
            ];
        });
    }

    /**
     * Return the "can_*" flags used by the API (backward-compatible shape).
     *
     * This mirrors the old getDynamicWorkflowSteps() but now reads from
     * PurchasingTypeService so logic is not duplicated.
     */
    public function resolveWorkflowFlags(
        PurchasingItem $item,
        bool $canPurchasing,
        bool $canVendor,
        Collection $purchasingItemSteps,
        Collection $releaseItemSteps
    ): array {
        $workflow  = $this->resolveWorkflow($item);
        $steps     = $this->resolvePurchasingSteps(
            $item, $canPurchasing, $canVendor, $purchasingItemSteps, $releaseItemSteps
        );

        // Primitives
        $step1Done = !empty($item->approvalRequest?->received_at);
        $step2Done = $item->vendors()->exists();
        $trialStep = $purchasingItemSteps->first(fn($s) => stripos($s->step_name, 'Trial') !== false);
        $hasTrial  = $trialStep !== null;
        $trialDone = $trialStep && in_array($trialStep->status, ['approved', 'skipped']);

        $releaseTotal    = $releaseItemSteps->count();
        $releaseApproved = $releaseItemSteps->where('status', 'approved')->count();
        $isReleaseFinished = $releaseTotal === 0
            || ($releaseApproved === $releaseTotal && $releaseTotal > 0);

        // Build step definitions array (for mobile)
        $stepDefinitions = ($workflow ? $workflow->getEnabledPurchasingSteps() : $this->buildDefaultStepConfig())
            ->map(fn($s) => [
                'step_key'   => $s->step_key,
                'label'      => $s->label,
                'enabled'    => $s->enabled,
                'order'      => $s->order,
                'allow_skip' => $s->allow_skip,
            ])
            ->values()
            ->all();

        $byKey = $steps->keyBy('step_key');

        return [
            // Backward-compatible flags
            'can_set_received_date' => $canPurchasing,
            'can_do_benchmarking'   => $canPurchasing,
            'can_do_trial'          => $canPurchasing && $step2Done && $hasTrial,
            'can_select_preferred'  => $byKey->get('preferred_vendor')?->active ?? false,
            'can_issue_po'          => $byKey->get('po')?->active ?? false,
            'can_input_invoice'     => $byKey->get('invoice_grn_done')?->active ?? false,
            'can_mark_done'         => $byKey->get('invoice_grn_done')?->active ?? false,

            'is_release_finished'   => $isReleaseFinished,

            'step1_done' => $step1Done,
            'step2_done' => $step2Done,
            'trial_done' => $trialDone,
            'step3_done' => !empty($item->preferred_vendor_id),
            'step4_done' => !empty($item->po_number),
            'step5_done' => !empty($item->invoice_number),

            'has_trial_step'  => $hasTrial,
            'dynamic_steps'   => $purchasingItemSteps->merge($releaseItemSteps)->toArray(),

            // NEW: structured step definitions for mobile
            'step_definitions' => $stepDefinitions,
        ];
    }

    /**
     * Resolve the ApprovalWorkflow linked to this purchasing item's request.
     * Returns null if no workflow found (graceful degradation → default config).
     */
    public function resolveWorkflow(PurchasingItem $item): ?ApprovalWorkflow
    {
        $request = $item->approvalRequest;
        if (!$request) {
            return null;
        }

        $workflowId = $request->workflow_id ?? null;
        if (!$workflowId) {
            return null;
        }

        try {
            return ApprovalWorkflow::find($workflowId);
        } catch (\Throwable $e) {
            Log::warning('[PurchasingTypeService] Failed to resolve workflow', [
                'approval_request_id' => $request->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get the default purchasing step config (all 5 steps enabled, standard order).
     * Used when workflow has no purchasing_step_config.
     */
    public function buildDefaultStepConfig(): Collection
    {
        return collect([
            (object) ['step_key' => 'benchmarking',     'label' => 'Benchmarking Vendor',   'enabled' => true, 'order' => 1, 'allow_skip' => false],
            (object) ['step_key' => 'trial',            'label' => 'Trial Vendor',           'enabled' => true, 'order' => 2, 'allow_skip' => true],
            (object) ['step_key' => 'preferred_vendor', 'label' => 'Pilih Preferred Vendor', 'enabled' => true, 'order' => 3, 'allow_skip' => false],
            (object) ['step_key' => 'po',               'label' => 'Input PO',               'enabled' => true, 'order' => 4, 'allow_skip' => false],
            (object) ['step_key' => 'invoice_grn_done', 'label' => 'Invoice & GRN (Selesai)','enabled' => true, 'order' => 5, 'allow_skip' => false],
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ────────────────────────────────────────────────────────────────────────

    private function isTrialDone(Collection $purchasingItemSteps): bool
    {
        $trialStep = $purchasingItemSteps->first(
            fn($s) => stripos($s->step_name, 'Trial') !== false
        );
        return $trialStep && in_array($trialStep->status, ['approved', 'skipped']);
    }

    private function formKeyForStep(string $stepKey): string
    {
        return match($stepKey) {
            'benchmarking'     => 'benchmarking',
            'trial'            => 'trial',
            'preferred_vendor' => 'preferred',
            'po'               => 'po',
            'invoice_grn_done' => 'grn',
            default            => $stepKey,
        };
    }
}
