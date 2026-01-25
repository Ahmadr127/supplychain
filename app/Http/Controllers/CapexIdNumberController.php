<?php

namespace App\Http\Controllers;

use App\Models\CapexIdNumber;
use Illuminate\Http\Request;

class CapexIdNumberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CapexIdNumber::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by year
        if ($request->filled('year')) {
            $query->where('fiscal_year', $request->year);
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        $capexIds = $query->orderBy('fiscal_year', 'desc')
            ->orderBy('code')
            ->paginate(20);

        return view('capex.index', compact('capexIds'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('capex.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:capex_id_numbers,code',
            'description' => 'required|string|max:500',
            'fiscal_year' => 'required|integer|min:2020|max:2100',
            'budget_amount' => 'required|string',
            'is_active' => 'boolean',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        // Parse budget amount (remove dots from Indonesian format)
        $validated['budget_amount'] = (float) str_replace('.', '', $validated['budget_amount']);
        $validated['is_active'] = $request->boolean('is_active', true);

        CapexIdNumber::create($validated);

        return redirect()->route('capex.index')
            ->with('success', 'CapEx ID berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CapexIdNumber $capex)
    {
        return redirect()->route('capex.edit', $capex);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CapexIdNumber $capex)
    {
        $capex->load('allocations.approvalRequest');
        return view('capex.edit', compact('capex'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CapexIdNumber $capex)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:capex_id_numbers,code,' . $capex->id,
            'description' => 'required|string|max:500',
            'fiscal_year' => 'required|integer|min:2020|max:2100',
            'budget_amount' => 'required|string',
            'is_active' => 'boolean',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        // Parse budget amount
        $newBudget = (float) str_replace('.', '', $validated['budget_amount']);
        
        // Ensure new budget >= allocated amount
        $allocatedAmount = $capex->budget_amount - $capex->getRemainingBudget();
        if ($newBudget < $allocatedAmount) {
            return back()->withErrors([
                'budget_amount' => 'Budget baru tidak boleh kurang dari jumlah yang sudah teralokasi (Rp ' . number_format($allocatedAmount, 0, ',', '.') . ')'
            ])->withInput();
        }

        $validated['budget_amount'] = $newBudget;
        $validated['is_active'] = $request->boolean('is_active', true);

        $capex->update($validated);

        return redirect()->route('capex.index')
            ->with('success', 'CapEx ID berhasil diupdate.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CapexIdNumber $capex)
    {
        // Check if CapEx has allocations
        if ($capex->allocations()->count() > 0) {
            return back()->withErrors([
                'error' => 'CapEx ID tidak dapat dihapus karena sudah memiliki alokasi.'
            ]);
        }

        $capex->delete();

        return redirect()->route('capex.index')
            ->with('success', 'CapEx ID berhasil dihapus.');
    }
}
