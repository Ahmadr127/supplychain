<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalItemStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Approval Item API — Per-item approval & rejection actions + status check.
 *
 * Routes:
 *   GET  /api/approval-requests/{reqId}/items/{itemId}          – Item detail
 *   GET  /api/approval-requests/{reqId}/items/{itemId}/status   – Quick status
 *   POST /api/approval-requests/{reqId}/items/{itemId}/approve  – Approve item
 *   POST /api/approval-requests/{reqId}/items/{itemId}/reject   – Reject item
 */
class ApprovalItemApiController extends Controller
{
    /**
     * GET /api/approval-requests/{reqId}/items/{itemId}
     */
    public function show(ApprovalRequest $approvalRequest, ApprovalRequestItem $item)
    {
        if ($item->approval_request_id !== $approvalRequest->id) {
            return response()->json(['status' => 'error', 'message' => 'Item tidak ditemukan dalam request ini.'], 404);
        }

        $item->load(['masterItem', 'steps.approverUser']);
        $currentStep = $item->getCurrentPendingStep();
        $userId      = Auth::id();

        return response()->json([
            'status' => 'success',
            'data'   => $this->formatItem($item, $currentStep, $userId),
        ]);
    }

    /**
     * GET /api/approval-requests/{reqId}/items/{itemId}/status
     * Lightweight status check — returns item status + pending step summary.
     */
    public function status(ApprovalRequest $approvalRequest, ApprovalRequestItem $item)
    {
        if ($item->approval_request_id !== $approvalRequest->id) {
            return response()->json(['status' => 'error', 'message' => 'Item tidak ditemukan.'], 404);
        }

        $currentStep = $item->getCurrentPendingStep();
        $userId      = Auth::id();

        $needsPriceInput = false;
        $needsCapexInput = false;
        $needsFsUpload = false;

        if ($currentStep) {
            $needsPriceInput = $currentStep->required_action === 'input_price' && (is_null($item->unit_price) || $item->unit_price <= 0);
            $needsCapexInput = $currentStep->required_action === 'select_capex';
            
            if ($currentStep->required_action === 'verify_budget') {
                $total = $item->quantity * ($item->unit_price ?? 0);
                $threshold = $currentStep->condition_value ?? \App\Models\Setting::get('fs_threshold_per_item', 100000000);
                $needsFsUpload = $total >= $threshold;
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'item_id'      => $item->id,
                'item_status'  => $item->status,
                'can_approve'  => $currentStep ? $currentStep->canApprove($userId) : false,
                'needs_price_input' => $needsPriceInput,
                'needs_capex_input' => $needsCapexInput,
                'needs_fs_upload' => $needsFsUpload,
                'current_step' => $currentStep ? [
                    'id'              => $currentStep->id,
                    'step_number'     => $currentStep->step_number,
                    'step_name'       => $currentStep->step_name,
                    'step_phase'      => $currentStep->step_phase,
                    'required_action' => $currentStep->required_action,
                ] : null,
            ],
        ]);
    }

    /**
     * POST /api/approval-requests/{reqId}/items/{itemId}/approve
     *
     * Body:
     *   - comments       : nullable|string
     *   - unit_price     : required if step->required_action == 'input_price' (string, e.g. "1.500.000")
     *   - capex_item_id  : nullable
     *   - fs_document    : file (pdf/doc/docx) required if verify_budget threshold crossed
     */
    public function approve(Request $request, ApprovalRequest $approvalRequest, ApprovalRequestItem $item)
    {
        if ($item->approval_request_id !== $approvalRequest->id) {
            return response()->json(['status' => 'error', 'message' => 'Item tidak ditemukan.'], 404);
        }

        $currentStep = $item->getCurrentPendingStep();

        if (!$currentStep) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada step yang perlu di-approve.'], 422);
        }

        if (!$currentStep->canApprove(Auth::id())) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki akses untuk approve item ini.'], 403);
        }

        // Build validation rules based on required_action
        $rules = ['comments' => 'nullable|string|max:1000'];

        if ($currentStep->required_action === 'input_price' && (is_null($item->unit_price) || $item->unit_price <= 0)) {
            $rules['unit_price'] = 'required|string|min:1';
        }

        if ($currentStep->required_action === 'verify_budget') {
            $total     = $item->quantity * ($item->unit_price ?? 0);
            $threshold = $currentStep->condition_value ?? \App\Models\Setting::get('fs_threshold_per_item', 100000000);
            if ($total >= $threshold) {
                $rules['fs_document'] = 'required|file|mimes:pdf,doc,docx|max:5120';
            }
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            // Handle price input
            if ($currentStep->required_action === 'input_price' && $request->has('unit_price')) {
                $unitPrice = (float) str_replace('.', '', $request->unit_price);

                if ($unitPrice <= 0) {
                    return response()->json(['status' => 'error', 'message' => 'Harga harus lebih dari 0.'], 422);
                }

                $item->update([
                    'unit_price'        => $unitPrice,
                    'total_price'       => $item->quantity * $unitPrice,
                    'capex_item_id'     => $request->capex_item_id ?? null,
                    'approved_price_by' => Auth::id(),
                    'approved_price_at' => now(),
                ]);
            }

            // Handle FS document
            if ($currentStep->required_action === 'verify_budget' && $request->hasFile('fs_document')) {
                $fsPath = $request->file('fs_document')->store('fs_documents', 'public');
                $item->update(['fs_document' => $fsPath]);
            }

            // Mark step approved
            $currentStep->update([
                'status'      => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'comments'    => $request->comments,
            ]);

            // Re-evaluate workflow when price was just inputted
            if ($currentStep->required_action === 'input_price') {
                try {
                    app(\App\Services\WorkflowService::class)->reevaluateWorkflow($item);
                    $currentStep = ApprovalItemStep::find($currentStep->id) ?? $currentStep;
                } catch (\Exception $e) {
                    Log::warning('Workflow re-evaluation failed: ' . $e->getMessage());
                }
            }

            // Advance item status
            $isReleasePhase = ($currentStep->step_phase ?? 'approval') === 'release';

            if ($isReleasePhase) {
                $nextRelease = ApprovalItemStep::where('approval_request_id', $approvalRequest->id)
                    ->where('approval_request_item_id', $item->id)
                    ->where('step_number', '>', $currentStep->step_number)
                    ->where('step_phase', 'release')
                    ->whereIn('status', ['pending', 'pending_purchase'])
                    ->orderBy('step_number')->first();

                if (!$nextRelease) {
                    $item->update(['status' => 'approved', 'approved_by' => Auth::id(), 'approved_at' => now()]);
                } else {
                    if ($nextRelease->status === 'pending_purchase') $nextRelease->update(['status' => 'pending']);
                    $item->update(['status' => 'in_release']);
                }
            } else {
                $nextApproval = ApprovalItemStep::where('approval_request_id', $approvalRequest->id)
                    ->where('approval_request_item_id', $item->id)
                    ->where('step_number', '>', $currentStep->step_number)
                    ->where('status', 'pending')
                    ->where(fn($q) => $q->where('step_phase', 'approval')->orWhereNull('step_phase'))
                    ->orderBy('step_number')->first();

                if (!$nextApproval) {
                    $hasRelease = ApprovalItemStep::where('approval_request_id', $approvalRequest->id)
                        ->where('approval_request_item_id', $item->id)
                        ->where('step_phase', 'release')->exists();

                    $item->update($hasRelease
                        ? ['status' => 'in_purchasing']
                        : ['status' => 'approved', 'approved_by' => Auth::id(), 'approved_at' => now()]
                    );
                } else {
                    $item->update(['status' => 'on progress']);
                }
            }

            $approvalRequest->refreshStatus();
            DB::commit();

            $item->load(['masterItem', 'steps.approverUser']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Item berhasil di-approve.',
                'data'    => $item,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API Item approval failed: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Gagal approve item: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/approval-requests/{reqId}/items/{itemId}/reject
     *
     * Body:
     *   - comments        : required|string
     *   - rejected_reason : required|string
     */
    public function reject(Request $request, ApprovalRequest $approvalRequest, ApprovalRequestItem $item)
    {
        if ($item->approval_request_id !== $approvalRequest->id) {
            return response()->json(['status' => 'error', 'message' => 'Item tidak ditemukan.'], 404);
        }

        $request->validate([
            'comments'        => 'required|string|max:1000',
            'rejected_reason' => 'required|string|max:500',
        ]);

        $currentStep = $item->getCurrentPendingStep();

        if (!$currentStep) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada step yang perlu di-reject.'], 422);
        }

        if (!$currentStep->canApprove(Auth::id())) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki akses untuk reject item ini.'], 403);
        }

        try {
            DB::beginTransaction();

            $currentStep->update([
                'status'          => 'rejected',
                'approved_by'     => Auth::id(),
                'approved_at'     => now(),
                'rejected_reason' => $request->rejected_reason,
                'comments'        => $request->comments,
            ]);

            $item->update([
                'status'          => 'rejected',
                'approved_by'     => Auth::id(),
                'approved_at'     => now(),
                'rejected_reason' => $request->rejected_reason,
            ]);

            $approvalRequest->refreshStatus();
            DB::commit();

            $item->load(['masterItem', 'steps.approverUser']);

            return response()->json([
                'status'  => 'success',
                'message' => 'Item berhasil di-reject.',
                'data'    => $item,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Gagal reject item: ' . $e->getMessage()], 500);
        }
    }

    private function formatItem(ApprovalRequestItem $item, $currentStep, int $userId): array
    {
        $needsPriceInput = false;
        $needsCapexInput = false;
        $needsFsUpload = false;

        if ($currentStep) {
            $needsPriceInput = $currentStep->required_action === 'input_price' && (is_null($item->unit_price) || $item->unit_price <= 0);
            $needsCapexInput = $currentStep->required_action === 'select_capex';
            
            if ($currentStep->required_action === 'verify_budget') {
                $total = $item->quantity * ($item->unit_price ?? 0);
                $threshold = $currentStep->condition_value ?? \App\Models\Setting::get('fs_threshold_per_item', 100000000);
                $needsFsUpload = $total >= $threshold;
            }
        }

        return [
            'id'              => $item->id,
            'master_item'     => $item->masterItem,
            'quantity'        => $item->quantity,
            'unit'            => $item->unit,
            'unit_price'      => $item->unit_price,
            'total_price'     => $item->total_price,
            'status'          => $item->status,
            'fs_document'     => $item->fs_document,
            'capex_item_id'   => $item->capex_item_id,
            'rejected_reason' => $item->rejected_reason,
            'can_approve'     => $currentStep ? $currentStep->canApprove($userId) : false,
            'needs_price_input' => $needsPriceInput,
            'needs_capex_input' => $needsCapexInput,
            'needs_fs_upload' => $needsFsUpload,
            'current_step'    => $currentStep ? [
                'id'              => $currentStep->id,
                'step_number'     => $currentStep->step_number,
                'step_name'       => $currentStep->step_name,
                'step_phase'      => $currentStep->step_phase,
                'required_action' => $currentStep->required_action,
                'approver_type'   => $currentStep->approver_type,
                'status'          => $currentStep->status,
            ] : null,
            'steps' => $item->steps->map(fn($s) => [
                'id'              => $s->id,
                'step_number'     => $s->step_number,
                'step_name'       => $s->step_name,
                'step_phase'      => $s->step_phase,
                'required_action' => $s->required_action,
                'status'          => $s->status,
                'approved_by'     => $s->approverUser?->name,
                'approved_at'     => $s->approved_at,
                'comments'        => $s->comments,
                'rejected_reason' => $s->rejected_reason,
            ]),
        ];
    }
}
