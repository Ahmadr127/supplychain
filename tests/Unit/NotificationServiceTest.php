<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\NotificationService;
use App\Services\FirebaseService;
use App\Models\User;
use App\Models\Notification;
use App\Models\UserDeviceToken;
use App\Jobs\SendFcmNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;
    protected FirebaseService $firebaseService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->firebaseService = $this->createMock(FirebaseService::class);
        $this->service = new NotificationService($this->firebaseService);
    }

    public function test_notify_users_creates_in_app_notifications(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        
        $this->service->notifyUsers(
            collect([$user]),
            'Test Title',
            'Test Body',
            ['type' => 'test']
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Test Title',
            'body' => 'Test Body',
        ]);

        $notification = Notification::where('user_id', $user->id)->first();
        $this->assertEquals(['type' => 'test'], $notification->data);
    }

    public function test_notify_users_queues_fcm_job_when_tokens_exist(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        UserDeviceToken::create([
            'user_id' => $user->id,
            'device_token' => 'test-token-123',
            'device_type' => 'android',
        ]);

        $this->service->notifyUsers(
            collect([$user]),
            'Test Title',
            'Test Body',
            ['type' => 'test']
        );

        Queue::assertPushed(SendFcmNotification::class, function ($job) {
            return $job->tokens === ['test-token-123'];
        });
    }

    public function test_notify_users_does_not_queue_fcm_job_when_no_tokens(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->service->notifyUsers(
            collect([$user]),
            'Test Title',
            'Test Body'
        );

        Queue::assertNotPushed(SendFcmNotification::class);
    }

    public function test_notify_user_sends_to_single_user(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->service->notifyUser(
            $user,
            'Test Title',
            'Test Body',
            ['type' => 'test']
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Test Title',
        ]);
    }

    public function test_notify_users_handles_multiple_users(): void
    {
        Queue::fake();

        $users = User::factory()->count(3)->create();

        $this->service->notifyUsers(
            $users,
            'Test Title',
            'Test Body'
        );

        foreach ($users as $user) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $user->id,
                'title' => 'Test Title',
            ]);
        }
    }

    public function test_notify_users_accepts_array_of_user_ids(): void
    {
        Queue::fake();

        $users = User::factory()->count(2)->create();
        $userIds = $users->pluck('id')->toArray();

        $this->service->notifyUsers(
            $userIds,
            'Test Title',
            'Test Body'
        );

        foreach ($users as $user) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $user->id,
                'title' => 'Test Title',
            ]);
        }
    }

    public function test_notify_approvers_sends_to_pending_approvers(): void
    {
        Queue::fake();

        $requester = User::factory()->create(['name' => 'John Requester']);
        $approver = User::factory()->create(['name' => 'Jane Approver']);
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'request_number' => 'REQ-2025-001',
            'requester_id' => $requester->id,
        ]);

        // Create a pending approval step
        \App\Models\ApprovalItemStep::factory()->create([
            'approval_request_id' => $request->id,
            'step_phase' => 'approval',
            'status' => 'pending',
            'approver_type' => 'user',
            'approver_id' => $approver->id,
            'step_number' => 1,
        ]);

        $this->service->notifyApprovers($request);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $approver->id,
            'title' => 'Persetujuan Baru Diperlukan',
        ]);

        $notification = Notification::where('user_id', $approver->id)->first();
        $this->assertEquals('approval_required', $notification->data['type']);
        $this->assertEquals($request->request_number, $notification->data['request_number']);
    }

    public function test_notify_approvers_does_not_send_for_release_phase(): void
    {
        Queue::fake();

        $requester = User::factory()->create();
        $approver = User::factory()->create();
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'requester_id' => $requester->id,
        ]);

        // Create a release phase step (should not be notified by notifyApprovers)
        \App\Models\ApprovalItemStep::factory()->create([
            'approval_request_id' => $request->id,
            'step_phase' => 'release',
            'status' => 'pending',
            'approver_type' => 'user',
            'approver_id' => $approver->id,
        ]);

        $this->service->notifyApprovers($request);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $approver->id,
        ]);
    }

    public function test_notify_requester_approved_sends_to_requester(): void
    {
        Queue::fake();

        $requester = User::factory()->create(['name' => 'John Requester']);
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'request_number' => 'REQ-2025-001',
            'requester_id' => $requester->id,
        ]);

        $this->service->notifyRequesterApproved($request);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $requester->id,
            'title' => 'Pengajuan Disetujui',
        ]);

        $notification = Notification::where('user_id', $requester->id)->first();
        $this->assertStringContainsString('REQ-2025-001', $notification->body);
        $this->assertEquals('request_approved', $notification->data['type']);
    }

    public function test_notify_requester_rejected_sends_to_requester_with_reason(): void
    {
        Queue::fake();

        $requester = User::factory()->create(['name' => 'John Requester']);
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'request_number' => 'REQ-2025-001',
            'requester_id' => $requester->id,
        ]);

        $reason = 'Budget tidak tersedia';
        $this->service->notifyRequesterRejected($request, $reason);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $requester->id,
            'title' => 'Pengajuan Ditolak',
        ]);

        $notification = Notification::where('user_id', $requester->id)->first();
        $this->assertStringContainsString('REQ-2025-001', $notification->body);
        $this->assertStringContainsString($reason, $notification->body);
        $this->assertEquals('request_rejected', $notification->data['type']);
        $this->assertEquals($reason, $notification->data['rejection_reason']);
    }

    public function test_notify_requester_rejected_works_without_reason(): void
    {
        Queue::fake();

        $requester = User::factory()->create();
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'request_number' => 'REQ-2025-001',
            'requester_id' => $requester->id,
        ]);

        $this->service->notifyRequesterRejected($request);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $requester->id,
            'title' => 'Pengajuan Ditolak',
        ]);
    }

    public function test_notify_approvers_handles_role_based_approver(): void
    {
        Queue::fake();

        $requester = User::factory()->create();
        $role = \App\Models\Role::factory()->create(['name' => 'Manager']);
        $approver1 = User::factory()->create(['role_id' => $role->id]);
        $approver2 = User::factory()->create(['role_id' => $role->id]);
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'requester_id' => $requester->id,
        ]);

        \App\Models\ApprovalItemStep::factory()->create([
            'approval_request_id' => $request->id,
            'step_phase' => 'approval',
            'status' => 'pending',
            'approver_type' => 'role',
            'approver_role_id' => $role->id,
        ]);

        $this->service->notifyApprovers($request);

        // Both users with the role should receive notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $approver1->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $approver2->id,
        ]);
    }

    public function test_notify_approvers_avoids_duplicate_notifications(): void
    {
        Queue::fake();

        $requester = User::factory()->create();
        $approver = User::factory()->create();
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'requester_id' => $requester->id,
        ]);

        // Create two pending steps for the same approver
        \App\Models\ApprovalItemStep::factory()->create([
            'approval_request_id' => $request->id,
            'step_phase' => 'approval',
            'status' => 'pending',
            'approver_type' => 'user',
            'approver_id' => $approver->id,
            'step_number' => 1,
        ]);

        \App\Models\ApprovalItemStep::factory()->create([
            'approval_request_id' => $request->id,
            'step_phase' => 'approval',
            'status' => 'pending',
            'approver_type' => 'user',
            'approver_id' => $approver->id,
            'step_number' => 2,
        ]);

        $this->service->notifyApprovers($request);

        // Should only create one notification for the approver
        $count = Notification::where('user_id', $approver->id)->count();
        $this->assertEquals(1, $count);
    }

    public function test_notify_purchasing_status_change_sends_to_requester(): void
    {
        Queue::fake();

        $requester = User::factory()->create(['name' => 'John Requester']);
        $supplier = \App\Models\Supplier::factory()->create(['name' => 'PT Supplier A']);
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'request_number' => 'REQ-2025-001',
            'requester_id' => $requester->id,
        ]);

        $masterItem = \App\Models\MasterItem::factory()->create([
            'name' => 'Laptop Dell',
        ]);

        $purchasingItem = \App\Models\PurchasingItem::factory()->create([
            'approval_request_id' => $request->id,
            'master_item_id' => $masterItem->id,
            'status' => 'selected',
            'preferred_vendor_id' => $supplier->id,
            'preferred_total_price' => 75000000,
        ]);

        $this->service->notifyPurchasingStatusChange(
            $purchasingItem,
            'benchmarking',
            'selected'
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $requester->id,
            'title' => 'Status Purchasing Berubah',
        ]);

        $notification = Notification::where('user_id', $requester->id)->first();
        $this->assertStringContainsString('Laptop Dell', $notification->body);
        $this->assertStringContainsString('REQ-2025-001', $notification->body);
        $this->assertStringContainsString('Vendor terpilih', $notification->body);
        
        $this->assertEquals('purchasing_status_change', $notification->data['type']);
        $this->assertEquals((string)$purchasingItem->id, $notification->data['purchasing_item_id']);
        $this->assertEquals('REQ-2025-001', $notification->data['request_number']);
        $this->assertEquals('benchmarking', $notification->data['old_status']);
        $this->assertEquals('selected', $notification->data['new_status']);
        $this->assertEquals('PT Supplier A', $notification->data['preferred_vendor_name']);
        $this->assertEquals('75000000', $notification->data['preferred_total_price']);
    }

    public function test_notify_purchasing_status_change_includes_po_number(): void
    {
        Queue::fake();

        $requester = User::factory()->create();
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'request_number' => 'REQ-2025-001',
            'requester_id' => $requester->id,
        ]);

        $masterItem = \App\Models\MasterItem::factory()->create([
            'name' => 'Laptop Dell',
        ]);

        $purchasingItem = \App\Models\PurchasingItem::factory()->create([
            'approval_request_id' => $request->id,
            'master_item_id' => $masterItem->id,
            'status' => 'po_issued',
            'po_number' => 'PO-2025-001',
        ]);

        $this->service->notifyPurchasingStatusChange(
            $purchasingItem,
            'selected',
            'po_issued'
        );

        $notification = Notification::where('user_id', $requester->id)->first();
        $this->assertEquals('PO-2025-001', $notification->data['po_number']);
    }

    public function test_notify_purchasing_status_change_includes_grn_date(): void
    {
        Queue::fake();

        $requester = User::factory()->create();
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'request_number' => 'REQ-2025-001',
            'requester_id' => $requester->id,
        ]);

        $masterItem = \App\Models\MasterItem::factory()->create([
            'name' => 'Laptop Dell',
        ]);

        $purchasingItem = \App\Models\PurchasingItem::factory()->create([
            'approval_request_id' => $request->id,
            'master_item_id' => $masterItem->id,
            'status' => 'grn_received',
            'grn_date' => '2025-01-20',
        ]);

        $this->service->notifyPurchasingStatusChange(
            $purchasingItem,
            'po_issued',
            'grn_received'
        );

        $notification = Notification::where('user_id', $requester->id)->first();
        $this->assertEquals('2025-01-20', $notification->data['grn_date']);
    }

    public function test_notify_purchasing_status_change_includes_done_notes(): void
    {
        Queue::fake();

        $requester = User::factory()->create();
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'request_number' => 'REQ-2025-001',
            'requester_id' => $requester->id,
        ]);

        $masterItem = \App\Models\MasterItem::factory()->create([
            'name' => 'Laptop Dell',
        ]);

        $purchasingItem = \App\Models\PurchasingItem::factory()->create([
            'approval_request_id' => $request->id,
            'master_item_id' => $masterItem->id,
            'status' => 'done',
            'done_notes' => 'Semua barang sudah diterima dengan baik',
        ]);

        $this->service->notifyPurchasingStatusChange(
            $purchasingItem,
            'grn_received',
            'done'
        );

        $notification = Notification::where('user_id', $requester->id)->first();
        $this->assertEquals('Semua barang sudah diterima dengan baik', $notification->data['done_notes']);
    }

    public function test_notify_purchasing_status_change_handles_missing_requester(): void
    {
        Queue::fake();

        $masterItem = \App\Models\MasterItem::factory()->create([
            'name' => 'Laptop Dell',
        ]);

        $request = \App\Models\ApprovalRequest::factory()->create([
            'requester_id' => null, // No requester
        ]);

        $purchasingItem = \App\Models\PurchasingItem::factory()->create([
            'approval_request_id' => $request->id,
            'master_item_id' => $masterItem->id,
            'status' => 'selected',
        ]);

        // Should not throw exception
        $this->service->notifyPurchasingStatusChange(
            $purchasingItem,
            'benchmarking',
            'selected'
        );

        // Should not create any notification
        $this->assertEquals(0, Notification::count());
    }

    public function test_notify_release_approver_sends_to_release_approver(): void
    {
        Queue::fake();

        $requester = User::factory()->create(['name' => 'John Requester']);
        $approver = User::factory()->create(['name' => 'Jane Approver']);
        $supplier = \App\Models\Supplier::factory()->create(['name' => 'PT Supplier A']);
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'request_number' => 'REQ-2025-001',
            'requester_id' => $requester->id,
        ]);

        $masterItem = \App\Models\MasterItem::factory()->create([
            'name' => 'Laptop Dell',
        ]);

        $requestItem = \App\Models\ApprovalRequestItem::factory()->create([
            'approval_request_id' => $request->id,
            'master_item_id' => $masterItem->id,
        ]);

        // Create purchasing item with preferred vendor
        $purchasingItem = \App\Models\PurchasingItem::factory()->create([
            'approval_request_id' => $request->id,
            'master_item_id' => $masterItem->id,
            'status' => 'selected',
            'preferred_vendor_id' => $supplier->id,
            'preferred_total_price' => 75000000,
            'po_number' => 'PO-2025-001',
        ]);

        // Create a release phase step
        $releaseStep = \App\Models\ApprovalItemStep::factory()->create([
            'approval_request_id' => $request->id,
            'approval_request_item_id' => $requestItem->id,
            'master_item_id' => $masterItem->id,
            'step_phase' => 'release',
            'step_name' => 'Manager PT Release',
            'step_number' => 1,
            'status' => 'pending',
            'approver_type' => 'user',
            'approver_id' => $approver->id,
        ]);

        $this->service->notifyReleaseApprover($releaseStep);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $approver->id,
            'title' => 'Persetujuan Release Diperlukan',
        ]);

        $notification = Notification::where('user_id', $approver->id)->first();
        $this->assertStringContainsString('Laptop Dell', $notification->body);
        $this->assertStringContainsString('REQ-2025-001', $notification->body);
        $this->assertStringContainsString('John Requester', $notification->body);
        
        $this->assertEquals('release_approval_required', $notification->data['type']);
        $this->assertEquals($request->request_number, $notification->data['request_number']);
        $this->assertEquals((string)$releaseStep->id, $notification->data['step_id']);
        $this->assertEquals('Manager PT Release', $notification->data['step_name']);
        $this->assertEquals('PT Supplier A', $notification->data['preferred_vendor_name']);
        $this->assertEquals('75000000', $notification->data['total_price']);
        $this->assertEquals('PO-2025-001', $notification->data['po_number']);
    }

    public function test_notify_release_approver_does_not_send_for_approval_phase(): void
    {
        Queue::fake();

        $requester = User::factory()->create();
        $approver = User::factory()->create();
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'requester_id' => $requester->id,
        ]);

        $masterItem = \App\Models\MasterItem::factory()->create();

        // Create an approval phase step (not release)
        $approvalStep = \App\Models\ApprovalItemStep::factory()->create([
            'approval_request_id' => $request->id,
            'master_item_id' => $masterItem->id,
            'step_phase' => 'approval',
            'status' => 'pending',
            'approver_type' => 'user',
            'approver_id' => $approver->id,
        ]);

        $this->service->notifyReleaseApprover($approvalStep);

        // Should not create any notification
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $approver->id,
        ]);
    }

    public function test_notify_release_approver_handles_role_based_approver(): void
    {
        Queue::fake();

        $requester = User::factory()->create();
        $role = \App\Models\Role::factory()->create(['name' => 'Finance Manager']);
        $approver1 = User::factory()->create(['role_id' => $role->id]);
        $approver2 = User::factory()->create(['role_id' => $role->id]);
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'request_number' => 'REQ-2025-001',
            'requester_id' => $requester->id,
        ]);

        $masterItem = \App\Models\MasterItem::factory()->create(['name' => 'Laptop Dell']);

        $releaseStep = \App\Models\ApprovalItemStep::factory()->create([
            'approval_request_id' => $request->id,
            'master_item_id' => $masterItem->id,
            'step_phase' => 'release',
            'status' => 'pending',
            'approver_type' => 'role',
            'approver_role_id' => $role->id,
        ]);

        $this->service->notifyReleaseApprover($releaseStep);

        // Both users with the role should receive notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $approver1->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $approver2->id,
        ]);
    }

    public function test_notify_release_status_change_approved_sends_to_requester(): void
    {
        Queue::fake();

        $requester = User::factory()->create(['name' => 'John Requester']);
        $supplier = \App\Models\Supplier::factory()->create(['name' => 'PT Supplier A']);
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'request_number' => 'REQ-2025-001',
            'requester_id' => $requester->id,
        ]);

        $masterItem = \App\Models\MasterItem::factory()->create([
            'name' => 'Laptop Dell',
        ]);

        $requestItem = \App\Models\ApprovalRequestItem::factory()->create([
            'approval_request_id' => $request->id,
            'master_item_id' => $masterItem->id,
            'status' => 'done',
        ]);

        // Create purchasing item
        $purchasingItem = \App\Models\PurchasingItem::factory()->create([
            'approval_request_id' => $request->id,
            'master_item_id' => $masterItem->id,
            'preferred_vendor_id' => $supplier->id,
            'preferred_total_price' => 75000000,
            'po_number' => 'PO-2025-001',
        ]);

        // Create release steps
        $releaseStep1 = \App\Models\ApprovalItemStep::factory()->create([
            'approval_request_id' => $request->id,
            'approval_request_item_id' => $requestItem->id,
            'master_item_id' => $masterItem->id,
            'step_phase' => 'release',
            'step_name' => 'Manager PT Release',
            'step_number' => 1,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $releaseStep2 = \App\Models\ApprovalItemStep::factory()->create([
            'approval_request_id' => $request->id,
            'approval_request_item_id' => $requestItem->id,
            'master_item_id' => $masterItem->id,
            'step_phase' => 'release',
            'step_name' => 'Direktur PT Release',
            'step_number' => 2,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->service->notifyReleaseStatusChange($requestItem, 'approved');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $requester->id,
            'title' => 'Release Disetujui',
        ]);

        $notification = Notification::where('user_id', $requester->id)->first();
        $this->assertStringContainsString('Laptop Dell', $notification->body);
        $this->assertStringContainsString('REQ-2025-001', $notification->body);
        
        $this->assertEquals('release_approved', $notification->data['type']);
        $this->assertEquals($request->request_number, $notification->data['request_number']);
        $this->assertEquals((string)$requestItem->id, $notification->data['item_id']);
        $this->assertEquals('approved', $notification->data['action']);
        $this->assertEquals('PT Supplier A', $notification->data['preferred_vendor_name']);
        $this->assertEquals('75000000', $notification->data['total_price']);
        $this->assertEquals('PO-2025-001', $notification->data['po_number']);
        
        // Check release steps are included
        $this->assertArrayHasKey('release_steps', $notification->data);
        $this->assertCount(2, $notification->data['release_steps']);
        $this->assertEquals('Manager PT Release', $notification->data['release_steps'][0]['step_name']);
        $this->assertEquals('approved', $notification->data['release_steps'][0]['status']);
    }

    public function test_notify_release_status_change_rejected_sends_to_requester_with_notes(): void
    {
        Queue::fake();

        $requester = User::factory()->create(['name' => 'John Requester']);
        
        $request = \App\Models\ApprovalRequest::factory()->create([
            'request_number' => 'REQ-2025-001',
            'requester_id' => $requester->id,
        ]);

        $masterItem = \App\Models\MasterItem::factory()->create([
            'name' => 'Laptop Dell',
        ]);

        $requestItem = \App\Models\ApprovalRequestItem::factory()->create([
            'approval_request_id' => $request->id,
            'master_item_id' => $masterItem->id,
            'status' => 'rejected',
        ]);

        $rejectionNotes = 'Budget tidak mencukupi untuk release';
        $this->service->notifyReleaseStatusChange($requestItem, 'rejected', $rejectionNotes);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $requester->id,
            'title' => 'Release Ditolak',
        ]);

        $notification = Notification::where('user_id', $requester->id)->first();
        $this->assertStringContainsString('Laptop Dell', $notification->body);
        $this->assertStringContainsString('REQ-2025-001', $notification->body);
        $this->assertStringContainsString($rejectionNotes, $notification->body);
        
        $this->assertEquals('release_rejected', $notification->data['type']);
        $this->assertEquals('rejected', $notification->data['action']);
        $this->assertEquals($rejectionNotes, $notification->data['notes']);
    }

    public function test_notify_release_status_change_handles_missing_requester(): void
    {
        Queue::fake();

        $masterItem = \App\Models\MasterItem::factory()->create([
            'name' => 'Laptop Dell',
        ]);

        $request = \App\Models\ApprovalRequest::factory()->create([
            'requester_id' => null, // No requester
        ]);

        $requestItem = \App\Models\ApprovalRequestItem::factory()->create([
            'approval_request_id' => $request->id,
            'master_item_id' => $masterItem->id,
        ]);

        // Should not throw exception
        $this->service->notifyReleaseStatusChange($requestItem, 'approved');

        // Should not create any notification
        $this->assertEquals(0, Notification::count());
    }
}
