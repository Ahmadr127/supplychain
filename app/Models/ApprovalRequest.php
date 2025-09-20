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
        'title',
        'description',
        'data',
        'status',
        'current_step',
        'total_steps',
        'approved_by',
        'approved_at',
        'rejection_reason'
    ];

    protected $casts = [
        'data' => 'array',
        'approved_at' => 'datetime',
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
}
