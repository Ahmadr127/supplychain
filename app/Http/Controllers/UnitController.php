<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $query = Unit::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $units = $query->orderBy('name')->paginate(10);
        $units->appends($request->query());

        return view('units.index', compact('units'));
    }

    public function create()
    {
        return view('units.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:units',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Unit::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('units.index')->with('success', 'Satuan barang berhasil dibuat!');
    }

    public function edit(Unit $unit)
    {
        return view('units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:units,name,' . $unit->id,
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $unit->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('units.index')->with('success', 'Satuan barang berhasil diperbarui!');
    }

    public function destroy(Unit $unit)
    {
        // Check if unit is used by any items
        if ($unit->masterItems()->count() > 0) {
            return redirect()->back()->with('error', 'Satuan tidak dapat dihapus karena masih digunakan oleh barang!');
        }

        $unit->delete();
        return redirect()->route('units.index')->with('success', 'Satuan barang berhasil dihapus!');
    }
}