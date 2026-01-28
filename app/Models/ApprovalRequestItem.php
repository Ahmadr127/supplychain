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
        'capex_item_id',
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

    public function capexItem()
    {
        return $this->belongsTo(CapexItem::class);
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

    // ═══════════════════════════════════════════════════════════════════════════
    // STATUS HELPERS (3-Phase Workflow)
    // ═══════════════════════════════════════════════════════════════════════════

    public function isFullyApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isInPurchasing(): bool
    {
        return $this->status === 'in_purchasing';
    }

    public function isInRelease(): bool
    {
        return $this->status === 'in_release';
    }

    /**
     * Get current phase of the approval process
     */
    public function getCurrentPhase(): string
    {
        return match($this->status) {
            'pending', 'on progress' => 'approval',
            'in_purchasing' => 'purchasing',
            'in_release' => 'release',
            'approved' => 'completed',
            'rejected', 'cancelled' => 'ended',
            default => 'unknown',
        };
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // STEP HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Get current pending step (approval or release phase)
     */
    public function getCurrentPendingStep()
    {
        return ApprovalItemStep::where('approval_request_id', $this->approval_request_id)
            ->where('master_item_id', $this->master_item_id)
            ->where('status', 'pending')
            ->orderBy('step_number')
            ->first();
    }

    /**
     * Get all approval phase steps
     */
    public function getApprovalPhaseSteps()
    {
        return ApprovalItemStep::where('approval_request_id', $this->approval_request_id)
            ->where('master_item_id', $this->master_item_id)
            ->where(function($q) {
                $q->where('step_phase', 'approval')
                  ->orWhereNull('step_phase');
            })
            ->orderBy('step_number')
            ->get();
    }

    /**
     * Get all release phase steps
     */
    public function getReleasePhaseSteps()
    {
        return ApprovalItemStep::where('approval_request_id', $this->approval_request_id)
            ->where('master_item_id', $this->master_item_id)
            ->where('step_phase', 'release')
            ->orderBy('step_number')
            ->get();
    }

    /**
     * Check if all approval phase steps are complete
     */
    public function isApprovalPhaseComplete(): bool
    {
        $pendingApprovalSteps = ApprovalItemStep::where('approval_request_id', $this->approval_request_id)
            ->where('master_item_id', $this->master_item_id)
            ->where('status', 'pending')
            ->where(function($q) {
                $q->where('step_phase', 'approval')
                  ->orWhereNull('step_phase');
            })
            ->count();

        return $pendingApprovalSteps === 0;
    }

    /**
     * Check if all release phase steps are complete
     */
    public function isReleasePhaseComplete(): bool
    {
        $pendingReleaseSteps = ApprovalItemStep::where('approval_request_id', $this->approval_request_id)
            ->where('master_item_id', $this->master_item_id)
            ->where('step_phase', 'release')
            ->whereIn('status', ['pending', 'pending_purchase'])
            ->count();

        return $pendingReleaseSteps === 0;
    }

    /**
     * Check if this item has release phase steps
     */
    public function hasReleasePhase(): bool
    {
        return ApprovalItemStep::where('approval_request_id', $this->approval_request_id)
            ->where('master_item_id', $this->master_item_id)
            ->where('step_phase', 'release')
            ->exists();
    }

    /**
     * Get the associated PurchasingItem
     */
    public function purchasingItem()
    {
        return PurchasingItem::where('approval_request_id', $this->approval_request_id)
            ->where('master_item_id', $this->master_item_id)
            ->first();
    }
}

