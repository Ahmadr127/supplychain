<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ApprovalRequest;

$reqs = ApprovalRequest::whereHas('items.masterItem', function($q) {
    $q->where('name', 'like', '%Sendal%')
      ->orWhere('name', 'like', '%wastafel%');
})->get();

foreach ($reqs as $req) {
    echo "Request #{$req->request_number} (ID: {$req->id})\n";
    foreach ($req->items as $item) {
        echo "  Item ID: {$item->id} | Name: " . ($item->masterItem->name ?? 'N/A') . "\n";
        $steps = \DB::table('approval_item_steps')
            ->where('approval_request_item_id', $item->id)
            ->orderBy('step_number')
            ->get(['id', 'step_number', 'step_phase', 'status']);
        
        foreach ($steps as $s) {
            echo "    Step {$s->step_number} (ID: {$s->id}) | Phase: {$s->step_phase} | Status: '{$s->status}'\n";
        }
    }
}
