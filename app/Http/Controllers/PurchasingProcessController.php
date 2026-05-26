<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalItemStep;
use App\Models\PurchasingItem;
use App\Services\Purchasing\PurchasingItemService;
use App\Services\Purchasing\PurchasingTypeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PurchasingProcessController extends Controller
{
    public function processPurchasing(Request $request, PurchasingTypeService $typeService)
    {
        // Authorization: users with manage_purchasing, process_purchasing_item, or manage_vendor may access
        if (!(auth()->user()?->hasPermission('manage_purchasing') || auth()->user()?->hasPermission('process_purchasing_item') || auth()->user()?->hasPermission('manage_vendor'))) {
            abort(403, 'Unauthorized action.');
        }
        $id = (int) $request->query('purchasing_item_id');
        if (!$id) {
            return redirect()->route('reports.approval-requests')->with('error', 'Purchasing Item tidak ditemukan.');
        }

        $item = PurchasingItem::with([
            'approvalRequest',
            'masterItem',
            'vendors.supplier',
            'vendors.latestTrial',
            'preferredVendor',
        ])->find($id);

        if (!$item) {
            return redirect()->route('reports.approval-requests')->with('error', 'Purchasing Item tidak ditemukan.');
        }
        
        // Dynamic workflow steps: purchasing + release phases, ordered by step_number.
        $purchasingSteps = ApprovalItemStep::where('approval_request_id', $item->approval_request_id)
            ->where('master_item_id', $item->master_item_id)
            ->whereIn('step_phase', ['purchasing', 'release'])
            ->orderBy('step_number')
            ->get();

        $user = auth()->user();
        $canPurchasing = $user->hasPermission('manage_purchasing') || $user->hasPermission('process_purchasing_item');
        $canVendor     = $user->hasPermission('manage_vendor');

        $pSteps = $typeService->resolvePurchasingSteps(
            $item,
            $canPurchasing,
            $canVendor,
            $purchasingSteps->where('step_phase', 'purchasing'),
            $purchasingSteps->where('step_phase', 'release'),
        );
        
        // Add status counts for consistency
        $statusCounts = ApprovalRequestItem::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
            
        $statusCounts = [
            'pending' => $statusCounts['pending'] ?? 0,
            'on_progress' => $statusCounts['on progress'] ?? 0,
            'approved' => $statusCounts['approved'] ?? 0,
            'rejected' => $statusCounts['rejected'] ?? 0,
            'cancelled' => $statusCounts['cancelled'] ?? 0,
            'in_purchasing' => $statusCounts['in_purchasing'] ?? 0,
            'in_release' => $statusCounts['in_release'] ?? 0,
            'done' => $statusCounts['done'] ?? 0,
        ];

        // Calculate Purchasing Status Counts
        $piCounts = PurchasingItem::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $readyButNoPI = ApprovalRequestItem::whereIn('status', ['in_purchasing', 'approved', 'in_release'])
            ->whereNotExists(function($query) {
                $query->select(DB::raw(1))
                      ->from('purchasing_items')
                      ->whereColumn('purchasing_items.approval_request_id', 'approval_request_items.approval_request_id')
                      ->whereColumn('purchasing_items.master_item_id', 'approval_request_items.master_item_id');
            })
            ->count();

        $pendingApproval = ApprovalRequestItem::whereIn('status', ['pending', 'on progress'])
            ->count();

        $totalUnprocessed = ($piCounts['unprocessed'] ?? 0) + $readyButNoPI;

        $purchasingCounts = [
            'pending_approval' => $pendingApproval,
            'unprocessed' => $totalUnprocessed,
            'benchmarking' => $piCounts['benchmarking'] ?? 0,
            'selected' => $piCounts['selected'] ?? 0,
            'po_issued' => $piCounts['po_issued'] ?? 0,
            'grn_received' => $piCounts['grn_received'] ?? 0,
            'done' => $piCounts['done'] ?? 0,
        ];

        return view('reports.approval-requests.process-purchasing', compact(
            'item',
            'statusCounts',
            'purchasingCounts',
            'purchasingSteps',
            'pSteps',
            'canPurchasing',
            'canVendor',
        ));
    }

    public function vendorForm(PurchasingItem $purchasingItem)
    {
        // Authorization: only users with manage_vendor may access vendor form
        if (!auth()->user()?->hasPermission('manage_vendor')) {
            abort(403, 'Unauthorized action.');
        }
        $purchasingItem->loadMissing([
            'approvalRequest',
            'masterItem',
            'vendors.supplier',
            'vendors.latestTrial',
            'preferredVendor',
        ]);

        return view('purchasing.items.form-vendor', [
            'item' => $purchasingItem,
        ]);
    }

    public function showPurchasingItemJson(PurchasingItem $purchasingItem)
    {
        $purchasingItem->loadMissing([
            'approvalRequest:id,request_number,received_at',
            'masterItem:id,name',
            'vendors.supplier:id,name',
            'preferredVendor:id,name',
        ]);
        return response()->json([
            'success' => true,
            'item' => [
                'id' => $purchasingItem->id,
                'approval_request_id' => (int) $purchasingItem->approval_request_id,
                'quantity' => (int) $purchasingItem->quantity,
                'request_number' => optional($purchasingItem->approvalRequest)->request_number,
                'item_name' => optional($purchasingItem->masterItem)->name,
                'received_at' => optional($purchasingItem->approvalRequest?->received_at)?->toDateString(),
                'preferred_vendor_id' => $purchasingItem->preferred_vendor_id,
                'preferred_vendor_name' => optional($purchasingItem->preferredVendor)->name,
                'preferred_unit_price' => $purchasingItem->preferred_unit_price,
                'preferred_total_price' => $purchasingItem->preferred_total_price,
                'po_number' => $purchasingItem->po_number,
                'grn_date' => optional($purchasingItem->grn_date)?->toDateString(),
                'invoice_number' => $purchasingItem->invoice_number,
                'proc_cycle_days' => $purchasingItem->proc_cycle_days,
                'vendors' => $purchasingItem->vendors->map(function($v){
                    return [
                        'supplier_id' => (int) $v->supplier_id,
                        'supplier_name' => optional($v->supplier)->name,
                        'unit_price' => $v->unit_price,
                        'total_price' => $v->total_price,
                    ];
                })->values(),
            ],
        ]);
    }

    public function resolvePurchasingItemByRequestAndItem(Request $request)
    {
        $data = $request->validate([
            'approval_request_id' => 'required|integer|exists:approval_requests,id',
            'master_item_id' => 'required|integer|exists:master_items,id',
        ]);
        
        $approvalRequest = ApprovalRequest::find($data['approval_request_id']);
        $requestItem = ApprovalRequestItem::where('approval_request_id', $data['approval_request_id'])
            ->where('master_item_id', $data['master_item_id'])
            ->first();

        $isReady = false;
        if ($approvalRequest->status === 'approved') {
            $isReady = true;
        } elseif ($requestItem && in_array($requestItem->status, ['in_purchasing', 'approved', 'in_release'])) {
            $isReady = true;
        }

        if (!$isReady) {
            return response()->json(['error' => 'Item belum siap untuk purchasing (Status: '.($requestItem->status ?? 'unknown').')'], 400);
        }
        
        $item = PurchasingItem::firstOrCreate(
            [
                'approval_request_id' => $data['approval_request_id'],
                'master_item_id' => $data['master_item_id'],
            ],
            [
                'quantity' => 1,
                'status' => 'unprocessed',
            ]
        );
        
        return response()->json(['id' => $item->id]);
    }

    public function saveBenchmarking(Request $request, PurchasingItem $purchasingItem)
    {
        if (!(auth()->user()?->hasPermission('manage_vendor') || auth()->user()?->hasPermission('manage_purchasing'))) {
            abort(403, 'Unauthorized action.');
        }
        
        if ($request->has('vendors') && is_array($request->vendors)) {
            $filteredVendors = array_filter($request->vendors, function($v) {
                return isset($v['supplier_id']) && !empty($v['supplier_id']);
            });
            $request->merge(['vendors' => $filteredVendors]);
        }

        $data = $request->validate([
            'vendors' => 'required|array|min:1',
            'vendors.*.supplier_id' => 'required|integer|exists:suppliers,id',
            'vendors.*.unit_price' => 'nullable|numeric|min:0|max:999999999999.99',
            'vendors.*.total_price' => 'nullable|numeric|min:0|max:999999999999.99',
            'vendors.*.notes' => 'nullable|string|max:255',
            'benchmark_notes' => 'nullable|string|max:2000',
        ], [
            'vendors.min' => 'Silakan pilih minimal 1 vendor benchmarking.',
            'vendors.*.supplier_id.required' => 'Supplier harus dipilih.',
        ]);

        $rows = $data['vendors'];

        $service = app(PurchasingItemService::class);
        $service->saveBenchmarking($purchasingItem, $rows);
        
        $purchasingItem->update([
            'benchmark_notes' => $request->input('benchmark_notes'),
        ]);
        
        return back()->with('success', 'Benchmarking dan catatan berhasil disimpan.');
    }

    public function receiveDocAndBenchmarking(Request $request, PurchasingItem $purchasingItem)
    {
        if ($request->has('vendors') && is_array($request->vendors)) {
            $filteredVendors = array_filter($request->vendors, function($v) {
                return isset($v['supplier_id']) && !empty($v['supplier_id']);
            });
            $request->merge(['vendors' => $filteredVendors]);
        }

        $data = $request->validate([
            'received_at' => 'required|date',
            'vendors' => 'required|array|min:1',
            'vendors.*.supplier_id' => 'required|integer|exists:suppliers,id',
            'vendors.*.unit_price' => 'nullable|numeric|min:0|max:999999999999.99',
            'vendors.*.total_price' => 'nullable|numeric|min:0|max:999999999999.99',
            'vendors.*.notes' => 'nullable|string|max:255',
            'benchmark_notes' => 'nullable|string|max:2000',
        ], [
            'vendors.min' => 'Silakan pilih minimal 1 vendor benchmarking.',
            'vendors.*.supplier_id.required' => 'Supplier harus dipilih.',
        ]);

        $rows = $data['vendors'];

        $service = app(PurchasingItemService::class);
        $service->receiveDocAndBenchmarking(
            $purchasingItem,
            Carbon::parse($data['received_at']),
            $rows,
            $data['benchmark_notes'] ?? null
        );

        return back()->with('success', 'Tanggal diterima dan benchmarking berhasil disimpan.');
    }

    public function saveTrial(Request $request, PurchasingItem $purchasingItem)
    {
        $data = $request->validate([
            'trials' => 'required|array|min:1',
            'trials.*.purchasing_item_vendor_id' => 'required|integer',
            'trials.*.trial_notes' => 'nullable|string|max:2000',
        ]);

        $service = app(PurchasingItemService::class);
        $service->saveTrial($purchasingItem, $data['trials']);

        return back()->with('success', 'Trial notes berhasil disimpan.');
    }

    public function invoiceGrnDone(Request $request, PurchasingItem $purchasingItem)
    {
        $data = $request->validate([
            'invoice_number' => 'required|string|max:100',
            'grn_date' => 'required|date',
            'done_notes' => 'nullable|string|max:1000',
        ]);

        $service = app(PurchasingItemService::class);
        $service->invoiceGrnDone(
            $purchasingItem,
            (string) $data['invoice_number'],
            Carbon::parse($data['grn_date']),
            $data['done_notes'] ?? null
        );

        return back()->with('success', 'Invoice, GRN, dan DONE berhasil disimpan.');
    }

    public function selectPreferred(Request $request, PurchasingItem $purchasingItem)
    {
        if (!auth()->user()?->hasPermission('manage_vendor')) {
            abort(403, 'Hanya Manager Keuangan (manage_vendor) yang dapat memilih preferred vendor.');
        }
        $data = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'unit_price' => 'nullable|numeric|min:0|max:999999999999.99',
            'total_price' => 'nullable|numeric|min:0|max:999999999999.99',
        ]);

        $service = app(PurchasingItemService::class);
        $service->selectPreferred(
            $purchasingItem,
            (int) $data['supplier_id'],
            isset($data['unit_price']) ? (float) $data['unit_price'] : null,
            isset($data['total_price']) ? (float) $data['total_price'] : null,
        );
        
        return back()->with('success', 'Preferred vendor berhasil disimpan.');
    }

    public function issuePO(Request $request, PurchasingItem $purchasingItem)
    {
        $data = $request->validate([
            'po_number' => 'required|string|max:255',
        ]);

        $service = app(PurchasingItemService::class);
        $service->issuePO($purchasingItem, (string) $data['po_number']);
        
        return back()->with('success', 'PO Number berhasil disimpan.');
    }

    public function receiveGRN(Request $request, PurchasingItem $purchasingItem)
    {
        $data = $request->validate([
            'invoice_number' => 'required|string|max:100',
            'grn_date' => 'required|date',
        ]);

        $service = app(PurchasingItemService::class);
        $service->receiveGRN($purchasingItem, Carbon::parse($data['grn_date']));
        
        $purchasingItem->update(['invoice_number' => $data['invoice_number']]);
        
        return back()->with('success', 'Nomor Invoice & Tanggal GRN berhasil disimpan.');
    }

    public function markDone(Request $request, PurchasingItem $purchasingItem)
    {
        $data = $request->validate([
            'done_notes' => 'nullable|string|max:1000',
        ]);
        $service = app(PurchasingItemService::class);
        $service->markDone($purchasingItem, $data['done_notes'] ?? null);
        
        return back()->with('success', 'Item berhasil ditandai sebagai DONE.');
    }

    public function saveInvoice(Request $request, PurchasingItem $purchasingItem)
    {
        $data = $request->validate([
            'invoice_number' => 'required|string|max:255',
        ]);

        $service = app(PurchasingItemService::class);
        if (method_exists($service, 'saveInvoice')) {
            try {
                $service->saveInvoice($purchasingItem, (string) $data['invoice_number']);
            } catch (\TypeError $e) {
                $service->saveInvoice($purchasingItem, $data);
            }
        } else {
            $purchasingItem->update(['invoice_number' => (string) $data['invoice_number']]);
        }
        
        ApprovalItemStep::syncPurchasingStep($purchasingItem->approval_request_id, $purchasingItem->master_item_id, 'purchasing_invoice');
        ApprovalItemStep::syncPurchasingStep($purchasingItem->approval_request_id, $purchasingItem->master_item_id, 'purchasing_invoice_grn_done');

        return back()->with('success', 'Invoice Number berhasil disimpan.');
    }

    public function deletePurchasingItem(PurchasingItem $purchasingItem)
    {
        try {
            if (!auth()->user()->hasPermission('manage_purchasing')) {
                return redirect()->back()->with('error', 'Unauthorized');
            }
            
            $purchasingItem->vendors()->detach();
            $purchasingItem->delete();
            
            return redirect()->route('reports.approval-requests')
                ->with('success', 'Purchasing item berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus purchasing item: ' . $e->getMessage());
        }
    }
}
