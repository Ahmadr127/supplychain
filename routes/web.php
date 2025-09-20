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

    // Approval Request routes - Public access for viewing and basic actions
    Route::get('approval-requests', [ApprovalRequestController::class, 'index'])->name('approval-requests.index');
    Route::get('approval-requests/create', [ApprovalRequestController::class, 'create'])->name('approval-requests.create');
    Route::post('approval-requests', [ApprovalRequestController::class, 'store'])->name('approval-requests.store');
    Route::get('approval-requests/{approvalRequest}', [ApprovalRequestController::class, 'show'])->name('approval-requests.show');
    Route::get('approval-requests/{approvalRequest}/edit', [ApprovalRequestController::class, 'edit'])->name('approval-requests.edit');
    Route::put('approval-requests/{approvalRequest}', [ApprovalRequestController::class, 'update'])->name('approval-requests.update');
    Route::post('approval-requests/{approvalRequest}/approve', [ApprovalRequestController::class, 'approve'])->name('approval-requests.approve');
    Route::post('approval-requests/{approvalRequest}/reject', [ApprovalRequestController::class, 'reject'])->name('approval-requests.reject');
    Route::post('approval-requests/{approvalRequest}/cancel', [ApprovalRequestController::class, 'cancel'])->name('approval-requests.cancel');
    
    // Admin-only approval routes
    Route::middleware('permission:manage_approvals')->group(function () {
        Route::delete('approval-requests/{approvalRequest}', [ApprovalRequestController::class, 'destroy'])->name('approval-requests.destroy');
    });

    // User-specific approval routes
    Route::get('my-requests', [ApprovalRequestController::class, 'myRequests'])->name('approval-requests.my-requests');
    Route::get('pending-approvals', [ApprovalRequestController::class, 'pendingApprovals'])->name('approval-requests.pending-approvals');

    // Master Items Management routes
    Route::middleware('permission:manage_items')->group(function () {
        Route::resource('master-items', MasterItemController::class);
        Route::resource('item-types', ItemTypeController::class);
        Route::resource('item-categories', ItemCategoryController::class);
        Route::resource('commodities', CommodityController::class);
        Route::resource('units', UnitController::class);
    });

    // API routes for AJAX requests
    Route::get('api/workflows/{workflow}/steps', [ApprovalWorkflowController::class, 'getSteps'])->name('api.workflows.steps');
    Route::get('api/master-items/by-type/{typeId}', [MasterItemController::class, 'getByType'])->name('api.master-items.by-type');
    Route::get('api/master-items/by-category/{categoryId}', [MasterItemController::class, 'getByCategory'])->name('api.master-items.by-category');
    Route::get('api/master-items/search', [MasterItemController::class, 'search'])->name('api.master-items.search');

});
