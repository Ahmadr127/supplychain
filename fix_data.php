<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ApprovalRequest;

$req = ApprovalRequest::find(3);
if ($req) {
    echo "Found Request #3\n";
    $wfSteps = $req->workflow->steps; // Already fixed this accessor
    foreach ($req->items as $item) {
        foreach ($item->steps as $step) {
            $wfStep = $wfSteps->firstWhere('step_number', $step->step_number);
            if ($wfStep && isset($wfStep->required_actions)) {
                $step->update(['required_actions' => $wfStep->required_actions]);
                echo "Updated Step #{$step->step_number}\n";
            }
        }
    }
} else {
    echo "Request #3 not found\n";
}
echo "Done\n";
