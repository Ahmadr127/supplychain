<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'master_item_id',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
        'specification',
        'brand',
        'supplier_id',
        'alternative_vendor',
        'allocation_department_id',
        'letter_number',
        'fs_document',
        // per-item approval (align with request-level statuses: pending|on progress|approved|rejected|cancelled)
        'status',
        'assignee_id',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function masterItem()
    {
        return $this->belongsTo(MasterItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function allocationDepartment()
    {
        return $this->belongsTo(Department::class, 'allocation_department_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Per-item workflow steps
    public function steps()
    {
        return $this->hasMany(ApprovalItemStep::class, 'master_item_id', 'master_item_id')
                    ->where('approval_request_id', $this->approval_request_id)
                    ->orderBy('step_number');
    }

    public function currentStep()
    {
        return $this->hasOne(ApprovalItemStep::class, 'master_item_id', 'master_item_id')
                    ->where('approval_request_id', $this->approval_request_id)
                    ->where('status', 'pending')
                    ->orderBy('step_number');
    }
    
    // Alternative accessor for steps (works better with eager loading)
    public function getStepsAttribute()
    {
        if (!isset($this->relations['steps'])) {
            return ApprovalItemStep::where('approval_request_id', $this->approval_request_id)
                ->where('master_item_id', $this->master_item_id)
                ->orderBy('step_number')
                ->get();
        }
        return $this->relations['steps'];
    }

    // Scopes
    public function scopeStatus($q, $status)
    {
        return $q->where('status', $status);
    }

    public function scopeDepartment($q, $deptId)
    {
        return $q->where('allocation_department_id', $deptId);
    }

    public function scopePending($q)
    {
        return $q->whereIn('status', ['pending', 'on progress']);
    }

    // Helper methods
    public function isFullyApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getCurrentPendingStep()
    {
        return ApprovalItemStep::where('approval_request_id', $this->approval_request_id)
            ->where('master_item_id', $this->master_item_id)
            ->where('status', 'pending')
            ->orderBy('step_number')
            ->first();
    }
}
