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
        $needsAttachmentUpload = false;

        if ($currentStep) {
            $isInputPriceStep  = $currentStep->hasRequiredAction('input_price');
            $isSelectCapexStep = $currentStep->hasRequiredAction('select_capex');

            $needsPriceInput = $isInputPriceStep && (is_null($item->unit_price) || $item->unit_price <= 0);
            $needsCapexInput = $isSelectCapexStep || $isInputPriceStep;
            
            if ($currentStep->hasRequiredAction('verify_budget')) {
                $needsFsUpload = true;
            }

            // Upload lampiran (nullable — tidak wajib)
            // Fallback: If required_actions is missing but scope_process mentions "Lampiran", enable it.
            $needsAttachmentUpload = $currentStep->needsAttachmentUpload() ||
                ($currentStep->scope_process && str_contains(strtolower($currentStep->scope_process), 'lampiran'));
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'item_id'                => $item->id,
                'item_status'            => $item->status,
                'can_approve'            => $currentStep ? $currentStep->canApprove($userId) : false,
                'needs_price_input'      => $needsPriceInput,
                'needs_capex_input'      => $needsCapexInput,
                'needs_fs_upload'        => $needsFsUpload,
                'needs_attachment_upload'=> $needsAttachmentUpload,
                'current_step' => $currentStep ? [
                    'id'              => $currentStep->id,
                    'step_number'     => $currentStep->step_number,
                    'step_name'       => $currentStep->step_name,
                    'step_phase'      => $currentStep->step_phase,
                    'required_action' => $currentStep->required_action,
                    'required_actions'=> $currentStep->getAllRequiredActions(),
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
     *   - fs_document    : file (pdf/doc/docx) required when required_action=verify_budget
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
        $rules = [
            'comments'          => 'nullable|string|max:1000',
            'step_attachments'  => 'nullable|array',
            'step_attachments.*'=> 'nullable|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png',
        ];

        if ($currentStep->hasRequiredAction('input_price') && (is_null($item->unit_price) || $item->unit_price <= 0)) {
            $rules['unit_price'] = 'required|string|min:1';
        }

        if ($currentStep->hasRequiredAction('verify_budget')) {
            $rules['fs_document'] = 'required|file|mimes:pdf,doc,docx|max:5120';
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            // Handle price input
            if ($currentStep->hasRequiredAction('input_price') && $request->has('unit_price')) {
                $rawPrice = $request->unit_price;
                if (is_numeric($rawPrice)) {
                    $unitPrice = (float) $rawPrice;
                } else {
                    $unitPrice = (float) str_replace('.', '', $rawPrice);
                }

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
            if ($currentStep->hasRequiredAction('verify_budget') && $request->hasFile('fs_document')) {
                $fsPath = $request->file('fs_document')->store('fs_documents', 'public');
                $item->update(['fs_document' => $fsPath]);
            }

            // Handle step attachments (nullable — simpan jika ada file)
            if ($request->hasFile('step_attachments')) {
                $attachmentService = app(\App\Services\AttachmentService::class);
                $files = $request->file('step_attachments');
                $attachmentService->storeStepAttachments(
                    $currentStep,
                    is_array($files) ? $files : [$files],
                    Auth::id()
                );
            }

            // Mark step approved
            $currentStep->update([
                'status'      => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'comments'    => $request->comments,
            ]);

            // Re-evaluate workflow when price was just inputted
            if ($currentStep->hasRequiredAction('input_price')) {
                try {
                    // Refresh item so WorkflowService reads the updated total_price
                    $item->refresh();
                    $item->load('approvalRequest');
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
                // Ambil step berikutnya berdasarkan urutan — putuskan dari phase-nya.
                $nextStep = ApprovalItemStep::where('approval_request_id', $approvalRequest->id)
                    ->where('approval_request_item_id', $item->id)
                    ->where('step_number', '>', $currentStep->step_number)
                    ->whereNotIn('status', ['approved', 'skipped'])
                    ->orderBy('step_number')
                    ->first();

                if (!$nextStep) {
                    // Tidak ada step tersisa → fully approved
                    $item->update(['status' => 'approved', 'approved_by' => Auth::id(), 'approved_at' => now()]);
                    app(\App\Services\NotificationService::class)->notifyPurchasingStaff($item);

                } elseif (in_array($nextStep->step_phase, ['purchasing', 'release'])) {
                    // Step berikutnya adalah purchasing/release → masuk fase purchasing
                    $item->update(['status' => 'in_purchasing']);
                    app(\App\Services\NotificationService::class)->notifyPurchasingStaff($item);

                } else {
                    // Step berikutnya adalah approval → lanjut proses approval
                    $item->update(['status' => 'on progress']);
                }
            }

            $approvalRequest->refreshStatus();
            DB::commit();

            try {
                $item->refresh();
                if ($item->status === 'approved') {
                    // Check if entire request is approved
                    if ($approvalRequest->status === 'approved') {
                        app(\App\Services\NotificationService::class)->notifyRequesterApproved($approvalRequest);
                    }
                } else if (!in_array($item->status, ['rejected', 'done', 'terpenuhi'])) {
                    // If not rejected or fully approved, notify next approvers
                    app(\App\Services\NotificationService::class)->notifyApprovers($approvalRequest);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send notification after API approval: ' . $e->getMessage());
            }

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
            'comments'        => 'nullable|string|max:1000',
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

            // Mark all remaining pending steps (after the rejected step) as 'skipped'
            // so the mobile app correctly shows them as locked/irrelevant instead of "Menunggu".
            ApprovalItemStep::where('approval_request_id', $approvalRequest->id)
                ->where('approval_request_item_id', $item->id)
                ->where('step_number', '>', $currentStep->step_number)
                ->whereIn('status', ['pending', 'pending_purchase'])
                ->update([
                    'status'      => 'skipped',
                    'skip_reason' => 'Item rejected at step ' . $currentStep->step_number . ' (' . $currentStep->step_name . ')',
                    'skipped_at'  => now(),
                    'skipped_by'  => Auth::id(),
                ]);

            $approvalRequest->refreshStatus();
            DB::commit();

            try {
                if ($approvalRequest->status === 'rejected') {
                    app(\App\Services\NotificationService::class)->notifyRequesterRejected($approvalRequest, $request->rejected_reason);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send notification after API rejection: ' . $e->getMessage());
            }

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
        $needsAttachmentUpload = false;

        if ($currentStep) {
            $isInputPriceStep  = $currentStep->hasRequiredAction('input_price');
            $isSelectCapexStep = $currentStep->hasRequiredAction('select_capex');

            $needsPriceInput = $isInputPriceStep && (is_null($item->unit_price) || $item->unit_price <= 0);
            $needsCapexInput = $isSelectCapexStep || $isInputPriceStep;
            
            if ($currentStep->hasRequiredAction('verify_budget')) {
                $needsFsUpload = true;
            }

            // Upload lampiran (nullable — tidak wajib)
            // Fallback: If required_actions is missing but scope_process mentions "Lampiran", enable it.
            $needsAttachmentUpload = $currentStep->needsAttachmentUpload() ||
                ($currentStep->scope_process && str_contains(strtolower($currentStep->scope_process), 'lampiran'));
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
            'needs_price_input'      => $needsPriceInput,
            'needs_capex_input'      => $needsCapexInput,
            'needs_fs_upload'        => $needsFsUpload,
            'needs_attachment_upload'=> $needsAttachmentUpload,
            'current_step'    => $currentStep ? [
                'id'              => $currentStep->id,
                'step_number'     => $currentStep->step_number,
                'step_name'       => $currentStep->step_name,
                'step_phase'      => $currentStep->step_phase,
                'required_action' => $currentStep->required_action,
                'required_actions'=> $currentStep->getAllRequiredActions(),
                'approver_type'   => $currentStep->approver_type,
                'status'          => $currentStep->status,
            ] : null,
            'steps' => $item->steps->map(fn($s) => [
                'id'              => $s->id,
                'step_number'     => $s->step_number,
                'step_name'       => $s->step_name,
                'step_phase'      => $s->step_phase,
                'required_action' => $s->required_action,
                'required_actions'=> $s->getAllRequiredActions(),
                'status'          => $s->status,
                'approved_by'     => $s->approver?->name,
                'approved_at'     => $s->approved_at,
                'comments'        => $s->comments,
                'rejected_reason' => $s->rejected_reason,
                'attachments'     => app(\App\Services\AttachmentService::class)
                                        ->formatAttachmentsForApi($s->attachments ?? collect()),
            ]),
        ];
    }

    /**
     * View FS Document (Inline if PDF)
     */
    public function viewFsDocument(ApprovalRequestItem $item)
    {
        $item->load('approvalRequest');

        if (empty($item->fs_document)) {
            return response()->json(['status' => 'error', 'message' => 'Dokumen FS tidak ditemukan.'], 404);
        }

        if (!Storage::disk('public')->exists($item->fs_document)) {
            return response()->json(['status' => 'error', 'message' => 'File fisik tidak ditemukan di server.'], 404);
        }

        $mime = Storage::disk('public')->mimeType($item->fs_document);
        $requestNumber = $item->approvalRequest->request_number ?? 'REQ-' . $item->approval_request_id;
        $filename = 'FS-' . $requestNumber . '-' . $item->id . '.' . pathinfo($item->fs_document, PATHINFO_EXTENSION);

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
    public function downloadFsDocument(ApprovalRequestItem $item)
    {
        $item->load('approvalRequest');

        if (empty($item->fs_document) || !Storage::disk('public')->exists($item->fs_document)) {
            abort(404, 'Dokumen FS tidak ditemukan.');
        }

        $requestNumber = $item->approvalRequest->request_number ?? 'REQ-' . $item->approval_request_id;
        $filename = 'FS-' . $requestNumber . '-' . $item->id . '.' . pathinfo($item->fs_document, PATHINFO_EXTENSION);
        return Storage::disk('public')->download($item->fs_document, $filename);
    }

    /**
     * View a step attachment inline via API.
     * GET /api/step-attachments/{attachment}/view
     */
    public function viewStepAttachment(\App\Models\ApprovalItemStepAttachment $attachment)
    {
        if (!Storage::disk('public')->exists($attachment->path)) {
            return response()->json(['status' => 'error', 'message' => 'Lampiran tidak ditemukan.'], 404);
        }

        $mime = Storage::disk('public')->mimeType($attachment->path);
        $filename = $attachment->original_name;

        $stream = Storage::disk('public')->readStream($attachment->path);
        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type'        => $mime ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }

    /**
     * Download a step attachment via API.
     * GET /api/step-attachments/{attachment}/download
     */
    public function downloadStepAttachment(\App\Models\ApprovalItemStepAttachment $attachment)
    {
        if (!Storage::disk('public')->exists($attachment->path)) {
            return response()->json(['status' => 'error', 'message' => 'Lampiran tidak ditemukan.'], 404);
        }

        return Storage::disk('public')->download($attachment->path, $attachment->original_name);
    }
}
