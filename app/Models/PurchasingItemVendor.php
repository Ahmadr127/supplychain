<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasingItemVendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchasing_item_id',
        'supplier_id',
        'unit_price',
        'total_price',
        'is_preferred',
        'notes',
    ];

    protected $casts = [
        'is_preferred' => 'boolean',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function purchasingItem()
    {
        return $this->belongsTo(PurchasingItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function trials()
    {
        return $this->hasMany(PurchasingItemVendorTrial::class, 'purchasing_item_vendor_id');
    }

    public function latestTrial()
    {
        return $this->hasOne(PurchasingItemVendorTrial::class, 'purchasing_item_vendor_id')->latestOfMany();
    }
}
