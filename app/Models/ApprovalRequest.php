<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PurchasingItem;

class ApprovalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'letter_number',
        'workflow_id',
        'requester_id',
        'submission_type_id',
        'description',
        'priority',
        'is_cto_request',
        'status', // Restored for aggregation
        'purchasing_status',
        // 'current_step', // REMOVED: per-item approval system
        // 'total_steps', // REMOVED: per-item approval system
        'approved_by',
        'approved_at',
        'rejection_reason',
        'item_type_id',
        'is_specific_type',
        'received_at',
        'fs_document',
        'procurement_type_id',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'is_specific_type' => 'boolean',
        'received_at' => 'date',
    ];

    // Relasi dengan workflow
    public function workflow()
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    public function procurementType()
    {
        return $this->belongsTo(\App\Models\ProcurementType::class);
    }

    // Relasi dengan requester
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // Relasi dengan approver
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }



    // Relasi purchasing items (per item purchasing process)
    public function purchasingItems()
    {
        return $this->hasMany(PurchasingItem::class, 'approval_request_id');
    }

    // Relasi baru: items sebagai model penuh (pengganti pivot di masa depan)
    public function items()
    {
        return $this->hasMany(\App\Models\ApprovalRequestItem::class, 'approval_request_id');
    }

    // Relasi dengan attachments
    // attachments feature removed; relation intentionally omitted

    // Relasi dengan submission type (Jenis Pengajuan)
    public function submissionType()
    {
        return $this->belongsTo(\App\Models\SubmissionType::class);
    }

    // Relasi dengan item type
    public function itemType()
    {
        return $this->belongsTo(\App\Models\ItemType::class);
    }

    // Relasi dengan item extras (form statis per item)
    public function itemExtras()
    {
        return $this->hasMany(ApprovalRequestItemExtra::class, 'approval_request_id');
    }

    // Relasi dengan per-item approval steps (Option B)
    public function itemSteps()
    {
        return $this->hasMany(\App\Models\ApprovalItemStep::class, 'approval_request_id');
    }

    // Scope untuk status tertentu
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope untuk requester tertentu
    public function scopeRequester($query, $userId)
    {
        return $query->where('requester_id', $userId);
    }



    // Aggregate purchasing status from purchasing_items to approval_requests.purchasing_status
    public function refreshPurchasingStatus(): void
    {
        // Define status order from least to most progressed
        $order = [
            'unprocessed' => 0,
            'benchmarking' => 1,
            'selected' => 2,
            'po_issued' => 3,
            'grn_received' => 4,
            'done' => 5,
        ];

        $items = $this->purchasingItems()->select(['status'])->get();

        if ($items->isEmpty()) {
            $status = 'unprocessed';
        } else {
            // Pick the most advanced status among child items
            $max = -1; $agg = 'unprocessed';
            foreach ($items as $pi) {
                $rank = $order[$pi->status] ?? 0;
                if ($rank > $max) { $max = $rank; $agg = $pi->status; }
            }
            $status = $agg;
        }

        if ($this->purchasing_status !== $status) {
            $this->update(['purchasing_status' => $status]);
        }
    }

    /**
     * Refresh request status based on items status
     */
    public function refreshStatus()
    {
        $items = $this->items;
        
        if ($items->isEmpty()) {
            return;
        }

        $allApproved = $items->every(fn($item) => $item->status === 'approved');
        $anyRejected = $items->contains(fn($item) => $item->status === 'rejected');
        
        $newStatus = 'on progress';

        if ($anyRejected) {
            $newStatus = 'rejected';
        } elseif ($allApproved) {
            $newStatus = 'approved';
        }
        
        if ($this->status !== $newStatus) {
            $this->update(['status' => $newStatus]);
        }
    }

    // Method untuk menghitung total harga dari semua items
    public function getTotalItemsPrice()
    {
        return $this->items()->sum('total_price');
    }

    // Method untuk mendapatkan jumlah total items
    public function getTotalItemsQuantity()
    {
        return $this->items()->sum('quantity');
    }

    // Method untuk menambahkan item ke request (UPDATED for new per-item system)
    public function addItem($masterItemId, $quantity = 1, $unitPrice = null, $notes = null)
    {
        $masterItem = MasterItem::findOrFail($masterItemId);
        $unitPrice = $unitPrice ?? $masterItem->total_price;
        $totalPrice = $quantity * $unitPrice;

        return $this->items()->create([
            'master_item_id' => $masterItemId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'notes' => $notes,
            'status' => 'pending',
        ]);
    }

    // Method untuk update item quantity (UPDATED for new per-item system)
    public function updateItemQuantity($masterItemId, $quantity)
    {
        $item = $this->items()->where('master_item_id', $masterItemId)->first();
        if ($item) {
            $totalPrice = $quantity * $item->unit_price;
            $item->update([
                'quantity' => $quantity,
                'total_price' => $totalPrice
            ]);
        }
    }

    // Method untuk menghapus item dari request (UPDATED for new per-item system)
    public function removeItem($masterItemId)
    {
        return $this->items()->where('master_item_id', $masterItemId)->delete();
    }



    // Method untuk update stock ketika request di-approve (UPDATED for new per-item system)
    public function updateStockForApprovedRequest()
    {
        // Load the items with their master item data
        $this->load('items.masterItem');
        
        foreach ($this->items as $item) {
            $requestedQuantity = $item->quantity;
            
            // Validate quantity is positive
            if ($requestedQuantity <= 0) {
                continue; // Skip invalid quantities
            }
            
            $masterItem = $item->masterItem;
            if (!$masterItem) continue;
            
            // Update stock: increase stock by requested quantity (for incoming items)
            $newStock = $masterItem->stock + $requestedQuantity;
            
            // Ensure stock doesn't go below 0 (safety check)
            if ($newStock < 0) {
                $newStock = 0;
            }
            
            $masterItem->update(['stock' => $newStock]);
        }
    }
    /**
     * Cancel the request and all its pending items
     */
    public function cancel($userId)
    {
        // Cancel all pending items
        $this->items()
            ->whereIn('status', ['pending', 'on progress'])
            ->update([
                'status' => 'cancelled',
                'rejected_reason' => 'Request cancelled by requester',
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);
            
        // Also cancel pending steps
        $this->itemSteps()
            ->where('status', 'pending')
            ->update([
                'status' => 'skipped',
                'skip_reason' => 'Request cancelled',
                'skipped_by' => $userId,
                'skipped_at' => now(),
            ]);

        // Update request status (legacy/aggregate)
        $this->update(['status' => 'cancelled']);

        return true;
    }
}
