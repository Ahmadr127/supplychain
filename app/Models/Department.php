<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'parent_id',
        'manager_id',
        'level',
        'approval_level',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relasi dengan departemen parent (self-referencing)
    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    // Relasi dengan departemen children
    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    // Relasi dengan manager
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // Relasi dengan users melalui pivot table
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_departments')
                    ->withPivot(['position', 'is_primary', 'is_manager', 'start_date', 'end_date'])
                    ->withTimestamps();
    }

    // Relasi dengan managers
    public function managers()
    {
        return $this->belongsToMany(User::class, 'user_departments')
                    ->wherePivot('is_manager', true)
                    ->withPivot(['position', 'start_date', 'end_date'])
                    ->withTimestamps();
    }

    // Scope untuk departemen aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope untuk level tertentu
    public function scopeLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    // Method untuk mendapatkan semua parent departments
    public function getAllParents()
    {
        $parents = collect();
        $current = $this->parent;
        
        while ($current) {
            $parents->push($current);
            $current = $current->parent;
        }
        
        return $parents;
    }

    // Method untuk mendapatkan semua child departments
    public function getAllChildren()
    {
        $children = collect();
        
        foreach ($this->children as $child) {
            $children->push($child);
            $children = $children->merge($child->getAllChildren());
        }
        
        return $children;
    }

    // Method untuk mendapatkan approver berdasarkan level
    public function getApproverByLevel($level)
    {
        if ($level <= $this->level) {
            return $this->manager;
        }
        
        // Cari di parent departments
        $current = $this->parent;
        while ($current) {
            if ($current->level >= $level) {
                return $current->manager;
            }
            $current = $current->parent;
        }
        
        return null;
    }
}
