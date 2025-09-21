<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'workflow_steps',
        'is_active'
    ];

    protected $casts = [
        'workflow_steps' => 'array',
        'is_active' => 'boolean',
    ];

    // Relasi dengan approval requests
    public function requests()
    {
        return $this->hasMany(ApprovalRequest::class, 'workflow_id');
    }

    // Method untuk mendapatkan workflow steps sebagai collection
    public function getStepsAttribute()
    {
        if (!$this->workflow_steps) {
            return collect();
        }

        return collect($this->workflow_steps)->map(function ($step, $index) {
            return (object) [
                'step_number' => $index + 1,
                'step_name' => $step['name'],
                'approver_type' => $step['approver_type'],
                'approver_id' => $step['approver_id'] ?? null,
                'approver_role_id' => $step['approver_role_id'] ?? null,
                'approver_department_id' => $step['approver_department_id'] ?? null,
                'approver_level' => $step['approver_level'] ?? null,
            ];
        });
    }

    // Scope untuk workflow aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Method untuk membuat approval request
    public function createRequest($requesterId, $title, $description = null)
    {
        $requestNumber = $this->generateRequestNumber();
        
        $request = $this->requests()->create([
            'request_number' => $requestNumber,
            'requester_id' => $requesterId,
            'title' => $title,
            'description' => $description,
            'total_steps' => count($this->workflow_steps),
            'status' => 'pending'
        ]);

        // Buat approval steps
        $this->createApprovalSteps($request);

        return $request;
    }

    // Method untuk membuat approval steps
    private function createApprovalSteps($request)
    {
        foreach ($this->workflow_steps as $index => $step) {
            $request->steps()->create([
                'step_number' => $index + 1,
                'step_name' => $step['name'],
                'approver_type' => $step['approver_type'],
                'approver_id' => $step['approver_id'] ?? null,
                'approver_role_id' => $step['approver_role_id'] ?? null,
                'approver_department_id' => $step['approver_department_id'] ?? null,
                'approver_level' => $step['approver_level'] ?? null,
                'status' => 'pending'
            ]);
        }
    }

    // Method untuk generate nomor request
    private function generateRequestNumber()
    {
        $prefix = strtoupper(substr($this->type, 0, 3));
        $date = now()->format('Ymd');
        $count = $this->requests()->whereDate('created_at', now()->toDateString())->count() + 1;
        
        return $prefix . '-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
