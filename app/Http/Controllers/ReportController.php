<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApprovalRequest;
use App\Models\SubmissionType;
use App\Models\Department;
use App\Models\ItemCategory;
use App\Models\PurchasingItem;
use App\Services\Purchasing\PurchasingItemService;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function approvalRequests(Request $request)
    {
        $q = ApprovalRequest::query()
            ->with([
                'submissionType:id,name',
                'requester.departments' => function($q){ $q->wherePivot('is_primary', true); },
                'masterItems' => function($q){
                    $q->select('master_items.id','master_items.name','master_items.item_category_id')
                      ->with(['itemCategory:id,name']);
                },
                // load purchasing items with vendors and preferred info
                'purchasingItems' => function($pi){
                    $pi->select('id','approval_request_id','master_item_id','quantity','status','po_number','grn_date','proc_cycle_days','invoice_number','preferred_vendor_id','preferred_unit_price','preferred_total_price')
                       ->with(['vendors' => function($v){ $v->with('supplier:id,name'); }]);
                },
            ]);

        // Filters
        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('submission_type_id')) {
            $q->where('submission_type_id', $request->submission_type_id);
        }
        if ($request->filled('department_id')) {
            // Filter by requester's primary department
            $deptId = (int) $request->department_id;
            $q->whereHas('requester.departments', function($w) use($deptId){
                $w->where('departments.id', $deptId)->where('user_departments.is_primary', true);
            });
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        // Filter by purchasing process/status if provided
        if ($request->filled('purchasing_status')) {
            $q->where('purchasing_status', $request->purchasing_status);
        }
        if ($request->filled('year')) {
            $q->whereYear('created_at', (int)$request->year);
        }
        if ($request->filled('category_id')) {
            $q->whereHas('masterItems.itemCategory', function($w) use ($request){
                $w->where('item_categories.id', $request->category_id);
            });
        }
        if ($s = trim((string)$request->get('search', ''))) {
            $q->where(function($w) use ($s){
                $w->where('request_number', 'like', "%$s%")
                  ->orWhere('description', 'like', "%$s%")
                  ->orWhere('status', 'like', "%$s%")
                  ->orWhereHas('masterItems', function($mi) use($s){
                      $mi->where('master_items.name', 'like', "%$s%");
                  });
            });
        }

        // Sorting
        $sortable = ['created_at','request_number'];
        $sortBy = in_array($request->sort_by, $sortable) ? $request->sort_by : 'created_at';
        $sortDir = $request->sort_dir === 'asc' ? 'asc' : 'desc';
        $q->orderBy($sortBy, $sortDir);

        // Get per_page parameter with default of 10
        $perPage = $request->get('per_page', 10);
        // Validate per_page value
        if (!in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $requests = $q->paginate($perPage)->withQueryString();
        // Preload department name map for allocation lookup
        $deptMap = Department::pluck('name', 'id');

        // Build table rows (one row per item)
        $rows = [];
        foreach ($requests as $req) {
            $primaryDept = optional($req->requester?->departments?->first())->name;
            // letter number is now per item (pivot)
            $procurementYear = $req->procurement_year ?? ($req->created_at?->format('Y'));
            $createdAt = $req->created_at; // Tanggal Pengajuan (Carbon|null)
            $receivedAt = $req->received_at ? \Carbon\Carbon::parse($req->received_at) : null; // Tanggal Terima Dokumen
            // Umur Pengajuan = selisih Tanggal Terima Dokumen dengan Tanggal Pengajuan (real days, float, non-negative)
            if ($createdAt && $receivedAt) {
                $ageSeconds = $createdAt->diffInRealSeconds($receivedAt, false); // signed seconds
                $ageDays = $ageSeconds <= 0 ? 0.0 : ($ageSeconds / 86400);
            } else {
                $ageDays = null;
            }

            // Purchasing status mapping to human-friendly Indonesian
            $purchasingStatusCode = $req->purchasing_status ?? 'unprocessed';
            $purchasingStatus = match($purchasingStatusCode) {
                'unprocessed' => 'Belum diproses',
                'benchmarking' => 'Pemilihan vendor',
                'selected' => 'Uji coba/Proses PR sistem',
                'po_issued' => 'Proses di vendor',
                'grn_received' => 'Barang sudah diterima',
                'done' => 'Selesai',
                default => strtoupper($purchasingStatusCode),
            };

            foreach ($req->masterItems as $m) {
                $qty = (int) ($m->pivot->quantity ?? 0);
                $spec = $m->pivot->specification ?? null;
                $notes = $m->pivot->notes ?? null;

                // Try map to purchasing item
                $pi = $req->purchasingItems?->firstWhere('master_item_id', $m->id);
                $piId = $pi?->id;
                $piStatus = $pi?->status ?? 'unprocessed';
                $piLabel = trim(($req->request_number ?: '-') . ' • ' . ($m->name ?: '-') . ' • QTY ' . $qty . ' • ' . strtoupper($piStatus));

                // Process text: show purchasing status only
                $processText = $purchasingStatus;

                // Benchmarking vendors (up to 3) for display
                $bench = [null, null, null];
                if ($pi && $pi->relationLoaded('vendors')) {
                    $vendors = $pi->vendors->take(3)->values();
                    foreach ($vendors as $idx => $v) {
                        $bench[$idx] = [
                            'supplier' => $v->supplier->name ?? '-',
                            'unit_price' => is_null($v->unit_price) ? '-' : ('Rp '.number_format((float)$v->unit_price, 0, ',', '.')),
                            'total_price' => is_null($v->total_price) ? '-' : ('Rp '.number_format((float)$v->total_price, 0, ',', '.')),
                        ];
                    }
                }

                // Preferred vendor display
                $preferred = [
                    'supplier' => '-',
                    'unit_price' => '-',
                    'total_price' => '-',
                ];
                if ($pi) {
                    $prefVendor = $pi->vendors?->firstWhere('supplier_id', $pi->preferred_vendor_id);
                    if ($prefVendor) {
                        $preferred['supplier'] = $prefVendor->supplier->name ?? ('Supplier #'.$prefVendor->supplier_id);
                    }
                    if (!is_null($pi->preferred_unit_price)) {
                        $preferred['unit_price'] = 'Rp '.number_format((float)$pi->preferred_unit_price, 0, ',', '.');
                    }
                    if (!is_null($pi->preferred_total_price)) {
                        $preferred['total_price'] = 'Rp '.number_format((float)$pi->preferred_total_price, 0, ',', '.');
                    }
                }

                // Proc Cycle = selisih Tanggal GRN dengan Tanggal Pengajuan
                $grnAt = $pi && $pi->grn_date ? \Carbon\Carbon::parse($pi->grn_date) : null;
                if ($createdAt && $grnAt) {
                    $procSeconds = $createdAt->diffInRealSeconds($grnAt, false);
                    $procDays = $procSeconds <= 0 ? 0.0 : ($procSeconds / 86400);
                } else {
                    $procDays = null;
                }

                // Format: 1 decimal with comma separator
                $ageText = $ageDays !== null ? (number_format((float)$ageDays, 1, ',', '.') . ' hari') : '-';
                $procText = $procDays !== null ? (number_format((float)$procDays, 1, ',', '.') . ' hari') : '-';

                $row = [
                    'approval_request_id' => $req->id,
                    'master_item_id' => $m->id,
                    'no_input' => $req->request_number ?? '-',
                    'process' => $processText,
                    // also include raw code for color mapping in view
                    'process_code' => $purchasingStatusCode,
                    // Jenis diisi nama item (bukan submission type)
                    'jenis' => $m->name ?? '-',
                    'unit_pengaju' => $primaryDept ?? '-',
                    'tanggal_pengajuan' => $createdAt?->format('Y-m-d') ?? '-',
                    'tanggal_terima_dokumen' => $req->received_at ? \Carbon\Carbon::parse($req->received_at)->format('Y-m-d') : '-',
                    'umur_pengajuan' => $ageText,
                    // Per-item No Surat from pivot
                    'no_surat' => ($m->pivot->letter_number ?? '-') ?: '-',
                    'tahun_pengadaan' => $procurementYear ?? '-',
                    // Detail diisi spesifikasi item
                    'detail' => $spec ?: '-',
                    // Per-item Unit Peruntukan from pivot allocation_department_id
                    'unit_peruntukan' => ($m->pivot->allocation_department_id && isset($deptMap[$m->pivot->allocation_department_id]))
                        ? $deptMap[$m->pivot->allocation_department_id]
                        : '-',
                    // Kategori per item
                    'kategori' => $m->itemCategory?->name ?? '-',
                    // Keterangan dari notes item (pivot)
                    'keterangan' => $notes ?: '-',
                    // Qty per item
                    'qty' => $qty,
                    // Benchmarking vendors columns
                    'bm_supplier_1' => $bench[0]['supplier'] ?? '-',
                    'bm_unit_price_1' => $bench[0]['unit_price'] ?? '-',
                    'bm_total_price_1' => $bench[0]['total_price'] ?? '-',
                    'bm_supplier_2' => $bench[1]['supplier'] ?? '-',
                    'bm_unit_price_2' => $bench[1]['unit_price'] ?? '-',
                    'bm_total_price_2' => $bench[1]['total_price'] ?? '-',
                    'bm_supplier_3' => $bench[2]['supplier'] ?? '-',
                    'bm_unit_price_3' => $bench[2]['unit_price'] ?? '-',
                    'bm_total_price_3' => $bench[2]['total_price'] ?? '-',
                    // Preferred vendor columns
                    'pref_supplier' => $preferred['supplier'],
                    'pref_unit_price' => $preferred['unit_price'],
                    'pref_total_price' => $preferred['total_price'],
                    // PO/INV/GRN/Cycle
                    'invoice' => $pi?->invoice_number ?? '-',
                    'po_number' => $pi?->po_number ?? '-',
                    'grn_date' => $pi?->grn_date ? \Carbon\Carbon::parse($pi->grn_date)->format('Y-m-d') : '-',
                    'proc_cycle' => $procText,
                ];

                // Add action buttons based on status
                $actions = [];
                $user = auth()->user();
                $canManagePurchasing = $user && $user->hasPermission('manage_purchasing');
                $canManageVendor = $user && $user->hasPermission('manage_vendor');

                if ($req->status === 'approved') {
                    // Show process action only if user has manage_purchasing
                    if ($canManagePurchasing) {
                        if ($piId) {
                            // If purchasing item exists, direct link to process page
                            $actions[] = [
                                'type' => 'link',
                                'label' => 'Proses',
                                'color' => 'emerald',
                                'url' => route('reports.approval-requests.process-purchasing', ['purchasing_item_id' => $piId])
                            ];
                        } else {
                            // If no purchasing item, create it first then redirect to process page
                            $actions[] = [
                                'type' => 'button',
                                'label' => 'Proses',
                                'color' => 'emerald',
                                'onclick' => "(async function(){try{const res=await fetch('" . route('api.purchasing.items.resolve') . "',{method:'POST',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.getAttribute('content')||''},body:JSON.stringify({approval_request_id:'{$req->id}',master_item_id:'{$m->id}'})});if(!res.ok){const err=await res.json();alert(err.error||'Request belum approved atau ada kesalahan.');return;}const data=await res.json();if(data&&data.id){window.location.href='" . route('reports.approval-requests.process-purchasing') . "?purchasing_item_id='+data.id;}}catch(e){alert('Gagal membuka halaman purchasing.');}})()"
                            ];
                        }
                    }

                    // Vendor page button (only if user can manage vendor)
                    if ($piId && $canManageVendor) {
                        $actions[] = [
                            'type' => 'link',
                            'label' => 'Vendor',
                            'color' => 'blue',
                            'url' => route('purchasing.items.vendor', $piId)
                        ];
                    }

                    // View approval request button
                    $actions[] = [
                        'type' => 'link',
                        'label' => 'Lihat Request',
                        'color' => 'blue',
                        'url' => route('approval-requests.show', $req->id)
                    ];
                } else {
                    // For non-approved requests, show view request button
                    $actions[] = [
                        'type' => 'link',
                        'label' => 'Lihat',
                        'color' => 'blue',
                        'url' => route('approval-requests.show', $req->id)
                    ];
                    
                    // Show status badge
                    $statusColor = match($req->status) {
                        'pending' => 'yellow',
                        'rejected' => 'red',
                        'cancelled' => 'gray',
                        default => 'gray'
                    };
                    $actions[] = [
                        'type' => 'text',
                        'label' => ucfirst($req->status),
                        'color' => $statusColor
                    ];
                }
                
                $row['actions'] = $actions;

                $rows[] = $row;
            }
        }
        $submissionTypes = SubmissionType::orderBy('name')->get(['id','name']);
        $departments = Department::orderBy('name')->get(['id','name']);
        $categories = ItemCategory::orderBy('name')->get(['id','name']);

        return view('reports.approval-requests.index', [
            'columns' => [
                ['label' => 'NO INPUT','field' => 'no_input','width' => 'w-36'],
                [
                    'label' => 'PROCESS',
                    'width' => 'w-32',
                    'render' => function($row){
                        $code = $row['process_code'] ?? 'unprocessed';
                        $text = $row['process'] ?? '-';
                        // Map purchasing status to Tailwind classes (align with my-requests.blade.php)
                        $cls = match($code){
                            'benchmarking' => 'bg-red-600 text-white',
                            'selected' => 'bg-yellow-400 text-black',
                            'po_issued' => 'bg-orange-500 text-white',
                            'grn_received' => 'bg-green-600 text-white',
                            'done' => 'bg-green-700 text-white',
                            'unprocessed' => 'bg-gray-200 text-gray-800',
                            default => 'bg-gray-200 text-gray-800',
                        };
                        return '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium '.$cls.'">'.e($text).'</span>';
                    }
                ],
                ['label' => 'Jenis Barang/Jasa/Program Kerja','field' => 'jenis','width' => 'w-56'],
                ['label' => 'Unit Pengaju','field' => 'unit_pengaju','width' => 'w-48'],
                ['label' => 'Tanggal Pengajuan','field' => 'tanggal_pengajuan','width' => 'w-40'],
                ['label' => 'Tanggal Terima Dokumen','field' => 'tanggal_terima_dokumen','width' => 'w-48'],
                ['label' => 'Umur Pengajuan','field' => 'umur_pengajuan','width' => 'w-32'],
                ['label' => 'No Surat','field' => 'no_surat','width' => 'w-40'],
                ['label' => 'Tahun Pengadaan','field' => 'tahun_pengadaan','width' => 'w-32'],
                ['label' => 'Detail Barang/jasa/program kerja','field' => 'detail','width' => 'w-[28rem]'],
                ['label' => 'Unit Peruntukan','field' => 'unit_peruntukan','width' => 'w-40'],
                ['label' => 'Kategori','field' => 'kategori','width' => 'w-56'],
                ['label' => 'Keterangan','field' => 'keterangan','width' => 'w-56'],
                ['label' => 'Qty','field' => 'qty','width' => 'w-20'],
                // Benchmarking vendors (up to 3)
                ['label' => 'BM Supplier 1','field' => 'bm_supplier_1','width' => 'w-56'],
                ['label' => 'BM Unit Price 1','field' => 'bm_unit_price_1','width' => 'w-40'],
                ['label' => 'BM Total Price 1','field' => 'bm_total_price_1','width' => 'w-40'],
                ['label' => 'BM Supplier 2','field' => 'bm_supplier_2','width' => 'w-56'],
                ['label' => 'BM Unit Price 2','field' => 'bm_unit_price_2','width' => 'w-40'],
                ['label' => 'BM Total Price 2','field' => 'bm_total_price_2','width' => 'w-40'],
                ['label' => 'BM Supplier 3','field' => 'bm_supplier_3','width' => 'w-56'],
                ['label' => 'BM Unit Price 3','field' => 'bm_unit_price_3','width' => 'w-40'],
                ['label' => 'BM Total Price 3','field' => 'bm_total_price_3','width' => 'w-40'],
                // Preferred vendor
                ['label' => 'Preferred Supplier','field' => 'pref_supplier','width' => 'w-56'],
                ['label' => 'Preferred Unit Price','field' => 'pref_unit_price','width' => 'w-40'],
                ['label' => 'Preferred Total Price','field' => 'pref_total_price','width' => 'w-40'],
                // PO/INV/GRN/CYCLE
                ['label' => 'INV','field' => 'invoice','width' => 'w-40'],
                ['label' => 'PO','field' => 'po_number','width' => 'w-40'],
                ['label' => 'Tanggal GRN','field' => 'grn_date','width' => 'w-40'],
                ['label' => 'Proc Cycle','field' => 'proc_cycle','width' => 'w-32'],
            ],
            'rows' => $rows,
            'paginator' => $requests,
            'perPage' => $perPage,
            'submissionTypes' => $submissionTypes,
            'departments' => $departments,
            'categories' => $categories,
        ]);
    }

    public function processPurchasing(Request $request)
    {
        // Authorization: only users with manage_purchasing may access
        if (!(auth()->user()?->hasPermission('manage_purchasing'))) {
            abort(403, 'Unauthorized action.');
        }
        $id = (int) $request->query('purchasing_item_id');
        if (!$id) {
            return redirect()->route('reports.approval-requests')->with('error', 'Purchasing Item tidak ditemukan.');
        }

        $item = \App\Models\PurchasingItem::with([
            'approvalRequest',
            'masterItem',
            'vendors.supplier',
            'preferredVendor',
        ])->find($id);

        if (!$item) {
            return redirect()->route('reports.approval-requests')->with('error', 'Purchasing Item tidak ditemukan.');
        }

        return view('reports.approval-requests.process-purchasing', compact('item'));
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
            'preferredVendor',
        ]);

        return view('purchasing.items.form-vendor', [
            'item' => $purchasingItem,
        ]);
    }

    // Purchasing Item API methods (moved from PurchasingItemController)
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
        if ($approvalRequest->status !== 'approved') {
            return response()->json(['error' => 'Request belum approved'], 400);
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
        // Pre-filter rows: only keep rows that have a supplier selected.
        // This prevents validation error for empty rows or rows where user typed prices without selecting a supplier.
        $rows = collect($request->input('vendors', []))
            ->filter(function ($row) {
                return isset($row['supplier_id']) && $row['supplier_id'] !== null && $row['supplier_id'] !== '';
            })
            ->values()
            ->all();

        if (count($rows) === 0) {
            return back()->withErrors(['vendors' => 'Minimal 1 baris vendor diisi.'])->withInput();
        }

        // Validate only the filtered rows
        $validator = \Validator::make(['vendors' => $rows], [
            'vendors' => 'required|array|min:1',
            'vendors.*.supplier_id' => 'required|integer|exists:suppliers,id',
            'vendors.*.unit_price' => 'nullable|numeric|min:0|max:999999999999.99',
            'vendors.*.total_price' => 'nullable|numeric|min:0|max:999999999999.99',
            'vendors.*.notes' => 'nullable|string|max:255',
        ]);
        $validator->validate();

        $service = app(PurchasingItemService::class);
        $service->saveBenchmarking($purchasingItem, $rows);
        
        return back()->with('success', 'Benchmarking berhasil disimpan.');
    }

    public function selectPreferred(Request $request, PurchasingItem $purchasingItem)
    {
        if (!(auth()->user()?->hasPermission('manage_vendor') || auth()->user()?->hasPermission('manage_purchasing'))) {
            abort(403, 'Unauthorized action.');
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

    public function saveBenchmarkNotes(Request $request, PurchasingItem $purchasingItem)
    {
        if (!(auth()->user()?->hasPermission('manage_vendor') || auth()->user()?->hasPermission('manage_purchasing'))) {
            abort(403, 'Unauthorized action.');
        }
        $data = $request->validate([
            'benchmark_notes' => 'nullable|string|max:2000',
        ]);
        $purchasingItem->update([
            'benchmark_notes' => $data['benchmark_notes'] ?? null,
        ]);
        return back()->with('success', 'Catatan benchmarking berhasil disimpan.');
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
            'grn_date' => 'required|date',
        ]);

        $service = app(PurchasingItemService::class);
        $service->receiveGRN($purchasingItem, \Carbon\Carbon::parse($data['grn_date']));
        
        return back()->with('success', 'GRN Date berhasil disimpan.');
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
        // Some codebases store invoice on PurchasingItem directly; if your service has saveInvoice(PurchasingItem, string), pass the string value
        if (method_exists($service, 'saveInvoice')) {
            try {
                // Try signature with string
                $service->saveInvoice($purchasingItem, (string) $data['invoice_number']);
            } catch (\TypeError $e) {
                // Fallback in case service expects array (older version)
                $service->saveInvoice($purchasingItem, $data);
            }
        } else {
            // Fallback: update directly if no service method
            $purchasingItem->update(['invoice_number' => (string) $data['invoice_number']]);
        }
        
        return back()->with('success', 'Invoice Number berhasil disimpan.');
    }

    public function deletePurchasingItem(PurchasingItem $purchasingItem)
    {
        try {
            // Check if user has permission
            if (!auth()->user()->hasPermission('manage_purchasing')) {
                return redirect()->back()->with('error', 'Unauthorized');
            }
            
            // Delete related vendor benchmarking data
            $purchasingItem->vendors()->detach();
            
            // Delete the purchasing item
            $purchasingItem->delete();
            
            return redirect()->route('reports.approval-requests')
                ->with('success', 'Purchasing item berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus purchasing item: ' . $e->getMessage());
        }
    }

    public function exportApprovalRequests(Request $request)
    {
        // Apply same filters as index
        $q = ApprovalRequest::query()
            ->with([
                'submissionType:id,name',
                'requester.departments' => function($q){ $q->wherePivot('is_primary', true); },
                'masterItems' => function($q){
                    $q->select('master_items.id','master_items.name','master_items.item_category_id')
                      ->with(['itemCategory:id,name']);
                },
                'purchasingItems' => function($pi){
                    $pi->select('id','approval_request_id','master_item_id','quantity','status','po_number','grn_date','proc_cycle_days','invoice_number','preferred_vendor_id','preferred_unit_price','preferred_total_price')
                       ->with(['vendors' => function($v){ $v->with('supplier:id,name'); }]);
                },
            ]);

        // Apply filters (same as index method)
        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('submission_type_id')) {
            $q->where('submission_type_id', $request->submission_type_id);
        }
        if ($request->filled('department_id')) {
            $deptId = (int) $request->department_id;
            $q->whereHas('requester.departments', function($w) use($deptId){
                $w->where('departments.id', $deptId)->where('user_departments.is_primary', true);
            });
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('year')) {
            $q->whereYear('created_at', (int)$request->year);
        }
        if ($request->filled('category_id')) {
            $q->whereHas('masterItems.itemCategory', function($w) use ($request){
                $w->where('item_categories.id', $request->category_id);
            });
        }
        if ($s = trim((string)$request->get('search', ''))) {
            $q->where(function($w) use ($s){
                $w->where('request_number', 'like', "%$s%")
                  ->orWhere('description', 'like', "%$s%")
                  ->orWhere('status', 'like', "%$s%")
                  ->orWhereHas('masterItems', function($mi) use($s){
                      $mi->where('master_items.name', 'like', "%$s%");
                  });
            });
        }

        $requests = $q->get();
        $deptMap = Department::pluck('name', 'id');

        // Build CSV content
        $csvData = [];
        $headers = [
            'NO INPUT', 'PROCESS', 'Jenis Barang/Jasa', 'Unit Pengaju', 'Tanggal Pengajuan',
            'Tanggal Terima Dokumen', 'Umur Pengajuan', 'No Surat', 'Tahun Pengadaan',
            'Detail', 'Unit Peruntukan', 'Kategori', 'Keterangan', 'Qty',
            'BM Supplier 1', 'BM Unit Price 1', 'BM Total Price 1',
            'BM Supplier 2', 'BM Unit Price 2', 'BM Total Price 2',
            'BM Supplier 3', 'BM Unit Price 3', 'BM Total Price 3',
            'Preferred Supplier', 'Preferred Unit Price', 'Preferred Total Price',
            'INV', 'PO', 'Tanggal GRN', 'Proc Cycle'
        ];
        $csvData[] = $headers;

        foreach ($requests as $req) {
            $primaryDept = optional($req->requester?->departments?->first())->name;
            $procurementYear = $req->procurement_year ?? ($req->created_at?->format('Y'));
            $createdAt = $req->created_at;
            $receivedAt = $req->received_at ? \Carbon\Carbon::parse($req->received_at) : null;
            if ($createdAt && $receivedAt) {
                $ageSeconds = $createdAt->diffInRealSeconds($receivedAt, false);
                $ageDays = $ageSeconds <= 0 ? 0.0 : ($ageSeconds / 86400);
            } else {
                $ageDays = null;
            }
            $purchasingStatusCode = $req->purchasing_status ?? 'unprocessed';
            $purchasingStatus = match($purchasingStatusCode) {
                'unprocessed' => 'Belum diproses',
                'benchmarking' => 'Pemilihan vendor',
                'selected' => 'Uji coba/Proses PR sistem',
                'po_issued' => 'Proses di vendor',
                'grn_received' => 'Barang sudah diterima',
                'done' => 'Selesai',
                default => strtoupper($purchasingStatusCode),
            };

            foreach ($req->masterItems as $m) {
                $qty = (int) ($m->pivot->quantity ?? 0);
                $spec = $m->pivot->specification ?? null;
                $notes = $m->pivot->notes ?? null;
                $pi = $req->purchasingItems?->firstWhere('master_item_id', $m->id);
                
                // Benchmarking vendors
                $bench = [null, null, null];
                if ($pi && $pi->relationLoaded('vendors')) {
                    $vendors = $pi->vendors->take(3)->values();
                    foreach ($vendors as $idx => $v) {
                        $bench[$idx] = [
                            'supplier' => $v->supplier->name ?? '-',
                            'unit_price' => is_null($v->unit_price) ? '-' : $v->unit_price,
                            'total_price' => is_null($v->total_price) ? '-' : $v->total_price,
                        ];
                    }
                }

                // Preferred vendor
                $preferred = ['supplier' => '-', 'unit_price' => '-', 'total_price' => '-'];
                if ($pi) {
                    $prefVendor = $pi->vendors?->firstWhere('supplier_id', $pi->preferred_vendor_id);
                    if ($prefVendor) {
                        $preferred['supplier'] = $prefVendor->supplier->name ?? ('Supplier #'.$prefVendor->supplier_id);
                    }
                    if (!is_null($pi->preferred_unit_price)) {
                        $preferred['unit_price'] = $pi->preferred_unit_price;
                    }
                    if (!is_null($pi->preferred_total_price)) {
                        $preferred['total_price'] = $pi->preferred_total_price;
                    }
                }

                $grnAt = $pi && $pi->grn_date ? \Carbon\Carbon::parse($pi->grn_date) : null;
                $procDays = ($createdAt && $grnAt) ? $createdAt->diffInRealDays($grnAt) : null;
                $ageText = $ageDays !== null ? number_format((float)$ageDays, 1, ',', '.') : '-';
                $procText = $procDays !== null ? number_format((float)$procDays, 1, ',', '.') : '-';

                $csvData[] = [
                    $req->request_number ?? '-',
                    $purchasingStatus,
                    $m->name ?? '-',
                    $primaryDept ?? '-',
                    $createdAt?->format('Y-m-d') ?? '-',
                    $req->received_at ? \Carbon\Carbon::parse($req->received_at)->format('Y-m-d') : '-',
                    $ageText,
                    $m->pivot->letter_number ?? '-',
                    $procurementYear ?? '-',
                    $spec ?: '-',
                    ($m->pivot->allocation_department_id && isset($deptMap[$m->pivot->allocation_department_id]))
                        ? $deptMap[$m->pivot->allocation_department_id] : '-',
                    $m->itemCategory?->name ?? '-',
                    $notes ?: '-',
                    $qty,
                    $bench[0]['supplier'] ?? '-',
                    $bench[0]['unit_price'] ?? '-',
                    $bench[0]['total_price'] ?? '-',
                    $bench[1]['supplier'] ?? '-',
                    $bench[1]['unit_price'] ?? '-',
                    $bench[1]['total_price'] ?? '-',
                    $bench[2]['supplier'] ?? '-',
                    $bench[2]['unit_price'] ?? '-',
                    $bench[2]['total_price'] ?? '-',
                    $preferred['supplier'],
                    $preferred['unit_price'],
                    $preferred['total_price'],
                    $pi?->invoice_number ?? '-',
                    $pi?->po_number ?? '-',
                    $pi?->grn_date ? \Carbon\Carbon::parse($pi->grn_date)->format('Y-m-d') : '-',
                    $procText
                ];
            }
        }

        // Generate CSV
        $filename = 'laporan-pengajuan-' . date('Y-m-d-His') . '.csv';
        $handle = fopen('php://temp', 'r+');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        foreach ($csvData as $row) {
            fputcsv($handle, $row, ';');
        }
        
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}