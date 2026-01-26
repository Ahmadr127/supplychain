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
        'status',
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

    // DEPRECATED: Old request-level approval system
    // Replaced by per-item approval (approval_item_steps)
    // Kept for backward compatibility - DO NOT USE
    /*
    public function steps()
    {
        return $this->hasMany(ApprovalStep::class, 'request_id');
    }

    public function currentStep()
    {
        return $this->hasOne(ApprovalStep::class, 'request_id')
                    ->where('step_number', $this->current_step);
    }
    */

    // DEPRECATED: Old pivot table relationship (approval_request_master_items dropped)
    // Use items() relation instead which uses approval_request_items table
    /*
    public function masterItems()
    {
        return $this->belongsToMany(MasterItem::class, 'approval_request_master_items')
                    ->withPivot(['quantity', 'unit_price', 'total_price', 'notes', 'specification', 'brand', 'supplier_id', 'alternative_vendor', 'allocation_department_id', 'letter_number', 'fs_document'])
                    ->withTimestamps();
    }
    */

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

    // Method untuk approve request
    public function approve($userId, $comments = null)
    {
        $currentStep = $this->currentStep;
        
        if (!$currentStep || $currentStep->status !== 'pending') {
            return false;
        }

        // Check if user can approve this step
        if (!$currentStep->canApprove($userId)) {
            return false;
        }

        // Update current step
        $currentStep->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'comments' => $comments
        ]);

        // DEPRECATED: Old request-level approval logic
        // In per-item approval system, check if ALL items are approved
        // For now, simplified: mark as approved immediately
        // TODO: Implement proper per-item approval aggregation
        if (true) { // Placeholder - should check all item statuses
            $this->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now()
            ]);
            
            // Update stock for all items in this request
            $this->updateStockForApprovedRequest();

            // Initialize purchasing items per approved item (idempotent)
            $this->load('items');
            foreach ($this->items as $item) {
                $exists = $this->purchasingItems()
                    ->where('master_item_id', $item->master_item_id)
                    ->exists();
                if (!$exists) {
                    $this->purchasingItems()->create([
                        'master_item_id' => $item->master_item_id,
                        'quantity' => (int)($item->quantity ?? 1),
                        'status' => 'unprocessed',
                    ]);
                }
            }

            // Refresh aggregated purchasing_status at request level (unprocessed|done)
            $this->refreshPurchasingStatus();
        }
        // Note: 'current_step' removed in per-item approval system

        return true;
    }

    // Method untuk reject request
    public function reject($userId, $reason, $comments = null)
    {
        $currentStep = $this->currentStep;
        
        if (!$currentStep || $currentStep->status !== 'pending') {
            return false;
        }

        // Check if user can approve this step
        if (!$currentStep->canApprove($userId)) {
            return false;
        }

        // Update current step
        $currentStep->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'comments' => $comments
        ]);

        // Update request status
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'rejection_reason' => $reason
        ]);

        return true;
    }

    // Method untuk cancel request
    public function cancel($userId)
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'approved_by' => $userId,
            'approved_at' => now()
        ]);

        return true;
    }

    // Method untuk mendapatkan approver untuk current step
    public function getCurrentApprover()
    {
        $currentStep = $this->currentStep;
        
        if (!$currentStep) {
            return null;
        }

        switch ($currentStep->approver_type) {
            case 'user':
                return User::find($currentStep->approver_id);
            case 'role':
                $role = Role::find($currentStep->approver_role_id);
                return $role ? $role->users->first() : null;
            case 'department_manager':
                $department = Department::find($currentStep->approver_department_id);
                return $department ? $department->manager : null;
            case 'any_department_manager':
                // Display-safe: return one manager (not authoritative). Authorization handled elsewhere.
                return \App\Models\User::whereHas('departments', function($q){
                    $q->where('user_departments.is_manager', true);
                })->first();
            default:
                return null;
        }
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

    // Method untuk check apakah user bisa approve request ini
    public function canApprove($userId)
    {
        $currentStep = $this->currentStep;
        
        if (!$currentStep || $currentStep->status !== 'pending') {
            return false;
        }

        return $currentStep->canApprove($userId);
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
}
