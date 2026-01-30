<?php

namespace App\Services\Dashboard;

use App\Models\ApprovalItemStep;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PendingApprovalsStatsService
{
    /**
     * Base query for steps this user can approve (same scope as pendingApprovals page).
     */
    private function stepScopeQuery()
    {
        $user = Auth::user();
        $userRoles = $user->role ? [$user->role->id] : [];

        return ApprovalItemStep::where(function ($q) use ($user, $userRoles) {
            $q->where('approver_id', $user->id)
                ->orWhereIn('approver_role_id', $userRoles)
                ->orWhere(function ($anyMgrQuery) use ($user) {
                    $anyMgrQuery->where('approver_type', 'any_department_manager')
                        ->whereExists(function ($exists) use ($user) {
                            $exists->select(DB::raw(1))
                                ->from('user_departments')
                                ->whereColumn('user_departments.user_id', DB::raw((int) $user->id))
                                ->where('user_departments.is_manager', true);
                        });
                })
                ->orWhere(function ($reqMgrQuery) use ($user) {
                    $reqMgrQuery->where('approver_type', 'requester_department_manager')
                        ->whereExists(function ($exists) use ($user) {
                            $exists->select(DB::raw(1))
                                ->from('approval_requests')
                                ->join('user_departments', function ($join) {
                                    $join->on('user_departments.user_id', '=', 'approval_requests.requester_id')
                                        ->where('user_departments.is_primary', true);
                                })
                                ->join('departments', 'departments.id', '=', 'user_departments.department_id')
                                ->whereColumn('approval_requests.id', 'approval_item_steps.approval_request_id')
                                ->where('departments.manager_id', $user->id);
                        });
                })
                ->orWhere(function ($deptMgrQuery) use ($user) {
                    $deptMgrQuery->where('approver_type', 'department_manager')
                        ->whereExists(function ($exists) use ($user) {
                            $exists->select(DB::raw(1))
                                ->from('departments')
                                ->whereColumn('departments.id', 'approval_item_steps.approver_department_id')
                                ->where('departments.manager_id', $user->id);
                        });
                });
        });
    }

    /**
     * Get statistics for pending approvals (same logic as pending-approvals page).
     *
     * @return array
     */
    public function getStats(): array
    {
        $countQuery = $this->stepScopeQuery();

        // Actionable pending steps (no prior unapproved) = "on progress" on page
        $onProgressCount = (clone $countQuery)
            ->where('status', 'pending')
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('approval_item_steps as prev')
                    ->whereColumn('prev.approval_request_item_id', 'approval_item_steps.approval_request_item_id')
                    ->whereColumn('prev.step_number', '<', 'approval_item_steps.step_number')
                    ->whereNotIn('prev.status', ['approved', 'skipped']);
            })
            ->count();

        // Blocked pending steps = "pending" on page
        $pendingCount = (clone $countQuery)
            ->where('status', 'pending')
            ->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('approval_item_steps as prev')
                    ->whereColumn('prev.approval_request_item_id', 'approval_item_steps.approval_request_item_id')
                    ->whereColumn('prev.step_number', '<', 'approval_item_steps.step_number')
                    ->whereNotIn('prev.status', ['approved', 'skipped']);
            })
            ->count();

        $approvedCount = (clone $countQuery)->where('status', 'approved')->count();
        $rejectedCount = (clone $countQuery)->where('status', 'rejected')->count();
        $cancelledCount = (clone $countQuery)
            ->whereHas('requestItem', function ($q) {
                $q->where('status', 'cancelled');
            })
            ->count();

        $approvedToday = (clone $countQuery)
            ->where('status', 'approved')
            ->whereDate('approved_at', today())
            ->count();

        $total = $onProgressCount + $pendingCount + $approvedCount + $rejectedCount + $cancelledCount;

        return [
            'total' => $total,
            'pending' => $pendingCount,
            'on_progress' => $onProgressCount,
            'approved' => $approvedCount,
            'rejected' => $rejectedCount,
            'cancelled' => $cancelledCount,
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
     * Get recent pending items (actionable steps only, same scope as page).
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRecentPendingItems(int $limit = 5)
    {
        return $this->stepScopeQuery()
            ->where('status', 'pending')
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('approval_item_steps as prev')
                    ->whereColumn('prev.approval_request_item_id', 'approval_item_steps.approval_request_item_id')
                    ->whereColumn('prev.step_number', '<', 'approval_item_steps.step_number')
                    ->whereNotIn('prev.status', ['approved', 'skipped']);
            })
            ->with(['approvalRequest', 'masterItem', 'requestItem'])
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
