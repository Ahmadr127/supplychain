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
use Illuminate\Support\Facades\Storage;

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

        $item->load(['masterItem', 'steps.approver', 'capexItem']);
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
            $isInputPriceStep  = $currentStep->required_action === 'input_price';
            $isSelectCapexStep = $currentStep->required_action === 'select_capex';

            $needsPriceInput = $isInputPriceStep && (is_null($item->unit_price) || $item->unit_price <= 0);

            // Pada fase input harga, user bisa memilih sumber anggaran:
            // - Gunakan Capex  → pilih capex_item_id
            // - Non‑Capex      → input manual tanpa capex_item_id
            // Di mobile, dropdown Capex perlu muncul di langkah input_price.
            $needsCapexInput = $isSelectCapexStep || $isInputPriceStep;
            
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

            $item->load(['masterItem', 'steps.approver']);

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

            $item->load(['masterItem', 'steps.approver']);

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
            $isInputPriceStep  = $currentStep->required_action === 'input_price';
            $isSelectCapexStep = $currentStep->required_action === 'select_capex';

            $needsPriceInput = $isInputPriceStep && (is_null($item->unit_price) || $item->unit_price <= 0);

            // Sama seperti di status(): pada langkah input_price, dropdown Capex
            // perlu tersedia supaya approver bisa memilih antara Capex vs Non‑Capex.
            $needsCapexInput = $isSelectCapexStep || $isInputPriceStep;
            
            if ($currentStep->required_action === 'verify_budget') {
                $total = $item->quantity * ($item->unit_price ?? 0);
                $threshold = $currentStep->condition_value ?? \App\Models\Setting::get('fs_threshold_per_item', 100000000);
                $needsFsUpload = $total >= $threshold;
            }
        }

        // Informasi sumber anggaran saat ini:
        // - 'capex'     jika sudah terhubung ke capex_item_id
        // - 'non_capex' jika belum terhubung ke Capex (manual / OpEx)
        $fundingSource = $item->capex_item_id ? 'capex' : 'non_capex';

        $capex = $item->capexItem;

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
            'funding_source'  => $fundingSource,
            'capex_item'      => $capex ? [
                'id'               => $capex->id,
                'capex_id_number'  => $capex->capex_id_number,
                'item_name'        => $capex->item_name,
                'category'         => $capex->category,
                'capex_type'       => $capex->capex_type,
                'priority_scale'   => $capex->priority_scale,
                'budget_amount'    => (float) $capex->budget_amount,
                'used_amount'      => (float) $capex->used_amount,
                'pending_amount'   => (float) $capex->pending_amount,
                'available_amount' => $capex->available_amount,
            ] : null,
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
                'approved_by'     => $s->approver?->name,
                'approved_at'     => $s->approved_at,
                'comments'        => $s->comments,
                'rejected_reason' => $s->rejected_reason,
            ]),
        ];
    }

    /**
     * View FS Document (Inline if PDF)
     */
    public function viewFsDocument(ApprovalRequest $approvalRequest, ApprovalRequestItem $item)
    {
        if ($item->approval_request_id !== $approvalRequest->id) {
            return response()->json(['status' => 'error', 'message' => 'Item tidak cocok dengan request.'], 403);
        }

        if (empty($item->fs_document)) {
            return response()->json(['status' => 'error', 'message' => 'Dokumen FS tidak ditemukan.'], 404);
        }

        if (!Storage::disk('public')->exists($item->fs_document)) {
            return response()->json(['status' => 'error', 'message' => 'File fisik tidak ditemukan di server.'], 404);
        }

        $mime = Storage::disk('public')->mimeType($item->fs_document);
        $filename = 'FS-' . $approvalRequest->request_number . '-' . $item->id . '.' . pathinfo($item->fs_document, PATHINFO_EXTENSION);

        if ($mime !== 'application/pdf') {
            return Storage::disk('public')->download($item->fs_document, $filename);
        }

        $stream = Storage::disk('public')->readStream($item->fs_document);
        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }

    /**
     * Download FS Document
     */
    public function downloadFsDocument(ApprovalRequest $approvalRequest, ApprovalRequestItem $item)
    {
        if ($item->approval_request_id !== $approvalRequest->id) {
            abort(403, 'Item tidak cocok dengan request.');
        }

        if (empty($item->fs_document) || !Storage::disk('public')->exists($item->fs_document)) {
            abort(404, 'Dokumen FS tidak ditemukan.');
        }

        $filename = 'FS-' . $approvalRequest->request_number . '-' . $item->id . '.' . pathinfo($item->fs_document, PATHINFO_EXTENSION);
        return Storage::disk('public')->download($item->fs_document, $filename);
    }
}
