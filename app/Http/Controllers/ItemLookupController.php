<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MasterItem;
use App\Services\ItemResolver;

class ItemLookupController extends Controller
{
    public function __construct(private ItemResolver $resolver)
    {
        $this->middleware('auth');
    }

    // GET /api/items/suggest?search=...&item_type_id=...
    public function suggest(Request $request)
    {
        try {
            $search = (string) $request->query('search', '');
            if (trim($search) === '') {
                return response()->json(['success' => true, 'items' => []]);
            }
            $itemTypeId = $request->query('item_type_id');
            $limit = max(1, min((int) $request->query('limit', 10), 20));

            $q = MasterItem::active()
                ->with(['unit','itemCategory'])
                ->when($itemTypeId, fn($query) => $query->where('item_type_id', $itemTypeId))
                ->where(function($query) use ($search) {
                    $query->where('name', 'like', "%$search%")
                          ->orWhere('code', 'like', "%$search%");
                })
                ->orderBy('name')
                ->limit($limit)
                ->get(['id','name','code','hna','ppn_amount','unit_id','item_category_id']);

            return response()->json([
                'success' => true,
                'items' => $q->map(fn($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'code' => $i->code,
                    'total_price' => (float) (($i->hna ?? 0) + ($i->ppn_amount ?? 0)),
                    'unit' => $i->unit ? ['id' => $i->unit->id, 'name' => $i->unit->name] : null,
                    'category' => $i->itemCategory ? ['id' => $i->itemCategory->id, 'name' => $i->itemCategory->name] : null,
                ])
            ]);
        } catch (\Throwable $e) {
            \Log::error('Item suggest failed', [
                'error' => $e->getMessage(),
                'trace' => app()->hasDebugModeEnabled() ? $e->getTraceAsString() : null,
            ]);
            $payload = ['success' => false, 'message' => 'Failed to fetch suggestions'];
            if (config('app.debug')) {
                $payload['exception'] = $e->getMessage();
            }
            return response()->json($payload, 500);
        }
    }

    // POST /api/items/resolve { id?| name, item_type_id?, item_category_id?, item_category_name? }
    public function resolve(Request $request)
    {
        $data = $request->validate([
            'id' => 'nullable|integer|exists:master_items,id',
            'name' => 'nullable|string|max:255',
            'item_type_id' => 'nullable|integer|exists:item_types,id',
            'item_category_id' => 'nullable|integer|exists:item_categories,id',
            'item_category_name' => 'nullable|string|max:255',
        ]);

        // If creating new (no id), category must be provided explicitly
        if (empty($data['id']) && empty($data['item_category_id']) && empty($data['item_category_name'])) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori wajib diisi untuk item baru.'
            ], 422);
        }

        $item = $this->resolver->resolveOrCreate($data);

        $price = (float) (($item->total_price ?? (($item->hna ?? 0) + ($item->ppn_amount ?? 0))));

        return response()->json([
            'success' => true,
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
                'total_price' => $price,
                'unit' => $item->unit ? ['id' => $item->unit->id, 'name' => $item->unit->name] : null,
            ]
        ], $request->id ? 200 : 201);
    }
}
