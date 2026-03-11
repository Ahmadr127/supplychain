<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if ($request->status)    $query->where('status', $request->status);
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)   $query->whereDate('created_at', '<=', $request->date_to);

        return response()->json([
            'status' => 'success',
            'data'   => $query->paginate($request->get('per_page', 15)),
        ]);
    }

    /**
     * GET /api/approval-requests/mine
     * Only requests belonging to the authenticated user.
     */
    public function myRequests(Request $request)
    {
        $query = ApprovalRequest::with(['requester', 'items'])
            ->where('requester_id', Auth::id())
            ->orderBy('created_at', 'desc');

        if ($request->status) $query->where('status', $request->status);
        if ($request->search) $query->where('request_number', 'like', "%{$request->search}%");

        return response()->json([
            'status' => 'success',
            'data'   => $query->paginate($request->get('per_page', 15)),
        ]);
    }

    /**
     * GET /api/approval-requests/pending
     * Requests that have items waiting for approval from the current user.
     */
    public function pending(Request $request)
    {
        $user = Auth::user();

        $query = ApprovalRequest::with(['requester', 'items.steps'])
            ->whereHas('items.steps', function ($q) use ($user) {
                $q->where('status', 'pending')
                  ->where(function ($sq) use ($user) {
                      $sq->where(fn($s) => $s->where('approver_type', 'user')->where('approver_id', $user->id))
                         ->orWhere(fn($s) => $s->where('approver_type', 'role')->where('approver_role_id', $user->role_id))
                         ->orWhere(fn($s) => $s->where('approver_type', 'department')->where('approver_department_id', $user->department_id ?? null));
                  });
            })
            ->orderBy('created_at', 'desc');

        if ($request->search) {
            $query->where('request_number', 'like', "%{$request->search}%");
        }

        return response()->json([
            'status' => 'success',
            'data'   => $query->paginate($request->get('per_page', 15)),
        ]);
    }

    /**
     * GET /api/approval-requests/{id}
     * Full detail with all items and their approval steps.
     */
    public function show(ApprovalRequest $approvalRequest)
    {
        $approvalRequest->load(['requester', 'items.masterItem', 'items.steps.approver']);

        $userId = Auth::id();

        $items = $approvalRequest->items->map(function ($item) use ($userId) {
            $currentStep = $item->getCurrentPendingStep();

            return [
                'id'              => $item->id,
                'master_item'     => $item->masterItem,
                'quantity'        => $item->quantity,
                'unit'            => $item->unit,
                'unit_price'      => $item->unit_price,
                'total_price'     => $item->total_price,
                'status'          => $item->status,
                'rejected_reason' => $item->rejected_reason,
                'can_approve'     => $currentStep ? $currentStep->canApprove($userId) : false,
                'current_step'    => $currentStep ? $this->formatStep($currentStep) : null,
                'steps'           => $item->steps->map(fn($s) => $this->formatStepFull($s)),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'             => $approvalRequest->id,
                'request_number' => $approvalRequest->request_number,
                'requester'      => $approvalRequest->requester,
                'status'         => $approvalRequest->status,
                'notes'          => $approvalRequest->notes,
                'created_at'     => $approvalRequest->created_at,
                'items'          => $items,
            ],
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
        ]);
    }
}
