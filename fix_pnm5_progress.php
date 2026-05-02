<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ApprovalRequest;
use App\Models\ApprovalItemStep;
use App\Models\PurchasingItem;

// Get PNM-5 Request (ID 12)
$request = ApprovalRequest::where('request_number', 'PNM-5')->first();
if (!$request) {
    echo "Request PNM-5 not found.\n";
    exit;
}

$purchasingItems = PurchasingItem::where('approval_request_id', $request->id)->get();

foreach ($purchasingItems as $pi) {
    echo "Processing Purchasing Item for master_item_id: {$pi->master_item_id}\n";
    
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
            echo "  - Fixed Benchmarking step\n";
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
            echo "  - Fixed Preferred step\n";
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
            echo "  - Activated next step: {$nextStep->step_name}\n";
        }
    }
}

echo "Done fixing PNM-5 progress steps.\n";
