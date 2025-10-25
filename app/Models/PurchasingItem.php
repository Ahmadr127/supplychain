<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasingItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'master_item_id',
        'pivot_ref_id',
        'quantity',
        'status',
        'status_changed_at',
        'status_changed_by',
        'benchmark_notes',
        'preferred_vendor_id',
        'preferred_unit_price',
        'preferred_total_price',
        'invoice_number',
        'po_number',
        'grn_date',
        'proc_cycle_days',
        'done_notes',
    ];

    protected $casts = [
        'grn_date' => 'date',
        'status_changed_at' => 'datetime',
    ];

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function masterItem()
    {
        return $this->belongsTo(MasterItem::class);
    }

    public function vendors()
    {
        return $this->hasMany(PurchasingItemVendor::class);
    }

    public function preferredVendor()
    {
        return $this->belongsTo(Supplier::class, 'preferred_vendor_id');
    }

    public function statusChanger()
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }
}
