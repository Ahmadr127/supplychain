<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TsCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'ts_approver_type',
        'ts_approver_id',
        'ts_approver_role_id',
    ];

    public function approverUser()
    {
        return $this->belongsTo(User::class, 'ts_approver_id');
    }

    public function approverRole()
    {
        return $this->belongsTo(Role::class, 'ts_approver_role_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
