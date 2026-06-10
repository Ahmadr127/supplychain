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
        $userRoleId = $user->role_id;
        $managerOfDeptIds = Department::where('manager_id', $user->id)->pluck('id');
        
        $query = ApprovalRequestItem::with(['approvalRequest.requester', 'masterItem.itemCategory', 'approvalRequest.workflow', 'tsCategory'])
            ->select('approval_request_items.*')
            ->join('approval_requests', 'approval_requests.id', '=', 'approval_request_items.approval_request_id')
            ->join('ts_categories', 'ts_categories.id', '=', 'approval_request_items.ts_category_id')
            ->where('approval_request_items.needs_ts', true)
            ->where(function($q) use ($user, $userRoleId, $managerOfDeptIds) {
                // 1. Tipe User
                $q->where(function($sub) use ($user) {
                    $sub->where('ts_categories.ts_approver_type', 'user')
                        ->where('ts_categories.ts_approver_id', $user->id);
                });

                // 2. Tipe Role
                if ($userRoleId) {
                    $q->orWhere(function($sub) use ($userRoleId) {
                        $sub->where('ts_categories.ts_approver_type', 'role')
                            ->where('ts_categories.ts_approver_role_id', $userRoleId);
                    });
                }

                // 3. Tipe Department Manager (Manager dari departemen si requester)
                if ($managerOfDeptIds->isNotEmpty()) {
                    $q->orWhere(function($sub) use ($managerOfDeptIds) {
                        $sub->where('ts_categories.ts_approver_type', 'department_manager')
                            ->whereIn('approval_requests.requester_id', function($q2) use ($managerOfDeptIds) {
                                $q2->select('user_id')
                                   ->from('user_departments')
                                   ->whereIn('department_id', $managerOfDeptIds);
                            });
                    });
                }
            });

        // Filter status
        if ($request->filled('status')) {
            $query->where('approval_request_items.ts_status', $request->status);
        } else {
            $query->where('approval_request_items.ts_status', 'pending');
        }

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
        $userRoleId = $user->role_id;
        $managerOfDeptIds = Department::where('manager_id', $user->id)->pluck('id');
        
        $item->load('tsCategory');
        $tsCategory = $item->tsCategory;
        
        $hasAccess = false;
        if ($tsCategory) {
            if ($tsCategory->ts_approver_type === 'user' && $tsCategory->ts_approver_id == $user->id) {
                $hasAccess = true;
            } elseif ($tsCategory->ts_approver_type === 'role' && $tsCategory->ts_approver_role_id == $userRoleId) {
                $hasAccess = true;
            } elseif ($tsCategory->ts_approver_type === 'department_manager' && $managerOfDeptIds->isNotEmpty()) {
                // Cek apakah requester item ini ada di salah satu departemen di mana user ini adalah manager
                $requesterId = $item->approvalRequest->requester_id;
                $requesterInDept = \DB::table('user_departments')
                    ->where('user_id', $requesterId)
                    ->whereIn('department_id', $managerOfDeptIds)
                    ->exists();
                if ($requesterInDept) {
                    $hasAccess = true;
                }
            }
        }

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
