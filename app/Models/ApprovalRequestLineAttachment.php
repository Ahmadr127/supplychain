<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequestLineAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_line_id',
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    public function line()
    {
        return $this->belongsTo(ApprovalRequestLine::class, 'approval_request_line_id');
    }
}
