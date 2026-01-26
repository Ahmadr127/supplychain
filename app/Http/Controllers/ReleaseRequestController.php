<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequestItem;
use App\Models\ApprovalItemStep;
use Illuminate\Http\Request;

class ReleaseRequestController extends Controller
{
    /**
     * Display a listing of items in release phase.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get items that are in release phase or have release steps
        $query = ApprovalRequestItem::query()
            ->with(['masterItem.itemType', 'masterItem.itemCategory', 'masterItem.unit', 'approvalRequest.requester'])
            ->whereHas('approvalRequest', function($q) {
                $q->whereIn('status', ['on progress', 'approved']);
            });

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Default: show items in_purchasing or in_release
            $query->whereIn('status', ['in_purchasing', 'in_release']);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('masterItem', function($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })
                ->orWhereHas('approvalRequest', function($q2) use ($search) {
                    $q2->where('request_number', 'like', "%{$search}%");
                });
            });
        }

        // Only show items where user can approve release steps
        // Or has role to view all release items
        $privilegedRoles = ['Admin', 'Super Admin', 'Manager Pembelian', 'Manager PT', 'Direktur PT'];
        $hasPrivilegedRole = $user->role && in_array($user->role->name, $privilegedRoles);
        
        if (!$hasPrivilegedRole) {
            $query->whereHas('approvalRequest', function($q) use ($user) {
                // Filter by items with release steps this user can approve
                $q->whereHas('itemSteps', function($q2) use ($user) {
                    $q2->where('step_phase', 'release')
                       ->where('status', 'pending')
                       ->where(function($q3) use ($user) {
                           $q3->where('approver_id', $user->id)
                              ->orWhere('approver_role_id', $user->role_id);
                       });
                });
            });
        }

        $releaseItems = $query->orderBy('updated_at', 'desc')->paginate(20);

        return view('release-requests.index', compact('releaseItems'));
    }

    /**
     * Approve a release step for an item.
     * This is handled by ApprovalItemApprovalController, redirect there.
     */
    public function approve(Request $request, ApprovalRequestItem $item)
    {
        // Redirect to the standard approval route
        return redirect()->route('approval-requests.show', [
            'approvalRequest' => $item->approval_request_id,
            'item_id' => $item->id
        ]);
    }

    /**
     * Reject a release step for an item.
     * This is handled by ApprovalItemApprovalController, redirect there.
     */
    public function reject(Request $request, ApprovalRequestItem $item)
    {
        return redirect()->route('approval-requests.show', [
            'approvalRequest' => $item->approval_request_id,
            'item_id' => $item->id
        ]);
    }

    /**
     * Get release items pending for current user.
     */
    public function myPendingReleases()
    {
        $user = auth()->user();
        
        // Get release steps where this user can approve
        $pendingReleaseSteps = ApprovalItemStep::where('step_phase', 'release')
            ->where('status', 'pending')
            ->where(function($q) use ($user) {
                $q->where('approver_id', $user->id)
                  ->orWhere('approver_role_id', $user->role_id);
            })
            ->with(['approvalRequest', 'masterItem'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('release-requests.my-pending', compact('pendingReleaseSteps'));
    }
}
