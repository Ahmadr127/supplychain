<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalItemStep;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReleaseApiController extends Controller
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth:sanctum');
        $this->notificationService = $notificationService;
    }

    /**
     * List release items
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = ApprovalRequestItem::with(['masterItem', 'approvalRequest', 'steps' => function ($q) {
            $q->where('step_phase', 'release');
        }]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('approvalRequest', function ($q2) use ($search) {
                    $q2->where('request_number', 'like', "%{$search}%");
                })->orWhereHas('masterItem', function ($q2) use ($search) {
                    $q2->where('item_name', 'like', "%{$search}%");
                });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['in_purchasing', 'in_release']);
        }

        // Filter by user permissions (only items user can approve atau privileged roles)
        $query->whereHas('steps', function ($q) use ($user) {
            $q->where('step_phase', 'release')
              ->where(function ($sub) use ($user) {
                  $sub->where('approver_type', 'user')->where('approver_id', $user->id)
                      ->orWhere(function ($rSub) use ($user) {
                          if ($user->role_id) {
                              $rSub->where('approver_type', 'role')->where('approver_role_id', $user->role_id);
                          }
                      });
              });
        });

        $items = $query->paginate($request->input('per_page', 20));

        // Mark items waiting for current user approval
        $formattedItems = collect($items->items())->map(function ($item) use ($user) {
            $itemArray = $item->toArray();
            $itemArray['waiting_for_me'] = false;
            
            $pendingStep = collect($item->steps)->where('status', 'pending')->first();
            if ($pendingStep && $pendingStep->canApprove($user->id)) {
                $itemArray['waiting_for_me'] = true;
            }

            return $itemArray;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar release items berhasil diambil',
            'data' => [
                'items' => $formattedItems,
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Detail release item
     */
    public function show($id): JsonResponse
    {
        $item = ApprovalRequestItem::with([
            'masterItem', 
            'approvalRequest.requester', 
            'steps' => function ($q) {
                $q->where('step_phase', 'release')->orderBy('step_number');
            }
        ])->find($id);

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Release item tidak ditemukan'], 404);
        }

        // Include purchasing info
        $purchasingItem = \App\Models\PurchasingItem::with('preferredVendor')
            ->where('approval_request_id', $item->approval_request_id)
            ->where('master_item_id', $item->master_item_id)
            ->first();

        // Identify current and next approver
        $currentStep = $item->steps->where('status', 'pending')->first();
        $nextStep = null;
        if ($currentStep) {
            $nextStep = $item->steps->where('step_number', '>', $currentStep->step_number)->first();
        }

        $itemArray = $item->toArray();
        $itemArray['purchasing_info'] = $purchasingItem ? $purchasingItem->toArray() : null;
        $itemArray['current_step'] = $currentStep ? $currentStep->toArray() : null;
        $itemArray['next_step'] = $nextStep ? $nextStep->toArray() : null;

        return response()->json([
            'status' => 'success',
            'message' => 'Detail release item berhasil diambil',
            'data' => $itemArray
        ]);
    }

    /**
     * Approve release item
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $item = ApprovalRequestItem::find($id);
        
        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Release item tidak ditemukan'], 404);
        }

        $step = $item->steps()->where('step_phase', 'release')->where('status', 'pending')->first();

        if (!$step) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada step pending untuk disetujui'], 400);
        }

        if (!$step->canApprove(auth()->id())) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak berhak menyetujui tahap ini'], 403);
        }

        $step->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'comments' => $request->input('comments'),
        ]);

        $nextStep = $item->steps()
            ->where('step_phase', 'release')
            ->where('step_number', '>', $step->step_number)
            ->orderBy('step_number', 'asc')
            ->first();

        if ($nextStep) {
            $nextStep->update(['status' => 'pending']);
            $this->notificationService->notifyReleaseApprover($nextStep);
        } else {
            // All release steps approved
            $item->update(['status' => 'approved']);
            
            $purchasingItem = \App\Models\PurchasingItem::where('approval_request_id', $item->approval_request_id)
                ->where('master_item_id', $item->master_item_id)
                ->first();
                
            if ($purchasingItem) {
                $purchasingItem->update([
                    'status' => 'done',
                    'status_changed_at' => now(),
                    'status_changed_by' => auth()->id()
                ]);
            }

            $this->notificationService->notifyReleaseStatusChange($item, 'approved', $request->input('comments', ''));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Release berhasil disetujui',
            'data' => [
                'next_step' => $nextStep
            ]
        ]);
    }

    /**
     * Reject release item
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $item = ApprovalRequestItem::find($id);
        
        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Release item tidak ditemukan'], 404);
        }

        $step = $item->steps()->where('step_phase', 'release')->where('status', 'pending')->first();

        if (!$step) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada step pending untuk ditolak'], 400);
        }

        if (!$step->canApprove(auth()->id())) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak berhak menolak tahap ini'], 403);
        }

        $step->update([
            'status' => 'rejected',
            'rejected_reason' => $validated['rejection_reason'],
        ]);

        $item->update(['status' => 'rejected', 'rejected_reason' => $validated['rejection_reason']]);

        $this->notificationService->notifyReleaseStatusChange($item, 'rejected', $validated['rejection_reason']);

        return response()->json([
            'status' => 'success',
            'message' => 'Release berhasil ditolak',
        ]);
    }

    /**
     * My Pending Releases
     */
    public function myPending(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ApprovalItemStep::with(['approvalRequest', 'masterItem'])
            ->where('step_phase', 'release')
            ->where('status', 'pending');

        $query->where(function ($q) use ($user) {
            $q->where('approver_type', 'user')->where('approver_id', $user->id)
              ->orWhere(function ($rSub) use ($user) {
                  if ($user->role_id) {
                      $rSub->where('approver_type', 'role')->where('approver_role_id', $user->role_id);
                  }
              });
        });

        $statusCountsQuery = ApprovalItemStep::where('step_phase', 'release')
            ->where(function ($q) use ($user) {
                $q->where('approver_type', 'user')->where('approver_id', $user->id)
                  ->orWhere(function ($rSub) use ($user) {
                      if ($user->role_id) {
                          $rSub->where('approver_type', 'role')->where('approver_role_id', $user->role_id);
                      }
                  });
            });
            
        $statusCounts = $statusCountsQuery->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $steps = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 20));

        $formattedSteps = collect($steps->items())->map(function ($step) {
            $stepArray = $step->toArray();
            $stepArray['created_at'] = $step->created_at ? $step->created_at->toIso8601String() : null;
            return $stepArray;
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'items' => $formattedSteps,
                'status_counts' => [
                    'pending' => $statusCounts['pending'] ?? 0,
                    'approved' => $statusCounts['approved'] ?? 0,
                    'rejected' => $statusCounts['rejected'] ?? 0,
                    'total' => array_sum($statusCounts)
                ],
                'pagination' => [
                    'total' => $steps->total(),
                    'per_page' => $steps->perPage(),
                    'current_page' => $steps->currentPage(),
                    'last_page' => $steps->lastPage(),
                ]
            ]
        ]);
    }
}
