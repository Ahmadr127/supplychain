<?php

namespace App\Jobs;

use App\Models\ImportHistory;
use App\Services\Import\DynamicImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public $timeout = 3600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ImportHistory $history,
        public array $config
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DynamicImportService $service): void
    {
        try {
            $service->execute($this->history, $this->config);
        } catch (\Throwable $e) {
            $this->history->update([
                'status' => 'failed',
                'finished_at' => now()
            ]);
            throw $e;
        }
    }
}
