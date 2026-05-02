<?php

/**
 * fix_pnm5_data.php
 *
 * Perbaiki data PNM-5 yang terlanjur tidak masuk purchasing.
 *
 * Logika yang dipakai SAMA dengan controller yang sudah diperbaiki:
 *   → Ambil step berikutnya berdasarkan step_number
 *   → Jika phase-nya purchasing/release → set item ke in_purchasing
 *   → Jika phase-nya approval           → item memang masih on progress
 *
 * Jalankan:
 *   php artisan tinker --execute="require base_path('fix_pnm5_data.php');"
 */

use App\Models\ApprovalRequest;
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

$anyFixed = false;

foreach ($req->items as $item) {
    $masterName = optional($item->masterItem)->name ?? '(null)';
    echo "--- Item #{$item->id} ($masterName) ---\n";
    echo "  Status saat ini : {$item->status}\n";

    // Cari step terakhir yang sudah approved (langkah approval terakhir selesai)
    $lastApprovedStep = ApprovalItemStep::where('approval_request_id', $req->id)
        ->where('approval_request_item_id', $item->id)
        ->where('status', 'approved')
        ->orderBy('step_number', 'desc')
        ->first();

    if (!$lastApprovedStep) {
        echo "  [SKIP] Belum ada step yang di-approve.\n\n";
        continue;
    }

    echo "  Last approved step : #{$lastApprovedStep->step_number} [{$lastApprovedStep->step_phase}] {$lastApprovedStep->step_name}\n";

    // Logika SAMA dengan controller: ambil step berikutnya berdasarkan step_number
    $nextStep = ApprovalItemStep::where('approval_request_id', $req->id)
        ->where('approval_request_item_id', $item->id)
        ->where('step_number', '>', $lastApprovedStep->step_number)
        ->whereNotIn('status', ['approved', 'skipped'])
        ->orderBy('step_number')
        ->first();

    if (!$nextStep) {
        echo "  Next step : (tidak ada) → seharusnya approved\n";

        if (!in_array($item->status, ['approved'])) {
            $item->update(['status' => 'approved', 'approved_by' => null, 'approved_at' => now()]);
            $item->refresh();
            echo "  [FIXED] Item status → approved\n";
            $anyFixed = true;
        } else {
            echo "  [OK] Status sudah approved.\n";
        }

    } elseif (in_array($nextStep->step_phase, ['purchasing', 'release'])) {
        echo "  Next step : #{$nextStep->step_number} [{$nextStep->step_phase}] {$nextStep->step_name}\n";
        echo "              → Step berikutnya adalah PURCHASING/RELEASE\n";
        echo "              → Item HARUS in_purchasing\n";

        // 1. Update item status
        if (!in_array($item->status, ['in_purchasing', 'in_release'])) {
            $item->update(['status' => 'in_purchasing']);
            $item->refresh();
            echo "  [FIXED] Item status → in_purchasing\n";
            $anyFixed = true;
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
            echo "  [CREATED] PurchasingItem baru (ID: {$pi->id})\n";
            $anyFixed = true;
        } else {
            echo "  [OK] PurchasingItem sudah ada (ID: {$pi->id}, status: {$pi->status})\n";
        }

    } else {
        echo "  Next step : #{$nextStep->step_number} [{$nextStep->step_phase}] {$nextStep->step_name}\n";
        echo "              → Step berikutnya adalah APPROVAL → item memang on progress, tidak perlu fix.\n";
    }

    echo "\n";
}

// Update approval_request status
$req->refresh()->load('items');
$anyInPurchasing = $req->items->contains(fn($i) => in_array($i->status, ['in_purchasing', 'in_release']));
$allApproved     = $req->items->every(fn($i) => $i->status === 'approved');
$anyRejected     = $req->items->contains(fn($i) => $i->status === 'rejected');

$newReqStatus = 'on progress';
if ($anyRejected)         { $newReqStatus = 'rejected'; }
elseif ($allApproved)     { $newReqStatus = 'approved'; }
elseif ($anyInPurchasing) { $newReqStatus = 'in_purchasing'; }

if ($req->status !== $newReqStatus) {
    $req->update(['status' => $newReqStatus]);
    echo "[FIXED] ApprovalRequest status → $newReqStatus\n\n";
    $anyFixed = true;
} else {
    echo "[OK] ApprovalRequest status sudah benar: {$req->status}\n\n";
}

echo "========================================================\n";
echo $anyFixed
    ? " SELESAI: Data berhasil diperbaiki!\n Silakan refresh /reports/approval-requests\n"
    : " Tidak ada perubahan yang diperlukan.\n";
echo "========================================================\n\n";
