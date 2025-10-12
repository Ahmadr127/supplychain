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
    
    // General approval actions (approve, reject, cancel) - accessible to users with manage_approvals permission
    Route::middleware('permission:manage_approvals')->group(function () {
        Route::post('approval-requests/{approvalRequest}/approve', [ApprovalRequestController::class, 'approve'])->name('approval-requests.approve');
        Route::post('approval-requests/{approvalRequest}/reject', [ApprovalRequestController::class, 'reject'])->name('approval-requests.reject');
        Route::post('approval-requests/{approvalRequest}/cancel', [ApprovalRequestController::class, 'cancel'])->name('approval-requests.cancel');
    });
    
    // Show approval request - accessible to users who can view any approval or are the requester
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

    // Submission Types Management routes
    Route::middleware('permission:manage_submission_types')->group(function () {
        Route::resource('submission-types', SubmissionTypeController::class)->except(['show', 'create', 'edit']);
    });

    // Suppliers Management routes
    Route::middleware('permission:manage_suppliers')->group(function () {
        Route::resource('suppliers', SupplierController::class);
    });

    // Reports
    Route::middleware('permission:view_reports')->group(function () {
        Route::get('reports/approval-requests', [ReportController::class, 'approvalRequests'])->name('reports.approval-requests');
    });

    // API routes for AJAX requests
    Route::get('api/workflows/{workflow}/steps', [ApprovalWorkflowController::class, 'getSteps'])->name('api.workflows.steps');
    Route::get('api/master-items/by-type/{typeId}', [MasterItemController::class, 'getByType'])->name('api.master-items.by-type');
    Route::get('api/master-items/by-category/{categoryId}', [MasterItemController::class, 'getByCategory'])->name('api.master-items.by-category');
    Route::get('api/master-items/search', [MasterItemController::class, 'search'])->name('api.master-items.search');
    Route::get('api/approval-requests/master-items', [ApprovalRequestController::class, 'getMasterItems'])->name('api.approval-requests.master-items');
    Route::get('api/approval-requests/workflow-for-item-type/{itemTypeId}', [ApprovalRequestController::class, 'getWorkflowForItemType'])->name('api.approval-requests.workflow-for-item-type');
    // Generic item lookup endpoints (suggest and resolve/create)
    Route::get('api/items/suggest', [ItemLookupController::class, 'suggest'])->name('api.items.suggest');
    Route::post('api/items/resolve', [ItemLookupController::class, 'resolve'])->name('api.items.resolve');

    // Supplier lookup endpoints
    Route::get('api/suppliers/suggest', [SupplierLookupController::class, 'suggest'])->name('api.suppliers.suggest');
    Route::post('api/suppliers/resolve', [SupplierLookupController::class, 'resolve'])->name('api.suppliers.resolve');
    
    // API routes for step details and status updates
    Route::get('api/approval-steps/{requestId}/{stepNumber}', [ApprovalRequestController::class, 'getStepDetails'])->name('api.approval-steps.details');
    Route::post('api/approval-steps/{requestId}/{stepNumber}/update-status', [ApprovalRequestController::class, 'updateStepStatus'])->name('api.approval-steps.update-status');
    
    // File download routes
    Route::get('approval-requests/attachments/{attachment}/download', [ApprovalRequestController::class, 'downloadAttachment'])->name('approval-requests.download-attachment');
    Route::get('approval-requests/attachments/{attachment}/view', [ApprovalRequestController::class, 'viewAttachment'])->name('approval-requests.view-attachment');

});
