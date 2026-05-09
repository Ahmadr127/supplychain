<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalRequestItemExtra;
use App\Models\ApprovalItemStep;
use App\Models\PurchasingItem;
use App\Models\PurchasingItemVendor;
use App\Models\PurchasingItemVendorTrial;
use App\Models\CapexAllocation;
use App\Models\CapexItem;
use App\Models\Notification;

class CleanRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-requests {--force : Skip confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete all approval requests, purchasing data, and reset CapEx budget tracking';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('WARNING: This will permanently delete ALL approval requests and related data. Do you want to proceed?')) {
            $this->info('Cleaning cancelled.');
            return;
        }

        $this->info('Cleaning started...');

        try {
            DB::beginTransaction();

            // Disable foreign key checks for the session
            // For PostgreSQL, we use TRUNCATE with CASCADE or defer constraints
            $this->info('Trimming tables...');

            $tables = [
                'notifications',
                'purchasing_item_vendor_trials',
                'purchasing_item_vendors',
                'purchasing_items',
                'approval_item_steps',
                'approval_request_item_extras',
                'approval_request_item_files',
                'approval_request_items',
                'capex_allocations',
                'approval_requests',
            ];

            foreach ($tables as $table) {
                $this->info("Cleaning table: {$table}");
                DB::statement("TRUNCATE TABLE {$table} RESTART IDENTITY CASCADE");
            }

            // Reset CapEx items budget tracking
            $this->info('Resetting CapEx budget tracking...');
            CapexItem::query()->update([
                'used_amount' => 0,
                'pending_amount' => 0,
                'status' => 'available',
                'approval_request_id' => null,
                'approval_request_item_id' => null,
            ]);

            DB::commit();
            $this->info('Database cleaned successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to clean database: ' . $e->getMessage());
            return 1;
        }

        // Clean up storage folders
        $this->info('Cleaning storage files...');
        
        $directories = [
            'fs_documents',
            'approval_items',
            'approval_request_item_files'
        ];

        foreach ($directories as $dir) {
            if (Storage::disk('public')->exists($dir)) {
                $this->info("Deleting directory: {$dir}");
                Storage::disk('public')->deleteDirectory($dir);
            }
        }

        $this->info('All requests and related files have been cleaned!');
        return 0;
    }
}
