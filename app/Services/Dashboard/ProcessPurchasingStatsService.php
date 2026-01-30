<?php

namespace App\Services\Dashboard;

use App\Models\ApprovalRequestItem;
use App\Models\PurchasingItem;
use Illuminate\Support\Facades\DB;

class ProcessPurchasingStatsService
{
    /**
     * Get statistics for purchasing process (same logic as reports approval-requests / process-purchasing page).
     *
     * @return array
     */
    public function getStats(): array
    {
        $piCounts = PurchasingItem::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Items ready for purchasing but no PurchasingItem yet (counted as "unprocessed" on page)
        $readyButNoPI = ApprovalRequestItem::whereIn('status', ['in_purchasing', 'approved', 'in_release'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('purchasing_items')
                    ->whereColumn('purchasing_items.approval_request_id', 'approval_request_items.approval_request_id')
                    ->whereColumn('purchasing_items.master_item_id', 'approval_request_items.master_item_id');
            })
            ->count();

        $pendingApproval = ApprovalRequestItem::whereIn('status', ['pending', 'on progress'])->count();
        $totalUnprocessed = ($piCounts['unprocessed'] ?? 0) + $readyButNoPI;

        return [
            'total' => array_sum($piCounts) + $readyButNoPI,
            'pending_approval' => $pendingApproval,
            'unprocessed' => $totalUnprocessed,
            'benchmarking' => $piCounts['benchmarking'] ?? 0,
            'selected' => $piCounts['selected'] ?? 0,
            'po_issued' => $piCounts['po_issued'] ?? 0,
            'grn_received' => $piCounts['grn_received'] ?? 0,
            'done' => $piCounts['done'] ?? 0,
        ];
    }
    
    /**
     * Get breakdown by purchasing status
     *
     * @return array
     */
    public function getBreakdown(): array
    {
        $stats = $this->getStats();
        
        return [
            [
                'label' => 'Belum Diproses',
                'count' => $stats['unprocessed'],
                'color' => 'gray',
                'icon' => 'fas fa-inbox',
            ],
            [
                'label' => 'Pemilihan Vendor',
                'count' => $stats['benchmarking'],
                'color' => 'yellow',
                'icon' => 'fas fa-search',
            ],
            [
                'label' => 'Proses PR & PO',
                'count' => $stats['selected'],
                'color' => 'blue',
                'icon' => 'fas fa-file-invoice',
            ],
            [
                'label' => 'Proses di Vendor',
                'count' => $stats['po_issued'],
                'color' => 'indigo',
                'icon' => 'fas fa-truck',
            ],
            [
                'label' => 'Barang Diterima',
                'count' => $stats['grn_received'],
                'color' => 'purple',
                'icon' => 'fas fa-box',
            ],
        ];
    }
    
    /**
     * Get recent purchasing items
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentItems(int $limit = 5)
    {
        return PurchasingItem::with(['approvalRequest', 'masterItem', 'preferredVendor'])
                             ->latest('updated_at')
                             ->limit($limit)
                             ->get();
    }
    
    /**
     * Get items that need attention (unprocessed + ready-but-no-PI + benchmarking, same as page).
     *
     * @return int
     */
    public function getNeedAttentionCount(): int
    {
        $stats = $this->getStats();
        return $stats['unprocessed'] + $stats['benchmarking'];
    }
}
