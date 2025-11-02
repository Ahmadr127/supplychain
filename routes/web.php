<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ApprovalWorkflowController;
use App\Http\Controllers\ApprovalRequestController;
use App\Http\Controllers\MasterItemController;
use App\Http\Controllers\ItemTypeController;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\CommodityController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\ItemLookupController;
use App\Http\Controllers\SubmissionTypeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierLookupController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\PurchasingItemController;
use App\Http\Controllers\ApprovalRequestItemController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will be
| assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/login');
});

    // Static print view for Identifikasi Kebutuhan form
    Route::get('approval-requests/formstatis', function () {
        return view('approval-requests.formstatis');
    })->name('approval-requests.formstatis');

    // Minimal input view for Identifikasi Kebutuhan form
    Route::get('approval-requests/formstatis-input', function () {
        return view('approval-requests.formstatis_input');
    })->name('approval-requests.formstatis-input');


// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // User Management routes
    Route::middleware('permission:manage_users')->group(function () {
        Route::resource('users', UserController::class);
    });

    // Role Management routes
    Route::middleware('permission:manage_roles')->group(function () {
        Route::resource('roles', RoleController::class);
    });

    // Permission Management routes
    Route::middleware('permission:manage_permissions')->group(function () {
        Route::resource('permissions', PermissionController::class);
    });

    // Department Management routes
    Route::middleware('permission:manage_departments')->group(function () {
        Route::resource('departments', DepartmentController::class);
        Route::post('departments/{department}/assign-user', [DepartmentController::class, 'assignUser'])->name('departments.assign-user');
        Route::delete('departments/{department}/remove-user/{user}', [DepartmentController::class, 'removeUser'])->name('departments.remove-user');
    });

    // Approval Workflow Management routes
    Route::middleware('permission:manage_workflows')->group(function () {
        Route::resource('approval-workflows', ApprovalWorkflowController::class);
        Route::patch('approval-workflows/{approvalWorkflow}/toggle-status', [ApprovalWorkflowController::class, 'toggleStatus'])->name('approval-workflows.toggle-status');
    });

    // Approval Request routes with specific permissions
    Route::middleware('permission:view_all_approvals')->group(function () {
        Route::get('approval-requests', [ApprovalRequestController::class, 'index'])->name('approval-requests.index');
    });
    
    Route::middleware('permission:manage_approvals')->group(function () {
        Route::get('approval-requests/create', [ApprovalRequestController::class, 'create'])->name('approval-requests.create');
        Route::post('approval-requests', [ApprovalRequestController::class, 'store'])->name('approval-requests.store');
        Route::get('approval-requests/{approvalRequest}/edit', [ApprovalRequestController::class, 'edit'])->name('approval-requests.edit');
        Route::put('approval-requests/{approvalRequest}', [ApprovalRequestController::class, 'update'])->name('approval-requests.update');
        Route::delete('approval-requests/{approvalRequest}', [ApprovalRequestController::class, 'destroy'])->name('approval-requests.destroy');
    });
    
    // Per-item approval actions (NEW)
    Route::middleware('permission:approval')->group(function () {
        Route::post('approval-requests/{approvalRequest}/items/{item}/approve', [\App\Http\Controllers\ApprovalItemApprovalController::class, 'approve'])->name('approval.items.approve');
        Route::post('approval-requests/{approvalRequest}/items/{item}/reject', [\App\Http\Controllers\ApprovalItemApprovalController::class, 'reject'])->name('approval.items.reject');
        Route::post('approval-requests/{approvalRequest}/items/{item}/set-pending', [\App\Http\Controllers\ApprovalItemApprovalController::class, 'setPending'])->name('approval.items.setPending');
        
        // Simplified approval action (single endpoint for approve/reject)
        Route::post('approval-requests/approve-item', [ApprovalRequestController::class, 'approveItem'])->name('approval-requests.approve-item');
    });
    
    // Cancel request (keep at request level)
    Route::middleware('permission:manage_approvals')->group(function () {
        Route::post('approval-requests/{approvalRequest}/cancel', [ApprovalRequestController::class, 'cancel'])->name('approval-requests.cancel');
    });
    
    // Show approval request
    Route::get('approval-requests/{approvalRequest}', [ApprovalRequestController::class, 'show'])->name('approval-requests.show');

    // User-specific approval routes
    Route::middleware('permission:view_my_approvals')->group(function () {
        Route::get('my-requests', [ApprovalRequestController::class, 'myRequests'])->name('approval-requests.my-requests');
    });
    
    Route::middleware('permission:approval')->group(function () {
        Route::get('pending-approvals', [ApprovalRequestController::class, 'pendingApprovals'])->name('approval-requests.pending-approvals');
    });

    // Master Items Management routes
    Route::middleware('permission:manage_items')->group(function () {
        Route::resource('master-items', MasterItemController::class);
        Route::resource('item-types', ItemTypeController::class);
        Route::resource('item-categories', ItemCategoryController::class);
        Route::resource('commodities', CommodityController::class);
        Route::resource('units', UnitController::class);
    });

    Route::middleware('permission:manage_submission_types')->group(function () {
        Route::resource('submission-types', SubmissionTypeController::class)->except(['show', 'create', 'edit']);
    });

    // Suppliers Management routes
    Route::middleware('permission:manage_suppliers')->group(function () {
        Route::resource('suppliers', SupplierController::class);
    });

    // Settings management
    Route::middleware('permission:manage_settings')->group(function () {
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
    // API endpoint for getting settings (accessible to all authenticated users)
    Route::get('api/settings', [SettingController::class, 'getSettings'])->name('api.settings.get');
    
    // Reports
    Route::middleware('permission:view_reports')->group(function () {
        Route::get('reports/approval-requests', [ReportController::class, 'approvalRequests'])->name('reports.approval-requests');
    });

    Route::middleware('permission:manage_purchasing')->group(function () {
        // Report Purchasing process page (server-rendered)
        Route::get('reports/approval-requests/process-purchasing', [\App\Http\Controllers\ReportController::class, 'processPurchasing'])
            ->name('reports.approval-requests.process-purchasing');
        // Set received date for approval request (tanggal diterima)
        Route::post('approval-requests/{approvalRequest}/received-date', [\App\Http\Controllers\ApprovalRequestController::class, 'setReceivedDate'])
            ->name('approval-requests.set-received-date');
        
        // Purchasing API endpoints (now handled by ReportController)
        Route::post('purchasing/items/{purchasingItem}/po', [\App\Http\Controllers\ReportController::class, 'issuePO'])
            ->name('purchasing.items.po');
        Route::post('purchasing/items/{purchasingItem}/grn', [\App\Http\Controllers\ReportController::class, 'receiveGRN'])
            ->name('purchasing.items.grn');
        Route::post('purchasing/items/{purchasingItem}/done', [\App\Http\Controllers\ReportController::class, 'markDone'])
            ->name('purchasing.items.done');
        Route::post('purchasing/items/{purchasingItem}/invoice', [\App\Http\Controllers\ReportController::class, 'saveInvoice'])
            ->name('purchasing.items.invoice');
        Route::delete('purchasing/items/{purchasingItem}', [\App\Http\Controllers\ReportController::class, 'deletePurchasingItem'])
            ->name('purchasing.items.delete');
    });
    
    // Standalone vendor form page + vendor actions (permission checked in controller to allow OR logic)
    Route::post('purchasing/items/{purchasingItem}/benchmarking', [ReportController::class, 'saveBenchmarking'])
        ->name('purchasing.items.benchmarking');
    Route::post('purchasing/items/{purchasingItem}/preferred', [ReportController::class, 'selectPreferred'])
        ->name('purchasing.items.preferred');
    Route::get('purchasing/items/{purchasingItem}/vendor', [ReportController::class, 'vendorForm'])
        ->name('purchasing.items.vendor');
    
    // Export route for reports
    Route::middleware('permission:view_reports')->group(function () {
        Route::get('reports/approval-requests/export', [\App\Http\Controllers\ReportController::class, 'exportApprovalRequests'])
            ->name('reports.approval-requests.export');
    });

    // (Removed) item-centric routes to avoid introducing new indexes/views per request

    // API routes for AJAX requests
    Route::get('api/workflows/{workflow}/steps', [ApprovalWorkflowController::class, 'getSteps'])->name('api.workflows.steps');
    Route::get('api/master-items/by-type/{typeId}', [MasterItemController::class, 'getByType'])->name('api.master-items.by-type');
    Route::get('api/master-items/by-category/{categoryId}', [MasterItemController::class, 'getByCategory'])->name('api.master-items.by-category');
    Route::get('api/approval-requests/master-items', [ApprovalRequestController::class, 'getMasterItems'])->name('api.approval-requests.master-items');
    Route::get('api/approval-requests/workflow-for-item-type/{itemTypeId}', [ApprovalRequestController::class, 'getWorkflowForItemType'])->name('api.approval-requests.workflow-for-item-type');
    // Generic item lookup endpoints (suggest and resolve/create)
    Route::get('api/items/suggest', [ItemLookupController::class, 'suggest'])->name('api.items.suggest');
    Route::post('api/items/resolve', [ItemLookupController::class, 'resolve'])->name('api.items.resolve');

    // Supplier lookup endpoints
    Route::get('api/suppliers/suggest', [SupplierLookupController::class, 'suggest'])->name('api.suppliers.suggest');
    Route::post('api/suppliers/resolve', [SupplierLookupController::class, 'resolve'])->name('api.suppliers.resolve');

    // Purchasing item lookup endpoints (now handled by ReportController)
    Route::get('api/purchasing/items/{purchasingItem}', [\App\Http\Controllers\ReportController::class, 'showPurchasingItemJson'])
        ->name('api.purchasing.items.show');
    Route::post('api/purchasing/items/resolve', [\App\Http\Controllers\ReportController::class, 'resolvePurchasingItemByRequestAndItem'])
        ->name('api.purchasing.items.resolve');
    // Purchasing Status details (JSON)
    Route::get('api/purchasing/status/{approvalRequest}', [PurchasingItemController::class, 'statusDetailsByRequest'])
        ->name('api.purchasing.status');
    
    // (Removed) API routes for request-level step details and status updates
    
    // File download routes
    Route::get('approval-requests/attachments/{attachment}/download', [ApprovalRequestController::class, 'downloadAttachment'])->name('approval-requests.download-attachment');
    Route::get('approval-requests/attachments/{attachment}/view', [ApprovalRequestController::class, 'viewAttachment'])->name('approval-requests.view-attachment');
});
