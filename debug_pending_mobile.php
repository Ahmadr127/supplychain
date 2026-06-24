<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ApprovalRequest;
use App\Models\User;

$user = User::where('name', 'like', '%Martanto Banu%')->first();
if (!$user) {
    die("User Martanto Banu tidak ditemukan.\n");
}

$userId = $user->id;
echo "=== SIMULASI ENDPOINT PENDING UNTUK USER: {$user->name} (Role ID: {$user->role_id}) ===\n\n";

$allRequests = ApprovalRequest::with(['requester', 'items.masterItem', 'items.steps.approver'])
    ->whereHas('items.steps', function ($q) use ($user) {
        $q->where(function ($phaseQ) {
            $phaseQ->whereIn('step_phase', ['approval', 'release'])->orWhereNull('step_phase');
        });
        $q->where(function ($sq) use ($user) {
            $sq->where('status', 'pending')->orWhere('approved_by', $user->id);
        });
    })
    ->orderBy('created_at', 'desc')
    ->get();

$filtered = $allRequests->map(function ($req) use ($userId) {
    $myItems = $req->items->filter(function ($item) use ($userId) {
        $step = $item->getCurrentPendingStep();
        $isPendingForMe = $step && in_array($step->step_phase ?? 'approval', ['approval', 'release']) && $step->canApprove($userId);
        
        $hasActioned = $item->steps->contains(function ($s) use ($userId) {
            $phase = $s->step_phase ?? 'approval';
            return (int) $s->approved_by === (int) $userId
                && in_array($phase, ['approval', 'release'])
                && in_array($s->status, ['approved', 'rejected'], true);
        });

        if ($isPendingForMe || $hasActioned) {
            $item->setAttribute('can_approve', (bool)$isPendingForMe);
            if (!in_array($item->status, ['approved', 'rejected', 'done', 'terpenuhi', 'fulfilled', 'completed', 'released'])) {
                $item->status = $isPendingForMe ? 'pending' : 'approved';
            }
            return true;
        }
        return false;
    })->values();

    if ($myItems->isEmpty()) {
        return null;
    }

    $req->setRelation('items', $myItems);
    
    $isPending = $myItems->contains('can_approve', true);
    $isRejected = $myItems->contains(function ($i) use ($userId) {
        return $i->steps->where('approved_by', $userId)
            ->where('status', 'rejected')
            ->filter(fn($s) => in_array(($s->step_phase ?? 'approval'), ['approval', 'release']))
            ->isNotEmpty();
    });

    $req->status = $isPending ? 'pending' : ($isRejected ? 'rejected' : 'approved');

    return $req;
})->filter();

$filteredPending = $filtered->where('status', 'pending')->values();

echo "Total requests yang akan dikembalikan oleh API (dengan status 'pending'): " . $filteredPending->count() . "\n\n";

foreach ($filteredPending as $req) {
    echo "Request #{$req->request_number} (ID: {$req->id})\n";
    echo "  Status di request (API JSON): {$req->status}\n";
    foreach ($req->items as $item) {
        echo "  - Item ID: {$item->id} | Name: {$item->item_name}\n";
        echo "    can_approve di JSON: " . ($item->can_approve ? 'TRUE' : 'FALSE') . "\n";
        echo "    status di JSON: {$item->status}\n";
        
        $step = $item->getCurrentPendingStep();
        if ($step) {
            echo "    [Current Pending Step] Step {$step->step_number} (Phase: {$step->step_phase}) | Approver Role ID: {$step->approver_role_id} | canApprove: " . ($step->canApprove($userId) ? 'TRUE' : 'FALSE') . "\n";
        } else {
            echo "    [Current Pending Step] None\n";
        }
        
        // Cek jika Martanto pernah approve
        $approvedSteps = $item->steps->filter(fn($s) => $s->approved_by == $userId);
        if ($approvedSteps->isNotEmpty()) {
            echo "    [Riwayat Approve oleh Martanto]:\n";
            foreach ($approvedSteps as $s) {
                echo "      -> Step {$s->step_number} (Phase: {$s->step_phase}) pada {$s->approved_at}\n";
            }
        }
    }
    echo "--------------------------------------------------------\n";
}
