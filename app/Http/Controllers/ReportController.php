<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ApprovalRequest;
use App\Models\Department;
use App\Models\SubmissionType;
use App\Models\ItemCategory;
use App\Models\User;
use App\Services\Reports\ApprovalRequestReportService;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ApprovalRequestReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function approvalRequests(Request $request)
    {
        $q = $this->reportService->buildBaseQuery($request);

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

        // Build table rows
        $rows = $this->reportService->mapToReportRows($requests->items(), $deptMap, $request);

        // Add action buttons based on status
        foreach ($rows as &$row) {
            $actions = [];
            $user = auth()->user();
            $canManagePurchasing = $user && ($user->hasPermission('manage_purchasing') || $user->hasPermission('process_purchasing_item'));
            $canManageVendor = $user && $user->hasPermission('manage_vendor');
            
            // Check if item is ready for purchasing
            $piId = $row['pi_id'];
            $isReadyForPurchasing = $piId || in_array($row['item_status'], ['in_purchasing', 'approved', 'in_release']);

            // Show process action only if user has manage_purchasing AND item is ready
            if ($canManagePurchasing && $isReadyForPurchasing) {
                if ($piId) {
                    $actions[] = [
                        'type' => 'link',
                        'label' => 'Proses',
                        'color' => 'green',
                        'url' => route('reports.approval-requests.process-purchasing', ['purchasing_item_id' => $piId])
                    ];
                } else {
                    $actions[] = [
                        'type' => 'button',
                        'label' => 'Proses',
                        'color' => 'green',
                        'onclick' => "(async function(){try{const res=await fetch('" . route('api.purchasing.items.resolve') . "',{method:'POST',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]')?.getAttribute('content')||''},body:JSON.stringify({approval_request_id:'{$row['approval_request_id']}',master_item_id:'{$row['master_item_id']}'})});if(!res.ok){const err=await res.json();alert(err.error||'Gagal memproses item.');return;}const data=await res.json();if(data&&data.id){window.location.href='" . route('reports.approval-requests.process-purchasing') . "?purchasing_item_id='+data.id;}}catch(e){alert('Gagal membuka halaman purchasing.');}})()"
                    ];
                }
            }

            // Vendor page button (only if user can manage vendor AND purchasing item exists)
            if ($piId && $canManageVendor) {
                $actions[] = [
                    'type' => 'link',
                    'label' => 'Vendor',
                    'color' => 'blue',
                    'url' => route('purchasing.items.vendor', $piId)
                ];
            }

            // View approval request button (ALWAYS SHOW)
            $actions[] = [
                'type' => 'link',
                'label' => 'Lihat Request',
                'color' => 'blue',
                'url' => route('approval-items.show', $row['approval_request_item_id'])
            ];

            // Show status badge if NOT ready for purchasing (or just as info)
            if (!$isReadyForPurchasing) {
                $statusColor = match($row['item_status']) {
                    'pending' => 'yellow',
                    'on progress' => 'blue',
                    'rejected' => 'red',
                    'cancelled' => 'gray',
                    default => 'gray'
                };
                
                $actions[] = [
                    'type' => 'text',
                    'label' => ucfirst($row['item_status']),
                    'color' => $statusColor
                ];
            }
            
            $row['actions'] = $actions;
        }
        unset($row);

        $submissionTypes = SubmissionType::orderBy('name')->get(['id','name']);
        $departments = Department::orderBy('name')->get(['id','name']);
        $categories = ItemCategory::orderBy('name')->get(['id','name']);

        // Fill nomor urut (row number, global across pages)
        $startNo = ($requests->currentPage() - 1) * $perPage + 1;
        foreach ($rows as $i => &$row) {
            $row['no'] = $startNo + $i;
        }
        unset($row);

        $counts = $this->reportService->getReportCounts();

        return view('reports.approval-requests.index', [
            'columns' => [
                ['label' => 'No',              'field' => 'no',              'width' => 'w-12', 'permanent' => true],
                ['label' => 'NO INPUT',         'field' => 'no_input',        'width' => 'w-36', 'permanent' => true],
                ['label' => 'Nama Pengaju',     'field' => 'nama_pengaju',    'width' => 'w-40', 'permanent' => true],
                [
                    'label' => 'PROCESS',
                    'field' => 'process',
                    'width' => 'w-32',
                    'permanent' => true,
                    'render' => function($row){
                        $code = $row['process_code'] ?? 'unprocessed';
                        $text = $row['process'] ?? '-';
                        $cls = match($code){
                            'pending_approval' => 'bg-yellow-100 text-yellow-800',
                            'in_purchasing'    => 'bg-blue-500 text-white',
                            'approved'         => 'bg-blue-500 text-white',
                            'in_release'       => 'bg-blue-500 text-white',
                            'benchmarking'     => 'bg-red-600 text-white',
                            'selected'         => 'bg-yellow-400 text-black',
                            'po_issued'        => 'bg-orange-500 text-white',
                            'grn_received'     => 'bg-green-600 text-white',
                            'done'             => 'bg-green-700 text-white',
                            'unprocessed'      => 'bg-gray-200 text-gray-800',
                            default            => 'bg-gray-200 text-gray-800',
                        };
                        return '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium '.$cls.'">'.e($text).'</span>';
                    }
                ],
                ['label' => 'Jenis Barang/Jasa/Program Kerja', 'field' => 'jenis',                  'width' => 'w-56', 'permanent' => true],
                ['label' => 'Unit Pengaju',                    'field' => 'unit_pengaju',            'width' => 'w-48', 'permanent' => true],
                ['label' => 'Tanggal Pengajuan',               'field' => 'tanggal_pengajuan',       'width' => 'w-40', 'permanent' => true],
                ['label' => 'Tanggal Terima Dokumen',          'field' => 'tanggal_terima_dokumen',  'width' => 'w-48'],
                ['label' => 'Umur Pengajuan',                  'field' => 'umur_pengajuan',          'width' => 'w-32'],
                ['label' => 'No Surat',                        'field' => 'no_surat',                'width' => 'w-40'],
                ['label' => 'Tahun Pengadaan',                 'field' => 'tahun_pengadaan',         'width' => 'w-32'],
                [
                    'label' => 'Detail Barang/Jasa/Program Kerja',
                    'field' => 'detail',
                    'width' => 'w-96',
                    'render' => function($row) {
                        $detail = $row['detail'] ?? '-';
                        return '<div class="truncate max-w-[22rem]" title="'.e($detail).'">'.e($detail).'</div>';
                    }
                ],
                ['label' => 'Unit Peruntukan',                 'field' => 'unit_peruntukan',         'width' => 'w-40'],
                ['label' => 'Kategori',                        'field' => 'kategori',                'width' => 'w-56'],
                ['label' => 'Keterangan',                      'field' => 'keterangan',              'width' => 'w-56'],
                ['label' => 'Qty',                             'field' => 'qty',                     'width' => 'w-20'],
                ['label' => 'BM Supplier 1',    'field' => 'bm_supplier_1',   'width' => 'w-56'],
                ['label' => 'BM Unit Price 1',  'field' => 'bm_unit_price_1', 'width' => 'w-40'],
                ['label' => 'BM Total Price 1', 'field' => 'bm_total_price_1','width' => 'w-40'],
                ['label' => 'BM Supplier 2',    'field' => 'bm_supplier_2',   'width' => 'w-56'],
                ['label' => 'BM Unit Price 2',  'field' => 'bm_unit_price_2', 'width' => 'w-40'],
                ['label' => 'BM Total Price 2', 'field' => 'bm_total_price_2','width' => 'w-40'],
                ['label' => 'BM Supplier 3',    'field' => 'bm_supplier_3',   'width' => 'w-56'],
                ['label' => 'BM Unit Price 3',  'field' => 'bm_unit_price_3', 'width' => 'w-40'],
                ['label' => 'BM Total Price 3', 'field' => 'bm_total_price_3','width' => 'w-40'],
                ['label' => 'Preferred Supplier',    'field' => 'pref_supplier',   'width' => 'w-56'],
                ['label' => 'Preferred Unit Price',  'field' => 'pref_unit_price', 'width' => 'w-40'],
                ['label' => 'Preferred Total Price', 'field' => 'pref_total_price','width' => 'w-40'],
                ['label' => 'INV',         'field' => 'invoice',   'width' => 'w-40'],
                ['label' => 'PO',          'field' => 'po_number', 'width' => 'w-40'],
                ['label' => 'Tanggal GRN', 'field' => 'grn_date',  'width' => 'w-40'],
                ['label' => 'Proc Cycle',  'field' => 'proc_cycle','width' => 'w-32'],
            ],
            'rows' => $rows,
            'paginator' => $requests,
            'perPage' => $perPage,
            'submissionTypes' => $submissionTypes,
            'departments' => $departments,
            'categories' => $categories,
            'statusCounts' => $counts['statusCounts'],
            'purchasingCounts'  => $counts['purchasingCounts'],
            'requesterName'     => request('requester_id')
                ? User::find((int) request('requester_id'))?->name
                : null,
        ]);
    }

    /**
     * AJAX endpoint: lazy-load filter dropdown options.
     */
    public function filterOptions(Request $request, string $type): JsonResponse
    {
        $search = trim($request->get('q', ''));
        $limit  = 40;

        $results = match($type) {
            'departments'      => Department::when($search, fn($q) => $q->where('name', 'ilike', "%$search%"))
                                    ->orderBy('name')->limit($limit)->get(['id','name']),
            'categories'       => ItemCategory::when($search, fn($q) => $q->where('name', 'ilike', "%$search%"))
                                    ->orderBy('name')->limit($limit)->get(['id','name']),
            'submission_types' => SubmissionType::when($search, fn($q) => $q->where('name', 'ilike', "%$search%"))
                                    ->orderBy('name')->limit($limit)->get(['id','name']),
            'requesters'       => User::when($search, fn($q) => $q->where('name', 'ilike', "%$search%"))
                                    ->orderBy('name')->limit($limit)->get(['id','name']),
            default            => collect(),
        };

        return response()->json($results);
    }

    public function exportApprovalRequests(Request $request)
    {
        $q = $this->reportService->buildBaseQuery($request);
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

        $rows = $this->reportService->mapToReportRows($requests, $deptMap, $request);

        foreach ($rows as $row) {
            $csvData[] = [
                $row['no_input'],
                $row['process'],
                $row['jenis'],
                $row['unit_pengaju'],
                $row['tanggal_pengajuan'],
                $row['tanggal_terima_dokumen'],
                $row['raw_umur_pengajuan'],
                $row['no_surat'],
                $row['tahun_pengadaan'],
                $row['detail'],
                $row['unit_peruntukan'],
                $row['kategori'],
                $row['keterangan'],
                $row['qty'],
                $row['benchmarking'][0]['supplier'] ?? '-',
                $row['benchmarking'][0]['raw_unit_price'] ?? '-',
                $row['benchmarking'][0]['raw_total_price'] ?? '-',
                $row['benchmarking'][1]['supplier'] ?? '-',
                $row['benchmarking'][1]['raw_unit_price'] ?? '-',
                $row['benchmarking'][1]['raw_total_price'] ?? '-',
                $row['benchmarking'][2]['supplier'] ?? '-',
                $row['benchmarking'][2]['raw_unit_price'] ?? '-',
                $row['benchmarking'][2]['raw_total_price'] ?? '-',
                $row['preferred']['supplier'],
                $row['preferred']['raw_unit_price'],
                $row['preferred']['raw_total_price'],
                $row['invoice'],
                $row['po_number'],
                $row['grn_date'],
                $row['raw_proc_cycle']
            ];
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
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}