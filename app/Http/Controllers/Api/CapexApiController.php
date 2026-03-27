<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Capex;
use App\Models\Department;
use App\Models\CapexItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CapEx Header API — CRUD for CapEx (department-level budget header).
 *
 * Permissions:
 *   manage_capex      → Admin: all departments.
 *   manage_capex_unit → Unit user: own department only.
 *
 * Routes:
 *   GET    /api/capex              – List CapEx headers
 *   POST   /api/capex              – Create (admin only)
 *   GET    /api/capex/available    – Available items for dropdown (for approve input_price)
 *   GET    /api/capex/departments  – Department list (for filter dropdowns)
 *   GET    /api/capex/{id}         – Detail + paginated items
 *   PATCH  /api/capex/{id}         – Update (admin only)
 *   DELETE /api/capex/{id}         – Delete (admin only)
 *   GET    /api/capex/{id}/items   – List items of a CapEx
 */
class CapexApiController extends Controller
{
    private function requireCapexAccess(): void
    {
        // No permission gate (as requested).
        // Access is still scoped by authenticated user's department throughout this controller.
        return;
    }

    private function requireCapexReadAccess(): void
    {
        // No permission gate (as requested).
        // Access is still scoped by authenticated user's department throughout this controller.
        return;
    }

    private function getUserDepartmentId(): ?int
    {
        return Auth::user()->primaryDepartment()->first()?->id;
    }

    private function authorizeCapex(Capex $capex): void
    {
        $deptId = $this->getUserDepartmentId();
        if (!$deptId || $capex->department_id !== $deptId) {
            abort(403, 'Anda tidak dapat mengelola CapEx dari unit lain.');
        }
    }

    // ----------------------------------------------------------------
    // LIST
    // ----------------------------------------------------------------

    /**
     * GET /api/capex
     * Admin → all, Unit → own department. Filter by ?year=, ?department_id=, ?status=, ?search=
     */
    public function index(Request $request)
    {
        $this->requireCapexAccess();
        $deptId = $this->getUserDepartmentId();
        if (!$deptId) {
            return response()->json(['status' => 'error', 'message' => 'Akun Anda tidak terhubung dengan departemen manapun.'], 403);
        }

        $query = Capex::with('department')
            ->where('department_id', $deptId)
            ->where('fiscal_year', $request->get('year', date('Y')));

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $capexes = $query->paginate($request->get('per_page', 20));
        $capexes->getCollection()->transform(fn($c) => $this->summary($c));

        return response()->json(['status' => 'success', 'data' => $capexes]);
    }

    /**
     * POST /api/capex — scoped to login user's department.
     * Body: fiscal_year*, notes
     */
    public function store(Request $request)
    {
        $this->requireCapexAccess();
        $deptId = $this->getUserDepartmentId();
        if (!$deptId) {
            return response()->json(['status' => 'error', 'message' => 'Akun Anda tidak terhubung dengan departemen manapun.'], 403);
        }

        $v = $request->validate([
            'fiscal_year'   => 'required|integer|min:2020|max:2100',
            'notes'         => 'nullable|string|max:500',
        ]);

        if (Capex::where('department_id', $deptId)->where('fiscal_year', $v['fiscal_year'])->exists()) {
            return response()->json(['status' => 'error', 'message' => 'CapEx untuk departemen dan tahun ini sudah ada.'], 422);
        }

        $capex = Capex::create(array_merge($v, [
            'department_id' => $deptId,
            'status' => 'active',
            'created_by' => Auth::id(),
        ]));
        $capex->load('department');

        return response()->json(['status' => 'success', 'message' => 'CapEx berhasil dibuat.', 'data' => $this->summary($capex)], 201);
    }

    /**
     * GET /api/capex/{capex}
     * Detail CapEx with paginated items. Filter items with ?search=, ?per_page=
     */
    public function show(Request $request, Capex $capex)
    {
        $this->requireCapexAccess();
        $this->authorizeCapex($capex);

        $capex->load(['department', 'creator']);

        $itemsQuery = $capex->items()->with('activeAllocations.approvalRequest');

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $itemsQuery->where(fn($q) => $q->where('capex_id_number', 'like', $s)
                ->orWhere('item_name', 'like', $s)
                ->orWhere('pic', 'like', $s)
                ->orWhere('category', 'like', $s)
                ->orWhere('description', 'like', $s));
        }

