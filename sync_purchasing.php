<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $items = \App\Models\ApprovalRequestItem::where('status', 'in_purchasing')->get();
    $count = 0;
    foreach ($items as $item) {
        $exists = \App\Models\PurchasingItem::where('approval_request_id', $item->approval_request_id)
            ->where('master_item_id', $item->master_item_id)
            ->exists();
            
        if (!$exists) {
            \App\Models\PurchasingItem::create([
                'approval_request_id' => $item->approval_request_id,
                'master_item_id'      => $item->master_item_id,
                'quantity'            => $item->quantity,
                'status'              => 'unprocessed',
            ]);
            $count++;
            echo "Created PurchasingItem for AR Item ID: {$item->id}\n";
        }
    }
    echo "Successfully synced $count missing purchasing items.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
