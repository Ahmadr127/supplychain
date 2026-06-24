<?php
// =========================================================================
// PHP Script to Debug Pending Approvals for martanto.banu
// Run this file using command: php debug_pending.php
// =========================================================================

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ApprovalRequest;
use App\Models\ApprovalItemStep;
use App\Models\Department;

$userIdentifier = 'martanto.banu';

// 1. Find the target user
$user = User::where('username', $userIdentifier)
    ->orWhere('nik', $userIdentifier)
    ->orWhere('email', $userIdentifier)
    ->first();

if (!$user) {
    echo "❌ User not found with username/nik/email: '$userIdentifier'\n";
    return;
}

echo "=== USER INFO ===\n";
echo "ID: {$user->id}\n";
echo "Name: {$user->name}\n";
echo "Role ID: " . ($user->role_id ?? 'None') . " (" . ($user->role?->name ?? 'N/A') . ")\n\n";

$userId = $user->id;

// 2. Fetch the candidate requests like in the API
$allRequests = ApprovalRequest::with(['requester', 'items.masterItem', 'items.steps.approver'])
    ->whereHas('items.steps', function ($q) use ($user) {
        $q->where(function ($phaseQ) {
            $phaseQ->whereIn('step_phase', ['approval', 'release'])->orWhereNull('step_phase');
        });
        $q->where(function ($sq) use ($user) {
            $sq->where('status', 'pending')->orWhere('approved_by', $user->id);
        });
    })
    ->orderBy('created_at', 'desc')
    ->get();

echo "=== CANDIDATE REQUESTS FROM DB ===\n";
echo "Total requests found: " . $allRequests->count() . "\n";
echo "-------------------------------------------------------------\n";

