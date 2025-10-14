<?php

namespace App\Services\Purchasing;

use App\Models\PurchasingItem;
use App\Models\PurchasingItemVendor;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchasingItemService
{
    /**
     * Save or replace benchmarking vendors for a purchasing item.
     * $vendors = [
     *   ['supplier_id' => int, 'unit_price' => float, 'total_price' => float, 'notes' => ?string], ...
     * ]
     */
    public function saveBenchmarking(PurchasingItem $item, array $vendors): PurchasingItem
    {
        DB::transaction(function() use ($item, $vendors) {
            // Replace benchmarking set: delete existing then insert new
            $item->vendors()->delete();

            foreach ($vendors as $v) {
                if (empty($v['supplier_id'])) { continue; }
                $unit = (float)($v['unit_price'] ?? 0);
                $total = (float)($v['total_price'] ?? 0);
                if ($unit <= 0 && $total <= 0) { continue; }

                PurchasingItemVendor::create([
                    'purchasing_item_id' => $item->id,
                    'supplier_id' => (int)$v['supplier_id'],
                    'unit_price' => $unit,
                    'total_price' => $total ?: ($unit * max(1, (int)$item->quantity)),
                    'is_preferred' => false,
                    'notes' => $v['notes'] ?? null,
                ]);
            }

            // Update status to benchmarking if there is data; otherwise keep unprocessed
            $hasVendors = $item->vendors()->exists();
            $item->update(['status' => $hasVendors ? 'benchmarking' : 'unprocessed']);
        });

        // refresh aggregated purchasing status
        $item->approvalRequest->refreshPurchasingStatus();

        return $item->refresh(['vendors']);
    }

    /**
     * Select preferred vendor for an item.
     */
    public function selectPreferred(PurchasingItem $item, int $supplierId, ?float $unitPrice, ?float $totalPrice): PurchasingItem
    {
        DB::transaction(function() use ($item, $supplierId, $unitPrice, $totalPrice) {
            // Ensure vendor exists in benchmarking; if not, create it with given prices
            $vendor = $item->vendors()->where('supplier_id', $supplierId)->first();
            if (!$vendor) {
                $vendor = $item->vendors()->create([
                    'supplier_id' => $supplierId,
                    'unit_price' => $unitPrice ?? 0,
                    'total_price' => $totalPrice ?? 0,
                    'is_preferred' => false,
                ]);
            }

            // Mark preferred
            $item->vendors()->update(['is_preferred' => false]);
            $vendor->update(['is_preferred' => true]);

            $item->update([
                'preferred_vendor_id' => $supplierId,
                'preferred_unit_price' => $unitPrice,
                'preferred_total_price' => $totalPrice,
                'status' => 'selected',
            ]);
        });

        $item->approvalRequest->refreshPurchasingStatus();

        return $item->refresh(['vendors', 'preferredVendor']);
    }

    /**
     * Issue PO for an item.
     */
    public function issuePO(PurchasingItem $item, string $poNumber): PurchasingItem
    {
        $item->update([
            'po_number' => $poNumber,
            'status' => 'po_issued',
        ]);
        $item->approvalRequest->refreshPurchasingStatus();
        return $item;
    }

    /**
     * Receive GRN for an item and compute cycle.
     */
    public function receiveGRN(PurchasingItem $item, Carbon $grnDate): PurchasingItem
    {
        $created = $item->created_at ?: now();
        $cycle = $grnDate->diffInDays($created);
        $item->update([
            'grn_date' => $grnDate->toDateString(),
            'proc_cycle_days' => $cycle,
            'status' => 'grn_received',
        ]);
        $item->approvalRequest->refreshPurchasingStatus();
        return $item;
    }

    /**
     * Mark item done (requires preferred vendor and GRN ideally).
     */
    public function markDone(PurchasingItem $item): PurchasingItem
    {
        $item->update(['status' => 'done']);
        $item->approvalRequest->refreshPurchasingStatus();
        return $item;
    }
}
