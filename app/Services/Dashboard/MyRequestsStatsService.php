<?php

namespace App\Services\Dashboard;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MyRequestsStatsService
{
    /**
     * Get statistics for user's own requests
     *
     * @return array
     */
    public function getStats(): array
    {
        $userId = Auth::id();
        
        // Get all items from requests created by this user
        $items = ApprovalRequestItem::whereHas('approvalRequest', function($query) use ($userId) {
            $query->where('requester_id', $userId);
        })->select('status', DB::raw('count(*) as count'))
          ->groupBy('status')
          ->pluck('count', 'status')
          ->toArray();
        
        // Get counts for purchasing items belonging to this user
        $purchasingStats = DB::table('purchasing_items')
            ->join('approval_requests', 'purchasing_items.approval_request_id', '=', 'approval_requests.id')
            ->where('approval_requests.requester_id', $userId)
            ->select('purchasing_items.status', DB::raw('count(*) as count'))
            ->groupBy('purchasing_items.status')
            ->pluck('count', 'status')
            ->toArray();

        // Match my-requests page: on_progress and pending are separate (ApprovalRequestItem status)
        $onProgress = $items['on progress'] ?? 0;
        $pending = $items['pending'] ?? 0;
        $inPurchasing = $items['in_purchasing'] ?? 0;
        $inRelease = $items['in_release'] ?? 0;

        // Active = on progress + pending + in_purchasing + in_release (same as page)
        $active = $onProgress + $pending + $inPurchasing + $inRelease;

        return [
            'total' => array_sum($items),
            'active' => $active,
            'on_progress' => $onProgress,
            'pending' => $pending,
            'approved' => $items['approved'] ?? 0,
            'rejected' => $items['rejected'] ?? 0,
            'cancelled' => $items['cancelled'] ?? 0,
            'in_purchasing' => $inPurchasing,
            'in_release' => $inRelease,
            
            // Detailed purchasing stats for this user
            'purchasing_unprocessed' => $purchasingStats['unprocessed'] ?? 0,
            'purchasing_benchmarking' => $purchasingStats['benchmarking'] ?? 0,
            'purchasing_selected' => $purchasingStats['selected'] ?? 0, // PR & PO
            'purchasing_po_issued' => $purchasingStats['po_issued'] ?? 0, // In Vendor
            'purchasing_grn_received' => $purchasingStats['grn_received'] ?? 0, // Goods Received
            'purchasing_done' => $purchasingStats['done'] ?? 0,
        ];
    }
    
    /**
     * Get breakdown by status
     *
     * @return array
     */
    public function getBreakdown(): array
    {
        $stats = $this->getStats();
        
        return [
            [
                'label' => 'On Progress',
                'count' => $stats['on_progress'],
                'color' => 'blue',
                'icon' => 'fas fa-spinner',
            ],
            [
                'label' => 'Pending',
                'count' => $stats['pending'],
                'color' => 'yellow',
                'icon' => 'fas fa-clock',
            ],
            [
                'label' => 'Approved',
                'count' => $stats['approved'],
                'color' => 'green',
                'icon' => 'fas fa-check-circle',
            ],
            [
                'label' => 'Rejected',
                'count' => $stats['rejected'],
                'color' => 'red',
                'icon' => 'fas fa-times-circle',
            ],
            [
                'label' => 'Cancelled',
                'count' => $stats['cancelled'],
                'color' => 'gray',
                'icon' => 'fas fa-ban',
            ],
        ];
    }
    
    /**
     * Get recent items from user's requests
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentItems(int $limit = 5)
    {
        $userId = Auth::id();
        
        return ApprovalRequestItem::whereHas('approvalRequest', function($query) use ($userId) {
            $query->where('requester_id', $userId);
        })->with(['approvalRequest', 'masterItem'])
          ->latest('updated_at')
          ->limit($limit)
          ->get();
    }
}
