<?php

namespace App\Console\Commands;

use App\Models\ApprovalItemStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalWorkflow;
use App\Models\PurchasingItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MigrateActiveWorkflows
 *
 * Migrates approval_item_steps for active (non-terminal) approval requests
 * to match a corrected workflow definition. Already-approved steps are
 * preserved; pending / pending_purchase steps are replaced with the steps
 * from the target workflow. Purchasing data (PurchasingItem records) are
 * never touched.
 *
 * Usage:
 *   php artisan workflow:migrate-active               # list all + migrate
 *   php artisan workflow:migrate-active --dry-run     # preview only, no DB changes
 *   php artisan workflow:migrate-active --request-id=5            # single request
 *   php artisan workflow:migrate-active --workflow-id=3           # requests on workflow 3
 *   php artisan workflow:migrate-active --target-id=7             # force target workflow
 */
class MigrateActiveWorkflows extends Command
{
    protected $signature = 'workflow:migrate-active
        {--dry-run      : Preview changes without modifying the database}
        {--request-id=  : Migrate only a specific approval_request ID}
        {--workflow-id= : Migrate only requests currently assigned to this workflow ID}
        {--target-id=   : Force a specific target workflow ID instead of auto-detect}';

    protected $description = 'Migrate active approval requests to use an updated workflow definition while preserving already-approved steps and purchasing data.';

    // -------------------------------------------------------------------------
    // ENTRY POINT
    // -------------------------------------------------------------------------

