<?php

/**
 * fix_pnm5_data.php
 *
 * Perbaiki data PNM-5 (ID:12) yang terlanjur tidak masuk purchasing
 * karena bug controller (approval step 10-11 ditemukan lebih dulu dari
 * purchasing step 6-9, sehingga item tidak pernah di-set in_purchasing).
 *
 * Apa yang dilakukan script ini:
 *  1. Update approval_request_item #12 → status 'in_purchasing'
 *  2. Buat PurchasingItem jika belum ada
 *  3. Update approval_request #12 → status 'in_purchasing'
 *
 * Jalankan:
 *   php artisan tinker --execute="require base_path('fix_pnm5_data.php');"
 */

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalItemStep;
use App\Models\PurchasingItem;

$REQUEST_NUMBER = 'PNM-5';

echo "\n========================================================\n";
echo " FIX DATA: $REQUEST_NUMBER\n";
echo "========================================================\n\n";

$req = ApprovalRequest::where('request_number', $REQUEST_NUMBER)
    ->with(['items.masterItem', 'purchasingItems'])
    ->first();

if (!$req) {
    echo "[ERROR] Request '$REQUEST_NUMBER' tidak ditemukan!\n\n";
    exit;
}

echo "Request ID   : {$req->id}\n";
echo "Status saat  : {$req->status}\n\n";

$fixed = false;

foreach ($req->items as $item) {
    $masterName = optional($item->masterItem)->name ?? '(null)';

    echo "--- Item #{$item->id} ($masterName) ---\n";
    echo "  Status saat ini  : {$item->status}\n";

    // Cek semua approval steps sudah selesai
    $openApprovalSteps = ApprovalItemStep::where('approval_request_id', $req->id)
        ->where('approval_request_item_id', $item->id)
        ->where('step_phase', 'approval')
        ->whereNotIn('status', ['approved', 'skipped'])
        ->orderBy('step_number')
        ->get();

    // Cek ada purchasing step berikutnya
    $nextPurchasingStep = ApprovalItemStep::where('approval_request_id', $req->id)
        ->where('approval_request_item_id', $item->id)
        ->whereIn('step_phase', ['purchasing', 'release'])
        ->whereNotIn('status', ['approved', 'skipped'])
        ->orderBy('step_number')
        ->first();

    echo "  Open approval steps : " . $openApprovalSteps->count() . "\n";

    if ($openApprovalSteps->isNotEmpty()) {
        echo "  [SKIP] Masih ada approval step yang belum selesai:\n";
        foreach ($openApprovalSteps as $s) {
            echo "         - Step #{$s->step_number} [{$s->step_phase}] {$s->step_name} (status: {$s->status})\n";
        }
        echo "\n";
        continue;
    }

    if (!$nextPurchasingStep) {
        echo "  [INFO] Tidak ada purchasing step berikutnya, skip.\n\n";
        continue;
    }

    echo "  Next purchasing step: #{$nextPurchasingStep->step_number} [{$nextPurchasingStep->step_phase}] {$nextPurchasingStep->step_name}\n";

    // 1. Update item status
    if (!in_array($item->status, ['in_purchasing', 'in_release', 'approved'])) {
        $item->update(['status' => 'in_purchasing']);
        $item->refresh();
        echo "  [FIXED] Item status diubah: on progress -> in_purchasing\n";
        $fixed = true;
    } else {
        echo "  [OK] Item status sudah: {$item->status}\n";
    }

    // 2. Buat PurchasingItem jika belum ada
    $pi = PurchasingItem::where('approval_request_id', $req->id)
        ->where('master_item_id', $item->master_item_id)
        ->first();

    if (!$pi) {
        $pi = PurchasingItem::create([
            'approval_request_id' => $req->id,
            'master_item_id'      => $item->master_item_id,
            'quantity'            => $item->quantity,
            'status'              => 'unprocessed',
        ]);
        echo "  [CREATED] PurchasingItem baru dibuat (ID: {$pi->id})\n";
        $fixed = true;
    } else {
        echo "  [OK] PurchasingItem sudah ada (ID: {$pi->id}, status: {$pi->status})\n";
    }

    echo "\n";
}

// 3. Refresh request status
$req->refresh()->load('items');
$anyInPurchasing = $req->items->contains(function ($i) {
    return in_array($i->status, ['in_purchasing', 'in_release']);
});
$allApproved = $req->items->every(fn($i) => $i->status === 'approved');
$anyRejected = $req->items->contains(fn($i) => $i->status === 'rejected');

$newReqStatus = 'on progress';
if ($anyRejected) {
    $newReqStatus = 'rejected';
} elseif ($allApproved) {
    $newReqStatus = 'approved';
} elseif ($anyInPurchasing) {
    $newReqStatus = 'in_purchasing';
}

if ($req->status !== $newReqStatus) {
    $req->update(['status' => $newReqStatus]);
    echo "[FIXED] ApprovalRequest status: {$req->status} -> $newReqStatus\n\n";
    $fixed = true;
} else {
    echo "[OK] ApprovalRequest status sudah benar: {$req->status}\n\n";
}

// Hasil akhir
echo "========================================================\n";
if ($fixed) {
    echo " SELESAI: Data berhasil diperbaiki!\n";
    echo " Silakan refresh halaman /reports/approval-requests\n";
} else {
    echo " Tidak ada perubahan yang diperlukan.\n";
}
echo "========================================================\n\n";
