<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasingItemVendorTrial extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchasing_item_vendor_id',
        'trial_notes',
        'created_by',
    ];

    public function vendor()
    {
        return $this->belongsTo(PurchasingItemVendor::class, 'purchasing_item_vendor_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

