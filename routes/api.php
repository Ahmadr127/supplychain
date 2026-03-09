<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SsoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// SSO Token Exchange Endpoint
Route::post('/sso-login', [SsoController::class, 'loginViaToken']);

// Protected User Endpoint
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json([
        'status' => 'success',
        'data' => [
            'user' => $request->user()
        ]
    ]);
});
