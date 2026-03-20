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
            // main-sso wraps data in {status, data: {...}}
            $ssoUserData = $ssoUser['data'] ?? $ssoUser;

            // Validasi: NIK wajib ada
            if (empty($ssoUserData['nik'])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'SSO user does not have a NIK. Cannot authenticate.',
                ], 422);
            }

            // Cari user lokal berdasarkan NIK saja (konsisten dengan web SSO)
            $user = User::where('nik', $ssoUserData['nik'])->first();

            if ($user) {
                // Update data supplementary jika ada
                $user->update([
                    'name'     => $ssoUserData['name'],
                    'email'    => $ssoUserData['email']    ?? $user->email,
                    'username' => $ssoUserData['username'] ?? $user->username,
                ]);
            } else {
                // Buat user baru
                $user = User::create([
                    'nik'      => $ssoUserData['nik'],
                    'name'     => $ssoUserData['name'],
                    'email'    => $ssoUserData['email']    ?? null,
                    'username' => $ssoUserData['username'] ?? null,
                    'password' => bcrypt(\Illuminate\Support\Str::random(32)),
                ]);
            }

            // Generate a local Sanctum token for the mobile app to use with supplychain API
            $token = $user->createToken('Supplychain API Token')->plainTextToken;

            // Gather permissions for the user
            $permissions = $user->role
                ? $user->role->permissions->pluck('name')->toArray()
                : [];

            return response()->json([
                'status'  => 'success',
                'message' => 'SSO Login successful',
                'data'    => [
                    'user' => [
                        'id'       => $user->id,
                        'name'     => $user->name,
                        'email'    => $user->email,
                        'username' => $user->username,
                        'nik'      => $user->nik ?? null,
                        'role'     => $user->role?->name,
                    ],
                    'permissions'  => $permissions,
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
