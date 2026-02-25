<?php

namespace App\Http\Controllers;

use App\Models\Capex;
use App\Models\CapexItem;
use Illuminate\Http\Request;

/**
 * Unit-level CapEx CRUD — accessible by users with `manage_capex_unit` permission.
 * Users can only manage CapEx items for their own department.
 */
class CapexUnitController extends Controller
{
    private function getDepartmentId(): int
    {
        $user = auth()->user();
        // Department disimpan di pivot table user_departments (bukan kolom department_id)
        $dept = $user->primaryDepartment()->first();
        if (!$dept) {
            abort(403, 'Akun Anda tidak terhubung dengan unit/departemen manapun.');
        }
        return $dept->id;
    }

    private function getOrCreateCapex(int $year = null): Capex
    {
        $year = $year ?? date('Y');
        $deptId = $this->getDepartmentId();
        return Capex::firstOrCreate(
            ['department_id' => $deptId, 'fiscal_year' => $year],
            ['status' => 'active', 'created_by' => auth()->id()]
        );
    }

    /**
     * GET /unit/capex — list capex items for the logged-in user's department
     */
    public function index(Request $request)
    {
        $this->checkPermission();

        $deptId = $this->getDepartmentId();
        $year   = (int) $request->get('year', date('Y'));

        $capex = Capex::where('department_id', $deptId)
            ->where('fiscal_year', $year)
            ->with('department')
            ->first();

        $itemsQuery = $capex ? $capex->items()->with([
        'activeAllocations.approvalRequest',
        'activeAllocations.approvalRequestItem.masterItem',
    ]) : null;

        if ($itemsQuery && $request->filled('search')) {
            $search = '%' . $request->search . '%';
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('capex_id_number', 'like', $search)
                  ->orWhere('item_name', 'like', $search)
                  ->orWhere('pic', 'like', $search)
                  ->orWhere('category', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        $items = $itemsQuery
            ? $itemsQuery->orderBy('priority_scale')->orderBy('id')->paginate(25)->withQueryString()
            : collect();

        $availableYears = Capex::where('department_id', $deptId)
            ->pluck('fiscal_year')->sort()->reverse();

        return view('capex-unit.index', compact('capex', 'items', 'year', 'availableYears'));
    }

    /**
     * GET /unit/capex/create
     */
    public function create(Request $request)
    {
        $this->checkPermission();
        $year  = (int) $request->get('year', date('Y'));
        $capex = $this->getOrCreateCapex($year);
        $capex->load('department');
        return view('capex-unit.create', compact('capex', 'year'));
    }

    /**
     * POST /unit/capex
     */
    public function store(Request $request)
    {
        $this->checkPermission();

        $validated = $request->validate([
            'fiscal_year'    => 'required|integer|min:2020|max:2100',
            'item_name'      => 'required|string|max:255',
            'description'    => 'nullable|string|max:500',
            'capex_type'     => 'nullable|in:New,Replacement',
            'priority_scale' => 'nullable|integer|in:1,2,3',
            'month'          => 'nullable|string|max:20',
            'budget_amount'  => 'required|string',
            'amount_per_year'=> 'nullable|string',
            'category'       => 'nullable|string|max:100',
            'pic'            => 'nullable|string|max:100',
        ]);

        $capex = $this->getOrCreateCapex($validated['fiscal_year']);
        $deptCode = $capex->department->code ?? 'XX';

        $item = CapexItem::create([
            'capex_id'        => $capex->id,
            'capex_id_number' => CapexItem::generateIdNumber($deptCode, $capex->fiscal_year),
            'item_name'       => $validated['item_name'],
            'description'     => $validated['description'] ?? null,
            'category'        => $validated['category'] ?? null,
            'capex_type'      => $validated['capex_type'] ?? null,
            'priority_scale'  => $validated['priority_scale'] ?? null,
            'month'           => $validated['month'] ?? null,
            'budget_amount'   => (float) str_replace('.', '', $validated['budget_amount']),
            'amount_per_year' => isset($validated['amount_per_year'])
                ? (float) str_replace('.', '', $validated['amount_per_year'])
                : null,
            'pic'             => $validated['pic'] ?? null,
            'used_amount'     => 0,
            'status'          => 'available',
        ]);

        return redirect()->route('unit.capex.index', ['year' => $capex->fiscal_year])
            ->with('success', "Item CapEx {$item->capex_id_number} berhasil ditambahkan.");
    }

    /**
     * GET /unit/capex/{item}/edit
     */
    public function edit(CapexItem $item)
    {
        $this->checkPermission();
        $this->authorizeItem($item);
        $item->load('capex.department');
        return view('capex-unit.edit', compact('item'));
    }

    /**
     * PATCH /unit/capex/{item}
     */
    public function update(Request $request, CapexItem $item)
    {
        $this->checkPermission();
        $this->authorizeItem($item);

        $validated = $request->validate([
            'item_name'      => 'required|string|max:255',
            'description'    => 'nullable|string|max:500',
            'capex_type'     => 'nullable|in:New,Replacement',
            'priority_scale' => 'nullable|integer|in:1,2,3',
            'month'          => 'nullable|string|max:20',
            'budget_amount'  => 'required|string',
            'amount_per_year'=> 'nullable|string',
            'category'       => 'nullable|string|max:100',
            'pic'            => 'nullable|string|max:100',
        ]);

        $newBudget = (float) str_replace('.', '', $validated['budget_amount']);
        if ($newBudget < $item->used_amount) {
            return back()->withErrors([
                'budget_amount' => 'Budget baru tidak boleh kurang dari yang sudah terpakai (Rp ' . number_format($item->used_amount, 0, ',', '.') . ')'
            ])->withInput();
        }

        $item->update([
            'item_name'       => $validated['item_name'],
            'description'     => $validated['description'] ?? null,
            'category'        => $validated['category'] ?? null,
            'capex_type'      => $validated['capex_type'] ?? null,
            'priority_scale'  => $validated['priority_scale'] ?? null,
            'month'           => $validated['month'] ?? null,
            'budget_amount'   => $newBudget,
            'amount_per_year' => isset($validated['amount_per_year'])
                ? (float) str_replace('.', '', $validated['amount_per_year'])
                : null,
            'pic'             => $validated['pic'] ?? null,
        ]);

        return redirect()->route('unit.capex.index', ['year' => $item->capex->fiscal_year])
            ->with('success', 'Item CapEx berhasil diupdate.');
    }

    /**
     * DELETE /unit/capex/{item}
     */
    public function destroy(CapexItem $item)
    {
        $this->checkPermission();
        $this->authorizeItem($item);

        if ($item->used_amount > 0) {
            return back()->withErrors(['error' => 'Item tidak dapat dihapus karena sudah terpakai.']);
        }

        $year = $item->capex->fiscal_year;
        $item->delete();

        return redirect()->route('unit.capex.index', ['year' => $year])
            ->with('success', 'Item CapEx berhasil dihapus.');
    }

    private function checkPermission(): void
    {
        if (!auth()->user()->hasPermission('manage_capex_unit') && !auth()->user()->hasPermission('manage_capex')) {
            abort(403, 'Anda tidak memiliki akses ke fitur ini.');
        }
    }

    private function authorizeItem(CapexItem $item): void
    {
        $item->load('capex');
        $deptId = $this->getDepartmentId();
        // Admin can manage any item
        if (auth()->user()->hasPermission('manage_capex')) return;
        // Unit users can only manage their own dept
        if ($item->capex->department_id !== $deptId) {
            abort(403, 'Anda tidak dapat mengelola item dari unit lain.');
        }
    }
}
