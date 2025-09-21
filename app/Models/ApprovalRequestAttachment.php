<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ApprovalRequestAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'approval_request_id',
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'description'
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // Relationship with approval request
    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    // Accessor for file URL
    public function getFileUrlAttribute()
    {
        return Storage::url($this->file_path);
    }

    // Accessor for human readable file size
    public function getHumanFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    // Method to check if file is PDF
    public function isPdf()
    {
        return $this->mime_type === 'application/pdf';
    }

    // Method to delete file from storage when model is deleted
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            if (Storage::exists($attachment->file_path)) {
                Storage::delete($attachment->file_path);
            }
        });
    }
}