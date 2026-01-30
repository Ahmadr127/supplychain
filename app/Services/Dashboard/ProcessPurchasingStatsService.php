<?php

namespace App\Services\Dashboard;

use App\Models\PurchasingItem;
use Illuminate\Support\Facades\DB;

class ProcessPurchasingStatsService
{
    /**
     * Get statistics for purchasing process
     *
     * @return array
     */
    public function getStats(): array
    {
        // Get count by purchasing status
        $stats = PurchasingItem::select('status', DB::raw('count(*) as count'))
                               ->groupBy('status')
                               ->pluck('count', 'status')
                               ->toArray();
        
        return [
            'total' => array_sum($stats),
            'unprocessed' => $stats['unprocessed'] ?? 0,
            'benchmarking' => $stats['benchmarking'] ?? 0,
            'selected' => $stats['selected'] ?? 0,
            'po_issued' => $stats['po_issued'] ?? 0,
            'grn_received' => $stats['grn_received'] ?? 0,
            'done' => $stats['done'] ?? 0,
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
     * Get items that need attention (unprocessed or benchmarking)
     *
     * @return int
     */
    public function getNeedAttentionCount(): int
    {
        return PurchasingItem::whereIn('status', ['unprocessed', 'benchmarking'])
                             ->count();
    }
}
