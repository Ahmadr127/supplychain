<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->role && $this->role->name === $role;
        }
        if (is_numeric($role)) {
            return $this->role && $this->role->id == $role;
        }
        return $this->role && $this->role->id === $role->id;
    }

    public function hasPermission($permission)
    {
        return $this->role && $this->role->hasPermission($permission);
    }

    // Relasi dengan departments melalui pivot table
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'user_departments')
                    ->withPivot(['position', 'is_primary', 'is_manager', 'start_date', 'end_date'])
                    ->withTimestamps();
    }

    // Relasi dengan primary department
    public function primaryDepartment()
    {
        return $this->belongsToMany(Department::class, 'user_departments')
                    ->wherePivot('is_primary', true)
                    ->withPivot(['position', 'start_date', 'end_date'])
                    ->withTimestamps();
    }

    // Relasi dengan managed departments
    public function managedDepartments()
    {
        return $this->belongsToMany(Department::class, 'user_departments')
                    ->wherePivot('is_manager', true)
                    ->withPivot(['position', 'start_date', 'end_date'])
                    ->withTimestamps();
    }

    // Relasi dengan approval requests sebagai requester
    public function approvalRequests()
    {
        return $this->hasMany(ApprovalRequest::class, 'requester_id');
    }

    // Relasi dengan approval steps sebagai approver
    public function approvalSteps()
    {
        return $this->hasMany(ApprovalStep::class, 'approved_by');
    }

    // Method untuk check apakah user adalah manager di department tertentu
    public function isManagerOf($departmentId)
    {
        return $this->departments()->wherePivot('department_id', $departmentId)
                    ->wherePivot('is_manager', true)->exists();
    }

    // Method untuk mendapatkan approval requests yang perlu di-approve
    public function getPendingApprovals()
    {
        $userDepartments = $this->departments()->pluck('departments.id');
        $userRoles = $this->role ? [$this->role->id] : [];

        return \App\Models\ApprovalStep::where('status', 'pending')
            ->where(function($query) use ($userDepartments, $userRoles) {
                $query->where('approver_id', $this->id)
                      ->orWhereIn('approver_role_id', $userRoles)
                      ->orWhereIn('approver_department_id', $userDepartments);
            })
            ->with(['request', 'request.requester'])
            ->get();
    }

    // Method untuk check apakah user adalah level direktur
    public function isDirectorLevel()
    {
        // Check if user has direktur role
        if ($this->hasRole('direktur')) {
            return true;
        }
        
        // Check if user is in a director-level department (level >= 2)
        $userDepartments = $this->departments()->wherePivot('is_primary', true)->get();
        foreach ($userDepartments as $dept) {
            if ($dept->level >= 2) {
                return true;
            }
        }
        
        return false;
    }
}
