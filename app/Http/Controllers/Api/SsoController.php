<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\User;

class SsoController extends Controller
{
    /**
     * Handle SSO Login from Main SSO Token.
     */
    public function loginViaToken(Request $request)
    {
        $request->validate([
            'access_token' => 'required|string',
        ]);

        $ssoBaseUrl = env('SSO_BASE_URL', 'http://127.0.0.1:8000'); // the main-sso URL

        try {
            // Verify token with main-sso
            $response = Http::withToken($request->access_token)
                ->get("{$ssoBaseUrl}/api/user");

            if ($response->failed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid SSO token or SSO server unreachable.',
                    'details' => $response->body()
                ], 401);
            }

            $ssoUser = $response->json();

            // Find or update the user locally
            $user = User::updateOrCreate(
                ['email' => $ssoUser['email']],
                [
                    'name'     => $ssoUser['name'],
                    'username' => $ssoUser['username'] ?? $ssoUser['email'],
                    // If you need to handle role mapping, it can be done here.
                    // 'role_id' => ...
                ]
            );

            // Generate a local Sanctum token for the mobile app to use with supplychain API
            $token = $user->createToken('Supplychain API Token')->plainTextToken;

            return response()->json([
                'status'  => 'success',
                'message' => 'SSO Login successful',
                'data'    => [
                    'user' => [
                        'id'       => $user->id,
                        'name'     => $user->name,
                        'email'    => $user->email,
                        'username' => $user->username,
                    ],
                    'access_token' => $token,
                    'token_type'   => 'Bearer',
                ]
            ], 200);

        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'SSO server is unreachable. Connection refused.',
                'error'   => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'An error occurred during SSO authentication.',
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ], 500);
        }
    }
}
