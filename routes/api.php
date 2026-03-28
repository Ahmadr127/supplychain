<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SsoController;
use App\Http\Controllers\Api\ApprovalRequestApiController;
use App\Http\Controllers\Api\ApprovalItemApiController;
use App\Http\Controllers\Api\CapexApiController;
use App\Http\Controllers\Api\CapexItemApiController;
use App\Http\Controllers\Api\FirebaseTestController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\PurchasingApiController;
use App\Http\Controllers\Api\ReleaseApiController;
use App\Http\Controllers\SupplierLookupController;

/*
|--------------------------------------------------------------------------
| Supplychain API Routes  (prefix: /api, middleware: api)
|--------------------------------------------------------------------------
| Public:    POST /api/sso-login
| Protected: everything else (auth:sanctum)
*/

Route::post('/sso-login', [SsoController::class, 'loginViaToken']);

Route::middleware('auth:sanctum')->group(function () {

    // Current user info
    Route::get('/user', function (Request $request) {
        $user = $request->user()->load(['role.permissions', 'primaryDepartment']);
        $primaryDept = $user->primaryDepartment->first();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'user'            => $user,
                'role'            => $user->role?->name,
                'permissions'     => $user->role?->permissions->pluck('name') ?? [],
                'department_id'   => $primaryDept?->id,
                'department_name' => $primaryDept?->name,
            ],
        ]);
    });

    // ----------------------------------------------------------------
    // Approval Requests  (ApprovalRequestApiController)
    // ----------------------------------------------------------------
    Route::prefix('approval-requests')->group(function () {
        Route::get('/mine',    [ApprovalRequestApiController::class, 'myRequests']);
        Route::get('/pending', [ApprovalRequestApiController::class, 'pending']);
        Route::get('/',        [ApprovalRequestApiController::class, 'index']);
        Route::get('/{approvalRequest}', [ApprovalRequestApiController::class, 'show']);
    });
    Route::get(
        '/approval-request-attachments/{attachmentId}/view',
        [ApprovalRequestApiController::class, 'viewAttachment']
    );
    Route::get(
        '/approval-request-attachments/{attachmentId}/download',
        [ApprovalRequestApiController::class, 'downloadAttachment']
    );

    // ----------------------------------------------------------------
    // Approval Items  (ApprovalItemApiController)
    // ----------------------------------------------------------------
    Route::prefix('approval-requests/{approvalRequest}/items/{item}')->group(function () {
        Route::get('/',        [ApprovalItemApiController::class, 'show']);
        Route::get('/status',  [ApprovalItemApiController::class, 'status']);
        Route::post('/approve',[ApprovalItemApiController::class, 'approve']);
        Route::post('/reject', [ApprovalItemApiController::class, 'reject']);
    });

    // FS Document routes (Flattened to match standard attachments)
    Route::get('approval-request-items/{item}/view-fs', [ApprovalItemApiController::class, 'viewFsDocument']);
    Route::get('approval-request-items/{item}/download-fs', [ApprovalItemApiController::class, 'downloadFsDocument']);

    // ----------------------------------------------------------------
    // CapEx Headers  (CapexApiController)
    // ----------------------------------------------------------------
    Route::prefix('capex')->group(function () {
        // Named sub-routes FIRST to avoid {capex} wildcard matching them
        Route::get('/available',   [CapexApiController::class, 'availableItems']);
        Route::get('/departments', [CapexApiController::class, 'departments']);
        Route::get('/budget-summary', [CapexApiController::class, 'budgetSummary']);

        Route::get('/',    [CapexApiController::class, 'index']);
        Route::post('/',   [CapexApiController::class, 'store']);
        Route::get('/{capex}',    [CapexApiController::class, 'show']);
        Route::patch('/{capex}',  [CapexApiController::class, 'update']);
        Route::delete('/{capex}', [CapexApiController::class, 'destroy']);

        // Item list scoped to a capex (read-only; writes go to /api/capex-items)
        Route::get('/{capex}/items',  [CapexApiController::class, 'indexItems']);

        // Item CREATE scoped to a capex
        Route::post('/{capex}/items', [CapexItemApiController::class, 'store']);
    });

    // ----------------------------------------------------------------
    // CapEx Items  (CapexItemApiController)
    // ----------------------------------------------------------------
    Route::prefix('capex-items/{item}')->group(function () {
        Route::get('/',    [CapexItemApiController::class, 'show']);
        Route::patch('/',  [CapexItemApiController::class, 'update']);
        Route::delete('/', [CapexItemApiController::class, 'destroy']);
    });

    // ----------------------------------------------------------------
    // Firebase Test  (FirebaseTestController)
    // ----------------------------------------------------------------
    Route::get('/firebase/ping', [FirebaseTestController::class, 'ping']);

    // ----------------------------------------------------------------
    // Notification Management  (NotificationApiController)
    // ----------------------------------------------------------------
    Route::get('/notifications', [NotificationApiController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationApiController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationApiController::class, 'markAllAsRead']);
    Route::patch('/notifications/{id}/read', [NotificationApiController::class, 'markAsRead']);
    // Backward compatibility aliases
    Route::put('/notifications/mark-all-read', [NotificationApiController::class, 'markAllAsRead']);
    Route::put('/notifications/{id}/read', [NotificationApiController::class, 'markAsRead']);

    // FCM Routes
    Route::post('/fcm-token', [FcmTokenController::class, 'store']);
    Route::delete('/fcm-token', [FcmTokenController::class, 'destroy']);
    Route::post('/test-notification', [FcmTokenController::class, 'testNotification']);


    // ----------------------------------------------------------------
    // Purchasing Management  (PurchasingApiController)
    // ----------------------------------------------------------------
    Route::get('/purchasing/items', [PurchasingApiController::class, 'index']);
    Route::get('/purchasing/items/{id}', [PurchasingApiController::class, 'show']);
    Route::post('/purchasing/items/{id}/set-received-date', [PurchasingApiController::class, 'setReceivedDate']);
    Route::post('/purchasing/items/{id}/benchmark', [PurchasingApiController::class, 'saveBenchmarking']);
    Route::post('/purchasing/items/{id}/select-vendor', [PurchasingApiController::class, 'selectPreferred']);
    Route::post('/purchasing/items/{id}/issue-po', [PurchasingApiController::class, 'issuePO']);
    Route::post('/purchasing/items/{id}/save-invoice', [PurchasingApiController::class, 'saveInvoice']);
    Route::post('/purchasing/items/{id}/receive-grn', [PurchasingApiController::class, 'receiveGRN']);
    Route::post('/purchasing/items/{id}/mark-done', [PurchasingApiController::class, 'markDone']);
    Route::get('/purchasing/status-by-request', [PurchasingApiController::class, 'statusByRequest']);

    // ----------------------------------------------------------------
    // Supplier Lookup
    // ----------------------------------------------------------------
    Route::prefix('suppliers')->group(function () {
        Route::get('/suggest', [SupplierLookupController::class, 'suggest']);
        Route::post('/resolve', [SupplierLookupController::class, 'resolve']);
    });

    // ----------------------------------------------------------------
    // Release Management  (ReleaseApiController)
    // ----------------------------------------------------------------
    Route::get('/release/items', [ReleaseApiController::class, 'index']);
    Route::get('/release/items/{id}', [ReleaseApiController::class, 'show']);
    Route::post('/release/items/{id}/approve', [ReleaseApiController::class, 'approve']);
    Route::post('/release/items/{id}/reject', [ReleaseApiController::class, 'reject']);
    Route::get('/release/my-pending', [ReleaseApiController::class, 'myPending']);
});
