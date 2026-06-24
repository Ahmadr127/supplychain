<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$steps = \DB::table('approval_item_steps')
    ->where('approval_request_item_id', 14) // PM-1 Item 14
    ->orderBy('step_number')
    ->get(['id', 'step_number', 'step_phase', 'status', 'approver_role_id']);

echo "Raw DB Rows for PM-1 Item 14 steps:\n";
foreach ($steps as $s) {
    echo "Step {$s->step_number} (ID: {$s->id}) | Phase: {$s->step_phase} | Status: '{$s->status}' | Role: {$s->approver_role_id}\n";
}
