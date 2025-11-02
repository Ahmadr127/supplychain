<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalItemStep;
use App\Models\PurchasingItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalItemApprovalController extends Controller
{
    /**
     * Approve a specific item at current pending step
     */
    public function approve(Request $request, ApprovalRequest $approvalRequest, ApprovalRequestItem $item)
    {
        $request->validate([
            'comments' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            // Get current pending step for this item
            $currentStep = $item->getCurrentPendingStep();

            if (!$currentStep) {
                return back()->withErrors(['error' => 'Tidak ada step yang perlu di-approve untuk item ini.']);
            }

            // Check authorization
            if (!$currentStep->canApprove(auth()->id())) {
                return back()->withErrors(['error' => 'Anda tidak memiliki akses untuk approve item ini.']);
            }

            // Mark current step as approved
            $currentStep->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'comments' => $request->comments,
            ]);

            Log::info('Item step approved', [
                'item_id' => $item->id,
                'step_number' => $currentStep->step_number,
                'approver_id' => auth()->id(),
            ]);

            // Check if there are more steps
            $nextStep = ApprovalItemStep::where('approval_request_id', $approvalRequest->id)
                ->where('master_item_id', $item->master_item_id)
                ->where('step_number', '>', $currentStep->step_number)
                ->where('status', 'pending')
                ->orderBy('step_number')
                ->first();

            if (!$nextStep) {
                // This was the last step - mark item as fully approved
                $item->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                // Create purchasing item immediately
                $this->createPurchasingItem($item);

                Log::info('Item fully approved', ['item_id' => $item->id]);
            } else {
                // Move to next step
                $item->update(['status' => 'on progress']);
            }

            // Aggregate request status
            $this->aggregateRequestStatus($approvalRequest);

            DB::commit();

            return back()->with('success', 'Item berhasil di-approve!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Item approval failed', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Gagal approve item: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject a specific item at current pending step
     */
    public function reject(Request $request, ApprovalRequest $approvalRequest, ApprovalRequestItem $item)
    {
        $request->validate([
            'comments' => 'required|string|max:1000',
            'rejected_reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Get current pending step
            $currentStep = $item->getCurrentPendingStep();

            if (!$currentStep) {
                return back()->withErrors(['error' => 'Tidak ada step yang perlu di-reject untuk item ini.']);
            }

            // Check authorization
            if (!$currentStep->canApprove(auth()->id())) {
                return back()->withErrors(['error' => 'Anda tidak memiliki akses untuk reject item ini.']);
            }

            // Mark current step as rejected
            $currentStep->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'comments' => $request->comments,
            ]);

            // Mark item as rejected
            $item->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejected_reason' => $request->rejected_reason,
            ]);

            Log::info('Item rejected', [
                'item_id' => $item->id,
                'step_number' => $currentStep->step_number,
                'approver_id' => auth()->id(),
                'reason' => $request->rejected_reason,
            ]);

            // Aggregate request status (will mark request as rejected if any item rejected)
            $this->aggregateRequestStatus($approvalRequest);

            DB::commit();

            return back()->with('success', 'Item berhasil di-reject.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Item rejection failed', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Gagal reject item: ' . $e->getMessage()]);
        }
    }

    /**
     * Create purchasing item when item is approved
     */
    private function createPurchasingItem(ApprovalRequestItem $item): void
    {
        // Check if purchasing item already exists
        $exists = PurchasingItem::where('approval_request_id', $item->approval_request_id)
            ->where('master_item_id', $item->master_item_id)
            ->exists();

        if (!$exists) {
            PurchasingItem::create([
                'approval_request_id' => $item->approval_request_id,
                'master_item_id' => $item->master_item_id,
                'quantity' => $item->quantity,
                'status' => 'unprocessed',
            ]);

            Log::info('Purchasing item created', [
                'item_id' => $item->id,
                'master_item_id' => $item->master_item_id,
            ]);
        }
    }

    /**
     * Set item step back to pending (reset approval)
     */
    public function setPending(Request $request, ApprovalRequest $approvalRequest, ApprovalRequestItem $item)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Get current pending step
            $currentStep = $item->getCurrentPendingStep();

            if (!$currentStep) {
                return back()->withErrors(['error' => 'Tidak ada step yang dapat di-reset untuk item ini.']);
            }

            // Check authorization - only approver can reset
            if (!$currentStep->canApprove(auth()->id())) {
                return back()->withErrors(['error' => 'Anda tidak memiliki akses untuk reset item ini.']);
            }

            // Reset current step to pending
            $currentStep->update([
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'comments' => 'Reset to pending: ' . $request->reason,
            ]);

            // Update item status to pending
            $item->update([
                'status' => 'pending',
                'approved_by' => null,
                'approved_at' => null,
                'rejected_reason' => null,
            ]);

            Log::info('Item reset to pending', [
                'item_id' => $item->id,
                'step_number' => $currentStep->step_number,
                'reset_by' => auth()->id(),
                'reason' => $request->reason,
            ]);

            // Aggregate request status
            $this->aggregateRequestStatus($approvalRequest);

            DB::commit();

            return back()->with('success', 'Item berhasil di-reset ke status pending.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Item reset to pending failed', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['error' => 'Gagal reset item: ' . $e->getMessage()]);
        }
    }

    /**
     * Aggregate item statuses to update request status
     */
    private function aggregateRequestStatus(ApprovalRequest $approvalRequest): void
    {
        $items = $approvalRequest->items;

        // If ANY item is rejected, mark request as rejected
        if ($items->contains('status', 'rejected')) {
            $approvalRequest->update(['status' => 'rejected']);
            Log::info('Request marked as rejected', ['request_id' => $approvalRequest->id]);
            return;
        }

        // If ALL items are approved, mark request as approved
        if ($items->every(fn($i) => $i->status === 'approved')) {
            $approvalRequest->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            Log::info('Request fully approved', ['request_id' => $approvalRequest->id]);
            return;
        }

        // If ANY item is pending or on progress, keep request as on progress
        if ($items->some(fn($i) => in_array($i->status, ['pending', 'on progress']))) {
            $approvalRequest->update(['status' => 'on progress']);
            return;
        }

        // Default: pending
        $approvalRequest->update(['status' => 'pending']);
    }
}
