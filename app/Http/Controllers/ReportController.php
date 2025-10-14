<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApprovalRequest;
use App\Models\SubmissionType;
use App\Models\Department;
use App\Models\ItemCategory;

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

        $requests = $q->paginate(25)->withQueryString();

        // Build table rows (one row per item)
        $rows = [];
        foreach ($requests as $req) {
            $primaryDept = optional($req->requester?->departments?->first())->name;
            $letterNumber = $req->letter_number ?? null; // optional
            $procurementYear = $req->procurement_year ?? ($req->created_at?->format('Y'));
            $createdAt = $req->created_at; // Tanggal Pengajuan (Carbon|null)
            $receivedAt = $req->received_at ? \Carbon\Carbon::parse($req->received_at) : null; // Tanggal Terima Dokumen
            // Umur Pengajuan = selisih Tanggal Terima Dokumen dengan Tanggal Pengajuan (real days, float)
            $ageDays = ($createdAt && $receivedAt) ? $createdAt->diffInRealDays($receivedAt) : null;

            // Purchasing status simplified: UNPROCESSED or DONE (from DB flag)
            $purchasingStatus = strtoupper(($req->purchasing_status ?? 'unprocessed') === 'done' ? 'DONE' : 'UNPROCESSED');

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
                $procDays = ($createdAt && $grnAt) ? $createdAt->diffInRealDays($grnAt) : null;

                // Format: 1 decimal with comma separator
                $ageText = $ageDays !== null ? (number_format((float)$ageDays, 1, ',', '.') . ' hari') : '-';
                $procText = $procDays !== null ? (number_format((float)$procDays, 1, ',', '.') . ' hari') : '-';

                $row = [
                    'approval_request_id' => $req->id,
                    'master_item_id' => $m->id,
                    'no_input' => $req->request_number ?? '-',
                    'process' => $processText,
                    // Jenis diisi nama item (bukan submission type)
                    'jenis' => $m->name ?? '-',
                    'unit_pengaju' => $primaryDept ?? '-',
                    'tanggal_pengajuan' => $createdAt?->format('Y-m-d') ?? '-',
                    'tanggal_terima_dokumen' => $req->received_at ? \Carbon\Carbon::parse($req->received_at)->format('Y-m-d') : '-',
                    'umur_pengajuan' => $ageText,
                    'no_surat' => $letterNumber ?? '-',
                    'tahun_pengadaan' => $procurementYear ?? '-',
                    // Detail diisi spesifikasi item
                    'detail' => $spec ?: '-',
                    'unit_peruntukan' => $req->unit_peruntukan ?? '-',
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

                // Add action button to process/edit purchasing via report page (resolve then redirect if missing)
                $row['actions'] = [
                    [
                        'type' => 'button',
                        'label' => 'Proses / Edit Purchasing',
                        'color' => 'emerald',
                        'onclick' => $piId
                            ? "window.location.href='" . route('reports.approval-requests.process-purchasing', ['purchasing_item_id' => $piId]) . "'"
                            : "(async function(){try{const res=await fetch('" . route('api.purchasing.items.resolve') . "',{method:'POST',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.getAttribute('content')||''},body:JSON.stringify({approval_request_id:'{$req->id}',master_item_id:'{$m->id}'})});const data=await res.json();if(data&&data.id){const url=new URL('" . route('reports.approval-requests.process-purchasing') . "', window.location.origin);url.searchParams.set('purchasing_item_id', String(data.id));window.location.href=url.toString();}}catch(e){alert('Gagal membuka halaman purchasing.');}})()"
                    ]
                ];

                $rows[] = $row;
            }
        }
        $submissionTypes = SubmissionType::orderBy('name')->get(['id','name']);
        $departments = Department::orderBy('name')->get(['id','name']);
        $categories = ItemCategory::orderBy('name')->get(['id','name']);

        return view('reports.approval-requests.index', [
            'columns' => [
                ['label' => 'NO INPUT','field' => 'no_input','width' => 'w-36'],
                ['label' => 'PROCESS','field' => 'process','width' => 'w-32'],
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
            'submissionTypes' => $submissionTypes,
            'departments' => $departments,
            'categories' => $categories,
        ]);
    }

    public function processPurchasing(Request $request)
    {
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
}