foreach ($allRequests as $req) {
    echo "\nRequest #{$req->request_number} (ID: {$req->id}) | Requester: {$req->requester?->name}\n";
    
    $myItems = $req->items->filter(function ($item) use ($userId, $user) {
        $step = $item->getCurrentPendingStep();
        
        $isPendingForMe = false;
        $canApproveReason = "No pending step";
        
        if ($step) {
            $isPhaseMatch = in_array($step->step_phase ?? 'approval', ['approval', 'release']);
            if ($isPhaseMatch) {
                // Dry-run of canApprove
                $isPendingForMe = $step->canApprove($userId);
                
                if ($isPendingForMe) {
                    $canApproveReason = "MATCHED: Step ID={$step->id} | Name='{$step->step_name}' | Type='{$step->approver_type}'";
                    switch ($step->approver_type) {
                        case 'user':
                            $canApproveReason .= " (User ID matches: {$step->approver_id})";
                            break;
                        case 'role':
                            $canApproveReason .= " (Role ID matches: {$step->approver_role_id})";
                            break;
                        case 'department_manager':
                            $dept = Department::find($step->approver_department_id);
                            $canApproveReason .= " (Dept ID {$step->approver_department_id} Manager ID " . ($dept?->manager_id ?? 'null') . " matches)";
                            break;
                        case 'requester_department_manager':
                            $primary = $step->approvalRequest?->requester?->departments()->wherePivot('is_primary', true)->first();
                            $canApproveReason .= " (Requester primary Dept ID " . ($primary?->id ?? 'null') . " Manager ID " . ($primary?->manager_id ?? 'null') . " matches)";
                            break;
                        case 'allocation_department_manager':
                            $canApproveReason .= " (Allocation Dept Manager matches)";
                            break;
                        case 'any_department_manager':
                            $canApproveReason .= " (User is a manager of some department)";
                            break;
                    }
                } else {
                    $canApproveReason = "FAILED canApprove for Step ID={$step->id} ('{$step->step_name}'): Type='{$step->approver_type}'";
                    if ($step->status !== 'pending') {
                        $canApproveReason .= " (Step status is '{$step->status}', not 'pending')";
                    } elseif ($step->isPurchasingPhase()) {
                        $canApproveReason .= " (Step is in purchasing phase)";
                    } else {
                        switch ($step->approver_type) {
                            case 'user':
                                $canApproveReason .= " (Step approver_id={$step->approver_id} != User ID {$userId})";
                                break;
                            case 'role':
                                $canApproveReason .= " (Step role_id={$step->approver_role_id} != User Role ID " . ($user->role_id ?? 'null') . ")";
                                break;
                            case 'department_manager':
                                $dept = Department::find($step->approver_department_id);
                                $canApproveReason .= " (Dept ID {$step->approver_department_id} Manager ID " . ($dept?->manager_id ?? 'null') . " != User ID {$userId})";
                                break;
                            case 'requester_department_manager':
                                $primary = $step->approvalRequest?->requester?->departments()->wherePivot('is_primary', true)->first();
                                $canApproveReason .= " (Requester primary Dept ID " . ($primary?->id ?? 'null') . " Manager ID " . ($primary?->manager_id ?? 'null') . " != User ID {$userId})";
                                break;
                            case 'allocation_department_manager':
                                $requestItem = \App\Models\ApprovalRequestItem::where('approval_request_id', $step->approval_request_id)
                                    ->where('master_item_id', $step->master_item_id)->first();
                                $canApproveReason .= " (Allocation Dept ID " . ($requestItem?->allocation_department_id ?? 'null') . " Manager ID " . ($requestItem?->allocationDepartment?->manager_id ?? 'null') . " != User ID {$userId})";
                                break;
                            case 'any_department_manager':
                                $canApproveReason .= " (User is not a manager in user_departments)";
                                break;
                        }
                    }
                }
            } else {
                $canApproveReason = "FAILED: Step ID={$step->id} ('{$step->step_name}') phase is '{$step->step_phase}', not in approval/release";
            }
        }
        
        $myActionedSteps = $item->steps->filter(function ($s) use ($userId) {
            $phase = $s->step_phase ?? 'approval';
            return (int) $s->approved_by === (int) $userId
                && in_array($phase, ['approval', 'release'])
                && in_array($s->status, ['approved', 'rejected'], true);
        });
        
        $hasActioned = $myActionedSteps->isNotEmpty();
        
        echo "  - Item ID: {$item->id} | Name: '" . ($item->masterItem?->name ?? $item->item_name ?? $item->notes) . "' | Ref: '{$item->letter_number}'\n";
        echo "    * Status in DB       : '{$item->status}'\n";
        echo "    * isPendingForMe     : " . ($isPendingForMe ? 'TRUE' : 'FALSE') . " | Reason: {$canApproveReason}\n";
        echo "    * hasActioned        : " . ($hasActioned ? 'TRUE' : 'FALSE') . " (Approved steps: [" . implode(', ', $myActionedSteps->pluck('id')->toArray()) . "])\n";
        
        if ($isPendingForMe || $hasActioned) {
            $overriddenStatus = $item->status;
            if (!in_array($item->status, ['approved', 'rejected', 'done', 'terpenuhi', 'fulfilled', 'completed', 'released'])) {
                $overriddenStatus = $isPendingForMe ? 'pending' : 'approved';
            }
            echo "    * OVERRIDDEN STATUS  : '{$overriddenStatus}' (can_approve=" . ($isPendingForMe ? 'true' : 'false') . ")\n";
            $item->setAttribute('can_approve', (bool)$isPendingForMe);
            return true;
        } else {
            echo "    * Action status      : Not pending for me and not actioned by me. Item skipped.\n";
            return false;
        }
    })->values();

    if ($myItems->isEmpty()) {
        echo "  ⚠️ No items returned for this user. Request skipped.\n";
        continue;
    }
    
    $isPending = $myItems->contains('can_approve', true);
    $isRejected = $myItems->contains(function ($i) use ($userId) {
        return $i->steps->where('approved_by', $userId)
            ->where('status', 'rejected')
            ->filter(fn($s) => in_array(($s->step_phase ?? 'approval'), ['approval', 'release']))
            ->isNotEmpty();
    });
    
    $computedReqStatus = $isPending ? 'pending' : ($isRejected ? 'rejected' : 'approved');
    echo "  => Computed Request Status: '{$computedReqStatus}' (isPending=" . ($isPending ? 'true' : 'false') . ", isRejected=" . ($isRejected ? 'true' : 'false') . ")\n";
    
    $filteredOut = $computedReqStatus !== 'pending';
    echo "  => Show in 'Pending' tab?  : " . ($filteredOut ? '❌ NO (filtered out)' : '✅ YES') . "\n";
}
