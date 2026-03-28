<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Department;
use App\Models\CapexItem;
use App\Models\PurchasingItem;
use App\Models\ApprovalRequestItem;

class ApprovalItemStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'approval_request_item_id',
        'master_item_id',
        'step_number',
        'step_name',
        'approver_type',
        'approver_id',
        'approver_role_id',
        'approver_department_id',
        'status',
        'approved_by',
        'approved_at',
        'comments',
        'rejected_reason',
        // Dynamic step insertion support
        'can_insert_step',
        'insert_step_template',
        'is_dynamic',
        'inserted_by',
        'inserted_at',
        'insertion_reason',
        'required_action',
        // Conditional step fields
        'is_conditional',
        'condition_type',
        'condition_value',
        // NEW: Step type and phase for 3-phase workflow
        'step_type',      // maker, approver, releaser
        'step_phase',     // approval, release
        'scope_process',  // Description of what this step does
        'selected_capex_id', // For Manager Unit to select CapEx ID
        // Skip tracking
        'skip_reason',
        'skipped_at',
        'skipped_by',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'inserted_at' => 'datetime',
        'skipped_at' => 'datetime',
        'can_insert_step' => 'boolean',
        'insert_step_template' => 'array',
        'is_dynamic' => 'boolean',
        'is_conditional' => 'boolean',
    ];

    // ═══════════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════════

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function masterItem()
    {
        return $this->belongsTo(MasterItem::class, 'master_item_id');
    }

    public function requestItem()
    {
        return $this->belongsTo(ApprovalRequestItem::class, 'approval_request_item_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function inserter()
    {
        return $this->belongsTo(User::class, 'inserted_by');
    }

    public function skipper()
    {
        return $this->belongsTo(User::class, 'skipped_by');
    }

    public function selectedCapex()
    {
        return $this->belongsTo(CapexItem::class, 'selected_capex_id');
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // STEP TYPE HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Check if this is a Maker step
     */
    public function isMaker(): bool
    {
        return $this->step_type === 'maker';
    }

    /**
     * Check if this is an Approver step
     */
    public function isApprover(): bool
    {
        return $this->step_type === 'approver';
    }

    /**
     * Check if this is a Releaser step
     */
    public function isReleaser(): bool
    {
        return $this->step_type === 'releaser';
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PHASE HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Check if this step belongs to approval phase
     */
    public function isApprovalPhase(): bool
    {
        return ($this->step_phase ?? 'approval') === 'approval';
    }

    /**
     * Check if this step belongs to release phase
     */
    public function isReleasePhase(): bool
    {
        return ($this->step_phase ?? 'approval') === 'release';
    }

    /**
     * Check if this step belongs to purchasing phase
     */
    public function isPurchasingPhase(): bool
    {
        return ($this->step_phase ?? 'approval') === 'purchasing';
    }

    /**
     * Check if this step is waiting for purchasing to complete
     */
    public function isPendingPurchase(): bool
    {
        return $this->status === 'pending_purchase';
    }

    /**
     * Check if this step can be activated (release phase after purchasing)
     */
    public function canBeActivated(): bool
    {
        // Only release phase steps that are pending_purchase can be activated
        if (!$this->isReleasePhase() || $this->status !== 'pending_purchase') {
            return false;
        }

        // Check if purchasing is complete for this item
        $purchasingItem = PurchasingItem::where('approval_request_id', $this->approval_request_id)
            ->where('master_item_id', $this->master_item_id)
            ->first();

        // Purchasing must be completely "done" before release can begin
        return $purchasingItem && $purchasingItem->status === 'done';
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // STATUS HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Check if step is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if step is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if step is rejected
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if step is skipped
     */
    public function isSkipped(): bool
    {
        return $this->status === 'skipped';
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // AUTHORIZATION
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Check if a user can approve this item step
     */
    public function canApprove(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user)
            return false;

        if ($this->isPurchasingPhase() || $this->step_type === 'purchasing') {
            return false;
        }

        // Release phase: check if step can be activated first
        if ($this->isReleasePhase() && $this->isPendingPurchase()) {
            if (!$this->canBeActivated()) {
                return false;
            }
        }

        switch ($this->approver_type) {
            case 'user':
                return (int) $this->approver_id === (int) $userId;
            case 'role':
                return $user->role && (int) $user->role->id === (int) $this->approver_role_id;
            case 'department_manager':
                $dept = Department::find($this->approver_department_id);
                return $dept && (int) $dept->manager_id === (int) $userId;
            case 'requester_department_manager':
                if (!$this->approvalRequest || !$this->approvalRequest->requester)
                    return false;
                $primary = $this->approvalRequest->requester->departments()->wherePivot('is_primary', true)->first();
                return $primary && (int) $primary->manager_id === (int) $userId;
            case 'allocation_department_manager':
                // Find the item related to this step
                $requestItem = \App\Models\ApprovalRequestItem::where('approval_request_id', $this->approval_request_id)
                    ->where('master_item_id', $this->master_item_id)
                    ->first();

                if (!$requestItem || !$requestItem->allocationDepartment)
                    return false;

                return (int) $requestItem->allocationDepartment->manager_id === (int) $userId;
            case 'any_department_manager':
                return $user->departments()->wherePivot('is_manager', true)->exists();
            default:
                return false;
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CAPEX HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Check if this step requires CapEx selection
     */
    public function requiresCapexSelection(): bool
    {
        return $this->required_action === 'select_capex';
    }

    /**
     * Check if this step requires FS creation
     */
    public function requiresFsCreation(): bool
    {
        return $this->required_action === 'create_fs';
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Scope: Approval phase steps only
     */
    public function scopeApprovalPhase($query)
    {
        return $query->where(function ($q) {
            $q->where('step_phase', 'approval')
                ->orWhereNull('step_phase');
        });
    }

    /**
     * Scope: Release phase steps only
     */
    public function scopeReleasePhase($query)
    {
        return $query->where('step_phase', 'release');
    }

    /**
     * Scope: Pending steps
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Pending purchase steps (waiting for purchasing)
     */
    public function scopePendingPurchase($query)
    {
        return $query->where('status', 'pending_purchase');
    }

    /**
     * Scope: Steps that can be approved now
     */
    public function scopeActionable($query)
    {
        return $query->whereIn('status', ['pending']);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PURCHASING SYNC HELPER
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Synchronize the workflow progress tracker with purchasing actions.
     * Marks the specific purchasing step as approved and activates the next purchasing step.
     */
    public static function syncPurchasingStep(int $approvalRequestId, int $masterItemId, string $requiredAction)
    {
        $step = static::where('approval_request_id', $approvalRequestId)
            ->where('master_item_id', $masterItemId)
            ->where('step_phase', 'purchasing')
            ->where('required_action', $requiredAction)
            ->whereIn('status', ['pending', 'pending_purchase'])
            ->first();

        if ($step) {
            $step->update([
                'status' => 'approved',
                'approved_by' => auth()->id() ?? 1, // fallback to System
                'approved_at' => now(),
            ]);

            // Find next step to activate
            $nextStep = static::where('approval_request_id', $approvalRequestId)
                ->where('master_item_id', $masterItemId)
                ->where('step_number', '>', $step->step_number)
                ->whereIn('step_phase', ['purchasing', 'release'])
                ->where('status', 'pending_purchase')
                ->orderBy('step_number')
                ->first();

            if ($nextStep && $nextStep->step_phase === 'purchasing') {
                $nextStep->update(['status' => 'pending']);
            }
        }
    }
}

