<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapexAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'capex_item_id',
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
        'allocation_date'  => 'date',
        'confirmed_at'     => 'datetime',
        'cancelled_at'     => 'datetime',
    ];

    // ═══════════════════════════════════════════════════════════════
    // RELATIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * CapEx Item yang dialokasikan
     */
    public function capexItem()
    {
        return $this->belongsTo(CapexItem::class);
    }

    /**
     * Approval request ini alokasi
     */
    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    /**
     * Item spesifik dalam pengajuan
     */
    public function approvalRequestItem()
    {
        return $this->belongsTo(ApprovalRequestItem::class);
    }

    /**
     * User yang membuat alokasi (Manager Unit)
     */
    public function allocator()
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    /**
     * User yang mengkonfirmasi alokasi
     */
    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * User yang membatalkan alokasi
     */
    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    // ═══════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'confirmed']);
    }
}
