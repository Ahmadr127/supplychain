<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapexAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'capex_id_number_id',
        'approval_request_id',
        'approval_request_item_id',
        'allocated_amount',
        'allocation_date',
        'status',
        'allocated_by',
        'confirmed_by',
        'confirmed_at',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'notes',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'allocation_date' => 'date',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * CapEx ID Number this allocation is from
     */
    public function capexIdNumber()
    {
        return $this->belongsTo(CapexIdNumber::class);
    }

    /**
     * Approval request this allocation is for
     */
    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    /**
     * Specific item this allocation is for (optional)
     */
    public function approvalRequestItem()
    {
        return $this->belongsTo(ApprovalRequestItem::class);
    }

    /**
     * User who made the allocation (Manager Unit)
     */
    public function allocator()
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    /**
     * User who confirmed the allocation
     */
    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * User who cancelled the allocation
     */
    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Confirm this allocation
     */
    public function confirm(int $userId): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->status = 'confirmed';
        $this->confirmed_by = $userId;
        $this->confirmed_at = now();
        
        return $this->save();
    }

    /**
     * Cancel this allocation and release budget back
     */
    public function cancel(int $userId, string $reason = null): bool
    {
        if (!in_array($this->status, ['pending', 'confirmed'])) {
            return false;
        }

        // Release budget back to capex
        $this->capexIdNumber->release($this->allocated_amount);

        $this->status = 'cancelled';
        $this->cancelled_by = $userId;
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        
        return $this->save();
    }

    /**
     * Release this allocation (final step after PO/GRN)
     */
    public function release(): bool
    {
        if ($this->status !== 'confirmed') {
            return false;
        }

        $this->status = 'released';
        return $this->save();
    }

    /**
     * Scope: Pending allocations
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Confirmed allocations
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope: Active allocations (not cancelled)
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'released']);
    }

    /**
     * Create allocation and deduct from capex budget
     */
    public static function createAllocation(
        int $capexId,
        int $requestId,
        float $amount,
        int $allocatedBy,
        ?int $itemId = null,
        ?string $notes = null
    ): ?self {
        $capex = CapexIdNumber::find($capexId);
        
        if (!$capex || !$capex->hasSufficientBudget($amount)) {
            return null;
        }

        // Allocate from capex
        $capex->allocate($amount);

        return self::create([
            'capex_id_number_id' => $capexId,
            'approval_request_id' => $requestId,
            'approval_request_item_id' => $itemId,
            'allocated_amount' => $amount,
            'allocation_date' => now()->toDateString(),
            'status' => 'pending',
            'allocated_by' => $allocatedBy,
            'notes' => $notes,
        ]);
    }
}
