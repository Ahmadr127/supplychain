<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Jobs\SendFcmNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private FirebaseService $firebaseService
    ) {}

    /**
     * Send notification to specific users
     * Creates in-app notification records and queues FCM jobs
     *
     * @param Collection|array $users Collection of User models or array of user IDs
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Custom data for deep linking
     * @return void
     */
    public function notifyUsers(
        Collection|array $users,
        string $title,
        string $body,
        array $data = []
    ): void
    {
        // Convert array of IDs to User collection if needed
        if (is_array($users)) {
            $users = User::whereIn('id', $users)->get();
        }

        if ($users->isEmpty()) {
            Log::warning('NotificationService: No users to notify');
            return;
        }

        // Create in-app notification records for each user
        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }

        // Get all device tokens for these users
        $userIds = $users->pluck('id')->toArray();
        $tokens = UserDeviceToken::whereIn('user_id', $userIds)
            ->pluck('device_token')
            ->toArray();

        // Queue FCM notification job if there are tokens
        if (!empty($tokens)) {
            SendFcmNotification::dispatch($tokens, $title, $body, $data);
            
            Log::info('NotificationService: Queued FCM notification', [
                'user_count' => count($userIds),
                'token_count' => count($tokens),
                'title' => $title,
            ]);
        } else {
            Log::info('NotificationService: No device tokens found for users', [
                'user_ids' => $userIds,
            ]);
        }
    }

    /**
     * Send notification to a single user
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function notifyUser(
        User $user,
        string $title,
        string $body,
        array $data = []
    ): void
    {
        $this->notifyUsers(collect([$user]), $title, $body, $data);
    }

    /**
     * Send notification to all users with a specific role
     *
     * @param string $roleName The role name (slug) to notify
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function notifyRole(
        string $roleName,
        string $title,
        string $body,
        array $data = []
    ): void {
        $users = User::whereHas('role', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        })->get();

        if ($users->isNotEmpty()) {
            $this->notifyUsers($users, $title, $body, $data);
        } else {
            Log::warning('NotificationService: No users found for role: ' . $roleName);
        }
    }

    /**
     * Notify approvers when approval is needed
     * Sends notification to the next pending approver(s) for an approval request
     *
     * @param \App\Models\ApprovalRequest $request
     * @return void
     */
    public function notifyApprovers(\App\Models\ApprovalRequest $request): void
    {
        // Get only the FIRST pending approval step for each item
        $request->load('items.currentStep');
        
        $pendingSteps = collect();
        foreach ($request->items as $item) {
            $step = $item->currentStep;
            if ($step && ($step->step_phase ?? 'approval') === 'approval' && $step->status === 'pending') {
                $pendingSteps->push($step);
            }
        }

        if ($pendingSteps->isEmpty()) {
            Log::info('NotificationService: No active pending approval steps found', [
                'approval_request_id' => $request->id,
            ]);
            return;
        }

        // Group steps by approver to avoid duplicate notifications
        $approverUsers = collect();

        foreach ($pendingSteps as $step) {
            // Determine who can approve this step
            $approvers = $this->getApproversForStep($step);
            $approverUsers = $approverUsers->merge($approvers);
        }

        // Remove duplicates
        $approverUsers = $approverUsers->unique('id');

        if ($approverUsers->isEmpty()) {
            Log::warning('NotificationService: No approvers found for pending steps', [
                'approval_request_id' => $request->id,
            ]);
            return;
        }

        // Prepare notification
        $title = 'Persetujuan Baru Diperlukan';
        $body = sprintf(
            'Anda perlu menyetujui pengajuan %s dari %s',
            $request->request_number,
            $request->requester->name ?? 'Unknown'
        );

        $data = [
            'type' => 'approval_required',
            'source' => 'sc',
            'approval_request_id' => (string)$request->id,
            'request_number' => $request->request_number,
        ];

        // Send notification
        $this->notifyUsers($approverUsers, $title, $body, $data);
    }

    /**
     * Notify requester when their request is approved
     *
     * @param \App\Models\ApprovalRequest $request
     * @return void
     */
    public function notifyRequesterApproved(\App\Models\ApprovalRequest $request): void
    {
        if (!$request->requester) {
            Log::warning('NotificationService: No requester found for approval request', [
                'approval_request_id' => $request->id,
            ]);
            return;
        }

        $title = 'Pengajuan Disetujui';
        $body = sprintf(
            'Pengajuan %s Anda telah disetujui',
            $request->request_number
        );

        $data = [
            'type' => 'request_approved',
            'source' => 'sc',
            'approval_request_id' => (string)$request->id,
            'request_number' => $request->request_number,
        ];

        $this->notifyUser($request->requester, $title, $body, $data);
    }

    /**
     * Notify requester when their request is rejected
     *
     * @param \App\Models\ApprovalRequest $request
     * @param string $reason Rejection reason
     * @return void
     */
    public function notifyRequesterRejected(
        \App\Models\ApprovalRequest $request,
        string $reason = ''
    ): void
    {
        if (!$request->requester) {
            Log::warning('NotificationService: No requester found for approval request', [
                'approval_request_id' => $request->id,
            ]);
            return;
        }

        $title = 'Pengajuan Ditolak';
        $body = sprintf(
            'Pengajuan %s Anda telah ditolak',
            $request->request_number
        );

        if ($reason) {
            $body .= ': ' . $reason;
        }

        $data = [
            'type' => 'request_rejected',
            'source' => 'sc',
            'approval_request_id' => (string)$request->id,
            'request_number' => $request->request_number,
            'rejection_reason' => $reason,
        ];

        $this->notifyUser($request->requester, $title, $body, $data);
    }

    /**
     * Notify when purchasing item status changes
     * Sends notification to the requester about purchasing progress
     *
     * @param \App\Models\PurchasingItem $item
     * @param string $oldStatus
     * @param string $newStatus
     * @return void
     */
    public function notifyPurchasingStatusChange(
        \App\Models\PurchasingItem $item,
        string $oldStatus,
        string $newStatus
    ): void
    {
        // Load necessary relationships
        $item->load(['approvalRequest.requester', 'masterItem', 'preferredVendor']);

        if (!$item->approvalRequest || !$item->approvalRequest->requester) {
            Log::warning('NotificationService: No requester found for purchasing item', [
                'purchasing_item_id' => $item->id,
            ]);
            return;
        }

        // Map status to Indonesian labels
        $statusLabels = [
            'unprocessed' => 'Belum diproses',
            'benchmarking' => 'Pemilihan vendor',
            'selected' => 'Vendor terpilih',
            'po_issued' => 'PO diterbitkan',
            'grn_received' => 'Barang diterima',
            'done' => 'Selesai',
        ];

        $newStatusLabel = $statusLabels[$newStatus] ?? $newStatus;
        $itemName = $item->masterItem->name ?? 'Item';

        $title = 'Status Purchasing Berubah';
        $body = sprintf(
            'Status purchasing untuk %s (%s) berubah menjadi: %s',
            $itemName,
            $item->approvalRequest->request_number,
            $newStatusLabel
        );

        // Prepare data payload with purchasing item details
        $data = [
            'type' => 'purchasing_status_change',
            'source' => 'sc',
            'purchasing_item_id' => (string)$item->id,
            'approval_request_id' => (string)$item->approval_request_id,
            'request_number' => $item->approvalRequest->request_number,
            'item_name' => $itemName,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'new_status_label' => $newStatusLabel,
        ];

        // Add additional details based on status
        if ($newStatus === 'selected' && $item->preferredVendor) {
            $data['preferred_vendor_name'] = $item->preferredVendor->name;
            $data['preferred_total_price'] = (string)$item->preferred_total_price;
        }

        if ($newStatus === 'po_issued' && $item->po_number) {
            $data['po_number'] = $item->po_number;
        }

        if ($newStatus === 'grn_received' && $item->grn_date) {
            $data['grn_date'] = $item->grn_date->format('Y-m-d');
        }

        if ($newStatus === 'done' && $item->done_notes) {
            $data['done_notes'] = $item->done_notes;
        }

        // Send notification to requester
        $this->notifyUser($item->approvalRequest->requester, $title, $body, $data);

        Log::info('NotificationService: Purchasing status change notification sent', [
            'purchasing_item_id' => $item->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'requester_id' => $item->approvalRequest->requester->id,
        ]);
    }

    /**
     * Notify release approver when release step is waiting for approval
     * Sends notification to the approver(s) for a specific release step
     *
     * @param \App\Models\ApprovalItemStep $step
     * @return void
     */
    public function notifyReleaseApprover(\App\Models\ApprovalItemStep $step): void
    {
        // Ensure this is a release phase step
        if (!$step->isReleasePhase()) {
            Log::warning('NotificationService: Step is not a release phase step', [
                'step_id' => $step->id,
                'step_phase' => $step->step_phase,
            ]);
            return;
        }

        // Load necessary relationships
        $step->load(['approvalRequest.requester', 'masterItem', 'requestItem']);

        if (!$step->approvalRequest) {
            Log::warning('NotificationService: No approval request found for release step', [
                'step_id' => $step->id,
            ]);
            return;
        }

        // Get approvers for this step
        $approvers = $this->getApproversForStep($step);

        if ($approvers->isEmpty()) {
            Log::warning('NotificationService: No approvers found for release step', [
                'step_id' => $step->id,
                'step_name' => $step->step_name,
            ]);
            return;
        }

        // Prepare notification
        $itemName = $step->masterItem->name ?? 'Item';
        $requestNumber = $step->approvalRequest->request_number;
        $requesterName = $step->approvalRequest->requester->name ?? 'Unknown';

        $title = 'Persetujuan Release Diperlukan';
        $body = sprintf(
            'Anda perlu menyetujui release untuk %s (%s) dari %s',
            $itemName,
            $requestNumber,
            $requesterName
        );

        // Prepare data payload with release step details
        $data = [
            'type' => 'release_approval_required',
            'source' => 'sc',
            'approval_request_id' => (string)$step->approval_request_id,
            'request_number' => $requestNumber,
            'item_id' => (string)($step->approval_request_item_id ?? $step->requestItem->id ?? ''),
            'step_id' => (string)$step->id,
            'step_name' => $step->step_name,
            'step_number' => (string)$step->step_number,
            'item_name' => $itemName,
            'requester_name' => $requesterName,
        ];

        // Add purchasing details if available
        $purchasingItem = \App\Models\PurchasingItem::where('approval_request_id', $step->approval_request_id)
            ->where('master_item_id', $step->master_item_id)
            ->first();

        if ($purchasingItem) {
            $purchasingItem->load('preferredVendor');
            
            if ($purchasingItem->preferredVendor) {
                $data['preferred_vendor_name'] = $purchasingItem->preferredVendor->name;
            }
            
            if ($purchasingItem->preferred_total_price) {
                $data['total_price'] = (string)$purchasingItem->preferred_total_price;
            }
            
            if ($purchasingItem->po_number) {
                $data['po_number'] = $purchasingItem->po_number;
            }
        }

        // Send notification
        $this->notifyUsers($approvers, $title, $body, $data);

        Log::info('NotificationService: Release approver notification sent', [
            'step_id' => $step->id,
            'step_name' => $step->step_name,
            'approver_count' => $approvers->count(),
        ]);
    }

    /**
     * Notify when release status changes (approved/rejected)
     * Sends notification to requester about release status change
     *
     * @param \App\Models\ApprovalRequestItem $item
     * @param string $action Action taken: 'approved' or 'rejected'
     * @param string $notes Optional notes or rejection reason
     * @return void
     */
    public function notifyReleaseStatusChange(
        \App\Models\ApprovalRequestItem $item,
        string $action,
        string $notes = ''
    ): void
    {
        // Load necessary relationships
        $item->load(['approvalRequest.requester', 'masterItem']);

        if (!$item->approvalRequest || !$item->approvalRequest->requester) {
            Log::warning('NotificationService: No requester found for release item', [
                'item_id' => $item->id,
            ]);
            return;
        }

        $itemName = $item->masterItem->name ?? 'Item';
        $requestNumber = $item->approvalRequest->request_number;

        // Prepare notification based on action
        if ($action === 'approved') {
            $title = 'Release Disetujui';
            $body = sprintf(
                'Release untuk %s (%s) telah disetujui',
                $itemName,
                $requestNumber
            );
            
            $dataType = 'release_approved';
        } else {
            $title = 'Release Ditolak';
            $body = sprintf(
                'Release untuk %s (%s) telah ditolak',
                $itemName,
                $requestNumber
            );
            
            if ($notes) {
                $body .= ': ' . $notes;
            }
            
            $dataType = 'release_rejected';
        }

        // Prepare data payload
        $data = [
            'type' => $dataType,
            'source' => 'sc',
            'approval_request_id' => (string)$item->approval_request_id,
            'request_number' => $requestNumber,
            'item_id' => (string)$item->id,
            'item_name' => $itemName,
            'action' => $action,
        ];

        if ($notes) {
            $data['notes'] = $notes;
        }

        // Add release step details
        $releaseSteps = $item->steps()
            ->where('step_phase', 'release')
            ->orderBy('step_number')
            ->get();

        if ($releaseSteps->isNotEmpty()) {
            $data['release_steps'] = $releaseSteps->map(function ($step) {
                return [
                    'step_number' => $step->step_number,
                    'step_name' => $step->step_name,
                    'status' => $step->status,
                    'approved_at' => $step->approved_at?->format('Y-m-d H:i:s'),
                ];
            })->toArray();
        }

        // Add purchasing details if available
        $purchasingItem = \App\Models\PurchasingItem::where('approval_request_id', $item->approval_request_id)
            ->where('master_item_id', $item->master_item_id)
            ->first();

        if ($purchasingItem) {
            $purchasingItem->load('preferredVendor');
            
            if ($purchasingItem->preferredVendor) {
                $data['preferred_vendor_name'] = $purchasingItem->preferredVendor->name;
            }
            
            if ($purchasingItem->preferred_total_price) {
                $data['total_price'] = (string)$purchasingItem->preferred_total_price;
            }
            
            if ($purchasingItem->po_number) {
                $data['po_number'] = $purchasingItem->po_number;
            }
        }

        // Send notification to requester
        $this->notifyUser($item->approvalRequest->requester, $title, $body, $data);

        Log::info('NotificationService: Release status change notification sent', [
            'item_id' => $item->id,
            'action' => $action,
            'requester_id' => $item->approvalRequest->requester->id,
        ]);
    }

    /**
     * Get approvers for a specific approval step
     * Returns collection of User models who can approve this step
     *
     * @param \App\Models\ApprovalItemStep $step
     * @return Collection
     */
    private function getApproversForStep(\App\Models\ApprovalItemStep $step): Collection
    {
        $approvers = collect();

        switch ($step->approver_type) {
            case 'user':
                if ($step->approver_id) {
                    $user = User::find($step->approver_id);
                    if ($user) {
                        $approvers->push($user);
                    }
                }
                break;

            case 'role':
                if ($step->approver_role_id) {
                    $users = User::where('role_id', $step->approver_role_id)->get();
                    $approvers = $approvers->merge($users);
                }
                break;

            case 'department_manager':
                if ($step->approver_department_id) {
                    $dept = \App\Models\Department::find($step->approver_department_id);
                    if ($dept && $dept->manager_id) {
                        $user = User::find($dept->manager_id);
                        if ($user) {
                            $approvers->push($user);
                        }
                    }
                }
                break;

            case 'requester_department_manager':
                if ($step->approvalRequest && $step->approvalRequest->requester) {
                    $primary = $step->approvalRequest->requester
                        ->departments()
                        ->wherePivot('is_primary', true)
                        ->first();
                    
                    if ($primary && $primary->manager_id) {
                        $user = User::find($primary->manager_id);
                        if ($user) {
                            $approvers->push($user);
                        }
                    }
                }
                break;

            case 'allocation_department_manager':
                $requestItem = \App\Models\ApprovalRequestItem::where('approval_request_id', $step->approval_request_id)
                    ->where('master_item_id', $step->master_item_id)
                    ->first();
                
                if ($requestItem && $requestItem->allocationDepartment && $requestItem->allocationDepartment->manager_id) {
                    $user = User::find($requestItem->allocationDepartment->manager_id);
                    if ($user) {
                        $approvers->push($user);
                    }
                }
                break;

            case 'any_department_manager':
                // Get all department managers
                $managers = User::whereHas('departments', function ($query) {
                    $query->wherePivot('is_manager', true);
                })->get();
                $approvers = $approvers->merge($managers);
                break;
        }

        return $approvers;
    }
}
