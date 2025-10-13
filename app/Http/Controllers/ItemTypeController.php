<?php

namespace App\Http\Controllers;

use App\Models\ItemType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemType::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $itemTypes = $query->orderBy('name')->paginate(10);
        $itemTypes->appends($request->query());

        return view('item-types.index', compact('itemTypes'));
    }

    public function create()
    {
        return view('item-types.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:item_types',
            'code' => 'nullable|string|max:10|unique:item_types,code',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        ItemType::create([
            'name' => $request->name,
            'code' => $request->filled('code') ? strtoupper(trim($request->code)) : null,
            'description' => $request->description,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('item-types.index')->with('success', 'Tipe barang berhasil dibuat!');
    }

    public function edit(ItemType $itemType)
    {
        return view('item-types.edit', compact('itemType'));
    }

    public function update(Request $request, ItemType $itemType)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:item_types,name,' . $itemType->id,
            'code' => 'nullable|string|max:10|unique:item_types,code,' . $itemType->id,
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $itemType->update([
            'name' => $request->name,
            'code' => $request->filled('code') ? strtoupper(trim($request->code)) : null,
            'description' => $request->description,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('item-types.index')->with('success', 'Tipe barang berhasil diperbarui!');
    }

    public function destroy(ItemType $itemType)
    {
        // Check if type is used by any items
        if ($itemType->masterItems()->count() > 0) {
            return redirect()->back()->with('error', 'Tipe tidak dapat dihapus karena masih digunakan oleh barang!');
        }

        $itemType->delete();
        return redirect()->route('item-types.index')->with('success', 'Tipe barang berhasil dihapus!');
    }
}