    public function handle(): int
    {
        $isDryRun      = (bool) $this->option('dry-run');
        $filterReqId   = $this->option('request-id');
        $filterWfId    = $this->option('workflow-id');
        $forceTargetId = $this->option('target-id');

        $this->newLine();
        $this->line('=========================================================');
        $this->info('  workflow:migrate-active — Workflow Migrator');
        $this->line('=========================================================');

        if ($isDryRun) {
            $this->warn('  DRY-RUN mode — no database changes will be made.');
        }
        $this->newLine();

        // 1. List all workflows in the system
        $this->listWorkflows();

        // 2. Resolve requests to process
        $requests = $this->resolveRequests($filterReqId, $filterWfId);

        if ($requests->isEmpty()) {
            $this->warn('No active approval requests found matching the given filters.');
            return Command::SUCCESS;
        }

        $this->info("Found {$requests->count()} active approval request(s) to evaluate.");
        $this->newLine();

        // 3. Summary table: which requests + which target workflow
        $this->printRequestSummaryTable($requests, $forceTargetId);

        // 4. Ask for confirmation unless dry-run
        if (!$isDryRun) {
            if (!$this->confirm('Proceed with migration?', false)) {
                $this->info('Aborted.');
                return Command::SUCCESS;
            }
        }

        // 5. Migrate
        $this->newLine();
        $this->info('Starting migration...');

        $stats = [
            'requests_processed' => 0,
            'items_migrated'     => 0,
            'items_skipped'      => 0,
            'steps_deleted'      => 0,
            'steps_inserted'     => 0,
            'errors'             => 0,
        ];

        foreach ($requests as $request) {
            $this->migrateRequest($request, $forceTargetId, $isDryRun, $stats);
        }

        // 6. Final summary
        $this->newLine();
        $this->line('---------------------------------------------------------');
        $this->info('Migration ' . ($isDryRun ? 'simulation ' : '') . 'complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Requests processed',              $stats['requests_processed']],
                ['Items migrated',                  $stats['items_migrated']],
                ['Items skipped (already up-to-date)', $stats['items_skipped']],
                ['Steps deleted (replaced)',         $stats['steps_deleted']],
                ['Steps inserted (new)',             $stats['steps_inserted']],
                ['Errors',                          $stats['errors']],
            ]
        );

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // STEP 1 — List all workflows
    // -------------------------------------------------------------------------

    private function listWorkflows(): void
    {
        $workflows = ApprovalWorkflow::orderBy('id')
            ->get(['id', 'name', 'type', 'nominal_range', 'is_active', 'priority']);

        $this->line('Available Workflows:');
        $this->table(
            ['ID', 'Name', 'Type', 'Nominal Range', 'Priority', 'Active'],
            $workflows->map(function ($w) {
                return [
                    $w->id,
                    $w->name,
                    $w->type,
                    $w->nominal_range ?? '-',
                    $w->priority ?? '-',
                    $w->is_active ? 'YES' : 'NO',
                ];
            })->toArray()
        );
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // STEP 2 — Resolve requests
    // -------------------------------------------------------------------------

    private function resolveRequests(?string $filterReqId, ?string $filterWfId): \Illuminate\Database\Eloquent\Collection
    {
        // "Active" = not in a terminal state
        $query = ApprovalRequest::with(['workflow', 'items.steps'])
            ->whereNotIn('status', ['approved', 'rejected', 'cancelled']);

        if ($filterReqId) {
            $query->where('id', (int) $filterReqId);
        }

        if ($filterWfId) {
            $query->where('workflow_id', (int) $filterWfId);
        }

        return $query->orderBy('id')->get();
    }

    // -------------------------------------------------------------------------
    // STEP 3 — Summary table
    // -------------------------------------------------------------------------

    private function printRequestSummaryTable(\Illuminate\Database\Eloquent\Collection $requests, ?string $forceTargetId): void
    {
        $rows = [];
        foreach ($requests as $request) {
            $targetWorkflow = $this->resolveTargetWorkflow($request, $forceTargetId);
            $currentWfName  = optional($request->workflow)->name ?? '-';
            $targetWfName   = $targetWorkflow
                ? "{$targetWorkflow->id}: {$targetWorkflow->name}"
                : '!!! NOT FOUND !!!';

            $needsMigration = false;
            if ($targetWorkflow) {
                foreach ($request->items as $item) {
                    if ($this->itemNeedsMigration($item, $targetWorkflow)) {
                        $needsMigration = true;
                        break;
                    }
                }
            }

            $rows[] = [
                $request->id,
                $request->request_number,
                $request->status,
                $currentWfName,
                $targetWfName,
                $request->items->count(),
                $needsMigration ? 'YES' : 'NO (already up-to-date)',
            ];
        }

        $this->line('Requests to Evaluate:');
        $this->table(
            ['ID', 'Request #', 'Status', 'Current Workflow', 'Target Workflow', 'Items', 'Needs Migration'],
            $rows
        );
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // STEP 5 — Migrate a single request
    // -------------------------------------------------------------------------

    private function migrateRequest(
        ApprovalRequest $request,
        ?string $forceTargetId,
        bool $isDryRun,
        array &$stats
    ): void {
        $targetWorkflow = $this->resolveTargetWorkflow($request, $forceTargetId);

        if (!$targetWorkflow) {
            $this->error("  [ERROR] Request #{$request->id} ({$request->request_number}): No target workflow found. Skipping.");
            $stats['errors']++;
            return;
        }

        $this->line("  [REQUEST] #{$request->id} ({$request->request_number}) => Workflow \"{$targetWorkflow->name}\"");
        $stats['requests_processed']++;

        foreach ($request->items as $item) {
            $this->migrateItem($item, $request, $targetWorkflow, $isDryRun, $stats);
        }
    }

    // -------------------------------------------------------------------------
    // Migrate a single ApprovalRequestItem
    // -------------------------------------------------------------------------

    private function migrateItem(
        ApprovalRequestItem $item,
        ApprovalRequest $request,
        ApprovalWorkflow $targetWorkflow,
        bool $isDryRun,
        array &$stats
    ): void {
        // Reload steps fresh from DB
        $item->load('steps');

        if (!$this->itemNeedsMigration($item, $targetWorkflow)) {
            $this->line("     [SKIP] Item #{$item->id} — already matches target workflow.");
            $stats['items_skipped']++;
            return;
        }

        // Last approved step for this item
        $lastApprovedStep = $item->steps
            ->where('status', 'approved')
            ->sortByDesc('step_number')
            ->first();

        $currentStepNumber = $lastApprovedStep ? (int) $lastApprovedStep->step_number : 0;

        // All existing non-approved steps after last approved (will be replaced)
        $stepsToDelete = $item->steps->filter(function ($s) use ($currentStepNumber) {
            return (int) $s->step_number > $currentStepNumber
                && !in_array($s->status, ['approved', 'skipped'], true);
        });

        // Future steps from target workflow
        $targetFutureSteps = collect($targetWorkflow->steps ?? [])
            ->filter(function ($s) use ($currentStepNumber) {
                return (int) ($s->step_number ?? 0) > $currentStepNumber;
            })
            ->values();

        $this->line("     [ITEM] #{$item->id} — last approved step: {$currentStepNumber}, to delete: {$stepsToDelete->count()}, to insert: {$targetFutureSteps->count()}");

        // ── DRY-RUN: just print what would happen ─────────────────────────────
        if ($isDryRun) {
            foreach ($stepsToDelete as $del) {
                $this->line("        [DELETE] step #{$del->step_number} '{$del->step_name}' (status={$del->status})");
            }
            foreach ($targetFutureSteps as $ins) {
                $phase = $ins->step_phase ?? 'approval';
                $this->line("        [INSERT] step #{$ins->step_number} '{$ins->step_name}' phase={$phase}");
            }

            // Show purchasing data that will be preserved
            $purchasingItem = PurchasingItem::where('approval_request_id', $request->id)
                ->where('master_item_id', $item->master_item_id)
                ->first();

            if ($purchasingItem) {
                $this->line("        [PRESERVE] purchasing data: status={$purchasingItem->status}, po={$purchasingItem->po_number}, grn={$purchasingItem->grn_date}");
            }

            $stats['items_migrated']++;
            $stats['steps_deleted']  += $stepsToDelete->count();
            $stats['steps_inserted'] += $targetFutureSteps->count();
            return;
        }

        // ── Real migration inside a transaction ───────────────────────────────
        // Use array reference inside a regular closure (not arrow function)
        $migrateResult = ['deleted' => 0, 'inserted' => 0, 'error' => null];

        try {
            DB::transaction(function () use (
                $item,
                $request,
                $targetWorkflow,
                $lastApprovedStep,
                $currentStepNumber,
                $targetFutureSteps,
                &$migrateResult
            ) {
                // 1) Update request -> workflow_id bookkeeping
                if ((int) $request->workflow_id !== (int) $targetWorkflow->id) {
                    $request->update(['workflow_id' => $targetWorkflow->id]);
                }

                // 2) Align already-approved step metadata with the matching workflow step
                if ($lastApprovedStep) {
                    $targetMatchStep = null;
                    foreach ($targetWorkflow->steps ?? [] as $s) {
                        if ((int) ($s->step_number ?? 0) === (int) $lastApprovedStep->step_number) {
                            $targetMatchStep = $s;
                            break;
                        }
                    }

                    if ($targetMatchStep) {
                        $lastApprovedStep->update([
                            'step_name'              => $targetMatchStep->step_name              ?? $lastApprovedStep->step_name,
                            'approver_type'          => $targetMatchStep->approver_type          ?? $lastApprovedStep->approver_type,
                            'approver_id'            => $targetMatchStep->approver_id            ?? $lastApprovedStep->approver_id,
                            'approver_role_id'       => $targetMatchStep->approver_role_id       ?? $lastApprovedStep->approver_role_id,
                            'approver_department_id' => $targetMatchStep->approver_department_id ?? $lastApprovedStep->approver_department_id,
                            'required_action'        => $targetMatchStep->required_action        ?? $lastApprovedStep->required_action,
                            'step_type'              => $targetMatchStep->step_type              ?? $lastApprovedStep->step_type,
                            'step_phase'             => $targetMatchStep->step_phase             ?? $lastApprovedStep->step_phase,
                            'scope_process'          => $targetMatchStep->scope_process          ?? $lastApprovedStep->scope_process,
                        ]);
                    }
                }

                // 3) Delete pending / pending_purchase steps after last approved
                $deleted = ApprovalItemStep::where('approval_request_id', $request->id)
                    ->where('approval_request_item_id', $item->id)
                    ->where('step_number', '>', $currentStepNumber)
                    ->whereNotIn('status', ['approved', 'skipped'])
                    ->delete();

                $migrateResult['deleted'] = (int) $deleted;

                // 4) Collect purchasing steps that were already approved BEFORE deletion
                //    so we can restore their "approved" status when re-inserting.
                $existingApprovedPurchasingActions = ApprovalItemStep::where('approval_request_id', $request->id)
                    ->where('approval_request_item_id', $item->id)
                    ->where('step_phase', 'purchasing')
                    ->where('status', 'approved')
                    ->pluck('required_action')
                    ->toArray();

                // 5) Insert new future steps from target workflow
                $insertedCount = 0;
                foreach ($targetFutureSteps as $step) {
                    $stepPhase = $step->step_phase ?? 'approval';
                    $reqAction = $step->required_action ?? null;

                    $initialStatus = ApprovalItemStep::initialStatusForWorkflowStep($step, $targetWorkflow->steps);

                    // Restore "approved" for purchasing steps that were already completed
                    if ($stepPhase === 'purchasing'
                        && $reqAction !== null
                        && in_array($reqAction, $existingApprovedPurchasingActions, true)
                    ) {
                        $initialStatus = 'approved';
                    }

                    ApprovalItemStep::create([
                        'approval_request_id'      => $request->id,
                        'approval_request_item_id' => $item->id,
                        'master_item_id'           => $item->master_item_id,
                        'step_number'              => $step->step_number,
                        'step_name'                => $step->step_name,
                        'approver_type'            => $step->approver_type,
                        'approver_id'              => $step->approver_id ?? null,
                        'approver_role_id'         => $step->approver_role_id ?? null,
                        'approver_department_id'   => $step->approver_department_id ?? null,
                        'status'                   => $initialStatus,
                        'required_action'          => $reqAction,
                        'required_actions'         => $step->required_actions ?? null,
                        'step_type'                => $step->step_type ?? 'approver',
                        'step_phase'               => $stepPhase,
                        'scope_process'            => $step->scope_process ?? null,
                    ]);
                    $insertedCount++;
                }

                $migrateResult['inserted'] = $insertedCount;

                Log::info('[MigrateActiveWorkflows] Item migrated', [
                    'request_id'         => $request->id,
                    'item_id'            => $item->id,
                    'target_workflow'    => $targetWorkflow->id,
                    'deleted'            => $deleted,
                    'inserted'           => $insertedCount,
                    'kept_approved_step' => $lastApprovedStep ? $lastApprovedStep->step_number : null,
                ]);
            });

            $stats['steps_deleted']  += $migrateResult['deleted'];
            $stats['steps_inserted'] += $migrateResult['inserted'];
            $stats['items_migrated']++;

            $this->info("     [OK] Item #{$item->id} migrated. Deleted: {$migrateResult['deleted']}, Inserted: {$migrateResult['inserted']}");

        } catch (\Throwable $e) {
            $stats['errors']++;
            $this->error("     [FAIL] Item #{$item->id}: " . $e->getMessage());
            Log::error('[MigrateActiveWorkflows] Item migration failed', [
                'request_id' => $request->id,
                'item_id'    => $item->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    /**
     * Resolve the target workflow for a request.
     * Priority: --target-id option → request's current assigned workflow.
     *
     * We intentionally do NOT auto-switch to a different workflow based on price.
     * The goal is only to regenerate steps from the workflow already assigned to
     * the request, so that any corrections made to the workflow definition are
     * reflected in the active steps without moving the request to a new workflow.
     */
    private function resolveTargetWorkflow(ApprovalRequest $request, ?string $forceTargetId): ?ApprovalWorkflow
    {
        // Forced override via CLI option
        if ($forceTargetId) {
            return ApprovalWorkflow::find((int) $forceTargetId);
        }

        // Always use the workflow already assigned to this request.
        return $request->workflow;
    }

    /**
     * Check whether an item's pending steps already match the target workflow.
     *
     * Returns true  => migration needed.
     * Returns false => already up-to-date.
     */
    private function itemNeedsMigration(ApprovalRequestItem $item, ApprovalWorkflow $targetWorkflow): bool
    {
        $lastApprovedStep = $item->steps
            ->where('status', 'approved')
            ->sortByDesc('step_number')
            ->first();

        $currentStepNumber = $lastApprovedStep ? (int) $lastApprovedStep->step_number : 0;

        $existingFuture = $item->steps->filter(function ($s) use ($currentStepNumber) {
            return (int) $s->step_number > $currentStepNumber
                && !in_array($s->status, ['approved', 'skipped'], true);
        })->sortBy('step_number')->values();

        $targetFuture = collect($targetWorkflow->steps ?? [])
            ->filter(function ($s) use ($currentStepNumber) {
                return (int) ($s->step_number ?? 0) > $currentStepNumber;
            })
            ->sortBy('step_number')
            ->values();

        if ($existingFuture->count() !== $targetFuture->count()) {
            return true;
        }

        foreach ($targetFuture as $i => $ts) {
            $es = $existingFuture[$i] ?? null;
            if (!$es) {
                return true;
            }
            if (($es->approver_type ?? '') !== ($ts->approver_type ?? '')) {
                return true;
            }
            if (($es->step_name ?? '') !== ($ts->step_name ?? '')) {
                return true;
            }
            if (($es->step_phase ?? 'approval') !== ($ts->step_phase ?? 'approval')) {
                return true;
            }
        }

        return false;
    }


}
