<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'workflow_id',
        'requester_id',
        'submission_type_id',
        'description',
        'priority',
        'is_cto_request',
        'status',
        'current_step',
        'total_steps',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'item_type_id',
        'is_specific_type'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'is_specific_type' => 'boolean',
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

    // Relasi dengan approval steps
    public function steps()
    {
        return $this->hasMany(ApprovalStep::class, 'request_id');
    }

    // Relasi dengan current step
    public function currentStep()
    {
        return $this->hasOne(ApprovalStep::class, 'request_id')
                    ->where('step_number', $this->current_step);
    }

    // Relasi dengan master items (many-to-many)
    public function masterItems()
    {
        return $this->belongsToMany(MasterItem::class, 'approval_request_master_items')
                    ->withPivot(['quantity', 'unit_price', 'total_price', 'notes'])
                    ->withTimestamps();
    }

    // Relasi dengan attachments
    public function attachments()
    {
        return $this->hasMany(ApprovalRequestAttachment::class);
    }

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

        // Check if this is the last step
        if ($this->current_step >= $this->total_steps) {
            $this->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now()
            ]);
            
            // Update stock for all items in this request
            $this->updateStockForApprovedRequest();
        } else {
            // Move to next step
            $this->update(['current_step' => $this->current_step + 1]);
        }

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
            case 'department_level':
                // Cari manager berdasarkan level departemen requester
                $requesterDepartment = $this->requester->departments()->wherePivot('is_primary', true)->first();
                if ($requesterDepartment) {
                    return $requesterDepartment->getApproverByLevel($currentStep->approver_level);
                }
                return null;
            default:
                return null;
        }
    }

    // Method untuk menghitung total harga dari semua items
    public function getTotalItemsPrice()
    {
        return $this->masterItems()->sum('approval_request_master_items.total_price');
    }

    // Method untuk mendapatkan jumlah total items
    public function getTotalItemsQuantity()
    {
        return $this->masterItems()->sum('approval_request_master_items.quantity');
    }

    // Method untuk menambahkan item ke request
    public function addItem($masterItemId, $quantity = 1, $unitPrice = null, $notes = null)
    {
        $masterItem = MasterItem::findOrFail($masterItemId);
        $unitPrice = $unitPrice ?? $masterItem->total_price;
        $totalPrice = $quantity * $unitPrice;

        return $this->masterItems()->attach($masterItemId, [
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'notes' => $notes
        ]);
    }

    // Method untuk update item quantity
    public function updateItemQuantity($masterItemId, $quantity)
    {
        $pivot = $this->masterItems()->where('master_item_id', $masterItemId)->first();
        if ($pivot) {
            $totalPrice = $quantity * $pivot->pivot->unit_price;
            $this->masterItems()->updateExistingPivot($masterItemId, [
                'quantity' => $quantity,
                'total_price' => $totalPrice
            ]);
        }
    }

    // Method untuk menghapus item dari request
    public function removeItem($masterItemId)
    {
        return $this->masterItems()->detach($masterItemId);
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

    // Method untuk update stock ketika request di-approve
    public function updateStockForApprovedRequest()
    {
        // Load the master items with their pivot data
        $this->load('masterItems');
        
        foreach ($this->masterItems as $masterItem) {
            $requestedQuantity = $masterItem->pivot->quantity;
            
            // Validate quantity is positive
            if ($requestedQuantity <= 0) {
                continue; // Skip invalid quantities
            }
            
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
