<?php

namespace App\Services;

use App\Models\MasterItem;
use App\Models\Unit;
use App\Models\ItemCategory;
use App\Models\Commodity;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class ItemResolver
{
    /**
     * Resolve an item by id or name. If not found by name, it will create a new MasterItem with safe defaults.
     *
     * @param array $payload ['id' => ?int, 'name' => ?string, 'item_type_id' => ?int, 'item_category_id' => ?int, 'item_category_name' => ?string]
     */
    public function resolveOrCreate(array $payload): MasterItem
    {
        // By ID
        if (!empty($payload['id'])) {
            return MasterItem::active()->findOrFail($payload['id']);
        }

        $name = trim((string)($payload['name'] ?? ''));
        if ($name === '') {
            abort(422, 'Nama item wajib diisi.');
        }

        // Try find existing by name or code (case-insensitive)
        $existing = MasterItem::query()
            ->where(function ($q) use ($name) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                  ->orWhereRaw('LOWER(code) = ?', [mb_strtolower($name)]);
            })
            ->first();
        if ($existing) {
            return $existing;
        }

        // Create new with safe defaults
        $itemTypeId = $payload['item_type_id'] ?? null;

        // Choose a default unit if available
        $defaultUnit = Unit::query()->first();

        // Resolve category preference: explicit id > explicit name > fallback default
        $categoryId = null;
        if (!empty($payload['item_category_id'])) {
            $categoryId = (int) $payload['item_category_id'];
            // Ensure exists
            if (!ItemCategory::where('id', $categoryId)->exists()) {
                $categoryId = null;
            }
        }
        if (!$categoryId && !empty($payload['item_category_name'])) {
            $catName = trim((string) $payload['item_category_name']);
            if ($catName !== '') {
                $catQuery = ItemCategory::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($catName)]);
                // If schema has item_type_id, prefer same type
                if ($itemTypeId && Schema::hasColumn('item_categories', 'item_type_id')) {
                    $catQuery->where('item_type_id', $itemTypeId);
                }
                $category = $catQuery->first();
                if (!$category) {
                    $category = new ItemCategory();
                    $category->name = $catName;
                    if ($itemTypeId && Schema::hasColumn('item_categories', 'item_type_id')) {
                        $category->item_type_id = $itemTypeId;
                    }
                    $category->description = null;
                    $category->is_active = true;
                    $category->save();
                }
                $categoryId = $category->id;
            }
        }
        // No fallback default: category is mandatory for new items
        if (!$categoryId) {
            abort(422, 'Kategori wajib dipilih/diisi untuk membuat item baru.');
        }

        // Choose a default commodity
        $defaultCommodityId = Commodity::query()->value('id');

        // Guard against missing required master data (DB may enforce NOT NULL FKs)
        if ($categoryId === null || $defaultCommodityId === null) {
            abort(422, 'Konfigurasi master belum lengkap: pastikan minimal 1 Item Category dan 1 Commodity tersedia.');
        }

        $codeBase = Str::upper(Str::slug($name, ''));
        $uniqueCode = $this->uniqueCode($codeBase);

        $item = new MasterItem();
        $item->name = $name;
        $item->code = $uniqueCode;
        $item->description = null;
        $item->hna = 0;
        $item->ppn_percentage = 0;
        $item->item_type_id = $itemTypeId;
        $item->item_category_id = $categoryId; // DB may require NOT NULL
        $item->commodity_id = $defaultCommodityId;    // DB may require NOT NULL
        $item->unit_id = $defaultUnit?->id; // may be null if no units yet
        $item->stock = 0;
        $item->is_active = true;
        $item->save();

        return $item;
    }

    private function uniqueCode(string $base): string
    {
        $base = $base !== '' ? $base : 'ITEM';
        $code = $base;
        $i = 1;
        while (MasterItem::where('code', $code)->exists()) {
            $i++;
            $code = $base . $i;
        }
        return $code;
    }
}
