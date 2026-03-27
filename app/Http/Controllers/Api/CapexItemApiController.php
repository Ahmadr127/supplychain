<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Capex;
use App\Models\CapexItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CapEx Item API — CRUD for individual CapEx items.
 *
 * Permissions:
 *   manage_capex      → Admin: any item.
 *   manage_capex_unit → Unit user: own department only.
 *
 * Routes:
 *   POST   /api/capex/{capexId}/items  – Add item to a CapEx
 *   GET    /api/capex-items/{item}     – Single item detail
 *   PATCH  /api/capex-items/{item}     – Update item
 *   DELETE /api/capex-items/{item}     – Delete item (if unused)
 */
class CapexItemApiController extends Controller
{
    private function requireCapexAccess(): void
    {
        // No permission gate (as requested).
        // Access is still scoped by authenticated user's department via authorizeCapex/authorizeItem.
        return;
    }

    private function getUserDepartmentId(): ?int
    {
        return Auth::user()->primaryDepartment()->first()?->id;
    }

    private function authorizeItem(CapexItem $item): void
    {
        $item->loadMissing('capex');
        $deptId = $this->getUserDepartmentId();
        if (!$deptId || $item->capex->department_id !== $deptId) {
            abort(403, 'Anda tidak dapat mengelola item dari unit lain.');
        }
    }

    private function authorizeCapex(Capex $capex): void
    {
        $deptId = $this->getUserDepartmentId();
        if (!$deptId || $capex->department_id !== $deptId) {
            abort(403, 'Anda tidak dapat mengelola CapEx dari unit lain.');
        }
    }

    // ----------------------------------------------------------------
    // CREATE (via parent Capex)
    // ----------------------------------------------------------------

    /**
     * POST /api/capex/{capex}/items
     * Add a new item to an existing CapEx. Both admin and unit users allowed.
     *
     * Body:
     *   item_name*       | description | category | capex_type (New|Replacement)
     *   priority_scale (1|2|3) | month | budget_amount* (e.g. "1.500.000") | amount_per_year | pic
     */
    public function store(Request $request, Capex $capex)
    {
        $this->requireCapexAccess();
        $this->authorizeCapex($capex);

        if ($capex->status === 'closed') {
            return response()->json(['status' => 'error', 'message' => 'CapEx sudah ditutup, tidak bisa menambahkan item.'], 422);
        }

        $v = $request->validate([
            'item_name'       => 'required|string|max:255',
            'description'     => 'nullable|string|max:500',
            'category'        => 'nullable|string|max:100',
            'capex_type'      => 'nullable|in:New,Replacement',
            'priority_scale'  => 'nullable|integer|in:1,2,3',
            'month'           => 'nullable|string|max:20',
            'budget_amount'   => 'required|string|min:1',
            'amount_per_year' => 'nullable|string',
            'pic'             => 'nullable|string|max:100',
        ]);

        $deptCode = $capex->department->code ?? 'XX';

        $item = CapexItem::create([
            'capex_id'        => $capex->id,
            'capex_id_number' => CapexItem::generateIdNumber($deptCode, $capex->fiscal_year),
            'item_name'       => $v['item_name'],
            'description'     => $v['description'] ?? null,
            'category'        => $v['category'] ?? null,
            'capex_type'      => $v['capex_type'] ?? null,
            'priority_scale'  => $v['priority_scale'] ?? null,
            'month'           => $v['month'] ?? null,
            'budget_amount'   => (float) str_replace('.', '', $v['budget_amount']),
            'amount_per_year' => isset($v['amount_per_year'])
                ? (float) str_replace('.', '', $v['amount_per_year'])
                : null,
            'pic'             => $v['pic'] ?? null,
            'used_amount'     => 0,
            'pending_amount'  => 0,
            'status'          => 'available',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => "Item {$item->capex_id_number} berhasil ditambahkan.",
            'data'    => $this->formatItem($item),
        ], 201);
    }

    // ----------------------------------------------------------------
    // READ
    // ----------------------------------------------------------------

    /**
     * GET /api/capex-items/{item}
     * Detail of a single CapEx item, including active allocations.
     */
    public function show(CapexItem $item)
    {
        $this->requireCapexAccess();
        $this->authorizeItem($item);

        $item->load(['capex.department', 'activeAllocations.approvalRequest']);

        return response()->json([
            'status' => 'success',
            'data'   => $this->formatItem($item, detailed: true),
        ]);
    }

    // ----------------------------------------------------------------
    // UPDATE
    // ----------------------------------------------------------------

    /**
     * PATCH /api/capex-items/{item}
     * Update item fields. budget_amount cannot drop below used_amount.
     *
     * Body: item_name* | description | category | capex_type | priority_scale | month | budget_amount* | amount_per_year | pic
     */
    public function update(Request $request, CapexItem $item)
    {
        $this->requireCapexAccess();
        $this->authorizeItem($item);

        $v = $request->validate([
            'item_name'       => 'required|string|max:255',
            'description'     => 'nullable|string|max:500',
            'category'        => 'nullable|string|max:100',
            'capex_type'      => 'nullable|in:New,Replacement',
            'priority_scale'  => 'nullable|integer|in:1,2,3',
            'month'           => 'nullable|string|max:20',
            'budget_amount'   => 'required|string|min:1',
            'amount_per_year' => 'nullable|string',
            'pic'             => 'nullable|string|max:100',
        ]);

        $newBudget = (float) str_replace('.', '', $v['budget_amount']);

        if ($newBudget < (float) $item->used_amount) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Budget baru tidak boleh kurang dari yang sudah terpakai (Rp ' . number_format($item->used_amount, 0, ',', '.') . ').',
            ], 422);
        }

        $item->update([
            'item_name'       => $v['item_name'],
            'description'     => $v['description'] ?? null,
            'category'        => $v['category'] ?? null,
            'capex_type'      => $v['capex_type'] ?? null,
            'priority_scale'  => $v['priority_scale'] ?? null,
            'month'           => $v['month'] ?? null,
            'budget_amount'   => $newBudget,
            'amount_per_year' => isset($v['amount_per_year'])
                ? (float) str_replace('.', '', $v['amount_per_year'])
                : null,
            'pic'             => $v['pic'] ?? null,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Item CapEx berhasil diupdate.',
            'data'    => $this->formatItem($item->fresh()),
        ]);
    }

    // ----------------------------------------------------------------
    // DELETE
    // ----------------------------------------------------------------

    /**
     * DELETE /api/capex-items/{item}
     * Cannot delete if used_amount > 0.
     */
    public function destroy(CapexItem $item)
    {
        $this->requireCapexAccess();
        $this->authorizeItem($item);

        if ((float) $item->used_amount > 0) {
            return response()->json(['status' => 'error', 'message' => 'Item tidak dapat dihapus karena sudah terpakai.'], 422);
        }

        $item->delete();

        return response()->json(['status' => 'success', 'message' => 'Item CapEx berhasil dihapus.']);
    }

    // ----------------------------------------------------------------
    // FORMATTER
    // ----------------------------------------------------------------

    private function formatItem(CapexItem $item, bool $detailed = false): array
    {
        $data = [
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

        if ($detailed && $item->relationLoaded('activeAllocations')) {
            $data['active_allocations'] = $item->activeAllocations->map(fn($a) => [
                'id'                  => $a->id,
                'approval_request_id' => $a->approval_request_id,
                'amount'              => (float) $a->amount,
                'status'              => $a->status,
            ]);
        }

        return $data;
    }
}
