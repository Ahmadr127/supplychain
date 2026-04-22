<?php

namespace App\Services\Purchasing;

use App\Models\PurchasingItem;
use App\Models\PurchasingItemVendor;
use App\Models\PurchasingItemVendorTrial;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchasingItemService
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Step 1 (Merged): Receive doc + benchmarking vendors in one submit.
     * - Sets approvalRequest.received_at
     * - Replaces vendor list + benchmark notes
     * - Moves to benchmarking status (when vendors exist)
     * - Syncs workflow tracker to merged purchasing step
     */
    public function receiveDocAndBenchmarking(
        PurchasingItem $item,
        Carbon $receivedAt,
        array $vendors,
        ?string $benchmarkNotes = null
    ): PurchasingItem {
        $oldStatus = $item->status;

        DB::transaction(function () use ($item, $receivedAt, $vendors, $benchmarkNotes) {
            $item->approvalRequest->update(['received_at' => $receivedAt]);

            // Replace benchmarking set
            $item->vendors()->delete();
            foreach ($vendors as $v) {
                if (empty($v['supplier_id'])) {
                    continue;
                }
                $unit = (float)($v['unit_price'] ?? 0);
                $total = (float)($v['total_price'] ?? 0);
                if ($unit <= 0 && $total <= 0) {
                    continue;
                }

                PurchasingItemVendor::create([
                    'purchasing_item_id' => $item->id,
                    'supplier_id' => (int)$v['supplier_id'],
                    'unit_price' => $unit,
                    'total_price' => $total ?: ($unit * max(1, (int)$item->quantity)),
                    'is_preferred' => false,
                    'notes' => $v['notes'] ?? null,
                ]);
            }

            $hasVendors = $item->vendors()->exists();
            $newStatus = $hasVendors ? 'benchmarking' : 'unprocessed';

            $item->update([
                'status' => $newStatus,
                'status_changed_at' => now(),
                'status_changed_by' => auth()->id(),
                'benchmark_notes' => $benchmarkNotes,
            ]);
        });

        $item->approvalRequest->refreshPurchasingStatus();
        $item = $item->refresh();

        if ($item->vendors()->exists()) {
            // New merged action
            \App\Models\ApprovalItemStep::syncPurchasingStep(
                $item->approval_request_id,
                $item->master_item_id,
                'purchasing_receive_doc_benchmark'
            );
            // Compatibility: old split actions
            \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_receive_doc');
            \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_benchmarking');
        }

        if ($oldStatus !== $item->status) {
            $this->notificationService->notifyPurchasingStatusChange($item, $oldStatus, $item->status);
        }

        return $item;
    }

    /**
     * Step 1: Set received date and transition to benchmarking
     */
    public function setReceivedDate(PurchasingItem $item, Carbon $date): PurchasingItem
    {
        $oldStatus = $item->status;
        
        DB::transaction(function() use ($item, $date) {
            $item->approvalRequest->update(['received_at' => $date]);
            
            // Advance status if it was unprocessed
            if ($item->status === 'unprocessed') {
                $item->update([
                    'status' => 'benchmarking',
                    'status_changed_at' => now(),
                    'status_changed_by' => auth()->id(),
                ]);
            }
        });

        $item->approvalRequest->refreshPurchasingStatus();
        $item = $item->refresh();

        \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_receive_doc');
        // Compatibility: if workflow uses merged step
        \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_receive_doc_benchmark');

        // Notify requester about progress
        $this->notificationService->notifyPurchasingStatusChange($item, $oldStatus, $item->status);

        return $item;
    }

    /**
     * Save or replace benchmarking vendors for a purchasing item.
     * $vendors = [
     *   ['supplier_id' => int, 'unit_price' => float, 'total_price' => float, 'notes' => ?string], ...
     * ]
     */
    public function saveBenchmarking(PurchasingItem $item, array $vendors): PurchasingItem
    {
        $oldStatus = $item->status;
        $newStatus = $oldStatus;

        DB::transaction(function() use ($item, $vendors, &$newStatus) {
            // Replace benchmarking set: delete existing then insert new
            $item->vendors()->delete();

            foreach ($vendors as $v) {
                if (empty($v['supplier_id'])) { continue; }
                $unit = (float)($v['unit_price'] ?? 0);
                $total = (float)($v['total_price'] ?? 0);
                if ($unit <= 0 && $total <= 0) { continue; }

                PurchasingItemVendor::create([
                    'purchasing_item_id' => $item->id,
                    'supplier_id' => (int)$v['supplier_id'],
                    'unit_price' => $unit,
                    'total_price' => $total ?: ($unit * max(1, (int)$item->quantity)),
                    'is_preferred' => false,
                    'notes' => $v['notes'] ?? null,
                ]);
            }

            // Update status to benchmarking if there is data; otherwise keep unprocessed
            $hasVendors = $item->vendors()->exists();
            $newStatus = $hasVendors ? 'benchmarking' : 'unprocessed';
            $item->update([
                'status' => $newStatus,
                'status_changed_at' => now(),
                'status_changed_by' => auth()->id(),
            ]);
        });

        $item->approvalRequest->refreshPurchasingStatus();

        $item = $item->refresh();

        if ($item->vendors()->exists()) {
            \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_benchmarking');
            // Compatibility: if workflow uses merged step
            \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_receive_doc_benchmark');
        }

        if ($oldStatus !== $newStatus) {
            $this->notificationService->notifyPurchasingStatusChange($item, $oldStatus, $newStatus);
        }

        return $item;
    }

    /**
     * Step: Trial (new).
     * Stores trial notes per vendor from benchmarking list.
     */
    public function saveTrial(PurchasingItem $item, array $vendorTrials): PurchasingItem
    {
        DB::transaction(function () use ($item, $vendorTrials) {
            foreach ($vendorTrials as $row) {
                if (empty($row['purchasing_item_vendor_id'])) {
                    continue;
                }
                $vendorId = (int) $row['purchasing_item_vendor_id'];
                $notes = $row['trial_notes'] ?? null;

                $vendor = $item->vendors()->where('id', $vendorId)->first();
                if (!$vendor) {
                    continue;
                }

                PurchasingItemVendorTrial::create([
                    'purchasing_item_vendor_id' => $vendorId,
                    'trial_notes' => $notes,
                    'created_by' => auth()->id(),
                ]);
            }
        });

        \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_trial');

        return $item->refresh();
    }

    /**
     * Select preferred vendor for an item.
     * This triggers the RELEASE phase in 3-phase workflow.
     */
    public function selectPreferred(PurchasingItem $item, int $supplierId, ?float $unitPrice, ?float $totalPrice): PurchasingItem
    {
        $oldStatus = $item->status;

        DB::transaction(function() use ($item, $supplierId, $unitPrice, $totalPrice) {
            // Ensure vendor exists in benchmarking; if not, create it with given prices
            $vendor = $item->vendors()->where('supplier_id', $supplierId)->first();
            if (!$vendor) {
                $vendor = $item->vendors()->create([
                    'supplier_id' => $supplierId,
                    'unit_price' => $unitPrice ?? 0,
                    'total_price' => $totalPrice ?? 0,
                    'is_preferred' => false,
                ]);
            }

            // Mark preferred
            $item->vendors()->update(['is_preferred' => false]);
            $vendor->update(['is_preferred' => true]);

            $item->update([
                'preferred_vendor_id' => $supplierId,
                'preferred_unit_price' => $unitPrice,
                'preferred_total_price' => $totalPrice,
                'status' => 'selected',
                'status_changed_at' => now(),
                'status_changed_by' => auth()->id(),
            ]);
            
        });

        $item->approvalRequest->refreshPurchasingStatus();

        $item = $item->refresh();

        \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_preferred_vendor');

        if ($oldStatus !== 'selected') {
            $this->notificationService->notifyPurchasingStatusChange($item, $oldStatus, 'selected');
        }

        return $item;
    }

    /**
     * Activate release phase steps when vendor is selected.
     * Changes release step status from 'pending_purchase' to 'pending'.
     * This is the transition from Phase 2 (Purchasing) to Phase 3 (Release).
     */
    public function activateReleaseSteps(PurchasingItem $item): bool
    {
        $releaseSteps = \App\Models\ApprovalItemStep::where('approval_request_id', $item->approval_request_id)
            ->where('master_item_id', $item->master_item_id)
            ->where('step_phase', 'release')
            ->where('status', 'pending_purchase')
            ->get();

        if ($releaseSteps->isEmpty()) {
            \Illuminate\Support\Facades\Log::info('No release steps to activate', [
                'purchasing_item_id' => $item->id,
            ]);
            return false;
        }

        // Activate first release step only (sequential approval)
        $firstReleaseStep = $releaseSteps->sortBy('step_number')->first();
        $firstReleaseStep->update([
            'status' => 'pending',
        ]);

        // Trigger notification for release approver
        $this->notificationService->notifyReleaseApprover($firstReleaseStep);

        // Update approval request item status
        $requestItem = \App\Models\ApprovalRequestItem::where('approval_request_id', $item->approval_request_id)
            ->where('master_item_id', $item->master_item_id)
            ->first();

        if ($requestItem) {
            $requestItem->update([
                'status' => 'in_release', // New status: in release phase
            ]);
        }

        \Illuminate\Support\Facades\Log::info('Release phase activated', [
            'purchasing_item_id' => $item->id,
            'release_steps_count' => $releaseSteps->count(),
            'first_release_step' => $firstReleaseStep->step_name,
        ]);

        return true;
    }

    /**
     * Issue PO for an item.
     */
    public function issuePO(PurchasingItem $item, string $poNumber): PurchasingItem
    {
        $oldStatus = $item->status;

        // Determine if we should update status
        // If item is still before PO stage or PO not yet set, mark as po_issued.
        // Otherwise (already GRN received or DONE), keep current status.
        $newStatus = $item->status;
        if (empty($item->po_number) || in_array($item->status, ['unprocessed','benchmarking','selected','po_issued'])) {
            $newStatus = 'po_issued';
        }

        $item->update([
            'po_number' => $poNumber,
            'status' => $newStatus,
            'status_changed_at' => now(),
            'status_changed_by' => auth()->id(),
        ]);
        $item->approvalRequest->refreshPurchasingStatus();

        if ($newStatus === 'po_issued' || !empty($item->po_number)) {
            \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_po');
        }

        if ($oldStatus !== $newStatus) {
            $this->notificationService->notifyPurchasingStatusChange($item, $oldStatus, $newStatus);
        }

        return $item;
    }

    /**
     * Receive GRN for an item and compute cycle.
     */
    public function receiveGRN(PurchasingItem $item, Carbon $grnDate): PurchasingItem
    {
        $oldStatus = $item->status;
        $created = $item->created_at ?: now();
        $cycle = (int) $grnDate->copy()->startOfDay()->diffInDays($created->copy()->startOfDay());
        $item->update([
            'grn_date' => $grnDate->toDateString(),
            'proc_cycle_days' => $cycle,
            'status' => 'grn_received',
            'status_changed_at' => now(),
            'status_changed_by' => auth()->id(),
        ]);
        $item->approvalRequest->refreshPurchasingStatus();

        \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_invoice');
        // Compatibility: if workflow uses merged final step
        \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_invoice_grn_done');

        if ($oldStatus !== 'grn_received') {
            $this->notificationService->notifyPurchasingStatusChange($item, $oldStatus, 'grn_received');
        }

        return $item;
    }

    /**
     * Step (Merged Final): invoice + GRN + done.
     */
    public function invoiceGrnDone(PurchasingItem $item, string $invoiceNumber, Carbon $grnDate, ?string $doneNotes = null): PurchasingItem
    {
        $oldStatus = $item->status;
        $created = $item->created_at ?: now();
        $cycle = (int) $grnDate->copy()->startOfDay()->diffInDays($created->copy()->startOfDay());

        $item->update([
            'invoice_number' => $invoiceNumber,
            'grn_date' => $grnDate->toDateString(),
            'proc_cycle_days' => $cycle,
            'status' => 'done',
            'status_changed_at' => now(),
            'status_changed_by' => auth()->id(),
            'done_notes' => $doneNotes,
        ]);

        $item->approvalRequest->refreshPurchasingStatus();

        // New merged final action
        \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_invoice_grn_done');
        // Compatibility: old split actions
        \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_invoice');
        \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_done');

        if ($oldStatus !== 'done') {
            $this->notificationService->notifyPurchasingStatusChange($item, $oldStatus, 'done');
        }

        return $item->refresh();
    }

    /**
     * Mark item done (requires preferred vendor and GRN ideally).
     */
    public function markDone(PurchasingItem $item, ?string $notes = null): PurchasingItem
    {
        $oldStatus = $item->status;

        $item->update([
            'status' => 'done',
            'status_changed_at' => now(),
            'status_changed_by' => auth()->id(),
            'done_notes' => $notes,
        ]);
        $item->approvalRequest->refreshPurchasingStatus();

        \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_done');
        // Compatibility: if workflow uses merged final step
        \App\Models\ApprovalItemStep::syncPurchasingStep($item->approval_request_id, $item->master_item_id, 'purchasing_invoice_grn_done');

        if ($oldStatus !== 'done') {
            $this->notificationService->notifyPurchasingStatusChange($item, $oldStatus, 'done');
        }

        return $item;
    }
}
