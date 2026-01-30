<?php

namespace App\Services\Dashboard;

use App\Models\ApprovalItemStep;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PendingApprovalsStatsService
{
    /**
     * Get statistics for pending approvals
     *
     * @return array
     */
    public function getStats(): array
    {
        $user = Auth::user();
        $userDepartments = $user->departments()->pluck('departments.id')->toArray();
        $userRoleId = $user->role_id;
        
        // Get all pending steps that this user can approve
        $query = ApprovalItemStep::where(function($q) use ($user, $userDepartments, $userRoleId) {
            $q->where('approver_id', $user->id)
              ->orWhere('approver_role_id', $userRoleId)
              ->orWhereIn('approver_department_id', $userDepartments);
        });
        
        // Count by status
        $stats = $query->select('status', DB::raw('count(*) as count'))
                       ->groupBy('status')
                       ->pluck('count', 'status')
                       ->toArray();
        
        // Get pending items that need action
        $pendingCount = ApprovalItemStep::where('status', 'pending')
            ->where(function($q) use ($user, $userDepartments, $userRoleId) {
                $q->where('approver_id', $user->id)
                  ->orWhere('approver_role_id', $userRoleId)
                  ->orWhereIn('approver_department_id', $userDepartments);
            })
            ->count();
        
        // Get items approved today
        $approvedToday = ApprovalItemStep::where('status', 'approved')
            ->where('approved_by', $user->id)
            ->whereDate('approved_at', today())
            ->count();
        
        return [
            'total' => array_sum($stats),
            'pending' => $pendingCount,
            'on_progress' => $pendingCount, // Map pending to on_progress as requested
            'approved' => $stats['approved'] ?? 0,
            'rejected' => $stats['rejected'] ?? 0,
            'approved_today' => $approvedToday,
        ];
    }
    
    /**
     * Get breakdown by status
     *
     * @return array
     */
    public function getBreakdown(): array
    {
        $stats = $this->getStats();
        
        return [
            [
                'label' => 'Need Action',
                'count' => $stats['pending'],
                'color' => 'red',
                'icon' => 'fas fa-exclamation-circle',
                'urgent' => true,
            ],
            [
                'label' => 'On Progress',
                'count' => $stats['on_progress'],
                'color' => 'blue',
                'icon' => 'fas fa-spinner',
            ],
        ];
    }
    
    /**
     * Get recent pending items
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentPendingItems(int $limit = 5)
    {
        $user = Auth::user();
        $userDepartments = $user->departments()->pluck('departments.id')->toArray();
        $userRoleId = $user->role_id;
        
        return ApprovalItemStep::where('status', 'pending')
            ->where(function($q) use ($user, $userDepartments, $userRoleId) {
                $q->where('approver_id', $user->id)
                  ->orWhere('approver_role_id', $userRoleId)
                  ->orWhereIn('approver_department_id', $userDepartments);
            })
            ->with(['approvalRequest', 'masterItem', 'approvalRequestItem'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
