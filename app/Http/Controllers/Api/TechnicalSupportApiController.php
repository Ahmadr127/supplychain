<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequestItem;
use App\Models\Department;
use App\Models\TsCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TechnicalSupportApiController extends Controller
{
    public function __construct()
    {
        // Require auth and specific permission for TS queue operations, but not for category list
        $this->middleware('auth:sanctum');
        $this->middleware('permission:access_technical_support')->except(['categories']);
    }

    /**
     * GET /api/ts-categories
     * Return active TS categories for selection in approval dialog.
     */
    public function categories()
    {
        $categories = TsCategory::where('is_active', true)->orderBy('name')->get();
        return response()->json([
            'status' => 'success',
            'data'   => $categories
        ]);
    }

    /**
     * GET /api/technical-support
     * Return paginated items in the TS queue assigned to the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userRoleId = $user->role_id;
        $managerOfDeptIds = Department::where('manager_id', $user->id)->pluck('id');

        $query = ApprovalRequestItem::with(['approvalRequest.requester', 'masterItem.itemCategory', 'masterItem.unit', 'approvalRequest.workflow', 'tsCategory'])
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

        // Filter status: pending, done, or all (defaults to pending)
        $status = $request->input('status', 'pending');
        if ($status !== 'all') {
            $query->where('approval_request_items.ts_status', $status);
        }

        // Search filter
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

        $items = $query->latest('approval_request_items.created_at')
            ->paginate($request->input('per_page', 15));

        // Format items to match mobile expectation
        $items->getCollection()->transform(fn($item) => $this->formatTsItem($item));

        return response()->json([
            'status' => 'success',
            'data'   => $items
        ]);
    }

    /**
     * GET /api/technical-support/{item}
     * Get detail of a specific TS item.
     */
    public function show(ApprovalRequestItem $item)
    {
        if (!$item->needs_ts) {
            return response()->json(['status' => 'error', 'message' => 'Item tidak membutuhkan Technical Support.'], 404);
        }

        $item->load(['approvalRequest.requester', 'masterItem.itemCategory', 'masterItem.unit', 'approvalRequest.workflow', 'tsCategory']);
        
        if (!$this->checkAccess($item)) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki akses sebagai Technical Support untuk item ini.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $this->formatTsItem($item)
        ]);
    }

    /**
     * PUT /api/technical-support/{item}
     * Save specification and mark TS as done.
     */
    public function update(Request $request, ApprovalRequestItem $item)
    {
        if (!$item->needs_ts) {
            return response()->json(['status' => 'error', 'message' => 'Item tidak membutuhkan Technical Support.'], 404);
        }

        if (!$this->checkAccess($item)) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki akses sebagai Technical Support untuk item ini.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'ts_specification' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
        }

        $item->update([
            'ts_specification' => $request->ts_specification,
            'ts_status'        => 'done',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Spesifikasi Technical Support berhasil disimpan.',
            'data'    => $this->formatTsItem($item->fresh())
        ]);
    }

    /**
     * DELETE /api/technical-support/{item}
     * Reset specification and mark TS as pending.
     */
    public function destroy(ApprovalRequestItem $item)
    {
        if (!$item->needs_ts) {
            return response()->json(['status' => 'error', 'message' => 'Item tidak membutuhkan Technical Support.'], 404);
        }

        if (!$this->checkAccess($item)) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki akses sebagai Technical Support untuk item ini.'], 403);
        }

        $item->update([
            'ts_specification' => null,
            'ts_status'        => 'pending',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Spesifikasi Technical Support berhasil direset.',
            'data'    => $this->formatTsItem($item->fresh())
        ]);
    }

    /**
     * Check if authenticated user has access to process the item.
     */
    private function checkAccess(ApprovalRequestItem $item): bool
    {
        $user = Auth::user();
        $userRoleId = $user->role_id;
        $managerOfDeptIds = Department::where('manager_id', $user->id)->pluck('id');
        $tsCategory = $item->tsCategory;

        if (!$tsCategory) {
            return false;
        }

        if ($tsCategory->ts_approver_type === 'user' && $tsCategory->ts_approver_id == $user->id) {
            return true;
        }

        if ($tsCategory->ts_approver_type === 'role' && $tsCategory->ts_approver_role_id == $userRoleId) {
            return true;
        }

        if ($tsCategory->ts_approver_type === 'department_manager' && $managerOfDeptIds->isNotEmpty()) {
            $requesterId = $item->approvalRequest->requester_id;
            $requesterInDept = \DB::table('user_departments')
                ->where('user_id', $requesterId)
                ->whereIn('department_id', $managerOfDeptIds)
                ->exists();
            if ($requesterInDept) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper to format an ApprovalRequestItem for the API response.
     */
    private function formatTsItem(ApprovalRequestItem $item): array
    {
        return [
            'id'                  => $item->id,
            'request_id'          => $item->approval_request_id,
            'request_number'      => $item->approvalRequest->request_number ?? '—',
            'requester_name'      => $item->approvalRequest->requester->name ?? '—',
            'created_at'          => $item->created_at ? $item->created_at->toIso8601String() : null,
            'item_name'           => $item->masterItem->name ?? '—',
            'category_name'       => $item->masterItem->itemCategory->name ?? '—',
            'quantity'            => $item->quantity,
            'unit'                => $item->masterItem->unit->name ?? 'Unit',
            'brand'               => $item->brand,
            'specification'       => $item->specification, // Initial spec from requester
            'needs_ts'            => (bool) $item->needs_ts,
            'ts_status'           => $item->ts_status,
            'ts_specification'    => $item->ts_specification, // Spec entered by TS
            'ts_category_id'      => $item->ts_category_id,
            'ts_category_name'    => $item->tsCategory->name ?? '—',
        ];
    }
}
