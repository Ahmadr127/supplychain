<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$workflows = App\Models\ApprovalWorkflow::select('id', 'name', 'type', 'procurement_type_id', 'nominal_range')->get();
foreach ($workflows as $w) {
    echo $w->id . ' | ' . $w->name . ' | ' . $w->type . ' | ' . $w->procurement_type_id . ' | ' . $w->nominal_range . "\n";
}
