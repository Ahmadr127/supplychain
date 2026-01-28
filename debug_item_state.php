<?php

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ApprovalRequestItem;
use App\Models\ApprovalItemStep;

$item = ApprovalRequestItem::with('approvalRequest.workflow')->find(4); // Assuming item_id 4 from logs

if (!$item) {
    echo "Item 4 not found. Trying latest...\n";
    $item = ApprovalRequestItem::with('approvalRequest.workflow')->latest()->first();
}

echo "Item ID: " . $item->id . "\n";
echo "Status: " . $item->status . "\n";
echo "Total Price: " . $item->total_price . "\n";
echo "Request Workflow: " . $item->approvalRequest->workflow->name . "\n";

echo "\nSteps:\n";
foreach ($item->steps as $step) {
    echo "Step " . $step->step_number . ": " . $step->step_name . " (" . $step->status . ")\n";
}
