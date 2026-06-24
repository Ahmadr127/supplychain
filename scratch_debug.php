<?php

use App\Models\ApprovalRequest;
use App\Models\User;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Let's inspect the requests from the screenshot:
$requestNumbers = [
    '45/RT/RSAZRA/V/2026',
    '28/K3/RSAZRA/VI/2026',
    '52/RSAZRA/VI/2026'
];

foreach ($requestNumbers as $num) {
    $req = ApprovalRequest::with(['items.steps.approver', 'items.steps'])
        ->where('request_number', $num)
        ->first();
        
    if (!$req) {
        echo "Request $num not found.\n";
        continue;
    }
    
    echo "=========================================\n";
    echo "Request: {$req->request_number} (ID: {$req->id}) - Status: {$req->status}\n";
    foreach ($req->items as $item) {
        echo "  Item: {$item->id} (Status: {$item->status})\n";
        foreach ($item->steps as $step) {
            echo "    Step {$step->step_number}: {$step->step_name} (Phase: {$step->step_phase}, Action: {$step->required_action}) - Status: {$step->status}\n";
            echo "      Approver Type: {$step->approver_type}, ID: {$step->approver_id}, Role: {$step->approver_role_id}, Dept: {$step->approver_department_id}\n";
            if ($step->approved_by) {
                echo "      Approved by User: {$step->approved_by} at {$step->approved_at}\n";
            }
        }
    }
}
