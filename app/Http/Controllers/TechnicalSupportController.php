<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequestItem;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TechnicalSupportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Departemen di mana user ini adalah manager
        $managerOfDeptIds = Department::where('manager_id', $user->id)->pluck('id');

        // Kita gunakan leftJoin ke approval_requests dan workflows agar bisa memfilter berdasarkan config TS
        $query = ApprovalRequestItem::with(['approvalRequest.requester', 'masterItem.itemCategory', 'approvalRequest.workflow', 'tsCategory'])
            ->select('approval_request_items.*')
            ->join('approval_requests', 'approval_requests.id', '=', 'approval_request_items.approval_request_id')
            ->join('approval_workflows', 'approval_workflows.id', '=', 'approval_requests.workflow_id')
            ->where('approval_request_items.needs_ts', true);

        // Filter status
        if ($request->filled('status')) {
            $query->where('approval_request_items.ts_status', $request->status);
        } else {
            $query->where('approval_request_items.ts_status', 'pending');
        }

        // Logic Akses: User hanya bisa melihat antrean jika dia di-assign sebagai TS
        $query->where(function($q) use ($user, $managerOfDeptIds) {
            // 1. Tipe User
            $q->where(function($sub) use ($user) {
                $sub->where('approval_workflows.ts_approver_type', 'user')
                    ->where('approval_workflows.ts_approver_id', $user->id);
            });
            // 2. Tipe Role
            $q->orWhere(function($sub) use ($user) {
                $sub->where('approval_workflows.ts_approver_type', 'role')
                    ->where('approval_workflows.ts_approver_role_id', $user->role_id);
            });
            // 3. Tipe Department Manager (Manager dari departemen si requester)
            if ($managerOfDeptIds->isNotEmpty()) {
                $q->orWhere(function($sub) use ($managerOfDeptIds) {
                    $sub->where('approval_workflows.ts_approver_type', 'department_manager')
                        ->whereIn('approval_requests.requester_id', function($q2) use ($managerOfDeptIds) {
                            $q2->select('user_id')
                               ->from('user_departments')
                               ->where('is_primary', true)
                               ->whereIn('department_id', $managerOfDeptIds);
                        });
                });
            }
        });

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('masterItem', function($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })->orWhereHas('approvalRequest', function($q2) use ($search) {
                    $q2->where('request_number', 'like', "%{$search}%");
                });
            });
        }

        $items = $query->latest('approval_request_items.created_at')->paginate(15)->withQueryString();

        return view('technical-support.index', compact('items'));
    }

    public function show(ApprovalRequestItem $item)
    {
        // Validasi apakah item ini butuh TS
        if (!$item->needs_ts) {
            abort(404, 'Item tidak membutuhkan Technical Support.');
        }

        $item->load(['approvalRequest.requester', 'masterItem.itemCategory', 'approvalRequest.workflow']);
        
        // TODO: Validasi akses user sama seperti di index

        return view('technical-support.show', compact('item'));
    }

    public function update(Request $request, ApprovalRequestItem $item)
    {
        if (!$item->needs_ts) {
            abort(404, 'Item tidak membutuhkan Technical Support.');
        }

        $validator = Validator::make($request->all(), [
            'ts_specification' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $item->update([
            'ts_specification' => $request->ts_specification,
            'ts_status' => 'done',
        ]);

        return redirect()->route('technical-support.index')->with('success', 'Spesifikasi berhasil disimpan dan status TS selesai.');
    }
}
