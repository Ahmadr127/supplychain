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

        // Kita gunakan subquery whereExists agar tidak bergantung pada workflow request secara global
        $query = ApprovalRequestItem::with(['approvalRequest.requester', 'masterItem.itemCategory', 'approvalRequest.workflow', 'tsCategory'])
            ->select('approval_request_items.*')
            ->join('approval_requests', 'approval_requests.id', '=', 'approval_request_items.approval_request_id')
            ->where('approval_request_items.needs_ts', true);

        // Filter status
        if ($request->filled('status')) {
            $query->where('approval_request_items.ts_status', $request->status);
        } else {
            $query->where('approval_request_items.ts_status', 'pending');
        }

        // Logic Akses: User hanya bisa melihat antrean jika dia di-assign sebagai TS di workflow aktif item ini
        $query->whereExists(function($q) use ($user, $managerOfDeptIds) {
            $q->select(\Illuminate\Support\Facades\DB::raw(1))
              ->from('approval_workflow_ts_categories')
              ->join('approval_workflows', 'approval_workflows.id', '=', 'approval_workflow_ts_categories.approval_workflow_id')
              ->whereColumn('approval_workflow_ts_categories.ts_category_id', 'approval_request_items.ts_category_id')
              ->whereColumn('approval_workflow_ts_categories.approval_workflow_id', 'approval_request_items.workflow_id')
              ->where(function($sub) use ($user, $managerOfDeptIds) {
                  // 1. Tipe User
                  $sub->where(function($s) use ($user) {
                      $s->where('approval_workflows.ts_approver_type', 'user')
                        ->where('approval_workflows.ts_approver_id', $user->id);
                  });
                  // 2. Tipe Role
                  $sub->orWhere(function($s) use ($user) {
                      $s->where('approval_workflows.ts_approver_type', 'role')
                        ->where('approval_workflows.ts_approver_role_id', $user->role_id);
                  });
                  // 3. Tipe Department Manager (Manager dari departemen si requester)
                  if ($managerOfDeptIds->isNotEmpty()) {
                      $sub->orWhere(function($s) use ($managerOfDeptIds) {
                          $s->where('approval_workflows.ts_approver_type', 'department_manager')
                            ->whereIn('approval_requests.requester_id', function($q2) use ($managerOfDeptIds) {
                                $q2->select('user_id')
                                   ->from('user_departments')
                                   ->where('is_primary', true)
                                   ->whereIn('department_id', $managerOfDeptIds);
                            });
                      });
                  }
              });
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
        
        $user = Auth::user();
        $managerOfDeptIds = Department::where('manager_id', $user->id)->pluck('id');
        
        $hasAccess = \Illuminate\Support\Facades\DB::table('approval_workflow_ts_categories')
            ->join('approval_workflows', 'approval_workflows.id', '=', 'approval_workflow_ts_categories.approval_workflow_id')
            ->where('approval_workflow_ts_categories.ts_category_id', $item->ts_category_id)
            ->where('approval_workflow_ts_categories.approval_workflow_id', $item->workflow_id)
            ->where(function($sub) use ($user, $managerOfDeptIds, $item) {
                // 1. Tipe User
                $sub->where(function($s) use ($user) {
                    $s->where('approval_workflows.ts_approver_type', 'user')
                      ->where('approval_workflows.ts_approver_id', $user->id);
                });
                // 2. Tipe Role
                $sub->orWhere(function($s) use ($user) {
                    $s->where('approval_workflows.ts_approver_type', 'role')
                      ->where('approval_workflows.ts_approver_role_id', $user->role_id);
                });
                // 3. Tipe Department Manager
                if ($managerOfDeptIds->isNotEmpty()) {
                    $requesterDept = \Illuminate\Support\Facades\DB::table('user_departments')
                        ->where('user_id', $item->approvalRequest->requester_id)
                        ->where('is_primary', true)
                        ->first();
                        
                    if ($requesterDept && $managerOfDeptIds->contains($requesterDept->department_id)) {
                        $sub->orWhere('approval_workflows.ts_approver_type', 'department_manager');
                    }
                }
            })->exists();

        if (!$hasAccess) {
            abort(403, 'Anda tidak memiliki akses sebagai Technical Support untuk item ini.');
        }

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
