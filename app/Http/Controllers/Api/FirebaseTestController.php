<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\JsonResponse;

class FirebaseTestController extends Controller
{
    public function __construct(
        private FirebaseService $firebaseService
    ) {}

    /**
     * Test Firebase connection
     *
     * @return JsonResponse
     */
    public function ping(): JsonResponse
    {
        try {
            $result = $this->firebaseService->ping();

            return response()->json([
                'status' => 'success',
                'message' => 'Firebase connection successful',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Firebase connection failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
