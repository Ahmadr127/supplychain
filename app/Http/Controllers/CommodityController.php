<?php

namespace App\Http\Controllers;

use App\Models\Commodity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommodityController extends Controller
{
    public function index(Request $request)
    {
        $query = Commodity::query();

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

        $commodities = $query->orderBy('name')->paginate(10);
        $commodities->appends($request->query());

        return view('commodities.index', compact('commodities'));
    }

    public function create()
    {
        return view('commodities.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:commodities',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        Commodity::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('commodities.index')->with('success', 'Komoditas berhasil dibuat!');
    }

    public function edit(Commodity $commodity)
    {
        return view('commodities.edit', compact('commodity'));
    }

    public function update(Request $request, Commodity $commodity)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:commodities,name,' . $commodity->id,
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $commodity->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('commodities.index')->with('success', 'Komoditas berhasil diperbarui!');
    }

    public function destroy(Commodity $commodity)
    {
        // Check if commodity is used by any items
        if ($commodity->masterItems()->count() > 0) {
            return redirect()->back()->with('error', 'Komoditas tidak dapat dihapus karena masih digunakan oleh barang!');
        }

        $commodity->delete();
        return redirect()->route('commodities.index')->with('success', 'Komoditas berhasil dihapus!');
    }
}