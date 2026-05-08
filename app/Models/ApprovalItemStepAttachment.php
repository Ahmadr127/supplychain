<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ApprovalItemStepAttachment extends Model
{
    use HasFactory;

    protected $table = 'approval_item_step_attachments';

    protected $fillable = [
        'approval_item_step_id',
        'approval_request_id',
        'approval_request_item_id',
        'original_name',
        'path',
        'mime',
        'size',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    // ─── Relations ───────────────────────────────────────────────────────────

    public function step()
    {
        return $this->belongsTo(ApprovalItemStep::class, 'approval_item_step_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function approvalRequestItem()
    {
        return $this->belongsTo(ApprovalRequestItem::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Get the public URL of this attachment.
     */
    public function getUrl(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Check if file exists on disk.
     */
    public function existsOnDisk(): bool
    {
        return Storage::disk('public')->exists($this->path);
    }

    /**
     * Check if this file is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->mime === 'application/pdf';
    }

    /**
     * Human-readable file size.
     */
    public function formattedSize(): string
    {
        if (!$this->size) return '-';
        if ($this->size < 1024) return $this->size . ' B';
        if ($this->size < 1048576) return round($this->size / 1024, 1) . ' KB';
        return round($this->size / 1048576, 1) . ' MB';
    }
}
