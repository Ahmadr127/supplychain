<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchasingItem;
use App\Models\ApprovalRequest;
use App\Services\Purchasing\PurchasingItemService;
use Carbon\Carbon;

class PurchasingItemController extends Controller
{
    public function __construct(private PurchasingItemService $service)
    {
    }

    // GET /api/purchasing/items/{purchasingItem}
    public function showJson(PurchasingItem $purchasingItem)
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
                // preferred info
                'preferred_vendor_id' => $purchasingItem->preferred_vendor_id,
                'preferred_vendor_name' => optional($purchasingItem->preferredVendor)->name,
                'preferred_unit_price' => $purchasingItem->preferred_unit_price,
                'preferred_total_price' => $purchasingItem->preferred_total_price,
                // PO/INV/GRN
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
                        'notes' => $v->notes,
                    ];
                })->values(),
            ],
        ]);
    }

    // GET /purchasing/items
    public function index(Request $request)
    {
        $q = PurchasingItem::query()
            ->with(['approvalRequest', 'masterItem', 'preferredVendor'])
            ->latest();

        if ($s = trim((string)$request->get('search', ''))) {
            $q->whereHas('approvalRequest', function($w) use ($s) {
                $w->where('request_number', 'like', "%$s%");
            })->orWhereHas('masterItem', function($w) use ($s) {
                $w->where('name', 'like', "%$s%");
            });
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        $items = $q->paginate(15)->withQueryString();

        return view('purchasing.items.index', [
            'items' => $items,
        ]);
    }

    // GET /purchasing/items/{purchasingItem}
    public function show(PurchasingItem $purchasingItem)
    {
        $purchasingItem->load(['approvalRequest', 'masterItem.unit', 'vendors.supplier', 'preferredVendor']);
        return view('purchasing.items.show', [
            'item' => $purchasingItem,
        ]);
    }

    // POST /purchasing/items/{purchasingItem}/benchmarking
    public function saveBenchmarking(Request $request, PurchasingItem $purchasingItem)
    {
        $data = $request->validate([
            'vendors' => 'required|array|min:1',
            'vendors.*.supplier_id' => 'required|integer|exists:suppliers,id',
            'vendors.*.unit_price' => 'nullable|numeric|min:0',
            'vendors.*.total_price' => 'nullable|numeric|min:0',
            'vendors.*.notes' => 'nullable|string|max:255',
        ]);

        $updated = $this->service->saveBenchmarking($purchasingItem, $data['vendors']);

        return back()->with('success', 'Benchmarking saved.');
    }

    // POST /purchasing/items/{purchasingItem}/preferred
    public function selectPreferred(Request $request, PurchasingItem $purchasingItem)
    {
        $data = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'unit_price' => 'nullable|numeric|min:0',
            'total_price' => 'nullable|numeric|min:0',
        ]);

        // Ensure supplier exists in benchmarking vendors for this item
        $existsInBenchmark = $purchasingItem->vendors()
            ->where('supplier_id', (int)$data['supplier_id'])
            ->exists();
        if (!$existsInBenchmark) {
            return back()->withErrors(['supplier_id' => 'Vendor harus berasal dari daftar benchmarking.'])->withInput();
        }

        $updated = $this->service->selectPreferred(
            $purchasingItem,
            (int)$data['supplier_id'],
            $data['unit_price'] ?? null,
            $data['total_price'] ?? null
        );

        return back()->with('success', 'Preferred vendor selected.');
    }

    // POST /purchasing/items/{purchasingItem}/po
    public function issuePO(Request $request, PurchasingItem $purchasingItem)
    {
        $data = $request->validate([
            'po_number' => 'required|string|max:100',
        ]);

        $updated = $this->service->issuePO($purchasingItem, $data['po_number']);

        return back()->with('success', 'PO issued.');
    }

    // POST /purchasing/items/{purchasingItem}/grn
    public function receiveGRN(Request $request, PurchasingItem $purchasingItem)
    {
        $data = $request->validate([
            'grn_date' => 'required|date',
        ]);

        $updated = $this->service->receiveGRN($purchasingItem, Carbon::parse($data['grn_date']));

        return back()->with('success', 'GRN recorded.');
    }

    // POST /purchasing/items/{purchasingItem}/done
    public function markDone(Request $request, PurchasingItem $purchasingItem)
    {
        $data = $request->validate([
            'done_notes' => 'nullable|string|max:1000',
        ]);
        $updated = $this->service->markDone($purchasingItem, $data['done_notes'] ?? null);

        return back()->with('success', 'Purchasing item marked as DONE.');
    }

    // POST /purchasing/items/{purchasingItem}/invoice
    public function saveInvoice(Request $request, PurchasingItem $purchasingItem)
    {
        $data = $request->validate([
            'invoice_number' => 'required|string|max:100',
        ]);

        $purchasingItem->update(['invoice_number' => $data['invoice_number']]);

        return back()->with('success', 'Invoice saved.');
    }

    // GET /api/purchasing/items/suggest
    public function suggest(Request $request)
    {
        $s = trim((string) $request->get('search', ''));
        $limit = min(20, (int)($request->get('limit', 10)) ?: 10);

        $q = PurchasingItem::query()
            ->with(['approvalRequest:id,request_number', 'masterItem:id,name'])
            ->latest();

        if ($s !== '') {
            $q->where(function($w) use ($s) {
                $w->whereHas('approvalRequest', function($w2) use ($s) {
                    $w2->where('request_number', 'like', "%$s%");
                })->orWhereHas('masterItem', function($w3) use ($s) {
                    $w3->where('name', 'like', "%$s%");
                })->orWhere('status', 'like', "%$s%");
            });
        }

        $items = $q->limit($limit)->get();

        $data = $items->map(function($pi){
            $ps = $pi->status ?? 'unprocessed';
            $psText = match($ps){
                'unprocessed' => 'Belum diproses',
                'benchmarking' => 'Pemilihan vendor',
                'selected' => 'Uji coba/Proses PR sistem',
                'po_issued' => 'Proses di vendor',
                'grn_received' => 'Barang sudah diterima',
                'done' => 'Selesai',
                default => strtoupper($ps),
            };
            return [
                'id' => $pi->id,
                'request_number' => $pi->approvalRequest?->request_number,
                'item_name' => $pi->masterItem?->name,
                'status' => $pi->status,
                'qty' => (int)$pi->quantity,
                'label' => trim(($pi->approvalRequest?->request_number ?: '-') . ' • ' . ($pi->masterItem?->name ?: '-') . ' • QTY ' . (int)$pi->quantity . ' • ' . $psText),
            ];
        })->values();

        return response()->json(['items' => $data]);
    }

    // POST /api/purchasing/items/resolve
    public function resolveByRequestAndItem(Request $request)
    {
        $data = $request->validate([
            'approval_request_id' => 'required|integer|exists:approval_requests,id',
            'master_item_id' => 'required|integer|exists:master_items,id',
        ]);

        $req = ApprovalRequest::with(['masterItems' => function($q){ $q->withPivot(['quantity']); }])
            ->findOrFail($data['approval_request_id']);

        $pi = PurchasingItem::firstOrCreate(
            [
                'approval_request_id' => $req->id,
                'master_item_id' => $data['master_item_id'],
            ],
            [
                'quantity' => (int)($req->masterItems->firstWhere('id', $data['master_item_id'])?->pivot?->quantity ?? 1),
                'status' => 'unprocessed',
            ]
        );

        return response()->json(['id' => $pi->id]);
    }

    // GET /api/purchasing/status/{approvalRequest}
    public function statusDetailsByRequest(ApprovalRequest $approvalRequest)
    {
        $order = [
            'unprocessed' => 0,
            'benchmarking' => 1,
            'selected' => 2,
            'po_issued' => 3,
            'grn_received' => 4,
            'done' => 5,
        ];

        $items = PurchasingItem::with(['statusChanger:id,name'])
            ->where('approval_request_id', $approvalRequest->id)
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'status_code' => 'unprocessed',
                'status_label' => 'Belum diproses',
                'changed_at' => null,
                'changed_by_name' => null,
            ]);
        }

        $selected = $items->sortByDesc(function($pi) use ($order){
            return $order[$pi->status] ?? 0;
        })->first();

        $code = $selected->status ?? 'unprocessed';
        $label = match($code){
            'unprocessed' => 'Belum diproses',
            'benchmarking' => 'Pemilihan vendor',
            'selected' => 'Uji coba/Proses PR sistem',
            'po_issued' => 'Proses di vendor',
            'grn_received' => 'Barang sudah diterima',
            'done' => 'Selesai',
            default => strtoupper($code),
        };

        return response()->json([
            'status_code' => $code,
            'status_label' => $label,
            'changed_at' => optional($selected->status_changed_at)?->toDateTimeString() ?? optional($selected->updated_at)?->toDateTimeString(),
            'changed_by_name' => optional($selected->statusChanger)->name,
            'done_notes' => $selected->done_notes,
        ]);
    }
}
