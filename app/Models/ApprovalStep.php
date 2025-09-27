<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'step_number',
        'step_name',
        'approver_type',
        'approver_id',
        'approver_role_id',
        'approver_department_id',
        'approver_level',
        'status',
        'approved_by',
        'approved_at',
        'comments'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    // Relasi dengan request
    public function request()
    {
        return $this->belongsTo(ApprovalRequest::class, 'request_id');
    }

    // Relasi dengan approver user
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    // Relasi dengan approver role
    public function approverRole()
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }

    // Relasi dengan approver department
    public function approverDepartment()
    {
        return $this->belongsTo(Department::class, 'approver_department_id');
    }

    // Relasi dengan user yang approve
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scope untuk status tertentu
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope untuk step tertentu
    public function scopeStep($query, $stepNumber)
    {
        return $query->where('step_number', $stepNumber);
    }

    // Method untuk mendapatkan approver berdasarkan type
    public function getApprover()
    {
        switch ($this->approver_type) {
            case 'user':
                return User::find($this->approver_id);
            case 'role':
                $role = Role::find($this->approver_role_id);
                return $role ? $role->users->first() : null;
            case 'department_manager':
                $department = Department::find($this->approver_department_id);
                return $department ? $department->manager : null;
            case 'department_level':
                // Cari manager berdasarkan level departemen requester
                $requesterDepartment = $this->request->requester->departments()->wherePivot('is_primary', true)->first();
                if ($requesterDepartment) {
                    return $requesterDepartment->getApproverByLevel($this->approver_level);
                }
                return null;
            default:
                return null;
        }
    }

    // Method untuk check apakah user bisa approve step ini
    public function canApprove($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return false;
        }

        switch ($this->approver_type) {
            case 'user':
                return $this->approver_id == $userId;
            case 'role':
                return $user->role && $user->role->id == $this->approver_role_id;
            case 'department_manager':
                $department = Department::find($this->approver_department_id);
                return $department && $department->manager_id == $userId;
            case 'department_level':
                // Check apakah user berada di department dengan level yang sesuai atau lebih tinggi
                $userDepartments = $user->departments()->get();
                foreach ($userDepartments as $dept) {
                    if ($dept->level >= $this->approver_level) {
                        return true;
                    }
                }
                return false;
            default:
                return false;
        }
    }
}
