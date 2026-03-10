<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SsoController;
use App\Http\Controllers\Api\ApprovalRequestApiController;
use App\Http\Controllers\Api\ApprovalItemApiController;
use App\Http\Controllers\Api\CapexApiController;
use App\Http\Controllers\Api\CapexItemApiController;

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
        $user = $request->user()->load('role.permissions');
        return response()->json([
            'status' => 'success',
            'data'   => [
                'user'        => $user,
                'role'        => $user->role?->name,
                'permissions' => $user->role?->permissions->pluck('name') ?? [],
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

    // ----------------------------------------------------------------
    // Approval Items  (ApprovalItemApiController)
    // ----------------------------------------------------------------
    Route::prefix('approval-requests/{approvalRequest}/items/{item}')->group(function () {
        Route::get('/',        [ApprovalItemApiController::class, 'show']);
        Route::get('/status',  [ApprovalItemApiController::class, 'status']);
        Route::post('/approve',[ApprovalItemApiController::class, 'approve']);
        Route::post('/reject', [ApprovalItemApiController::class, 'reject']);
    });

    // ----------------------------------------------------------------
    // CapEx Headers  (CapexApiController)
    // ----------------------------------------------------------------
    Route::prefix('capex')->group(function () {
        // Named sub-routes FIRST to avoid {capex} wildcard matching them
        Route::get('/available',   [CapexApiController::class, 'availableItems']);
        Route::get('/departments', [CapexApiController::class, 'departments']);

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
});
