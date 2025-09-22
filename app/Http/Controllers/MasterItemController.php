<?php

namespace App\Http\Controllers;

use App\Models\MasterItem;
use App\Models\ItemType;
use App\Models\ItemCategory;
use App\Models\Commodity;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MasterItemController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterItem::with(['itemType', 'itemCategory', 'commodity', 'unit']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Type filter
        if ($request->filled('item_type_id')) {
            $query->where('item_type_id', $request->item_type_id);
        }

        // Category filter
        if ($request->filled('item_category_id')) {
            $query->where('item_category_id', $request->item_category_id);
        }

        // Commodity filter
        if ($request->filled('commodity_id')) {
            $query->where('commodity_id', $request->commodity_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $masterItems = $query->latest()->paginate(15)->withQueryString();
        
        // Get filter options
        $itemTypes = ItemType::active()->get();
        $itemCategories = ItemCategory::active()->get();
        $commodities = Commodity::active()->get();
        $units = Unit::active()->get();
        
        return view('master-items.index', compact('masterItems', 'itemTypes', 'itemCategories', 'commodities', 'units'));
    }

    public function create()
    {
        $itemTypes = ItemType::active()->get();
        $itemCategories = ItemCategory::active()->get();
        $commodities = Commodity::active()->get();
        $units = Unit::active()->get();
        
        return view('master-items.create', compact('itemTypes', 'itemCategories', 'commodities', 'units'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:master_items',
            'description' => 'nullable|string',
            'hna' => 'required|numeric|min:0',
            'ppn_percentage' => 'required|numeric|min:0|max:100',
            'item_type_id' => 'required|exists:item_types,id',
            'item_category_id' => 'required|exists:item_categories,id',
            'commodity_id' => 'required|exists:commodities,id',
            'unit_id' => 'required|exists:units,id',
            'stock' => 'integer|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $masterItem = MasterItem::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'hna' => $request->hna,
            'ppn_percentage' => $request->ppn_percentage,
            'item_type_id' => $request->item_type_id,
            'item_category_id' => $request->item_category_id,
            'commodity_id' => $request->commodity_id,
            'unit_id' => $request->unit_id,
            'stock' => $request->stock ?? 0,
            'is_active' => $request->has('is_active')
        ]);

        // Load relationships for the response
        $masterItem->load(['itemType', 'itemCategory', 'commodity', 'unit']);

        // Calculate total price
        $ppnAmount = ($masterItem->hna * $masterItem->ppn_percentage) / 100;
        $totalPrice = $masterItem->hna + $ppnAmount;

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Master item berhasil dibuat!',
                'item' => [
                    'id' => $masterItem->id,
                    'name' => $masterItem->name,
                    'code' => $masterItem->code,
                    'description' => $masterItem->description,
                    'hna' => $masterItem->hna,
                    'ppn_percentage' => $masterItem->ppn_percentage,
                    'total_price' => $totalPrice,
                    'item_type_id' => $masterItem->item_type_id,
                    'item_category_id' => $masterItem->item_category_id,
                    'commodity_id' => $masterItem->commodity_id,
                    'unit_id' => $masterItem->unit_id,
                    'stock' => $masterItem->stock,
                    'is_active' => $masterItem->is_active,
                    'item_type' => $masterItem->itemType,
                    'item_category' => $masterItem->itemCategory,
                    'commodity' => $masterItem->commodity,
                    'unit' => $masterItem->unit,
                ]
            ]);
        }

        return redirect()->route('master-items.index')->with('success', 'Master item berhasil dibuat!');
    }

    public function show(MasterItem $masterItem)
    {
        $masterItem->load(['itemType', 'itemCategory', 'commodity', 'unit']);
        
        // Get data for modal form
        $itemTypes = ItemType::active()->get();
        $itemCategories = ItemCategory::active()->get();
        $commodities = Commodity::active()->get();
        $units = Unit::active()->get();
        
        return view('master-items.show', compact('masterItem', 'itemTypes', 'itemCategories', 'commodities', 'units'));
    }

    public function edit(MasterItem $masterItem)
    {
        $itemTypes = ItemType::active()->get();
        $itemCategories = ItemCategory::active()->get();
        $commodities = Commodity::active()->get();
        $units = Unit::active()->get();
        
        return view('master-items.edit', compact('masterItem', 'itemTypes', 'itemCategories', 'commodities', 'units'));
    }

    public function update(Request $request, MasterItem $masterItem)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:master_items,code,' . $masterItem->id,
            'description' => 'nullable|string',
            'hna' => 'required|numeric|min:0',
            'ppn_percentage' => 'required|numeric|min:0|max:100',
            'item_type_id' => 'required|exists:item_types,id',
            'item_category_id' => 'required|exists:item_categories,id',
            'commodity_id' => 'required|exists:commodities,id',
            'unit_id' => 'required|exists:units,id',
            'stock' => 'integer|min:0',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $masterItem->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'hna' => $request->hna,
            'ppn_percentage' => $request->ppn_percentage,
            'item_type_id' => $request->item_type_id,
            'item_category_id' => $request->item_category_id,
            'commodity_id' => $request->commodity_id,
            'unit_id' => $request->unit_id,
            'stock' => $request->stock ?? $masterItem->stock,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('master-items.index')->with('success', 'Master item berhasil diperbarui!');
    }

    public function destroy(MasterItem $masterItem)
    {
        $masterItem->delete();
        return redirect()->route('master-items.index')->with('success', 'Master item berhasil dihapus!');
    }

    // API methods for AJAX requests
    public function getByType($typeId)
    {
        $items = MasterItem::active()
            ->where('item_type_id', $typeId)
            ->with(['itemCategory', 'commodity', 'unit'])
            ->get();
            
        return response()->json($items);
    }

    public function getByCategory($categoryId)
    {
        $items = MasterItem::active()
            ->where('item_category_id', $categoryId)
            ->with(['itemType', 'commodity', 'unit'])
            ->get();
            
        return response()->json($items);
    }

    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
            $items = MasterItem::active()
                ->where(function($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('code', 'like', "%{$query}%");
                })
            ->with(['itemType', 'itemCategory', 'commodity', 'unit'])
            ->limit(10)
            ->get();
            
        return response()->json($items);
    }
}