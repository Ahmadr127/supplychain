<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\PurchasingItem;
use App\Services\Purchasing\PurchasingItemService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchasingApiController extends Controller
{
    private PurchasingItemService $purchasingItemService;

    public function __construct(PurchasingItemService $purchasingItemService)
    {
        $this->middleware('auth:sanctum');
        $this->purchasingItemService = $purchasingItemService;
    }

    /**
     * List purchasing items
     */
    public function index(Request $request): JsonResponse
    {
        $query = PurchasingItem::with(['approvalRequest', 'masterItem', 'preferredVendor', 'statusChanger']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('approvalRequest', function ($q2) use ($search) {
                    $q2->where('request_number', 'like', "%{$search}%");
                })->orWhereHas('masterItem', function ($q2) use ($search) {
                    $q2->where('item_name', 'like', "%{$search}%");
                });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Calculate status counts for all statuses without the status filter applied
        $baseQuery = PurchasingItem::query();
        if ($search = $request->input('search')) {
            $baseQuery->where(function ($q) use ($search) {
                $q->whereHas('approvalRequest', function ($q2) use ($search) {
                    $q2->where('request_number', 'like', "%{$search}%");
                })->orWhereHas('masterItem', function ($q2) use ($search) {
                    $q2->where('item_name', 'like', "%{$search}%");
                });
            });
        }
        $statusCounts = $baseQuery->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $items = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        // Format the items to include ISO 8601 dates
        $formattedItems = collect($items->items())->map(function ($item) {
            $itemArray = $item->toArray();
            $itemArray['created_at'] = $item->created_at ? $item->created_at->toIso8601String() : null;
            $itemArray['updated_at'] = $item->updated_at ? $item->updated_at->toIso8601String() : null;
            $itemArray['status_changed_at'] = $item->status_changed_at ? $item->status_changed_at->toIso8601String() : null;
            $itemArray['grn_date'] = $item->grn_date ? $item->grn_date->toIso8601String() : null;
            return $itemArray;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data purchasing items berhasil diambil',
            'data' => [
                'items' => $formattedItems,
                'status_counts' => $statusCounts,
                'pagination' => [
                    'total' => $items->total(),
                    'per_page' => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                ]
            ]
        ]);
    }

    /**
     * Detail purchasing item
     */
    public function show($id): JsonResponse
    {
        $item = PurchasingItem::with([
            'approvalRequest', 
            'masterItem', 
            'preferredVendor', 
            'vendors.supplier', 
            'statusChanger'
        ])->find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Purchasing item tidak ditemukan',
            ], 404);
        }

        $itemArray = $item->toArray();
        $itemArray['created_at'] = $item->created_at ? $item->created_at->toIso8601String() : null;
        $itemArray['updated_at'] = $item->updated_at ? $item->updated_at->toIso8601String() : null;
        $itemArray['status_changed_at'] = $item->status_changed_at ? $item->status_changed_at->toIso8601String() : null;
        $itemArray['grn_date'] = $item->grn_date ? $item->grn_date->toIso8601String() : null;

        return response()->json([
            'status' => 'success',
            'message' => 'Detail purchasing item berhasil diambil',
            'data' => $itemArray,
        ]);
    }

    /**
     * Save benchmarking data
     */
    public function saveBenchmarking(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'vendors' => 'required|array|min:1',
            'vendors.*.supplier_id' => 'required|exists:suppliers,id',
            'vendors.*.unit_price' => 'nullable|numeric|min:0',
            'vendors.*.total_price' => 'nullable|numeric|min:0',
            'vendors.*.notes' => 'nullable|string',
        ]);

        $item = PurchasingItem::find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Purchasing item tidak ditemukan',
            ], 404);
        }

        try {
            $updatedItem = $this->purchasingItemService->saveBenchmarking($item, $validated['vendors']);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Benchmarking data berhasil disimpan',
                'data' => $updatedItem->load(['vendors.supplier']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan benchmarking data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Select preferred vendor
     */
    public function selectPreferred(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
        ]);

        $item = PurchasingItem::find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Purchasing item tidak ditemukan',
            ], 404);
        }

        // Verify supplier exists in benchmarking
        $supplierInBenchmarking = $item->vendors()->where('supplier_id', $validated['supplier_id'])->first();
        if (!$supplierInBenchmarking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Supplier tidak ditemukan dalam daftar benchmarking vendors item ini',
            ], 422);
        }

        try {
            $updatedItem = $this->purchasingItemService->selectPreferred(
                $item, 
                $validated['supplier_id'], 
                $supplierInBenchmarking->unit_price, 
                $supplierInBenchmarking->total_price
            );
            
            return response()->json([
                'status' => 'success',
                'message' => 'Vendor pilihan berhasil disimpan',
                'data' => $updatedItem->load(['preferredVendor']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memilih vendor: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Issue PO
     */
    public function issuePO(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'po_number' => 'required|string|max:255',
        ]);

        $item = PurchasingItem::find($id);

        if (!$item) {
            return response()->json([
            'status' => 'error',
            'message' => 'Purchasing item tidak ditemukan',
            ], 404);
        }

        try {
            $updatedItem = $this->purchasingItemService->issuePO($item, $validated['po_number']);
            
            return response()->json([
                'status' => 'success',
                'message' => 'PO Number berhasil disimpan',
                'data' => $updatedItem,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan PO Number: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Receive GRN
     */
    public function receiveGRN(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'grn_date' => 'required|date',
        ]);

        $item = PurchasingItem::find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Purchasing item tidak ditemukan',
            ], 404);
        }

        try {
            $grnDate = Carbon::parse($validated['grn_date']);
            $updatedItem = $this->purchasingItemService->receiveGRN($item, $grnDate);
            
            return response()->json([
                'status' => 'success',
                'message' => 'GRN Date berhasil disimpan',
                'data' => $updatedItem,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan GRN Date: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark done
     */
    public function markDone(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'done_notes' => 'nullable|string',
        ]);

        $item = PurchasingItem::find($id);

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Purchasing item tidak ditemukan',
            ], 404);
        }

        try {
            $updatedItem = $this->purchasingItemService->markDone($item, $validated['done_notes'] ?? null);
            
            return response()->json([
                'status' => 'success',
                'message' => 'Purchasing item berhasil ditandai selesai',
                'data' => $updatedItem,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menandai selesai: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Status By Request
     */
    public function statusByRequest($approvalRequestId): JsonResponse
    {
        $items = PurchasingItem::with(['statusChanger'])->where('approval_request_id', $approvalRequestId)->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'status_code' => 'unprocessed',
                    'status_label' => 'Unprocessed',
                    'changed_at' => null,
                    'changed_by_name' => null,
                    'done_notes' => null,
                ]
            ]);
        }

        $statusWeight = [
            'done' => 5,
            'grn_received' => 4,
            'po_issued' => 3,
            'selected' => 2,
            'benchmarking' => 1,
            'unprocessed' => 0,
        ];

        $mostAdvancedItem = $items->sortByDesc(function ($item) use ($statusWeight) {
            return $statusWeight[$item->status] ?? -1;
        })->first();

        // format label
        $labels = [
            'done' => 'Done',
            'grn_received' => 'GRN Received',
            'po_issued' => 'PO Issued',
            'selected' => 'Vendor Selected',
            'benchmarking' => 'Benchmarking',
            'unprocessed' => 'Unprocessed',
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'status_code' => $mostAdvancedItem->status,
                'status_label' => $labels[$mostAdvancedItem->status] ?? ucfirst($mostAdvancedItem->status),
                'changed_at' => $mostAdvancedItem->status_changed_at ? $mostAdvancedItem->status_changed_at->toIso8601String() : null,
                'changed_by_name' => $mostAdvancedItem->statusChanger ? $mostAdvancedItem->statusChanger->name : null,
                'done_notes' => $mostAdvancedItem->done_notes,
            ]
        ]);
    }
}
