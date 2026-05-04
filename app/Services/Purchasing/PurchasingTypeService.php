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
 * Resolves which purchasing forms to show and their done/active/locked state.
 *
 * **Workflow-driven (preferred):** when there are `ApprovalItemStep` rows with
 * `step_phase = purchasing`, order and behaviour follow those rows only. Each row’s
 * `required_action` (Langkah Purchasing from the workflow editor) selects the form —
 * **step_name is not used for logic.**
 *
 * **Legacy:** if there are no purchasing-phase tracker rows, fall back to
 * `approval_workflows.purchasing_step_config` (or the default 5-step template).
 */
class PurchasingTypeService
{
    /** required_action (DB) → canonical step_key used for flags / config lookup */
    private const REQUIRED_ACTION_TO_CANONICAL = [
        'purchasing_receive_doc_benchmark' => 'benchmarking',
        'purchasing_benchmarking'          => 'benchmarking',
        'purchasing_receive_doc'           => 'benchmarking',
        'purchasing_trial'                 => 'trial',
        'purchasing_preferred_vendor'      => 'preferred_vendor',
        'purchasing_po'                    => 'po',
        'purchasing_invoice_grn_done'      => 'invoice_grn_done',
        'purchasing_invoice'               => 'invoice_grn_done',
        'purchasing_done'                  => 'invoice_grn_done',
    ];

    private const REQUIRED_ACTION_LABELS = [
        'purchasing_receive_doc_benchmark' => 'Terima Dok & Benchmarking',
        'purchasing_benchmarking'          => 'Benchmarking Vendor',
        'purchasing_receive_doc'           => 'Terima Dokumen',
        'purchasing_trial'                 => 'Trial Vendor',
        'purchasing_preferred_vendor'      => 'Pilih Preferred Vendor',
        'purchasing_po'                    => 'Input PO',
        'purchasing_invoice_grn_done'      => 'Invoice & GRN (Selesai)',
        'purchasing_invoice'               => 'Invoice',
        'purchasing_done'                  => 'Selesai',
    ];

    public function resolvePurchasingSteps(
        PurchasingItem $item,
        bool $canPurchasing,
        bool $canVendor,
        Collection $purchasingItemSteps,
        Collection $releaseItemSteps
    ): Collection {
        $trackers = $purchasingItemSteps->sortBy('step_number')->values();

        if ($trackers->isNotEmpty()) {
            return $this->resolvePurchasingStepsFromTrackers(
                $item,
                $canPurchasing,
                $canVendor,
                $trackers,
                $releaseItemSteps
            );
        }

        return $this->resolvePurchasingStepsFromWorkflowConfig(
            $item,
            $canPurchasing,
            $canVendor,
            $purchasingItemSteps,
            $releaseItemSteps
        );
    }

    /**
     * One UI card per purchasing-phase ApprovalItemStep, driven by required_action only.
     */
    private function resolvePurchasingStepsFromTrackers(
        PurchasingItem $item,
        bool $canPurchasing,
        bool $canVendor,
        Collection $trackers,
        Collection $releaseItemSteps
    ): Collection {
        $isReleaseFinished = $this->computeReleaseFinished($releaseItemSteps);

        $benchmarkingDone  = !empty($item->approvalRequest?->received_at) && $item->vendors()->exists();
        $preferredVendorDone = !empty($item->preferred_vendor_id);
        $poDone              = !empty($item->po_number);
        $invoiceDone         = !empty($item->invoice_number);

        $trialRow = $trackers->first(
            fn($s) => ($s->required_action ?? '') === 'purchasing_trial'
        );
        $trialDone = $trialRow && in_array($trialRow->status, ['approved', 'skipped'], true);
        $hasTrialInWorkflow = $trialRow !== null;
        $trialEffectiveDone = !$hasTrialInWorkflow || $trialDone;

        return $trackers->map(function (ApprovalItemStep $row) use (
            $item,
            $canPurchasing,
            $canVendor,
            $trackers,
            $isReleaseFinished,
            $benchmarkingDone,
            $preferredVendorDone,
            $poDone,
            $invoiceDone,
            $trialEffectiveDone
        ) {
            $action = (string) ($row->required_action ?? '');
            $canonical = self::REQUIRED_ACTION_TO_CANONICAL[$action] ?? null;

            if ($canonical === null) {
                Log::warning('[PurchasingTypeService] Purchasing step has unknown required_action', [
                    'approval_item_step_id' => $row->id,
                    'required_action'        => $action,
                ]);
            }

            $label = self::REQUIRED_ACTION_LABELS[$action] ?? 'Langkah purchasing';

            $priorOk = $trackers
                ->filter(fn($s) => (int) $s->step_number < (int) $row->step_number)
                ->every(fn($s) => in_array($s->status, ['approved', 'skipped'], true));

            $wfDone = in_array($row->status, ['approved', 'skipped'], true);
            $wfRenderable = ($row->status === 'pending') && $priorOk;

            $dataOk = $this->dataPrerequisiteMetForAction(
                $action,
                $item,
                $benchmarkingDone,
                $trialEffectiveDone,
                $preferredVendorDone,
                $poDone,
                $isReleaseFinished
            );

            $permOk = $this->permissionMetForAction($action, $canPurchasing, $canVendor);

            $legacyActive = $this->legacyStyleActiveForAction(
                $action,
                $canPurchasing,
                $canVendor,
                $benchmarkingDone,
                $trialEffectiveDone,
                $preferredVendorDone,
                $poDone,
                $isReleaseFinished
            );

            $done = $wfDone;
            if ($canonical === null) {
                $active = false;
                $locked = !$done;
            } else {
                $active = !$done && $wfRenderable && $permOk && $dataOk && $legacyActive;
                $locked = !$done && !$active;
            }

            $formKey = $canonical ? $this->formKeyForCanonical($canonical) : 'benchmarking';

            return (object) [
                'step_key'             => $canonical ? "{$canonical}_{$row->id}" : "unknown_{$row->id}",
                'canonical_step_key'   => $canonical ?? 'benchmarking',
                'approval_item_step_id'=> $row->id,
                'required_action'      => $action,
                'label'                => $label,
                'enabled'              => true,
                'order'                => (int) $row->step_number,
                'allow_skip'           => ($canonical === 'trial'),
                'done'                 => $done,
                'active'               => $active || $done,
                'locked'               => $locked,
                'form'                 => $formKey,
                'is_release_finished'  => $isReleaseFinished,
            ];
        })->values();
    }

