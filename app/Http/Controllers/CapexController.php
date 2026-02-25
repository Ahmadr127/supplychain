<?php

namespace App\Http\Controllers;

use App\Models\Capex;
use App\Models\CapexItem;
use App\Models\Department;
use Illuminate\Http\Request;

class CapexController extends Controller
{
    /**
     * Display a listing of capex (per department)
     */
    public function index(Request $request)
    {
        $query = Capex::with(['department', 'items'])
            ->join('departments', 'capexes.department_id', '=', 'departments.id')
            ->select('capexes.*');

        // Filter by year
        if ($request->filled('year')) {
            $query->where('fiscal_year', $request->year);
        } else {
            // Default to current year
            $query->where('fiscal_year', date('Y'));
        }

        // Filter by department
        if ($request->filled('department_id')) {
            $query->where('capexes.department_id', $request->department_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('capexes.status', $request->status);
        }

        // Search by department name or code
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('departments.name', 'like', $search)
                  ->orWhere('departments.code', 'like', $search);
            });
        }

        $capexes = $query->orderBy('departments.name')
            ->paginate(20)
            ->withQueryString();

        $departments = Department::where('code', '!=', 'DIR')->orderBy('name')->get();
        $years = Capex::distinct()->pluck('fiscal_year')->sort()->reverse();

        return view('capex.index', compact('capexes', 'departments', 'years'));
    }

    /**
     * Show the form for creating a new capex
     */
    public function create()
    {
        $departments = Department::where('code', '!=', 'DIR')->orderBy('name')->get();
        return view('capex.create', compact('departments'));
    }

    /**
     * Store a newly created capex
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'fiscal_year' => 'required|integer|min:2020|max:2100',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if capex already exists for this department and year
        $exists = Capex::where('department_id', $validated['department_id'])
            ->where('fiscal_year', $validated['fiscal_year'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'department_id' => 'CapEx untuk department dan tahun ini sudah ada.'
            ])->withInput();
        }

        $validated['status'] = 'active';
        $validated['created_by'] = auth()->id();

        $capex = Capex::create($validated);

        return redirect()->route('capex.show', $capex)
            ->with('success', 'CapEx berhasil dibuat. Silahkan tambahkan item.');
    }

    /**
     * Display the specified capex with its items
     */
    public function show(Request $request, Capex $capex)
    {
        $capex->load(['department', 'creator']);

        $itemsQuery = $capex->items()->with([
            'activeAllocations.approvalRequest',
            'activeAllocations.approvalRequestItem.masterItem',
            'activeAllocations.allocator',
        ]);

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $itemsQuery->where(function ($q) use ($search) {
                $q->where('capex_id_number', 'like', $search)
                  ->orWhere('item_name', 'like', $search)
                  ->orWhere('pic', 'like', $search)
                  ->orWhere('category', 'like', $search)
                  ->orWhere('description', 'like', $search);
            });
        }

        $items = $itemsQuery->orderBy('priority_scale')->orderBy('id')->paginate(25)->withQueryString();

        return view('capex.show', compact('capex', 'items'));
    }

    /**
     * Show the form for editing capex
     */
    public function edit(Capex $capex)
    {
        $capex->load(['department', 'items']);
        $departments = Department::where('code', '!=', 'DIR')->orderBy('name')->get();
        return view('capex.edit', compact('capex', 'departments'));
    }

    /**
     * Update the specified capex
     */
    public function update(Request $request, Capex $capex)
    {
        $validated = $request->validate([
            'fiscal_year' => 'required|integer|min:2020|max:2100',
            'notes' => 'nullable|string|max:500',
            'status' => 'required|in:draft,active,closed',
        ]);

        // Check uniqueness if year changed
        if ($request->fiscal_year != $capex->fiscal_year) {
            // Check if items exist (prevent year change if items exist to maintain ID consistency)
            if ($capex->items()->exists()) {
                return back()->withErrors([
                    'fiscal_year' => 'Tahun anggaran tidak dapat diubah karena sudah ada item yang terdaftar. Hapus semua item terlebih dahulu jika ingin mengubah tahun.'
                ])->withInput();
            }

            // Check if target year already exists for this department
            $exists = Capex::where('department_id', $capex->department_id)
                ->where('fiscal_year', $request->fiscal_year)
                ->where('id', '!=', $capex->id)
                ->exists();

            if ($exists) {
                return back()->withErrors([
                    'fiscal_year' => 'CapEx untuk departemen ini di tahun ' . $request->fiscal_year . ' sudah ada.'
                ])->withInput();
            }
        }

        $capex->update($validated);

        return redirect()->route('capex.show', $capex)
            ->with('success', 'CapEx berhasil diupdate.');
    }

    /**
     * Remove the specified capex
     */
    public function destroy(Capex $capex)
    {
        // Check if any item has been used
        $hasUsedItems = $capex->items()->where('used_amount', '>', 0)->exists();

        if ($hasUsedItems) {
            return back()->withErrors([
                'error' => 'CapEx tidak dapat dihapus karena ada item yang sudah terpakai.'
            ]);
        }

        $capex->items()->delete();
        $capex->delete();

        return redirect()->route('capex.index')
            ->with('success', 'CapEx berhasil dihapus.');
    }

    /**
     * Store a new capex item
     */
    public function storeItem(Request $request, Capex $capex)
    {
        $validated = $request->validate([
            'item_name'      => 'required|string|max:255',
            'description'    => 'nullable|string|max:500',
            'category'       => 'nullable|string|max:100',
            'capex_type'     => 'nullable|in:New,Replacement',
            'priority_scale' => 'nullable|integer|in:1,2,3',
            'month'          => 'nullable|string|max:20',
            'budget_amount'  => 'required|string',
            'amount_per_year'=> 'nullable|string',
            'pic'            => 'nullable|string|max:100',
        ]);

        // Parse amounts
        $validated['budget_amount'] = (float) str_replace('.', '', $validated['budget_amount']);
        if (!empty($validated['amount_per_year'])) {
            $validated['amount_per_year'] = (float) str_replace('.', '', $validated['amount_per_year']);
        }

        // Generate CapEx ID Number in new format: {seq}/CapEx/{year}/{dept_code}
        $deptCode = $capex->department->code;
        $validated['capex_id_number'] = CapexItem::generateIdNumber($deptCode, $capex->fiscal_year);
        $validated['capex_id'] = $capex->id;
        $validated['status'] = 'available';
        $validated['used_amount'] = 0;

        CapexItem::create($validated);

        return redirect()->route('capex.show', $capex)
            ->with('success', 'Item CapEx berhasil ditambahkan.');
    }

    /**
     * Update a capex item
     */
    public function updateItem(Request $request, CapexItem $item)
    {
        $validated = $request->validate([
            'item_name'      => 'required|string|max:255',
            'description'    => 'nullable|string|max:500',
            'category'       => 'nullable|string|max:100',
            'capex_type'     => 'nullable|in:New,Replacement',
            'priority_scale' => 'nullable|integer|in:1,2,3',
            'month'          => 'nullable|string|max:20',
            'budget_amount'  => 'required|string',
            'amount_per_year'=> 'nullable|string',
            'pic'            => 'nullable|string|max:100',
        ]);

        $newBudget = (float) str_replace('.', '', $validated['budget_amount']);

        // Ensure new budget >= used amount
        if ($newBudget < $item->used_amount) {
            return back()->withErrors([
                'budget_amount' => 'Budget baru tidak boleh kurang dari jumlah yang sudah terpakai (Rp ' . number_format($item->used_amount, 0, ',', '.') . ')'
            ])->withInput();
        }

        $validated['budget_amount'] = $newBudget;
        if (!empty($validated['amount_per_year'])) {
            $validated['amount_per_year'] = (float) str_replace('.', '', $validated['amount_per_year']);
        }

        $item->update($validated);

        return redirect()->route('capex.show', $item->capex)
            ->with('success', 'Item CapEx berhasil diupdate.');
    }

    /**
     * Remove a capex item
     */
    public function destroyItem(CapexItem $item)
    {
        if ($item->used_amount > 0) {
            return back()->withErrors([
                'error' => 'Item tidak dapat dihapus karena sudah terpakai.'
            ]);
        }

        $capex = $item->capex;
        $item->delete();

        return redirect()->route('capex.show', $capex)
            ->with('success', 'Item CapEx berhasil dihapus.');
    }

    /**
     * API: Get available capex items for a department
     */
    public function getAvailableItems(Request $request)
    {
        $departmentId = $request->input('department_id');
        $year = $request->input('year', date('Y'));

        $capex = Capex::where('department_id', $departmentId)
            ->where('fiscal_year', $year)
            ->where('status', 'active')
            ->first();

        if (!$capex) {
            return response()->json(['items' => []]);
        }

        $items = $capex->items()
            ->available()
            ->select('id', 'capex_id_number', 'item_name', 'category', 'budget_amount', 'used_amount', 'pending_amount')
            ->get()
            ->map(function ($item) {
                $available = max(0, (float) $item->budget_amount - (float) $item->used_amount - (float) $item->pending_amount);
                return [
                    'id'               => $item->id,
                    'capex_id_number'  => $item->capex_id_number,
                    'item_name'        => $item->item_name,
                    'category'         => $item->category,
                    'budget_amount'    => $item->budget_amount,
                    'used_amount'      => $item->used_amount,
                    'pending_amount'   => $item->pending_amount,
                    'available_amount' => $available,
                    'remaining_amount' => $available, // compat alias
                ];
            });

        return response()->json(['items' => $items]);
    }
}
