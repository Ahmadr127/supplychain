<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapexItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'capex_id',
        'capex_id_number',
        'item_name',
        'description',
        'category',
        'priority_scale',
        'month',
        'amount_per_year',
        'capex_type',
        'pic',
        'budget_amount',
        'used_amount',
        'pending_amount',
        'status',
        'approval_request_id',
        'approval_request_item_id',
    ];

    protected $casts = [
        'budget_amount'  => 'decimal:2',
        'used_amount'    => 'decimal:2',
        'pending_amount' => 'decimal:2',
    ];

    /**
     * Budget tersedia = budget_amount - used_amount - pending_amount
     */
    public function getAvailableAmountAttribute(): float
    {
        return max(0, (float) $this->budget_amount
            - (float) $this->used_amount
            - (float) $this->pending_amount);
    }

    /**
     * @deprecated Gunakan getAvailableAmountAttribute()
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->available_amount;
    }

    /**
     * Cek budget tersedia (memperhitungkan pending_amount)
     */
    public function hasSufficientBudget(float $amount): bool
    {
        return $this->available_amount >= $amount;
    }

    /**
     * Use budget from this item
     */
    public function useBudget(float $amount): bool
    {
        if (!$this->hasSufficientBudget($amount)) {
            return false;
        }

        $this->used_amount += $amount;
        
        // Update status
        if ($this->remaining_amount <= 0) {
            $this->status = 'exhausted';
        } elseif ($this->used_amount > 0) {
            $this->status = 'partially_used';
        }
        
        return $this->save();
    }

    /**
     * Release used budget
     */
    public function releaseBudget(float $amount): bool
    {
        $this->used_amount = max(0, $this->used_amount - $amount);
        
        // Update status
        if ($this->used_amount <= 0) {
            $this->status = 'available';
        } elseif ($this->remaining_amount > 0) {
            $this->status = 'partially_used';
        }
        
        return $this->save();
    }

    /**
     * Parent capex
     */
    public function capex()
    {
        return $this->belongsTo(Capex::class);
    }

    /**
     * Linked approval request
     */
    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    /**
     * Linked approval request item
     */
    public function approvalRequestItem()
    {
        return $this->belongsTo(ApprovalRequestItem::class);
    }

    /**
     * Alokasi aktif (pending/confirmed) untuk item ini
     */
    public function activeAllocations()
    {
        return $this->hasMany(CapexAllocation::class, 'capex_item_id')
                    ->whereIn('status', ['pending', 'confirmed']);
    }

    /**
     * Semua alokasi historis
     */
    public function allocations()
    {
        return $this->hasMany(CapexAllocation::class, 'capex_item_id');
    }

    /**
     * Scope: Available items only
     */
    public function scopeAvailable($query)
    {
        return $query->whereIn('status', ['available', 'partially_used']);
    }

    /**
     * Scope: By category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Generate next CapEx ID Number
     */
    /**
     * Generate next CapEx ID Number in format: {seq}/CapEx/{year}/{dept_code}
     * e.g. 7/CapEx/2026/I-RI
     */
    public static function generateIdNumber(string $deptCode, int $year): string
    {
        // Count existing items for this dept+year to determine next sequence
        $count = self::whereHas('capex', function ($q) use ($deptCode, $year) {
            $q->where('fiscal_year', $year)
              ->whereHas('department', fn($d) => $d->where('code', $deptCode));
        })->count();

        $seq = $count + 1;
        return "{$seq}/CapEx/{$year}/{$deptCode}";
    }
}