        $items = $itemsQuery->orderBy('priority_scale')->orderBy('id')
            ->paginate($request->get('per_page', 25));
        $items->getCollection()->transform(fn($i) => $this->formatItem($i));

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'               => $capex->id,
                'department'       => $capex->department,
                'fiscal_year'      => $capex->fiscal_year,
                'status'           => $capex->status,
                'notes'            => $capex->notes,
                'total_budget'     => $capex->total_budget,
                'total_used'       => $capex->total_used,
                'remaining_budget' => $capex->remaining_budget,
                'utilization_pct'  => $capex->utilization_percent,
                'created_by'       => $capex->creator?->name,
                'items'            => $items,
            ],
        ]);
    }

    /**
     * PATCH /api/capex/{capex} — scoped to login user's department.
     * Body: fiscal_year*, status* (draft|active|closed), notes
     */
    public function update(Request $request, Capex $capex)
    {
        $this->requireCapexAccess();
        $this->authorizeCapex($capex);

        $v = $request->validate([
            'fiscal_year' => 'required|integer|min:2020|max:2100',
            'notes'       => 'nullable|string|max:500',
            'status'      => 'required|in:draft,active,closed',
        ]);

        if ((int) $v['fiscal_year'] !== (int) $capex->fiscal_year) {
            if ($capex->items()->exists()) {
                return response()->json(['status' => 'error', 'message' => 'Tahun tidak dapat diubah karena sudah ada item CapEx.'], 422);
            }
            if (Capex::where('department_id', $capex->department_id)->where('fiscal_year', $v['fiscal_year'])->where('id', '!=', $capex->id)->exists()) {
                return response()->json(['status' => 'error', 'message' => 'CapEx untuk departemen + tahun ini sudah ada.'], 422);
            }
        }

        $capex->update($v);
        $capex->load('department');

        return response()->json(['status' => 'success', 'message' => 'CapEx berhasil diupdate.', 'data' => $this->summary($capex)]);
    }

    /**
     * DELETE /api/capex/{capex} — scoped to login user's department.
     * Fails if any item has been used.
     */
    public function destroy(Capex $capex)
    {
        $this->requireCapexAccess();
        $this->authorizeCapex($capex);

        if ($capex->items()->where('used_amount', '>', 0)->exists()) {
            return response()->json(['status' => 'error', 'message' => 'CapEx tidak dapat dihapus karena ada item yang sudah terpakai.'], 422);
        }

        $capex->items()->delete();
        $capex->delete();

        return response()->json(['status' => 'success', 'message' => 'CapEx berhasil dihapus.']);
    }

    // ----------------------------------------------------------------
    // ITEM LIST (read-only — write ops handled by CapexItemApiController)
    // ----------------------------------------------------------------

    /**
     * GET /api/capex/{capex}/items
     * All items for one CapEx. Filter: ?search=, ?status=, ?per_page=
     */
    public function indexItems(Request $request, Capex $capex)
    {
        $this->requireCapexAccess();
        $this->authorizeCapex($capex);

        $query = $capex->items();

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('item_name', 'like', $s)
                ->orWhere('capex_id_number', 'like', $s)
                ->orWhere('category', 'like', $s)
                ->orWhere('pic', 'like', $s));
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $items = $query->orderBy('priority_scale')->orderBy('id')
            ->paginate($request->get('per_page', 25));
        $items->getCollection()->transform(fn($i) => $this->formatItem($i));

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    // ----------------------------------------------------------------
    // SPECIAL LOOKUP ENDPOINTS
    // ----------------------------------------------------------------

    /**
     * GET /api/capex/available?department_id=X&year=2026
     * Available CapEx items for the approval `input_price` step dropdown.
     */
    public function availableItems(Request $request)
    {
        $this->requireCapexReadAccess();
        $request->validate(['department_id' => 'nullable|exists:departments,id']);
        // Use requested department_id if provided; fallback to user's primary department.
        $deptId = $request->filled('department_id')
            ? (int) $request->department_id
            : $this->getUserDepartmentId();
        if (!$deptId) {
            return response()->json(['status' => 'error', 'message' => 'Akun Anda tidak terhubung dengan departemen manapun.'], 403);
        }

        $year  = (int) $request->get('year', date('Y'));
        // Match web `/unit/capex` behavior:
        // - find capex by department + fiscal_year (no status=active filter)
        // - do not fallback to a different year automatically
        $capex = Capex::where('department_id', $deptId)
            ->where('fiscal_year', $year)
            ->first();

        if (!$capex) {
            return response()->json([
                'status' => 'success',
                'data' => [],
                'meta' => ['year' => $year],
            ]);
        }

        // Match web `/unit/capex` items list behavior:
        // - do not apply scopeAvailable(); return all items for that capex
        $query = $capex->items()
            ->select(
                'id',
                'capex_id_number',
                'item_name',
                'category',
                'capex_type',
                'priority_scale',
                'budget_amount',
                'used_amount',
                'pending_amount',
                'month',
                'pic'
            );

        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(fn($q) => $q->where('item_name', 'like', $s)->orWhere('capex_id_number', 'like', $s));
        }

        $items = $query->orderBy('priority_scale')->get()->map(fn($i) => [
            'id'               => $i->id,
            'capex_id_number'  => $i->capex_id_number,
            'item_name'        => $i->item_name,
            'category'         => $i->category,
            'capex_type'       => $i->capex_type,
            'priority_scale'   => $i->priority_scale,
            'month'            => $i->month,
            'pic'              => $i->pic,
            'budget_amount'    => (float) $i->budget_amount,
            'used_amount'      => (float) $i->used_amount,
            'pending_amount'   => (float) $i->pending_amount,
            'available_amount' => $i->available_amount,
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => $items,
            'meta'   => ['capex_id' => $capex->id, 'year' => $year, 'total' => $items->count()],
        ]);
    }

    /**
     * GET /api/capex/departments
     * Department list for filter dropdowns.
     */
    public function departments()
    {
        $this->requireCapexReadAccess();
        $deptId = $this->getUserDepartmentId();
        if (!$deptId) {
            return response()->json(['status' => 'error', 'message' => 'Akun Anda tidak terhubung dengan departemen manapun.'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data'   => Department::where('id', $deptId)->get(['id', 'name', 'code']),
        ]);
    }

    /**
     * GET /api/capex/budget-summary?department_id=X&year=2026
     * Summary for dashboard widgets / validations.
     */
    public function budgetSummary(Request $request)
    {
        $this->requireCapexReadAccess();
        $deptId = $this->getUserDepartmentId();
        if (!$deptId) {
            return response()->json(['status' => 'error', 'message' => 'Akun Anda tidak terhubung dengan departemen manapun.'], 403);
        }

        $v = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'year'          => 'nullable|integer|min:2020|max:2100',
        ]);
        if (isset($v['department_id']) && (int) $v['department_id'] !== (int) $deptId) {
            return response()->json(['status' => 'error', 'message' => 'Akses department tidak diizinkan.'], 403);
        }

        $year = (int) ($v['year'] ?? date('Y'));
        $capex = Capex::where('department_id', $deptId)
            ->where('fiscal_year', $year)
            ->first();

        if (!$capex) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'department_id'     => (int) $deptId,
                    'fiscal_year'       => $year,
                    'capex_id'          => null,
                    'status'            => null,
                    'total_budget'      => 0,
                    'total_used'        => 0,
                    'remaining_budget'  => 0,
                    'utilization_pct'   => 0,
                ],
            ]);
        }

        $this->authorizeCapex($capex);

        return response()->json([
            'status' => 'success',
            'data' => [
                'department_id'     => $capex->department_id,
                'fiscal_year'       => (int) $capex->fiscal_year,
                'capex_id'          => $capex->id,
                'status'            => $capex->status,
                'total_budget'      => (float) $capex->total_budget,
                'total_used'        => (float) $capex->total_used,
                'remaining_budget'  => (float) $capex->remaining_budget,
                'utilization_pct'   => (float) $capex->utilization_percent,
            ],
        ]);
    }

    // ----------------------------------------------------------------
    // SHARED FORMATTERS
    // ----------------------------------------------------------------

    public function summary(Capex $capex): array
    {
        return [
            'id'               => $capex->id,
            'department'       => $capex->department,
            'fiscal_year'      => $capex->fiscal_year,
            'status'           => $capex->status,
            'notes'            => $capex->notes,
            'total_budget'     => $capex->total_budget,
            'total_used'       => $capex->total_used,
            'remaining_budget' => $capex->remaining_budget,
            'utilization_pct'  => $capex->utilization_percent,
            'items_count'      => $capex->items_count,
            'created_at'       => $capex->created_at,
        ];
    }

    public function formatItem(CapexItem $item): array
    {
        return [
            'id'               => $item->id,
            'capex_id'         => $item->capex_id,
            'capex_id_number'  => $item->capex_id_number,
            'item_name'        => $item->item_name,
            'description'      => $item->description,
            'category'         => $item->category,
            'capex_type'       => $item->capex_type,
            'priority_scale'   => $item->priority_scale,
            'month'            => $item->month,
            'pic'              => $item->pic,
            'budget_amount'    => (float) $item->budget_amount,
            'used_amount'      => (float) $item->used_amount,
            'pending_amount'   => (float) $item->pending_amount,
            'available_amount' => $item->available_amount,
            'amount_per_year'  => $item->amount_per_year ? (float) $item->amount_per_year : null,
            'status'           => $item->status,
            'created_at'       => $item->created_at,
            'updated_at'       => $item->updated_at,
        ];
    }
}
