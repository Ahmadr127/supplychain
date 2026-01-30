<?php

namespace App\Services\Dashboard;

use App\Models\ApprovalRequest;
use App\Models\ApprovalItemStep;
use App\Models\PurchasingItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class RecentUpdatesService
{
    /**
     * Get recent updates from various sources
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentUpdates(int $limit = 10): Collection
    {
        $user = Auth::user();
        $updates = collect();
        
        // Get recent approval requests (if user has permission)
        if ($user->hasPermission('view_my_approvals')) {
            $recentRequests = ApprovalRequest::where('requester_id', $user->id)
                ->with(['items', 'submissionType'])
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->map(function($request) {
                    return [
                        'type' => 'request_created',
                        'title' => 'Request Created',
                        'description' => "Request #{$request->request_number} - {$request->items->count()} items",
                        'icon' => 'fas fa-file-alt',
                        'color' => 'blue',
                        'timestamp' => $request->created_at,
                        'url' => route('approval-requests.show', $request->id),
                    ];
                });
            
            $updates = $updates->merge($recentRequests);
        }
        
        // Get recent approvals (if user has approval permission)
        if ($user->hasPermission('approval')) {
            $recentApprovals = ApprovalItemStep::where('approved_by', $user->id)
                ->whereIn('status', ['approved', 'rejected'])
                ->with(['approvalRequest', 'masterItem'])
                ->latest('approved_at')
                ->limit(5)
                ->get()
                ->map(function($step) {
                    $isApproved = $step->status === 'approved';
                    return [
                        'type' => $isApproved ? 'approval_approved' : 'approval_rejected',
                        'title' => $isApproved ? 'Approved' : 'Rejected',
                        'description' => "{$step->masterItem->name} - {$step->step_name}",
                        'icon' => $isApproved ? 'fas fa-check-circle' : 'fas fa-times-circle',
                        'color' => $isApproved ? 'green' : 'red',
                        'timestamp' => $step->approved_at,
                        'url' => route('approval-requests.show', [
                            'approvalRequest' => $step->approval_request_id,
                            'item_id' => $step->approval_request_item_id
                        ]),
                    ];
                });
            
            $updates = $updates->merge($recentApprovals);
        }
        
        // Get recent purchasing updates (if user has permission)
        if ($user->hasPermission('view_process_purchasing')) {
            $recentPurchasing = PurchasingItem::with(['approvalRequest', 'masterItem', 'statusChanger'])
                ->whereNotNull('status_changed_at')
                ->latest('status_changed_at')
                ->limit(5)
                ->get()
                ->map(function($item) {
                    $statusLabels = [
                        'unprocessed' => 'Belum Diproses',
                        'benchmarking' => 'Pemilihan Vendor',
                        'selected' => 'Vendor Dipilih',
                        'po_issued' => 'PO Diterbitkan',
                        'grn_received' => 'Barang Diterima',
                        'done' => 'Selesai',
                    ];
                    
                    $statusLabel = $statusLabels[$item->status] ?? $item->status;
                    
                    return [
                        'type' => 'purchasing_status_changed',
                        'title' => 'Purchasing Status Updated',
                        'description' => "{$item->masterItem->name} - {$statusLabel}",
                        'icon' => 'fas fa-shopping-cart',
                        'color' => 'indigo',
                        'timestamp' => $item->status_changed_at,
                        'url' => route('reports.approval-requests.process-purchasing', [
                            'purchasing_item_id' => $item->id
                        ]),
                    ];
                });
            
            $updates = $updates->merge($recentPurchasing);
        }
        
        // Get recent release approvals (if user has permission)
        if ($user->hasPermission('view_pending_release')) {
            $recentReleases = ApprovalItemStep::where('step_phase', 'release')
                ->where('approved_by', $user->id)
                ->whereIn('status', ['approved', 'rejected'])
                ->with(['approvalRequest', 'masterItem'])
                ->latest('approved_at')
                ->limit(5)
                ->get()
                ->map(function($step) {
                    $isApproved = $step->status === 'approved';
                    return [
                        'type' => $isApproved ? 'release_approved' : 'release_rejected',
                        'title' => $isApproved ? 'Release Approved' : 'Release Rejected',
                        'description' => "{$step->masterItem->name} - {$step->step_name}",
                        'icon' => 'fas fa-paper-plane',
                        'color' => 'purple',
                        'timestamp' => $step->approved_at,
                        'url' => route('approval-requests.show', [
                            'approvalRequest' => $step->approval_request_id,
                            'item_id' => $step->approval_request_item_id
                        ]),
                    ];
                });
            
            $updates = $updates->merge($recentReleases);
        }
        
        // Sort by timestamp and limit
        return $updates->sortByDesc('timestamp')->take($limit)->values();
    }
}
