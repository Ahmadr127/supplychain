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

        // Build table rows
        $rows = [];
        foreach ($requests as $req) {
            $primaryDept = optional($req->requester?->departments?->first())->name;
            $letterNumber = $req->letter_number ?? null; // optional
            $procurementYear = $req->procurement_year ?? ($req->created_at?->format('Y'));
            $createdAt = $req->created_at; // Carbon|null
            // Umur Pengajuan: jumlah hari sejak pengajuan (created_at) sebagai bilangan bulat non-negatif
            $ageDays = $createdAt ? $createdAt->diffInDays(now()) : null;

            // Detail items summary
            $items = $req->masterItems;
            $detailParts = [];
            $catMap = [];
            $totalQty = 0;
            foreach ($items as $m) {
                $qty = (int) ($m->pivot->quantity ?? 0);
                $totalQty += $qty;
                $label = $m->name . ($qty ? " ({$qty})" : '');
                if ($m->pivot?->specification) {
                    $label .= ' - ' . $m->pivot->specification;
                }
                $detailParts[] = $label;
                if ($m->itemCategory?->name) { $catMap[$m->itemCategory->name] = true; }
            }
            $detail = count($detailParts) > 3
                ? implode(', ', array_slice($detailParts, 0, 3)) . ' +' . (count($detailParts) - 3) . ' lagi'
                : implode(', ', $detailParts);

            $rows[] = [
                'no_input' => $req->request_number ?? '-',
                'process' => $req->status ?? '-',
                'jenis' => $req->submissionType?->name ?? '-',
                'unit_pengaju' => $primaryDept ?? '-',
                'tanggal_pengajuan' => $createdAt?->format('Y-m-d') ?? '-',
                'tanggal_terima_dokumen' => $req->received_at ? \Carbon\Carbon::parse($req->received_at)->format('Y-m-d') : '-',
                'umur_pengajuan' => $ageDays !== null ? intval($ageDays) . ' hari' : '-',
                'no_surat' => $letterNumber ?? '-',
                'tahun_pengadaan' => $procurementYear ?? '-',
                'detail' => $detail ?: '-',
                'unit_peruntukan' => $req->unit_peruntukan ?? '-',
                'kategori' => !empty($catMap) ? implode(', ', array_keys($catMap)) : '-',
                'keterangan' => $req->notes ?? '-',
                'qty' => $totalQty,
            ];
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
            ],
            'rows' => $rows,
            'paginator' => $requests,
            'submissionTypes' => $submissionTypes,
            'departments' => $departments,
            'categories' => $categories,
        ]);
    }
}
