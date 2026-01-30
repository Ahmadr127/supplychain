<?php

namespace App\Services\Dashboard;

use App\Models\ApprovalRequest;
use App\Models\ApprovalItemStep;
use App\Models\PurchasingItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecentUpdatesService
{
    /**
     * Get recent updates from various sources
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentUpdates(int $limit = 10): Collection
    {
        $user = Auth::user();
        $updates = collect();
        
        // 1. Get Pending Approvals (Items waiting for CURRENT user)
        if ($user->hasPermission('approval')) {
            $userDepartments = $user->departments()->pluck('departments.id')->toArray();
            $userRoles = $user->role_id ? [$user->role_id] : [];
            
            // Robust query matching ApprovalRequestController::pendingApprovals
            $pendingApprovals = ApprovalItemStep::where('status', 'pending')
                 ->where(function($q) {
                    $q->where('step_phase', 'approval')
                      ->orWhereNull('step_phase');
                })
                ->where(function($q) use ($user, $userDepartments, $userRoles) {
                    $q->where('approver_id', $user->id)
                      ->orWhereIn('approver_role_id', $userRoles)
                      ->orWhereIn('approver_department_id', $userDepartments)
                      // Department Manager Logic
                      ->orWhere(function($deptMgrQuery) use ($user) {
                          $deptMgrQuery->where('approver_type', 'department_manager')
                                       ->whereExists(function($exists) use ($user) {
                                           $exists->select(DB::raw(1))
                                                 ->from('departments')
                                                 ->whereColumn('departments.id', 'approval_item_steps.approver_department_id')
                                                 ->where('departments.manager_id', $user->id);
                                       });
                      })
                      // Requester Department Manager Logic
                      ->orWhere(function($reqMgrQuery) use ($user) {
                          $reqMgrQuery->where('approver_type', 'requester_department_manager')
                                      ->whereExists(function($exists) use ($user) {
                                          $exists->select(DB::raw(1))
                                                ->from('approval_requests')
                                                ->join('user_departments', function($join) {
                                                    $join->on('user_departments.user_id', '=', 'approval_requests.requester_id')
                                                         ->where('user_departments.is_primary', true);
                                                })
                                                ->join('departments', 'departments.id', '=', 'user_departments.department_id')
                                                ->whereColumn('approval_requests.id', 'approval_item_steps.approval_request_id')
                                                ->where('departments.manager_id', $user->id);
                                      });
                      })
                      // Any Department Manager Logic
                      ->orWhere(function($anyMgrQuery) use ($user) {
                          $anyMgrQuery->where('approver_type', 'any_department_manager')
                                      ->whereExists(function($exists) use ($user) {
                                          $exists->select(DB::raw(1))
                                                ->from('user_departments')
                                                ->whereColumn('user_departments.user_id', DB::raw((int)$user->id))
                                                ->where('user_departments.is_manager', true);
                                      });
                      });
                })
                // Enforce sequential visibility: ensure no previous steps are unapproved
                ->whereNotExists(function($sub) {
                     $sub->select(DB::raw(1))
                         ->from('approval_item_steps as prev')
                         ->whereColumn('prev.approval_request_item_id', 'approval_item_steps.approval_request_item_id')
                         ->where('prev.step_number', '<', DB::raw('approval_item_steps.step_number'))
                         ->whereNotIn('prev.status', ['approved', 'skipped']);
                })
                ->with(['approvalRequest', 'masterItem', 'requestItem'])
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(function($step) {
                    return [
                        'type' => 'pending_approval',
                        'title' => 'Butuh Persetujuan',
                        'description' => "{$step->masterItem->name} - {$step->step_name}",
                        'icon' => 'fas fa-exclamation-circle',
                        'color' => 'yellow',
                        'timestamp' => $step->created_at,
                        'url' => route('approval-requests.show', [
                            'approvalRequest' => $step->approval_request_id,
                            'item_id' => $step->approval_request_item_id
                        ]),
                        'sort_priority' => 10,
                    ];
                });
            
            $updates = $updates->merge($pendingApprovals);
        }

        // 2. Get Pending Releases (Items waiting for CURRENT user to release)
        if ($user->hasPermission('view_pending_release')) {
            // Simplified logic for release phase (assuming direct assignment usually)
            // But let's be safe and use basic checks + sequential
            $userDepartments = $user->departments()->pluck('departments.id')->toArray();
            $userRoles = $user->role_id ? [$user->role_id] : [];

            $pendingReleases = ApprovalItemStep::where('step_phase', 'release')
                ->where('status', 'pending')
                ->where(function($q) use ($user, $userDepartments, $userRoles) {
                    $q->where('approver_id', $user->id)
                      ->orWhereIn('approver_role_id', $userRoles)
                      ->orWhereIn('approver_department_id', $userDepartments);
                })
                ->with(['approvalRequest', 'masterItem'])
                ->latest('created_at')
                ->limit(5)
                ->get()
                ->map(function($step) {
                    return [
                        'type' => 'pending_release',
                        'title' => 'Butuh Release', 
                        'description' => "{$step->masterItem->name} - {$step->step_name}",
                        'icon' => 'fas fa-box-open', 
                        'color' => 'red', 
                        'timestamp' => $step->created_at,
                        'url' => route('approval-requests.show', [
                            'approvalRequest' => $step->approval_request_id,
                            'item_id' => $step->approval_request_item_id
                        ]),
                        'sort_priority' => 9, 
                    ];
                });
            
            $updates = $updates->merge($pendingReleases);
        }

        // 3. Get Recently Approved Items 
        if ($user->hasPermission('approval')) {
            $recentHistory = ApprovalItemStep::where('approved_by', $user->id)
                ->whereIn('status', ['approved', 'rejected'])
                ->with(['approvalRequest', 'masterItem'])
                ->latest('approved_at')
                ->limit(5)
                ->get()
                ->map(function($step) {
                    $isApproved = $step->status === 'approved';
                    return [
                        'type' => $isApproved ? 'approval_approved' : 'approval_rejected',
                        'title' => $isApproved ? 'Disetujui' : 'Ditolak', 
                        'description' => "{$step->masterItem->name} - {$step->step_name}",
                        'icon' => $isApproved ? 'fas fa-check-circle' : 'fas fa-times-circle',
                        'color' => $isApproved ? 'green' : 'red',
                        'timestamp' => $step->approved_at,
                        'url' => route('approval-requests.show', [
                            'approvalRequest' => $step->approval_request_id,
                            'item_id' => $step->approval_request_item_id
                        ]),
                        'sort_priority' => 5, 
                    ];
                });
            
            $updates = $updates->merge($recentHistory);
        }
        
        // 4. Purchasing Updates
        if ($user->hasPermission('view_process_purchasing')) {
            $recentPurchasing = PurchasingItem::with(['approvalRequest', 'masterItem', 'statusChanger'])
                ->whereNotNull('status_changed_at')
                ->latest('status_changed_at')
                ->limit(5)
                ->get()
                ->map(function($item) {
                     $statusLabels = [
                        'unprocessed' => 'Belum Diproses',
                        'benchmarking' => 'Pemilihan Vendor',
                        'selected' => 'Vendor Dipilih',
                        'po_issued' => 'PO Diterbitkan',
                        'grn_received' => 'Barang Diterima',
                        'done' => 'Selesai',
                    ];
                    $statusLabel = $statusLabels[$item->status] ?? $item->status;
                    
                    return [
                        'type' => 'purchasing_status_changed',
                        'title' => 'Update Purchasing', 
                        'description' => "{$item->masterItem->name} - {$statusLabel}",
                        'icon' => 'fas fa-shopping-cart',
                        'color' => 'indigo',
                        'timestamp' => $item->status_changed_at,
                        'url' => route('reports.approval-requests.process-purchasing', [
                            'purchasing_item_id' => $item->id
                        ]),
                        'sort_priority' => 4,
                    ];
                });
            
            $updates = $updates->merge($recentPurchasing);
        }

        // 5. My Recent Requests
        if ($user->hasPermission('view_my_approvals')) {
            $recentRequests = ApprovalRequest::where('requester_id', $user->id)
                ->with(['items', 'submissionType'])
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->map(function($request) {
                    return [
                        'type' => 'request_created',
                        'title' => 'Pengajuan Baru', 
                        'description' => "Request #{$request->request_number} - {$request->items->count()} items",
                        'icon' => 'fas fa-file-alt',
                        'color' => 'blue',
                        'timestamp' => $request->created_at,
                        'url' => route('approval-requests.show', $request->id),
                        'sort_priority' => 1, 
                    ];
                });
            
            $updates = $updates->merge($recentRequests);
        }

        // Sort by Priority first (High to Low), then by Timestamp (Newest first)
        return $updates->sort(function ($a, $b) {
            $prio = ($b['sort_priority'] ?? 0) <=> ($a['sort_priority'] ?? 0);
            if ($prio !== 0) return $prio;
            return $b['timestamp'] <=> $a['timestamp'];
        })->take($limit)->values();
    }
}
