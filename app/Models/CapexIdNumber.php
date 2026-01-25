<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapexIdNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'capex_number',
        'capex_year',
        'capex_category',
        'budget_amount',
        'used_amount',
        'status',
        'description',
        'department_id',
        'created_by',
        'approved_by',
        'approved_at',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    /**
     * Get remaining amount (budget - used)
     */
    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->budget_amount - (float) $this->used_amount;
    }

    /**
     * Check if capex has sufficient budget for amount
     */
    public function hasSufficientBudget(float $amount): bool
    {
        return $this->remaining_amount >= $amount;
    }

    /**
     * Allocate amount from this capex
     */
    public function allocate(float $amount): bool
    {
        if (!$this->hasSufficientBudget($amount)) {
            return false;
        }

        $this->used_amount += $amount;
        
        // Auto-update status if exhausted
        if ($this->remaining_amount <= 0) {
            $this->status = 'exhausted';
        }
        
        return $this->save();
    }

    /**
     * Release allocated amount back to capex
     */
    public function release(float $amount): bool
    {
        $this->used_amount = max(0, $this->used_amount - $amount);
        
        // Reactivate if was exhausted
        if ($this->status === 'exhausted' && $this->remaining_amount > 0) {
            $this->status = 'active';
        }
        
        return $this->save();
    }

    /**
     * Scope: Active capex only
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: By year
     */
    public function scopeByYear($query, int $year)
    {
        return $query->where('capex_year', $year);
    }

    /**
     * Scope: With sufficient budget for amount
     */
    public function scopeWithBudget($query, float $amount)
    {
        return $query->whereRaw('(budget_amount - used_amount) >= ?', [$amount]);
    }

    /**
     * Department that owns this capex
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * User who created this capex
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User who approved this capex
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Allocations made from this capex
     */
    public function allocations()
    {
        return $this->hasMany(CapexAllocation::class);
    }

    /**
     * Approval requests using this capex
     */
    public function approvalRequests()
    {
        return $this->hasMany(ApprovalRequest::class);
    }

    /**
     * Generate next capex number
     */
    public static function generateNumber(int $year = null): string
    {
        $year = $year ?? date('Y');
        
        $lastNumber = self::where('capex_year', $year)
            ->orderByDesc('id')
            ->value('capex_number');
        
        $sequence = 1;
        if ($lastNumber && preg_match('/CAPEX-' . $year . '-(\d+)/', $lastNumber, $matches)) {
            $sequence = (int) $matches[1] + 1;
        }
        
        return sprintf('CAPEX-%d-%03d', $year, $sequence);
    }
}
