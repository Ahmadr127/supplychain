<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = [
        'history_id',
        'row_number',
        'row_data',
        'errors',
    ];

    protected $casts = [
        'row_data' => 'array',
        'errors'   => 'array',
    ];

    public function history()
    {
        return $this->belongsTo(ImportHistory::class, 'history_id');
    }
}
