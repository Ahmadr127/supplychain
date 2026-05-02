<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ApprovalItemStep;
use App\Models\PurchasingItem;

$purchasingItems = PurchasingItem::all();

foreach ($purchasingItems as $pi) {
    // Check Benchmarking
    if ($pi->vendors()->exists()) {
        $step = ApprovalItemStep::where('approval_request_id', $pi->approval_request_id)
            ->where('master_item_id', $pi->master_item_id)
            ->where('step_phase', 'purchasing')
            ->whereNull('required_action')
            ->where('step_name', 'like', '%Benchmark%')
            ->first();
            
        if ($step && $step->status !== 'approved') {
            $step->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => 1]);
        }
    }
    
    // Check Trial
    if ($pi->vendors()->whereHas('trials')->exists()) {
        $step = ApprovalItemStep::where('approval_request_id', $pi->approval_request_id)
            ->where('master_item_id', $pi->master_item_id)
            ->where('step_phase', 'purchasing')
            ->whereNull('required_action')
            ->where('step_name', 'like', '%Trial%')
            ->first();
            
        if ($step && $step->status !== 'approved') {
            $step->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => 1]);
        }
    }
    
    // Check Preferred
    if ($pi->preferred_vendor_id) {
        $step = ApprovalItemStep::where('approval_request_id', $pi->approval_request_id)
            ->where('master_item_id', $pi->master_item_id)
            ->where('step_phase', 'purchasing')
            ->whereNull('required_action')
            ->where('step_name', 'like', '%Preferred%')
            ->first();
            
        if ($step && $step->status !== 'approved') {
            $step->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => 1]);
        }
    }
    
    // Activate next step if any
    $lastApproved = ApprovalItemStep::where('approval_request_id', $pi->approval_request_id)
        ->where('master_item_id', $pi->master_item_id)
        ->where('status', 'approved')
        ->orderBy('step_number', 'desc')
        ->first();
        
    if ($lastApproved) {
        $nextStep = ApprovalItemStep::where('approval_request_id', $pi->approval_request_id)
            ->where('master_item_id', $pi->master_item_id)
            ->where('step_number', '>', $lastApproved->step_number)
            ->where('status', 'pending_purchase')
            ->orderBy('step_number')
            ->first();
            
        if ($nextStep) {
            $nextStep->update(['status' => 'pending']);
        }
    }
}

echo "Done fixing all purchasing progress steps.\n";
