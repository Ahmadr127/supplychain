<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'hna',
        'ppn_percentage',
        'ppn_amount',
        'total_price',
        'item_type_id',
        'item_category_id',
        'commodity_id',
        'unit_id',
        'stock',
        'is_active'
    ];

    protected $casts = [
        'hna' => 'decimal:2',
        'ppn_percentage' => 'decimal:2',
        'ppn_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function itemType()
    {
        return $this->belongsTo(ItemType::class);
    }

    public function itemCategory()
    {
        return $this->belongsTo(ItemCategory::class);
    }

    public function commodity()
    {
        return $this->belongsTo(Commodity::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    // Relationship with approval requests (many-to-many)
    public function approvalRequests()
    {
        return $this->belongsToMany(ApprovalRequest::class, 'approval_request_master_items')
                    ->withPivot(['quantity', 'unit_price', 'total_price', 'notes'])
                    ->withTimestamps();
    }

    // Relationship with item extras (form statis)
    public function itemExtras()
    {
        return $this->hasMany(ApprovalRequestItemExtra::class, 'master_item_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $typeId)
    {
        return $query->where('item_type_id', $typeId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('item_category_id', $categoryId);
    }

    public function scopeByCommodity($query, $commodityId)
    {
        return $query->where('commodity_id', $commodityId);
    }

    // Accessors & Mutators
    public function getCodeAttribute($value)
    {
        if ($value === null) {
            return null;
        }
        return strtoupper((string) $value);
    }

    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = strtoupper($value);
    }


    // Methods
    public function calculatePpnAmount()
    {
        return ($this->hna * $this->ppn_percentage) / 100;
    }

    public function calculateTotalPrice()
    {
        return $this->hna + $this->ppn_amount;
    }

    // Boot method to auto-calculate PPN and total price
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->ppn_amount = $item->calculatePpnAmount();
            $item->total_price = $item->calculateTotalPrice();
        });
    }
}