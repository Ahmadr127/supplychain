<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capex extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'fiscal_year',
        'status',
        'notes',
        'created_by',
    ];

    /**
     * Get total budget (sum of all items)
     */
    public function getTotalBudgetAttribute(): float
    {
        return (float) $this->items()->sum('budget_amount');
    }

    /**
     * Get total used amount
     */
    public function getTotalUsedAttribute(): float
    {
        return (float) $this->items()->sum('used_amount');
    }

    /**
     * Get remaining budget
     */
    public function getRemainingBudgetAttribute(): float
    {
        return $this->total_budget - $this->total_used;
    }

    /**
     * Get utilization percentage
     */
    public function getUtilizationPercentAttribute(): float
    {
        if ($this->total_budget <= 0) return 0;
        return round(($this->total_used / $this->total_budget) * 100, 1);
    }

    /**
     * Get items count
     */
    public function getItemsCountAttribute(): int
    {
        return $this->items()->count();
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
     * Items in this capex
     */
    public function items()
    {
        return $this->hasMany(CapexItem::class);
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
        return $query->where('fiscal_year', $year);
    }

    /**
     * Scope: By department
     */
    public function scopeByDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Get or create capex for a department and year
     */
    public static function getOrCreateForDepartment(int $departmentId, int $year): self
    {
        return self::firstOrCreate(
            ['department_id' => $departmentId, 'fiscal_year' => $year],
            ['status' => 'active', 'created_by' => auth()->id()]
        );
    }
}
