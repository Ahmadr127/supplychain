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
        'budget_amount',
        'used_amount',
        'status',
        'approval_request_id',
        'approval_request_item_id',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
    ];

    /**
     * Get remaining amount
     */
    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->budget_amount - (float) $this->used_amount;
    }

    /**
     * Check if item has sufficient budget
     */
    public function hasSufficientBudget(float $amount): bool
    {
        return $this->remaining_amount >= $amount;
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
    public static function generateIdNumber(string $deptCode, int $year): string
    {
        $prefix = "CAPEX-{$deptCode}-{$year}";
        
        $lastItem = self::where('capex_id_number', 'like', "{$prefix}-%")
            ->orderByDesc('id')
            ->first();
        
        $sequence = 1;
        if ($lastItem && preg_match("/{$prefix}-(\d+)/", $lastItem->capex_id_number, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }
        
        return sprintf('%s-%03d', $prefix, $sequence);
    }
}
