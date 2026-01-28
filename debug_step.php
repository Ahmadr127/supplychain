<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ApprovalItemStep;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalWorkflow;

$item = ApprovalRequestItem::with('approvalRequest')->latest()->first();
if (!$item) {
    echo "No items found.\n";
    exit;
}

$req = $item->approvalRequest;
echo "Request ID: " . $req->id . "\n";
echo "Procurement Type ID: " . $req->procurement_type_id . "\n";
echo "Workflow ID: " . $req->workflow_id . "\n";

$currentWorkflow = ApprovalWorkflow::find($req->workflow_id);
echo "Current Workflow Name: " . ($currentWorkflow ? $currentWorkflow->name : 'NULL') . "\n";

// Check target workflow logic
$price = $item->total_price;
$range = 'high';
if ($price <= 10000000) $range = 'low';
elseif ($price <= 50000000) $range = 'medium';

echo "Calculated Range: " . $range . "\n";

$target = ApprovalWorkflow::where('procurement_type_id', $req->procurement_type_id)
    ->where('nominal_range', $range)
    ->where('is_active', true)
    ->first();

echo "Target Workflow: " . ($target ? $target->name : 'NOT FOUND') . "\n";
