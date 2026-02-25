<?php

namespace App\Services;

use App\Models\CapexItem;
use App\Models\CapexAllocation;
use App\Models\ApprovalRequestItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CapexAllocationService
{
    /**
     * Reserve budget dari CapexItem untuk sebuah pengajuan.
     * Dipanggil saat Manager Unit approve step input_price.
     */
    public function reserve(
        CapexItem $capexItem,
        ApprovalRequestItem $item,
        float $amount,
        int $userId
    ): ?CapexAllocation {
        if (!$this->hasSufficientBudget($capexItem, $amount)) {
            Log::warning('CapexAllocation: Budget tidak mencukupi', [
                'capex_item_id'  => $capexItem->id,
                'requested'      => $amount,
                'available'      => $this->getAvailableBudget($capexItem),
            ]);
            return null;
        }

        DB::transaction(function () use ($capexItem, $item, $amount, $userId, &$allocation) {
            // Tambah pending_amount
            $capexItem->increment('pending_amount', $amount);

            // Update status jika perlu
            $this->syncStatus($capexItem);

            // Buat record alokasi
            $allocation = CapexAllocation::create([
                'capex_item_id'             => $capexItem->id,
                'approval_request_id'       => $item->approval_request_id,
                'approval_request_item_id'  => $item->id,
                'allocated_amount'          => $amount,
                'allocation_date'           => now()->toDateString(),
                'status'                    => 'pending',
                'allocated_by'              => $userId,
                'notes'                     => 'Reserve otomatis saat Manager Unit approve',
            ]);
        });

        Log::info('CapexAllocation: Budget di-reserve', [
            'capex_item_id'    => $capexItem->id,
            'approval_item_id' => $item->id,
            'amount'           => $amount,
            'allocation_id'    => $allocation?->id,
        ]);

        return $allocation ?? null;
    }

    /**
     * Konfirmasi alokasi saat item fully approved.
     * Pindahkan dari pending_amount → used_amount.
     */
    public function confirmAllocation(ApprovalRequestItem $item): bool
    {
        $allocation = CapexAllocation::where('approval_request_item_id', $item->id)
            ->where('status', 'pending')
            ->first();

        if (!$allocation) {
            Log::warning('CapexAllocation: Tidak ada alokasi pending untuk dikonfirmasi', [
                'approval_item_id' => $item->id,
            ]);
            return false;
        }

        DB::transaction(function () use ($allocation) {
            $capexItem = CapexItem::lockForUpdate()->find($allocation->capex_item_id);
            if (!$capexItem) return;

            $amount = (float) $allocation->allocated_amount;

            // Kurangi pending, tambah used
            $capexItem->decrement('pending_amount', $amount);
            $capexItem->increment('used_amount', $amount);

            // Update status
            $this->syncStatus($capexItem);

            // Konfirmasi alokasi
            $allocation->update([
                'status'       => 'confirmed',
                'confirmed_by' => auth()->id(),
                'confirmed_at' => now(),
            ]);
        });

        Log::info('CapexAllocation: Alokasi dikonfirmasi', [
            'allocation_id'    => $allocation->id,
            'approval_item_id' => $item->id,
            'amount'           => $allocation->allocated_amount,
        ]);

        return true;
    }

    /**
     * Lepaskan reservation saat item di-reject atau di-cancel.
     * Kembalikan pending_amount ke pool tersedia.
     */
    public function releaseReservation(ApprovalRequestItem $item, string $reason = 'Dibatalkan'): bool
    {
        $allocation = CapexAllocation::where('approval_request_item_id', $item->id)
            ->whereIn('status', ['pending'])
            ->first();

        if (!$allocation) {
            // Tidak ada reservation aktif — mungkin item tidak pakai CapEx atau sudah released
            return false;
        }

        DB::transaction(function () use ($allocation, $reason) {
            $capexItem = CapexItem::lockForUpdate()->find($allocation->capex_item_id);
            if (!$capexItem) return;

            $amount = (float) $allocation->allocated_amount;

            // Kembalikan pending_amount (pastikan tidak negatif)
            $capexItem->decrement('pending_amount', min($amount, (float) $capexItem->pending_amount));

            // Update status
            $this->syncStatus($capexItem);

            // Tandai alokasi sebagai cancelled
            $allocation->update([
                'status'              => 'cancelled',
                'cancelled_by'        => auth()->id(),
                'cancelled_at'        => now(),
                'cancellation_reason' => $reason,
            ]);
        });

        Log::info('CapexAllocation: Reservation dilepas', [
            'allocation_id'    => $allocation->id,
            'approval_item_id' => $item->id,
            'reason'           => $reason,
        ]);

        return true;
    }

    /**
     * Budget tersedia = budget_amount - used_amount - pending_amount
     */
    public function getAvailableBudget(CapexItem $capexItem): float
    {
        $capexItem->refresh();
        return max(0, (float) $capexItem->budget_amount
            - (float) $capexItem->used_amount
            - (float) $capexItem->pending_amount);
    }

    /**
     * Cek apakah budget mencukupi
     */
    public function hasSufficientBudget(CapexItem $capexItem, float $amount): bool
    {
        return $this->getAvailableBudget($capexItem) >= $amount;
    }

    /**
     * Ringkasan budget sebuah CapexItem
     */
    public function getCapexSummary(CapexItem $capexItem): array
    {
        $capexItem->refresh();
        $budget  = (float) $capexItem->budget_amount;
        $used    = (float) $capexItem->used_amount;
        $pending = (float) $capexItem->pending_amount;
        $avail   = max(0, $budget - $used - $pending);

        return [
            'budget_amount'   => $budget,
            'used_amount'     => $used,
            'pending_amount'  => $pending,
            'available_amount'=> $avail,
            'utilization_pct' => $budget > 0 ? round(($used / $budget) * 100, 1) : 0,
        ];
    }

    /**
     * Ambil daftar alokasi aktif untuk sebuah CapexItem (untuk UI)
     */
    public function getActiveAllocations(CapexItem $capexItem)
    {
        return CapexAllocation::with([
                'approvalRequest',
                'approvalRequestItem.masterItem',
                'allocator',
            ])
            ->where('capex_item_id', $capexItem->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Sync status CapexItem berdasarkan budget yang tersedia
     */
    private function syncStatus(CapexItem $capexItem): void
    {
        $capexItem->refresh();
        $available = (float) $capexItem->budget_amount
            - (float) $capexItem->used_amount
            - (float) $capexItem->pending_amount;

        $newStatus = match (true) {
            $available <= 0 && (float) $capexItem->used_amount >= (float) $capexItem->budget_amount => 'exhausted',
            (float) $capexItem->used_amount > 0 || (float) $capexItem->pending_amount > 0           => 'partially_used',
            default                                                                                   => 'available',
        };

        $capexItem->update(['status' => $newStatus]);
    }
}
