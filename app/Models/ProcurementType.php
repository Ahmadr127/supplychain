<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope: Active procurement types only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get workflows associated with this procurement type
     */
    public function workflows()
    {
        return $this->hasMany(ApprovalWorkflow::class);
    }

    /**
     * Get approval requests with this procurement type
     */
    public function approvalRequests()
    {
        return $this->hasMany(ApprovalRequest::class);
    }

    /**
     * Check if this is "Barang Baru" type
     */
    public function isBarangBaru(): bool
    {
        return $this->code === 'BARANG_BARU';
    }

    /**
     * Check if this is "Peremajaan" type
     */
    public function isPeremajaan(): bool
    {
        return $this->code === 'PEREMAJAAN';
    }
}
