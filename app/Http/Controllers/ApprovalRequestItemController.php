<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalRequestItemController extends Controller
{
    // GET /approval-items
    public function index(Request $request)
    {
        $q = ApprovalRequestItem::query()
            ->with(['approvalRequest:id,request_number,status,created_at', 'masterItem:id,name', 'allocationDepartment:id,name'])
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
        if ($request->filled('department_id')) {
            $q->where('allocation_department_id', (int)$request->department_id);
        }

        $perPage = $request->get('per_page', 10);
        if (!in_array($perPage, [10,25,50,100])) { $perPage = 10; }

        $items = $q->paginate($perPage)->withQueryString();

        return view('approval-items.index', [
            'items' => $items,
            'perPage' => $perPage,
        ]);
    }

    // GET /approval-items/{item}
    public function show(ApprovalRequestItem $approvalItem)
    {
        $approvalItem->load([
            'approvalRequest.workflow', 
            'approvalRequest.requester.departments', 
            'approvalRequest.submissionType',
            'approvalRequest.items.masterItem.itemType',
            'masterItem.itemType',
            'masterItem.itemCategory',
            'masterItem.commodity',
            'masterItem.unit',
            'steps.approver',
            'steps.attachments',
            'approver',
            'allocationDepartment',
            'capexItem',
            'supplier',
        ]);

        // Load item files grouped by master_item_id to show per item (consistent with old show)
        $files = \DB::table('approval_request_item_files')
            ->where('approval_request_id', $approvalItem->approval_request_id)
            ->where('master_item_id', $approvalItem->master_item_id)
            ->get()
            ->groupBy('master_item_id');

        // Group item extras by master_item_id for easy access
        $itemExtras = \App\Models\ApprovalRequestItemExtra::where('approval_request_id', $approvalItem->approval_request_id)
            ->where('master_item_id', $approvalItem->master_item_id)
            ->get()
            ->keyBy('master_item_id');

        return view('approval-items.show', [
            'item' => $approvalItem,
            'approvalRequest' => $approvalItem->approvalRequest,
            'itemFiles' => $files,
            'itemExtras' => $itemExtras,
        ]);
    }

    // POST /approval-items/{item}/submit
    public function submit(Request $request, ApprovalRequestItem $approvalItem)
    {
        // Basic guard
        if (!in_array($approvalItem->status, ['draft', 'rejected'])) {
            return back()->with('error', 'Item tidak dapat disubmit dari status saat ini.');
        }
        $approvalItem->update(['status' => 'submitted']);
        return back()->with('success', 'Item berhasil disubmit.');
    }

    // POST /approval-items/{item}/approve
    public function approve(Request $request, ApprovalRequestItem $approvalItem)
    {
        if (!in_array($approvalItem->status, ['submitted', 'reviewed'])) {
            return back()->with('error', 'Item tidak dapat di-approve dari status saat ini.');
        }
        $approvalItem->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        return back()->with('success', 'Item disetujui.');
    }

    // POST /approval-items/{item}/reject
    public function reject(Request $request, ApprovalRequestItem $approvalItem)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);
        if (!in_array($approvalItem->status, ['submitted', 'reviewed'])) {
            return back()->with('error', 'Item tidak dapat di-reject dari status saat ini.');
        }
        $approvalItem->update([
            'status'      => 'rejected',
            'approved_by' => null,
            'approved_at' => null,
        ]);
        return back()->with('success', 'Item ditolak.');
    }
}
