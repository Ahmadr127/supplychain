<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Approval Request API — Read only (list & detail).
 *
 * Routes:
 *   GET /api/approval-requests               – All requests (filterable)
 *   GET /api/approval-requests/mine          – Own requests
 *   GET /api/approval-requests/pending       – Requests with pending steps for me
 *   GET /api/approval-requests/{id}          – Request detail with all items + steps
 */
class ApprovalRequestApiController extends Controller
{
    private function mapSingleStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }
        $normalized = strtolower(trim($status));
        if (in_array($normalized, ['on progress', 'in_purchasing', 'in_release'])) {
            return 'approved';
        }
        return $status;
    }

    private function transformRequestStatus(ApprovalRequest $req): ApprovalRequest
    {
        $req->status = $this->mapSingleStatus($req->status);
        
        if ($req->relationLoaded('items')) {
            $req->items->each(function ($item) {
                $item->status = $this->mapSingleStatus($item->status);
            });
        }
        
        return $req;
    }

    private function normalizeStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        return match (strtolower(trim($status))) {
            'all' => null,
            'fulfilled', 'terpenuhi', 'released', 'on progress', 'in_purchasing', 'in_release' => 'approved',
            default => strtolower(trim($status)),
        };
    }

    /**
     * GET /api/approval-requests
     * All approval requests. Supports ?search=, ?status=, ?date_from=, ?date_to=, ?per_page=
     */
    public function index(Request $request)
    {
        $query = ApprovalRequest::with(['requester', 'items'])
            ->orderBy('created_at', 'desc');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('request_number', 'like', "%{$request->search}%")
                  ->orWhereHas('requester', fn($u) => $u->where('name', 'like', "%{$request->search}%"));
            });
        }

        $requestedStatus = $this->normalizeStatus($request->input('status'));
        if ($requestedStatus) {
            if ($requestedStatus === 'approved') {
                $query->whereIn('status', ['approved', 'on progress', 'in_purchasing', 'in_release']);
            } else {
                $query->where('status', $requestedStatus);
            }
        }
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('created_at', '<=', $request->date_to);

        $paginator = $query->paginate($request->get('per_page', 15));
        $paginator->getCollection()->transform(function ($req) {
            return $this->transformRequestStatus($req);
        });

        return response()->json([
            'status' => 'success',
            'data'   => $paginator,
        ]);
    }

    /**
     * GET /api/approval-requests/mine
     * Only requests belonging to the authenticated user.
     */
    public function myRequests(Request $request)
    {
        $query = ApprovalRequest::with(['requester', 'items.masterItem'])
            ->where('requester_id', Auth::id())
            ->orderBy('created_at', 'desc');

        $requestedStatus = $this->normalizeStatus($request->input('status'));
        if ($requestedStatus) {
            if ($requestedStatus === 'approved') {
                $query->whereIn('status', ['approved', 'on progress', 'in_purchasing', 'in_release']);
            } else {
                $query->where('status', $requestedStatus);
            }
        }
        if ($request->search) $query->where('request_number', 'like', "%{$request->search}%");

        $paginator = $query->paginate($request->get('per_page', 15));
        $paginator->getCollection()->transform(function ($req) {
            return $this->transformRequestStatus($req);
        });

        return response()->json([
            'status' => 'success',
            'data'   => $paginator,
        ]);
    }

    public function pending(Request $request)
    {
        $user = Auth::user();
        $userId = $user->id;
        $requestedStatus = $this->normalizeStatus($request->input('status'));

        $allRequests = ApprovalRequest::with(['requester', 'items.masterItem', 'items.steps.approver'])
            ->whereHas('items.steps', function ($q) use ($user) {
                // Only consider approval and release phase steps for "pending approvals".
                $q->where(function ($phaseQ) {
                    $phaseQ->whereIn('step_phase', ['approval', 'release'])->orWhereNull('step_phase');
                });
                // No "eligibility" pre-filter here:
                // - approver resolution can depend on department manager relationships and allocation department
                // - keep SQL broad, then use ApprovalItemStep::canApprove() in PHP filtering below
                // Include steps that are pending (actionable candidates) OR already actioned by this user.
                $q->where(function ($sq) use ($user) {
                    $sq->where('status', 'pending')->orWhere('approved_by', $user->id);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $filtered = $allRequests->map(function ($req) use ($userId) {
            $myItems = $req->items->map(function ($item) use ($userId) {
                // Get the true current pending step in the sequential workflow.
                $step = $item->getCurrentPendingStep();
                $isPendingForMe = $step && in_array($step->step_phase ?? 'approval', ['approval', 'release']) && $step->canApprove($userId);
                
                $hasActioned = $item->steps->contains(function ($s) use ($userId) {
                    $phase = $s->step_phase ?? 'approval';
                    return (int) $s->approved_by === (int) $userId
                        && in_array($phase, ['approval', 'release'])
                        && in_array($s->status, ['approved', 'rejected'], true);
                });
                
                if (!($isPendingForMe || $hasActioned)) {
                    return null;
                }

                // Override display status based on the user's interaction point
                // if it's not natively fully approved or rejected
                if (!in_array($item->status, ['approved', 'rejected', 'done', 'terpenuhi', 'fulfilled', 'completed', 'released'])) {
                    if ($isPendingForMe) {
                        $item->status = 'pending';
                    } elseif ($hasActioned) {
                        $item->status = 'approved'; // User has done their part, marked as approved from their perspective
                    } else {
                        $item->status = $this->mapSingleStatus($item->status);
                    }
                } else {
                    $item->status = $this->mapSingleStatus($item->status);
                }
                
                $item->setAttribute('can_approve', (bool) $isPendingForMe);

                return $item;
            })->filter()->values();

            if ($myItems->isEmpty()) return null;

            $isPending = $myItems->contains(function ($i) use ($userId) {
                $step = $i->getCurrentPendingStep();
                return $step && in_array($step->step_phase ?? 'approval', ['approval', 'release']) && $step->canApprove($userId);
            });
            $isRejected = $myItems->contains(
                fn($i) => $i->steps
                    ->where('approved_by', $userId)
                    ->where('status', 'rejected')
                    ->filter(fn($s) => in_array(($s->step_phase ?? 'approval'), ['approval', 'release']))
                    ->isNotEmpty()
            );
            $isActionedByMe = $myItems->contains(fn($i) => $i->status === 'approved');

            $computedStatus = $isPending ? 'pending' : ($isRejected ? 'rejected' : 'approved');

            $req->status = $this->mapSingleStatus($computedStatus);
            $req->setRelation('items', $myItems);
            return $req;
        })->filter();

        if ($requestedStatus) {
            $filtered = $filtered->where('status', $requestedStatus);
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $filtered = $filtered->filter(function($req) use ($search) {
                return str_contains(strtolower($req->request_number), $search)
                    || str_contains(strtolower($req->requester->name ?? ''), $search);
            });
        }

        $filtered = $filtered->values();

        $perPage = (int)$request->get('per_page', 15);
        $page = (int)$request->get('page', 1);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'status' => 'success',
            'data'   => $paginator,
        ]);
    }

    /**
     * GET /api/approval-requests/{id}
     * Full detail with all items and their approval steps.
     */
    public function show(ApprovalRequest $approvalRequest)
    {
        $approvalRequest->load([
            'requester',
            'items.masterItem.itemType',
            'items.masterItem.itemCategory',
            'items.steps.approver',
            'items.capexItem',
            'items.allocationDepartment',
        ]);

        $userId = Auth::id();
        $requester = $approvalRequest->requester;
        $primaryDept = $requester
            ? $requester->departments()->wherePivot('is_primary', true)->first()
            : null;

        $items = $approvalRequest->items->map(function ($item) use ($userId) {
            $currentStep = $item->getCurrentPendingStep();

            $needsPriceInput = false;
            $needsCapexInput = false;
            $needsFsUpload   = false;
            $needsAttachmentUpload = false;

            if ($currentStep) {
                $isInputPriceStep  = $currentStep->required_action === 'input_price';
                $isSelectCapexStep = $currentStep->required_action === 'select_capex';

                $needsPriceInput = $isInputPriceStep
                    && (is_null($item->unit_price) || $item->unit_price <= 0);

                // Sama dengan API per-item: pada langkah input_price, dropdown Capex
                // perlu tersedia supaya approver bisa memilih antara Capex vs Non‑Capex.
                $needsCapexInput = $isSelectCapexStep || $isInputPriceStep;

                if ($currentStep->required_action === 'verify_budget') {
                    $needsFsUpload = true;
                }

                $needsAttachmentUpload = $currentStep->needsAttachmentUpload() ||
                    ($currentStep->scope_process && str_contains(strtolower($currentStep->scope_process), 'lampiran'));
            }

            $fundingSource = $item->capex_item_id ? 'capex' : 'non_capex';
            $capex         = $item->capexItem;

            $displayStatus = $item->status;
            if (!in_array($displayStatus, ['approved', 'rejected', 'done', 'terpenuhi', 'fulfilled', 'completed', 'released'])) {
                $isPendingForMe = $currentStep && $currentStep->canApprove($userId);
                $hasActioned = $item->steps->contains(function ($s) use ($userId) {
                    $phase = $s->step_phase ?? 'approval';
                    return (int) $s->approved_by === (int) $userId
                        && in_array($phase, ['approval', 'release'])
                        && in_array($s->status, ['approved', 'rejected'], true);
                });
                
                if ($hasActioned && !$isPendingForMe) {
                    $displayStatus = 'approved';
                } else {
                    $displayStatus = $this->mapSingleStatus($displayStatus);
                }
            } else {
                $displayStatus = $this->mapSingleStatus($displayStatus);
            }
            $pi = $item->purchasingItem();
            $psCode = $pi ? $pi->status : 'unprocessed';
            $psText = match($psCode) {
                'unprocessed' => 'Belum diproses',
                'benchmarking' => 'Pemilihan vendor',
                'selected' => 'Proses PR & PO',
                'po_issued' => 'Proses di vendor',
                'grn_received' => 'Barang sudah diterima',
                'done' => 'Selesai',
                default => strtoupper($psCode),
            };

            return [
                'id'              => $item->id,
                'purchasing_status' => $psText,
                'master_item'     => $item->masterItem,
                'quantity'        => $item->quantity,
                'unit'            => $item->unit,
                'unit_price'      => $item->unit_price,
                'total_price'     => $item->total_price,
                'status'          => $displayStatus,
                'fs_document'     => $item->fs_document,
                'brand'           => $item->brand,
                'specification'   => $item->specification,
                'notes'           => $item->notes,
                'vendor_alt'      => $item->alternative_vendor,
                'reference_number' => $item->letter_number,
                'unit_allocation'  => $item->allocationDepartment?->name,
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
                'needs_fs_upload'   => $needsFsUpload,
                'needs_attachment_upload' => $needsAttachmentUpload,
                'current_step'    => $currentStep ? $this->formatStep($currentStep) : null,
                'steps'           => $item->steps->map(fn($s) => $this->formatStepFull($s)),
            ];
        });

        $itemFiles = DB::table('approval_request_item_files')
            ->where('approval_request_id', $approvalRequest->id)
            ->get()
            ->groupBy('master_item_id');

        $items = $items->map(function ($itemData) use ($itemFiles) {
            $masterItemId = data_get($itemData, 'master_item.id');
            $files = $masterItemId
                ? ($itemFiles->get($masterItemId) ?? collect())
                : collect();

            $itemData['supporting_documents'] = $files->map(function ($f) {
                return [
                    'id' => $f->id,
                    'original_name' => $f->original_name,
                    'mime' => $f->mime,
                    'size' => (int) $f->size,
                    'view_url' => url("/api/approval-request-attachments/{$f->id}/view"),
                    'download_url' => url("/api/approval-request-attachments/{$f->id}/download"),
                ];
            })->values();

            return $itemData;
        });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'             => $approvalRequest->id,
                'request_number' => $approvalRequest->request_number,
                'requester'      => $requester,
                'department'     => $primaryDept,
                'department_id'  => $primaryDept?->id,
                'status'         => $this->mapSingleStatus($approvalRequest->status),
                'notes'          => $approvalRequest->notes,
                'created_at'     => $approvalRequest->created_at,
                'items'          => $items,
            ],
        ]);
    }

    public function downloadAttachment($attachmentId)
    {
        $file = DB::table('approval_request_item_files')->where('id', $attachmentId)->first();
        if (!$file) {
            return response()->json(['status' => 'error', 'message' => 'File tidak ditemukan.'], 404);
        }
        if (!Storage::disk('public')->exists($file->path)) {
            return response()->json(['status' => 'error', 'message' => 'Path file tidak ditemukan.'], 404);
        }
        return Storage::disk('public')->download($file->path, $file->original_name);
    }

    public function viewAttachment($attachmentId)
    {
        $file = DB::table('approval_request_item_files')->where('id', $attachmentId)->first();
        if (!$file) {
            return response()->json(['status' => 'error', 'message' => 'File tidak ditemukan.'], 404);
        }
        if (!Storage::disk('public')->exists($file->path)) {
            return response()->json(['status' => 'error', 'message' => 'Path file tidak ditemukan.'], 404);
        }

        $mime = $file->mime ?: Storage::disk('public')->mimeType($file->path);
        if ($mime !== 'application/pdf') {
            return Storage::disk('public')->download($file->path, $file->original_name);
        }
        $stream = Storage::disk('public')->readStream($file->path);
        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addslashes($file->original_name) . '"',
        ]);
    }

    private function formatStep($step): array
    {
        return [
            'id'              => $step->id,
            'step_number'     => $step->step_number,
            'step_name'       => $step->step_name,
            'step_phase'      => $step->step_phase,
            'required_action' => $step->required_action,
            'approver_type'   => $step->approver_type,
            'status'          => $step->status,
        ];
    }

    private function formatStepFull($step): array
    {
        return array_merge($this->formatStep($step), [
            'approved_by'     => $step->approver?->name,
            'approved_at'     => $step->approved_at,
            'comments'        => $step->comments,
            'rejected_reason' => $step->rejected_reason,
            'attachments'     => $step->attachments->map(fn($a) => [
                'id'            => $a->id,
                'original_name' => $a->original_name,
                'mime'          => $a->mime,
                'size'          => $a->size,
                'view_url'      => url("/api/step-attachments/{$a->id}/view"),
                'download_url'  => url("/api/step-attachments/{$a->id}/download"),
            ]),
        ]);
    }
}
