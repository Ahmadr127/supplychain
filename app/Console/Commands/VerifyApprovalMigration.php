<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalItemStep;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class VerifyApprovalMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'approval:verify-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that approval system migration to per-item approval completed successfully';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verifying approval system migration...');
        $this->newLine();
        
        $errors = 0;
        $warnings = 0;
        
        // Check 1: New tables exist
        $this->line('1️⃣  Checking new tables...');
        if (!Schema::hasTable('approval_request_items')) {
            $this->error('   ❌ Table approval_request_items does not exist!');
            $errors++;
        } else {
            $this->info('   ✅ Table approval_request_items exists');
        }
        
        if (!Schema::hasTable('approval_item_steps')) {
            $this->error('   ❌ Table approval_item_steps does not exist!');
            $errors++;
        } else {
            $this->info('   ✅ Table approval_item_steps exists');
        }
        
        $this->newLine();
        
        // Check 2: All requests have items
        $this->line('2️⃣  Checking all requests have items...');
        $requestsWithoutItems = ApprovalRequest::doesntHave('items')->count();
        if ($requestsWithoutItems > 0) {
            $this->error("   ❌ Found {$requestsWithoutItems} request(s) without items!");
            $errors++;
            
            // Show details
            $requests = ApprovalRequest::doesntHave('items')->limit(5)->get(['id', 'request_number']);
            foreach ($requests as $req) {
                $this->warn("      Request #{$req->id} ({$req->request_number})");
            }
            if ($requestsWithoutItems > 5) {
                $this->warn("      ... and " . ($requestsWithoutItems - 5) . " more");
            }
        } else {
            $this->info('   ✅ All requests have items');
        }
        
        $this->newLine();
        
        // Check 3: All items have steps
        $this->line('3️⃣  Checking all items have approval steps...');
        $itemsWithoutSteps = ApprovalRequestItem::whereDoesntHave('steps')->count();
        if ($itemsWithoutSteps > 0) {
            $this->error("   ❌ Found {$itemsWithoutSteps} item(s) without steps!");
            $errors++;
            
            // Show details
            $items = ApprovalRequestItem::whereDoesntHave('steps')
                ->with('masterItem')
                ->limit(5)
                ->get();
            foreach ($items as $item) {
                $this->warn("      Item #{$item->id} (Request #{$item->approval_request_id})");
            }
            if ($itemsWithoutSteps > 5) {
                $this->warn("      ... and " . ($itemsWithoutSteps - 5) . " more");
            }
            
            $this->newLine();
            $this->info('   💡 Run: php artisan approval:initialize-item-steps');
        } else {
            $this->info('   ✅ All items have approval steps');
        }
        
        $this->newLine();
        
        // Check 4: Old tables status
        $this->line('4️⃣  Checking old tables...');
        if (Schema::hasTable('approval_request_master_items')) {
            $this->warn('   ⚠️  Old pivot table approval_request_master_items still exists');
            $warnings++;
            
            $pivotCount = DB::table('approval_request_master_items')->count();
            $this->line("      Contains {$pivotCount} record(s)");
            $this->info('      💡 Run cleanup migration to drop this table');
        } else {
            $this->info('   ✅ Old pivot table has been dropped');
        }
        
        if (Schema::hasTable('approval_steps')) {
            $this->warn('   ⚠️  Old approval_steps table still exists');
            $warnings++;
            
            $stepsCount = DB::table('approval_steps')->count();
            $this->line("      Contains {$stepsCount} record(s)");
            $this->info('      💡 Run cleanup migration to drop this table');
        } else {
            $this->info('   ✅ Old approval_steps table has been dropped');
        }
        
        $this->newLine();
        
        // Check 5: Data integrity
        $this->line('5️⃣  Checking data integrity...');
        
        // Count items vs steps
        $totalItems = ApprovalRequestItem::count();
        $totalSteps = ApprovalItemStep::count();
        $this->info("   📊 Total items: {$totalItems}");
        $this->info("   📊 Total steps: {$totalSteps}");
        
        // Check for orphaned steps
        $orphanedSteps = ApprovalItemStep::whereDoesntHave('approvalRequest')->count();
        if ($orphanedSteps > 0) {
            $this->warn("   ⚠️  Found {$orphanedSteps} orphaned step(s) (no matching request)");
            $warnings++;
        } else {
            $this->info('   ✅ No orphaned steps found');
        }
        
        $this->newLine();
        
        // Check 6: Status consistency
        $this->line('6️⃣  Checking status consistency...');
        
        // Items with approved status should have all steps approved
        $inconsistentApproved = ApprovalRequestItem::where('status', 'approved')
            ->whereHas('steps', function($q) {
                $q->where('status', '!=', 'approved');
            })
            ->count();
        
        if ($inconsistentApproved > 0) {
            $this->warn("   ⚠️  Found {$inconsistentApproved} approved item(s) with pending/rejected steps");
            $warnings++;
        } else {
            $this->info('   ✅ Approved items have consistent step statuses');
        }
        
        // Items with rejected status should have at least one rejected step
        $inconsistentRejected = ApprovalRequestItem::where('status', 'rejected')
            ->whereDoesntHave('steps', function($q) {
                $q->where('status', 'rejected');
            })
            ->count();
        
        if ($inconsistentRejected > 0) {
            $this->warn("   ⚠️  Found {$inconsistentRejected} rejected item(s) without rejected steps");
            $warnings++;
        } else {
            $this->info('   ✅ Rejected items have consistent step statuses');
        }
        
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        if ($errors === 0 && $warnings === 0) {
            $this->info('✅ Migration verification PASSED!');
            $this->info('   All checks completed successfully.');
            return 0;
        } elseif ($errors === 0) {
            $this->warn("⚠️  Migration verification PASSED with {$warnings} warning(s)");
            $this->info('   Core functionality is working, but cleanup recommended.');
            return 0;
        } else {
            $this->error("❌ Migration verification FAILED!");
            $this->error("   Found {$errors} error(s) and {$warnings} warning(s)");
            $this->error('   Please fix the errors before proceeding.');
            return 1;
        }
    }
}
