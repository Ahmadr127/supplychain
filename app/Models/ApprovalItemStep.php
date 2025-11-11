<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalItemStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
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
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'inserted_at' => 'datetime',
        'can_insert_step' => 'boolean',
        'insert_step_template' => 'array',
        'is_dynamic' => 'boolean',
    ];

    public function request()
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function item()
    {
        return $this->belongsTo(MasterItem::class, 'master_item_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function inserter()
    {
        return $this->belongsTo(User::class, 'inserted_by');
    }

    // Check if a user can approve this item step
    public function canApprove(int $userId): bool
    {
        $user = User::find($userId);
        if (!$user) return false;

        switch ($this->approver_type) {
            case 'user':
                return (int)$this->approver_id === (int)$userId;
            case 'role':
                return $user->role && (int)$user->role->id === (int)$this->approver_role_id;
            case 'department_manager':
                $dept = Department::find($this->approver_department_id);
                return $dept && (int)$dept->manager_id === (int)$userId;
            case 'requester_department_manager':
                if (!$this->request || !$this->request->requester) return false;
                $primary = $this->request->requester->departments()->wherePivot('is_primary', true)->first();
                return $primary && (int)$primary->manager_id === (int)$userId;
            case 'any_department_manager':
                return $user->departments()->wherePivot('is_manager', true)->exists();
            default:
                return false;
        }
    }
}
