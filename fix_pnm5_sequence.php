<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ApprovalItemStep;
use App\Models\PurchasingItem;

// Get PNM-5 Request (ID 12)
$request = \App\Models\ApprovalRequest::where('request_number', 'PNM-5')->first();
if (!$request) {
    echo "Request PNM-5 not found.\n";
    exit;
}

$purchasingItems = PurchasingItem::where('approval_request_id', $request->id)->get();

foreach ($purchasingItems as $pi) {
    // Find Trial Vendor step
    $trialStep = ApprovalItemStep::where('approval_request_id', $pi->approval_request_id)
        ->where('master_item_id', $pi->master_item_id)
        ->where('step_phase', 'purchasing')
        ->where('step_name', 'like', '%Trial%')
        ->first();
        
    // Find Preferred Vendor step
    $prefStep = ApprovalItemStep::where('approval_request_id', $pi->approval_request_id)
        ->where('master_item_id', $pi->master_item_id)
        ->where('step_phase', 'purchasing')
        ->where('step_name', 'like', '%Preferred%')
        ->first();
        
    // If preferred is approved but trial is pending, skip trial
    if ($prefStep && $prefStep->status === 'approved' && $trialStep && $trialStep->status === 'pending') {
        $trialStep->update([
            'status' => 'skipped',
            'comments' => 'Otomatis dilewati karena Vendor Preferred sudah dipilih'
        ]);
        echo "Marked Trial step as skipped.\n";
    }
    
    // Find PO step
    $poStep = ApprovalItemStep::where('approval_request_id', $pi->approval_request_id)
        ->where('master_item_id', $pi->master_item_id)
        ->where('step_phase', 'purchasing')
        ->where('step_name', 'like', '%PO%')
        ->first();
        
    if ($poStep && $poStep->status === 'pending_purchase') {
        $poStep->update([
            'status' => 'pending'
        ]);
        echo "Marked PO step as active (pending).\n";
    }
}

echo "Done fixing PNM-5 progress steps sequence.\n";