    /**
     * Legacy: enabled micro-steps from workflow JSON purchasing_step_config (or default).
     */
    private function resolvePurchasingStepsFromWorkflowConfig(
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

        $benchmarkingDone  = !empty($item->approvalRequest?->received_at) && $item->vendors()->exists();
        $trialDone           = $this->isTrialDoneByRequiredActionOnly($purchasingItemSteps);
        $hasTrial            = $enabledSteps->contains('step_key', 'trial');
        $trialSkippable      = $workflow ? $workflow->isPurchasingStepSkippable('trial') : true;
        $hasTrialTrackerRow  = $this->hasTrialRowByRequiredAction($purchasingItemSteps);

        $trialEffectiveDone = !$hasTrial || $trialDone
            || ($trialSkippable && !$hasTrialTrackerRow);

        $preferredVendorDone = !empty($item->preferred_vendor_id);
        $poDone              = !empty($item->po_number);
        $invoiceDone         = !empty($item->invoice_number);

        $isReleaseFinished = $this->computeReleaseFinished($releaseItemSteps);

        $stepState = [
            'benchmarking' => [
                'done'      => $benchmarkingDone,
                'active'    => $canPurchasing,
                'prev_done' => true,
            ],
            'trial' => [
                'done'      => $trialDone,
                'active'    => $canPurchasing && $benchmarkingDone,
                'prev_done' => $benchmarkingDone,
            ],
            'preferred_vendor' => [
                'done'      => $preferredVendorDone,
                'active'    => $canVendor && $benchmarkingDone && $trialEffectiveDone,
                'prev_done' => $benchmarkingDone && $trialEffectiveDone,
            ],
            'po' => [
                'done'      => $poDone,
                'active'    => $canPurchasing && $preferredVendorDone,
                'prev_done' => $preferredVendorDone,
            ],
            'invoice_grn_done' => [
                'done'      => $invoiceDone,
                'active'    => $canPurchasing && $poDone && $isReleaseFinished,
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
                'step_key'             => $key,
                'canonical_step_key'   => $key,
                'approval_item_step_id'=> null,
                'required_action'      => null,
                'label'                => $stepDef->label,
                'enabled'              => $stepDef->enabled,
                'order'                => $stepDef->order,
                'allow_skip'           => $stepDef->allow_skip,
                'done'                 => $done,
                'active'               => $active || $done,
                'locked'               => $locked,
                'form'                 => $this->formKeyForCanonical($key),
                'is_release_finished'  => $isReleaseFinished,
            ];
        });
    }

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

        $step1Done = !empty($item->approvalRequest?->received_at);
        $step2Done = $item->vendors()->exists();

        $trialRow = $purchasingItemSteps->first(
            fn($s) => ($s->required_action ?? '') === 'purchasing_trial'
        );
        $hasTrial  = $trialRow !== null;
        $trialDone = $trialRow && in_array($trialRow->status, ['approved', 'skipped'], true);

        $isReleaseFinished = $this->computeReleaseFinished($releaseItemSteps);

        $stepDefinitions = $steps->map(fn($s) => [
            'step_key'   => $s->canonical_step_key ?? $s->step_key,
            'label'      => $s->label,
            'enabled'    => (bool) ($s->enabled ?? true),
            'order'      => (int) ($s->order ?? 0),
            'allow_skip' => (bool) ($s->allow_skip ?? false),
        ])->values()->all();

        $byCanon = $steps->keyBy(fn($s) => $s->canonical_step_key ?? $s->step_key);

        $actionable = fn(?string $k) => $k && ($s = $byCanon->get($k)) && !$s->done && !$s->locked;

        return [
            'can_set_received_date' => $canPurchasing,
            'can_do_benchmarking'   => $actionable('benchmarking'),
            'can_do_trial'          => $actionable('trial'),
            'can_select_preferred'  => $actionable('preferred_vendor'),
            'can_issue_po'          => $actionable('po'),
            'can_input_invoice'     => $actionable('invoice_grn_done'),
            'can_mark_done'         => $actionable('invoice_grn_done'),

            'is_release_finished'   => $isReleaseFinished,

            'step1_done' => $step1Done,
            'step2_done' => $step2Done,
            'trial_done' => $trialDone,
            'step3_done' => !empty($item->preferred_vendor_id),
            'step4_done' => !empty($item->po_number),
            'step5_done' => !empty($item->invoice_number),

            'has_trial_step'  => $hasTrial,
            'dynamic_steps'   => $purchasingItemSteps->merge($releaseItemSteps)->toArray(),

            'step_definitions' => $stepDefinitions,
        ];
    }

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

    /**
     * Aliases of required_action that one purchasing API action may complete.
     * Used only with column `required_action` — never step_name.
     */
    public static function purchasingRequiredActionAliases(string $actionType): array
    {
        return match (true) {
            in_array($actionType, ['purchasing_receive_doc_benchmark', 'purchasing_benchmarking', 'purchasing_receive_doc'], true)
                => ['purchasing_receive_doc_benchmark', 'purchasing_benchmarking', 'purchasing_receive_doc'],
            in_array($actionType, ['purchasing_invoice_grn_done', 'purchasing_invoice', 'purchasing_done'], true)
                => ['purchasing_invoice_grn_done', 'purchasing_invoice', 'purchasing_done'],
            default => [$actionType],
        };
    }

    private function computeReleaseFinished(Collection $releaseItemSteps): bool
    {
        $releaseTotal    = $releaseItemSteps->count();
        $releaseApproved = $releaseItemSteps->where('status', 'approved')->count();

        return $releaseTotal === 0
            || ($releaseApproved === $releaseTotal && $releaseTotal > 0);
    }

    private function dataPrerequisiteMetForAction(
        string $action,
        PurchasingItem $item,
        bool $benchmarkingDone,
        bool $trialEffectiveDone,
        bool $preferredVendorDone,
        bool $poDone,
        bool $isReleaseFinished
    ): bool {
        return match ($action) {
            'purchasing_receive_doc_benchmark', 'purchasing_benchmarking', 'purchasing_receive_doc' => true,
            'purchasing_trial' => $benchmarkingDone,
            'purchasing_preferred_vendor' => $benchmarkingDone && $trialEffectiveDone,
            'purchasing_po' => $preferredVendorDone,
            'purchasing_invoice_grn_done', 'purchasing_invoice', 'purchasing_done' => $poDone && $isReleaseFinished,
            default => true,
        };
    }

    private function permissionMetForAction(string $action, bool $canPurchasing, bool $canVendor): bool
    {
        return match ($action) {
            'purchasing_preferred_vendor' => $canVendor,
            default => $canPurchasing,
        };
    }

    private function legacyStyleActiveForAction(
        string $action,
        bool $canPurchasing,
        bool $canVendor,
        bool $benchmarkingDone,
        bool $trialEffectiveDone,
        bool $preferredVendorDone,
        bool $poDone,
        bool $isReleaseFinished
    ): bool {
        return match ($action) {
            'purchasing_receive_doc_benchmark', 'purchasing_benchmarking', 'purchasing_receive_doc' => $canPurchasing,
            'purchasing_trial' => $canPurchasing && $benchmarkingDone,
            'purchasing_preferred_vendor' => $canVendor && $benchmarkingDone && $trialEffectiveDone,
            'purchasing_po' => $canPurchasing && $preferredVendorDone,
            'purchasing_invoice_grn_done', 'purchasing_invoice', 'purchasing_done' => $canPurchasing && $poDone && $isReleaseFinished,
            default => $canPurchasing,
        };
    }

    private function formKeyForCanonical(string $canonical): string
    {
        return match ($canonical) {
            'benchmarking'     => 'benchmarking',
            'trial'            => 'trial',
            'preferred_vendor' => 'preferred',
            'po'               => 'po',
            'invoice_grn_done' => 'grn',
            default            => 'benchmarking',
        };
    }

    private function isTrialDoneByRequiredActionOnly(Collection $purchasingItemSteps): bool
    {
        $trial = $purchasingItemSteps->first(
            fn($s) => ($s->required_action ?? '') === 'purchasing_trial'
        );

        return $trial && in_array($trial->status, ['approved', 'skipped'], true);
    }

    private function hasTrialRowByRequiredAction(Collection $purchasingItemSteps): bool
    {
        return $purchasingItemSteps->contains(
            fn($s) => ($s->required_action ?? '') === 'purchasing_trial'
        );
    }
}
