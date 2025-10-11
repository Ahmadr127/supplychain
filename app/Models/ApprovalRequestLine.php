<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequestLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'item_name',
        'quantity',
        'unit_name',
        'specification',
        'brand',
        'notes',
        'alternative_vendor',
    ];

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function attachments()
    {
        return $this->hasMany(ApprovalRequestLineAttachment::class);
    }
}
