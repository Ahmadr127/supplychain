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
        
        return [
            'total' => array_sum($items),
            'on_progress' => $items['on progress'] ?? 0,
            'pending' => $items['pending'] ?? 0,
            'approved' => $items['approved'] ?? 0,
            'rejected' => $items['rejected'] ?? 0,
            'cancelled' => $items['cancelled'] ?? 0,
            'in_purchasing' => $items['in_purchasing'] ?? 0,
            'in_release' => $items['in_release'] ?? 0,
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
