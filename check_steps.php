<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$req = \App\Models\ApprovalRequest::where('request_number', 'PNM-1 test')->first();
if($req) {
    $steps = \App\Models\ApprovalItemStep::where('approval_request_id', $req->id)->get(['step_number', 'step_name', 'step_type', 'step_phase', 'status']);
    echo json_encode($steps, JSON_PRETTY_PRINT);
} else {
    echo 'Not found';
}
