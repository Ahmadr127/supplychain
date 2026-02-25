<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportHistory extends Model
{
    protected $fillable = [
        'target_model',
        'import_mode',
        'filename',
        'original_filename',
        'total_rows',
        'success_rows',
        'failed_rows',
        'status',
        'imported_by',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(ImportLog::class, 'history_id');
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    /**
     * Progress percentage (0–100).
     */
    public function progressPercent(): int
    {
        if ($this->total_rows <= 0) return 0;
        $processed = $this->success_rows + $this->failed_rows;
        return (int) min(100, round(($processed / $this->total_rows) * 100));
    }

    /**
     * Is the import still running?
     */
    public function isRunning(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    /**
     * Duration in human-readable format.
     */
    public function duration(): ?string
    {
        if (!$this->started_at || !$this->finished_at) return null;
        $seconds = $this->started_at->diffInSeconds($this->finished_at);
        if ($seconds < 60) return "{$seconds}s";
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        return "{$minutes}m {$seconds}s";
    }
}
