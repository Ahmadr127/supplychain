<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemCategory::query();

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

        $itemCategories = $query->orderBy('name')->paginate(10);
        $itemCategories->appends($request->query());

        return view('item-categories.index', compact('itemCategories'));
    }

    public function create()
    {
        return view('item-categories.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:item_categories',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        ItemCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('item-categories.index')->with('success', 'Kategori barang berhasil dibuat!');
    }

    public function edit(ItemCategory $itemCategory)
    {
        return view('item-categories.edit', compact('itemCategory'));
    }

    public function update(Request $request, ItemCategory $itemCategory)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:item_categories,name,' . $itemCategory->id,
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $itemCategory->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('item-categories.index')->with('success', 'Kategori barang berhasil diperbarui!');
    }

    public function destroy(ItemCategory $itemCategory)
    {
        // Check if category is used by any items
        if ($itemCategory->masterItems()->count() > 0) {
            return redirect()->back()->with('error', 'Kategori tidak dapat dihapus karena masih digunakan oleh barang!');
        }

        $itemCategory->delete();
        return redirect()->route('item-categories.index')->with('success', 'Kategori barang berhasil dihapus!');
    }
}