<?php

namespace App\Services\Reports;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use App\Models\PurchasingItem;
use App\Models\Department;
use App\Models\SubmissionType;
use App\Models\ItemCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ApprovalRequestReportService
{
    /**
     * Build the query for reports and export with all filters applied.
     */
    public function buildBaseQuery(Request $request)
    {
        // Default purchasing_status filter to unprocessed if not present in request
        if (!$request->has('purchasing_status')) {
            $request->merge(['purchasing_status' => 'unprocessed']);
        }

        $q = ApprovalRequest::query()
            ->with([
                'submissionType:id,name',
                'requester' => fn($q) => $q->select('id','name'),
                'requester.departments' => function($q){ $q->wherePivot('is_primary', true)->select('departments.id','departments.name'); },
                'items.masterItem' => function($q){
                    $q->select('id','name','item_category_id')
                      ->with(['itemCategory:id,name']);
                },
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
            $deptId = (int) $request->department_id;
            $q->whereHas('requester.departments', function($w) use($deptId){
                $w->where('departments.id', $deptId)->where('user_departments.is_primary', true);
            });
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        } else {
            // Default: exclude rejected and cancelled requests
            $q->whereNotIn('status', ['rejected', 'cancelled']);
        }
        if ($request->filled('requester_id')) {
            $q->where('requester_id', (int) $request->requester_id);
        }
        
        // Filter by purchasing item status
        if ($request->filled('purchasing_status')) {
            $ps = $request->purchasing_status;
            if ($ps === 'pending_approval') {
                $q->whereHas('items', function($i) {
                    $i->whereIn('status', ['pending', 'on progress']);
                });
            } elseif ($ps === 'unprocessed') {
                $q->where(function($w) {
                    $w->whereHas('items', function($i) {
                        $i->whereIn('status', ['in_purchasing', 'approved', 'in_release']);
                    })->where(function($w2) {
                        $w2->whereHas('purchasingItems', function($pi) {
                            $pi->where('status', 'unprocessed');
                        })->orWhereDoesntHave('purchasingItems');
                    });
                });
            } else {
                $q->whereHas('purchasingItems', function($pi) use ($ps) {
                    $pi->where('status', $ps);
                });
            }
        }
        if ($request->filled('year')) {
            $q->whereYear('created_at', (int)$request->year);
        }
        if ($request->filled('category_id')) {
            $q->whereHas('items.masterItem.itemCategory', function($w) use ($request){
                $w->where('item_categories.id', $request->category_id);
            });
        }
        if ($s = trim((string)$request->get('search', ''))) {
            $q->where(function($w) use ($s){
                $w->where('request_number', 'ilike', "%$s%")
                  ->orWhereHas('requester', function($r) use($s){
                      $r->where('name', 'ilike', "%$s%");
                  })
                  ->orWhereHas('requester.departments', function($d) use($s){
                      $d->where('name', 'ilike', "%$s%");
                  })
                  ->orWhereHas('items.masterItem', function($mi) use($s){
                      $mi->where('name', 'ilike', "%$s%");
                  })
                  ->orWhere('created_at', 'ilike', "%$s%");

                // Mapping status Indonesian -> code
                $statusMap = [
                    'menunggu approval' => ['pending', 'on progress'],
                    'belum diproses'   => ['unprocessed'],
                    'pemilihan vendor' => ['benchmarking'],
                    'proses pr & po'   => ['selected'],
                    'proses di vendor' => ['po_issued'],
                    'barang diterima'  => ['grn_received'],
                    'selesai'          => ['done'],
                ];
                $sLower = strtolower($s);
                foreach ($statusMap as $key => $codes) {
                    if (str_contains($key, $sLower)) {
                        $w->orWhereHas('items', function($i) use($codes) {
                            $i->whereIn('status', $codes);
                        })->orWhereHas('purchasingItems', function($pi) use($codes) {
                            $pi->whereIn('status', $codes);
                        });
                    }
                }
            });
        }

        return $q;
    }

    /**
     * Map requests and items to the presentation structure required by reports.
     */
    public function mapToReportRows($requests, $deptMap, Request $request)
    {
        $rows = [];
        foreach ($requests as $req) {
            $primaryDept = optional($req->requester?->departments?->first())->name;
            $procurementYear = $req->procurement_year ?? ($req->created_at?->format('Y'));
            $createdAt = $req->created_at;
            $receivedAt = $req->received_at ? Carbon::parse($req->received_at) : null;
            
            if ($createdAt && $receivedAt) {
                $ageSeconds = $createdAt->diffInRealSeconds($receivedAt, false);
                $ageDays = $ageSeconds <= 0 ? 0.0 : ($ageSeconds / 86400);
            } else {
                $ageDays = null;
            }

            foreach ($req->items as $item) {
                if (in_array($item->status, ['rejected', 'cancelled'])) {
                    continue;
                }

                $m = $item->masterItem;
                $qty = (int) ($item->quantity ?? 0);
                $spec = $item->specification ?? null;
                $notes = $item->notes ?? null;

                $pi = $req->purchasingItems?->firstWhere('master_item_id', $m->id);
                $piId = $pi?->id;

                if ($pi) {
                    $itemPurchasingStatusCode = $pi->status;
                } else {
                    if (in_array($item->status, ['in_purchasing', 'approved', 'in_release'])) {
                        $itemPurchasingStatusCode = $item->status;
                    } else {
                        $itemPurchasingStatusCode = 'pending_approval';
                    }
                }

                // Filter items by purchasing status if filter is active
                if ($request->filled('purchasing_status')) {
                    $psFilter = $request->purchasing_status;
                    if ($psFilter === 'pending_approval') {
                        if ($itemPurchasingStatusCode !== 'pending_approval') {
                            continue;
                        }
                    } elseif ($psFilter === 'unprocessed') {
                        if (!in_array($itemPurchasingStatusCode, ['unprocessed', 'in_purchasing', 'approved', 'in_release'])) {
                            continue;
                        }
                    } else {
                        if ($itemPurchasingStatusCode !== $psFilter) {
                            continue;
                        }
                    }
                }

                $itemPurchasingStatusText = match($itemPurchasingStatusCode) {
                    'pending_approval' => 'Menunggu Approval',
                    'unprocessed'      => 'Belum diproses',
                    'in_purchasing'    => 'Menunggu Proses',
                    'approved'         => 'Menunggu Proses',
                    'in_release'       => 'Menunggu Proses',
                    'benchmarking'     => 'Pemilihan vendor',
                    'selected'         => 'Proses PR & PO',
                    'po_issued'        => 'Proses di vendor',
                    'grn_received'     => 'Barang diterima',
                    'done'             => 'Selesai',
                    default            => strtoupper($itemPurchasingStatusCode),
                };

                $bench = [null, null, null];
                if ($pi && $pi->relationLoaded('vendors')) {
                    $vendors = $pi->vendors->take(3)->values();
                    foreach ($vendors as $idx => $v) {
                        $bench[$idx] = [
                            'supplier' => $v->supplier->name ?? '-',
                            'unit_price' => is_null($v->unit_price) ? '-' : ('Rp '.number_format((float)$v->unit_price, 0, ',', '.')),
                            'total_price' => is_null($v->total_price) ? '-' : ('Rp '.number_format((float)$v->total_price, 0, ',', '.')),
                            'raw_unit_price' => is_null($v->unit_price) ? '-' : $v->unit_price,
                            'raw_total_price' => is_null($v->total_price) ? '-' : $v->total_price,
                        ];
                    }
                }

                $preferred = [
                    'supplier' => '-',
                    'unit_price' => '-',
                    'total_price' => '-',
                    'raw_unit_price' => '-',
                    'raw_total_price' => '-',
                ];
                if ($pi) {
                    $prefVendor = $pi->vendors?->firstWhere('supplier_id', $pi->preferred_vendor_id);
                    if ($prefVendor) {
                        $preferred['supplier'] = $prefVendor->supplier->name ?? ('Supplier #'.$prefVendor->supplier_id);
                    }
                    if (!is_null($pi->preferred_unit_price)) {
                        $preferred['unit_price'] = 'Rp '.number_format((float)$pi->preferred_unit_price, 0, ',', '.');
                        $preferred['raw_unit_price'] = $pi->preferred_unit_price;
                    }
                    if (!is_null($pi->preferred_total_price)) {
                        $preferred['total_price'] = 'Rp '.number_format((float)$pi->preferred_total_price, 0, ',', '.');
                        $preferred['raw_total_price'] = $pi->preferred_total_price;
                    }
                }

                $grnAt = $pi && $pi->grn_date ? Carbon::parse($pi->grn_date) : null;
                if ($createdAt && $grnAt) {
                    $procSeconds = $createdAt->diffInRealSeconds($grnAt, false);
                    $procDays = $procSeconds <= 0 ? 0.0 : ($procSeconds / 86400);
                    $procRealDays = $createdAt->diffInRealDays($grnAt);
                } else {
                    $procDays = null;
                    $procRealDays = null;
                }

                $ageText = $ageDays !== null ? (number_format((float)$ageDays, 1, ',', '.') . ' hari') : '-';
                $procText = $procDays !== null ? (number_format((float)$procDays, 1, ',', '.') . ' hari') : '-';
                $rawAgeText = $ageDays !== null ? number_format((float)$ageDays, 1, ',', '.') : '-';
                $rawProcText = $procRealDays !== null ? number_format((float)$procRealDays, 1, ',', '.') : '-';

                $rows[] = [
                    'approval_request_id' => $req->id,
                    'approval_request_item_id' => $item->id,
                    'master_item_id' => $m->id,
                    'no' => '',
                    'no_input' => $req->request_number ?? '-',
                    'nama_pengaju' => $req->requester?->name ?? '-',
                    'process' => $itemPurchasingStatusText,
                    'process_code' => $itemPurchasingStatusCode,
                    'jenis' => $m->name ?? '-',
                    'unit_pengaju' => $primaryDept ?? '-',
                    'tanggal_pengajuan' => $createdAt?->format('Y-m-d') ?? '-',
                    'tanggal_terima_dokumen' => $req->received_at ? Carbon::parse($req->received_at)->format('Y-m-d') : '-',
                    'umur_pengajuan' => $ageText,
                    'raw_umur_pengajuan' => $rawAgeText,
                    'no_surat' => ($item->letter_number ?? '-') ?: '-',
                    'tahun_pengadaan' => $procurementYear ?? '-',
                    'detail' => $spec ?: '-',
                    'unit_peruntukan' => ($item->allocation_department_id && isset($deptMap[$item->allocation_department_id]))
                        ? $deptMap[$item->allocation_department_id]
                        : '-',
                    'kategori' => $m->itemCategory?->name ?? '-',
                    'keterangan' => $notes ?: '-',
                    'qty' => $qty,
                    
                    'benchmarking' => $bench,
                    'preferred' => $preferred,
                    
                    'invoice' => $pi?->invoice_number ?? '-',
                    'po_number' => $pi?->po_number ?? '-',
                    'grn_date' => $pi?->grn_date ? Carbon::parse($pi->grn_date)->format('Y-m-d') : '-',
                    'proc_cycle' => $procText,
                    'raw_proc_cycle' => $rawProcText,
                    'pi_id' => $piId,
                    'item_status' => $item->status,
                ];
            }
        }
        return $rows;
    }

    /**
     * Get counts for statuses in report page.
     */
    public function getReportCounts()
    {
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

        return [
            'statusCounts' => $statusCounts,
            'purchasingCounts' => $purchasingCounts,
        ];
    }
}
