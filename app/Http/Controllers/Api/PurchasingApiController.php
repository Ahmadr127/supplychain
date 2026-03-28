<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\PurchasingItem;
use App\Services\Purchasing\PurchasingItemService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchasingApiController extends Controller
{
    private PurchasingItemService $purchasingItemService;

    private function normalizeStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        return match (strtolower(trim($status))) {
            'all' => null,
            // Backward-compatible aliases used by older clients
            'fulfilled', 'terpenuhi', 'approved' => 'done',
            default => strtolower(trim($status)),
        };
    }

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
        // 1. Auto-resolve missing purchasing items for legacy or lazily resolved items.
        // This ensures the Mobile API returns items that technically reached purchasing phase 
        // but haven't been clicked "Proses" on the Web UI yet.
        $readyItems = \App\Models\ApprovalRequestItem::whereIn('status', ['in_purchasing', 'approved', 'in_release'])
            ->whereNotExists(function($query) {
                $query->select(\Illuminate\Support\Facades\DB::raw(1))
                      ->from('purchasing_items')
                      ->whereColumn('purchasing_items.approval_request_id', 'approval_request_items.approval_request_id')
                      ->whereColumn('purchasing_items.master_item_id', 'approval_request_items.master_item_id');
            })
            ->get();
            
        foreach ($readyItems as $readyItem) {
            \App\Models\PurchasingItem::create([
                'approval_request_id' => $readyItem->approval_request_id,
                'master_item_id' => $readyItem->master_item_id,
                'quantity' => $readyItem->quantity,
                'status' => 'unprocessed',
            ]);
        }

        $requestedStatus = $this->normalizeStatus($request->input('status'));
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

        if ($requestedStatus) {
            $query->where('status', $requestedStatus);
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

        $user = Auth::user();
        $canPurchasing = $user->hasPermission('manage_purchasing') || $user->hasPermission('process_purchasing_item');
        $canVendor     = $user->hasPermission('manage_vendor');

        // Format the items to include ISO 8601 dates + sequential gating flags
        $formattedItems = collect($items->items())->map(function ($item) use ($canPurchasing, $canVendor) {
            $itemArray = $item->toArray();
            $itemArray['created_at']      = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->toIso8601String() : null;
            $itemArray['updated_at']      = $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->toIso8601String() : null;
            $itemArray['status_changed_at'] = $item->status_changed_at ? \Carbon\Carbon::parse($item->status_changed_at)->toIso8601String() : null;
            $itemArray['grn_date']        = $item->grn_date ? \Carbon\Carbon::parse($item->grn_date)->toIso8601String() : null;

            // Sequential gating
            $step1Done = !empty($item->approvalRequest?->received_at);
            $step2Done = $item->vendors()->exists();
            $step3Done = !empty($item->preferred_vendor_id);
            $step4Done = !empty($item->po_number);
            $step5Done = !empty($item->invoice_number);

            $itemArray['workflow_steps'] = [
                'can_set_received_date'    => $canPurchasing,
                'can_do_benchmarking'      => $canPurchasing && $step1Done,
                'can_select_preferred'     => $canVendor     && $step2Done,
                'can_issue_po'             => $canPurchasing && $step3Done,
                'can_input_invoice'        => $canPurchasing && $step4Done,
                'can_mark_done'            => $canPurchasing && $step5Done,
                'step1_done' => $step1Done,
                'step2_done' => $step2Done,
                'step3_done' => $step3Done,
                'step4_done' => $step4Done,
                'step5_done' => $step5Done,
            ];
            return $itemArray;
        })->values()->all();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data purchasing items berhasil diambil',
            'data'    => [
                'items'       => $formattedItems,
                'status_counts' => $statusCounts,
                'pagination'  => [
                    'total'        => $items->total(),
                    'per_page'     => $items->perPage(),
                    'current_page' => $items->currentPage(),
                    'last_page'    => $items->lastPage(),
                ],
                'user_permissions' => [
                    'can_manage_purchasing' => $canPurchasing,
                    'can_select_preferred'  => $canVendor,
                ],
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
        $itemArray['created_at']        = $item->created_at ? \Carbon\Carbon::parse($item->created_at)->toIso8601String() : null;
        $itemArray['updated_at']        = $item->updated_at ? \Carbon\Carbon::parse($item->updated_at)->toIso8601String() : null;
        $itemArray['status_changed_at'] = $item->status_changed_at ? \Carbon\Carbon::parse($item->status_changed_at)->toIso8601String() : null;
        $itemArray['grn_date']          = $item->grn_date ? \Carbon\Carbon::parse($item->grn_date)->toIso8601String() : null;

        $user = Auth::user();
        $canPurchasing = $user->hasPermission('manage_purchasing') || $user->hasPermission('process_purchasing_item');
        $canVendor     = $user->hasPermission('manage_vendor');

        $step1Done = !empty($item->approvalRequest?->received_at);
        $step2Done = $item->vendors()->exists();
        $step3Done = !empty($item->preferred_vendor_id);
        $step4Done = !empty($item->po_number);
        $step5Done = !empty($item->invoice_number);

        $itemArray['workflow_steps'] = [
            'can_set_received_date' => $canPurchasing,
            'can_do_benchmarking'   => $canPurchasing && $step1Done,
            'can_select_preferred'  => $canVendor     && $step2Done,
            'can_issue_po'          => $canPurchasing && $step3Done,
            'can_input_invoice'     => $canPurchasing && $step4Done,
            'can_mark_done'         => $canPurchasing && $step5Done,
            'step1_done' => $step1Done,
            'step2_done' => $step2Done,
            'step3_done' => $step3Done,
            'step4_done' => $step4Done,
            'step5_done' => $step5Done,
        ];
        $itemArray['user_permissions'] = [
            'can_manage_purchasing' => $canPurchasing,
            'can_select_preferred'  => $canVendor,
        ];

        return response()->json([
            'status'  => 'success',
            'message' => 'Detail purchasing item berhasil diambil',
            'data'    => $itemArray,
        ]);
    }

    /**
     * Save benchmarking data
     */
    public function saveBenchmarking(Request $request, $id): JsonResponse
    {
        // Permission check: hanya manage_purchasing
        if (!Auth::user()->hasPermission('manage_purchasing') && !Auth::user()->hasPermission('process_purchasing_item')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak. Hanya tim purchasing yang dapat mengisi benchmarking.'], 403);
        }

        $validated = $request->validate([
            'vendors'                  => 'required|array|min:1',
            'vendors.*.supplier_id'    => 'required|exists:suppliers,id',
            'vendors.*.unit_price'     => 'nullable|numeric|min:0',
            'vendors.*.total_price'    => 'nullable|numeric|min:0',
            'vendors.*.notes'          => 'nullable|string',
        ]);

        $item = PurchasingItem::with('approvalRequest')->find($id);

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Purchasing item tidak ditemukan'], 404);
        }

        // Step 1 harus selesai dulu (received_at diisi)
        if (empty($item->approvalRequest?->received_at)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Tanggal dokumen diterima (Step 1) harus diisi terlebih dahulu sebelum benchmarking.',
            ], 422);
        }

        try {
            $updatedItem = $this->purchasingItemService->saveBenchmarking($item, $validated['vendors']);
            return response()->json([
                'status'  => 'success',
                'message' => 'Benchmarking data berhasil disimpan',
                'data'    => $updatedItem->load(['vendors.supplier']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan benchmarking data: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Select preferred vendor
     */
    public function selectPreferred(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'unit_price'  => 'nullable|numeric|min:0',
            'total_price' => 'nullable|numeric|min:0',
        ]);

        $item = PurchasingItem::find($id);

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Purchasing item tidak ditemukan'], 404);
        }

        // Only manage_vendor (Manager Keuangan) can select preferred vendor
        if (!Auth::user()->hasPermission('manage_vendor')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Hanya Manager Keuangan yang dapat memilih preferred vendor.',
            ], 403);
        }

        // Step 2 must be done before step 3
        if (!$item->vendors()->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Benchmarking vendor harus diisi terlebih dahulu sebelum memilih preferred vendor.',
            ], 422);
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
            // Use provided prices, fallback to benchmarking prices if not provided
            $unitPrice  = $validated['unit_price']  ?? $supplierInBenchmarking->unit_price;
            $totalPrice = $validated['total_price'] ?? $supplierInBenchmarking->total_price;

            $updatedItem = $this->purchasingItemService->selectPreferred(
                $item, 
                $validated['supplier_id'], 
                $unitPrice, 
                $totalPrice
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
        // Permission check: hanya manage_purchasing
        if (!Auth::user()->hasPermission('manage_purchasing') && !Auth::user()->hasPermission('process_purchasing_item')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak. Hanya tim purchasing yang dapat input PO.'], 403);
        }

        $validated = $request->validate([
            'po_number' => 'required|string|max:255',
        ]);

        $item = PurchasingItem::find($id);

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Purchasing item tidak ditemukan'], 404);
        }

        // Step 3 harus selesai dulu (preferred_vendor_id diisi)
        if (empty($item->preferred_vendor_id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Preferred Vendor (Step 3) harus dipilih terlebih dahulu sebelum input PO.',
            ], 422);
        }

        try {
            $updatedItem = $this->purchasingItemService->issuePO($item, $validated['po_number']);
            return response()->json([
                'status'  => 'success',
                'message' => 'PO Number berhasil disimpan',
                'data'    => $updatedItem,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan PO Number: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Receive GRN
     */
    public function receiveGRN(Request $request, $id): JsonResponse
    {
        // Permission check: hanya manage_purchasing
        if (!Auth::user()->hasPermission('manage_purchasing') && !Auth::user()->hasPermission('process_purchasing_item')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak. Hanya tim purchasing yang dapat input GRN \u0026 Invoice.'], 403);
        }

        $validated = $request->validate([
            'invoice_number' => 'required|string|max:100',
            'grn_date' => 'required|date',
        ]);

        $item = PurchasingItem::find($id);

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Purchasing item tidak ditemukan'], 404);
        }

        // Step 4 harus selesai dulu (po_number diisi)
        if (empty($item->po_number)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'PO Number (Step 4) harus diisi terlebih dahulu sebelum input GRN \u0026 Invoice.',
            ], 422);
        }

        try {
            $grnDate     = Carbon::parse($validated['grn_date']);
            $updatedItem = $this->purchasingItemService->receiveGRN($item, $grnDate);
            $item->update(['invoice_number' => $validated['invoice_number']]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Invoice dan GRN berhasil disimpan',
                'data'    => $updatedItem->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menyimpan GRN \u0026 Invoice: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Set received date on the approval request (Step 1)
     * Requires: manage_purchasing
     */
    public function setReceivedDate(Request $request, $id): JsonResponse
    {
        if (!Auth::user()->hasPermission('manage_purchasing') && !Auth::user()->hasPermission('process_purchasing_item')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'received_at' => 'required|date',
        ]);

        $item = PurchasingItem::with('approvalRequest')->find($id);
        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Purchasing item tidak ditemukan'], 404);
        }

        try {
            $updatedItem = $this->purchasingItemService->setReceivedDate($item, Carbon::parse($validated['received_at']));
            return response()->json([
                'status'  => 'success',
                'message' => 'Tanggal dokumen berhasil disimpan',
                'data'    => $updatedItem,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Save invoice number (Step 5)
     * Requires: manage_purchasing, gated by po_number
     */
    public function saveInvoice(Request $request, $id): JsonResponse
    {
        if (!Auth::user()->hasPermission('manage_purchasing') && !Auth::user()->hasPermission('process_purchasing_item')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak.'], 403);
        }

        $validated = $request->validate([
            'invoice_number' => 'required|string|max:255',
        ]);

        $item = PurchasingItem::find($id);
        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Purchasing item tidak ditemukan'], 404);
        }

        if (empty($item->po_number)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'PO Number harus diisi terlebih dahulu sebelum input Invoice.',
            ], 422);
        }

        try {
            $item->update(['invoice_number' => $validated['invoice_number']]);
            return response()->json([
                'status'  => 'success',
                'message' => 'Nomor invoice berhasil disimpan',
                'data'    => $item->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Mark done
     */
    public function markDone(Request $request, $id): JsonResponse
    {
        // Permission check: hanya manage_purchasing
        if (!Auth::user()->hasPermission('manage_purchasing') && !Auth::user()->hasPermission('process_purchasing_item')) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak. Hanya tim purchasing yang dapat menandai selesai.'], 403);
        }

        $validated = $request->validate([
            'done_notes' => 'nullable|string',
        ]);

        $item = PurchasingItem::find($id);

        if (!$item) {
            return response()->json(['status' => 'error', 'message' => 'Purchasing item tidak ditemukan'], 404);
        }

        // Step 5 harus selesai (invoice_number diisi)
        if (empty($item->invoice_number)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Nomor Invoice (Step 5) harus diisi terlebih dahulu sebelum menandai selesai.',
            ], 422);
        }

        try {
            $updatedItem = $this->purchasingItemService->markDone($item, $validated['done_notes'] ?? null);
            return response()->json([
                'status'  => 'success',
                'message' => 'Purchasing item berhasil ditandai selesai',
                'data'    => $updatedItem,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menandai selesai: ' . $e->getMessage()], 500);
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
