<?php

/**
 * debug_pnm5.php
 * Debug kenapa PNM-5 masih tampil "Menunggu Approval" di laporan
 *
 * Jalankan:
 *   php artisan tinker --execute="require base_path('debug_pnm5.php');"
 */

use App\Models\ApprovalRequest;
use App\Models\ApprovalItemStep;

$REQUEST_NUMBER = 'PNM-5'; // Ganti jika perlu

echo "\n========================================================\n";
echo " DEBUG REQUEST: $REQUEST_NUMBER\n";
echo "========================================================\n\n";

// 1. Cari request
$req = ApprovalRequest::where('request_number', $REQUEST_NUMBER)
    ->with(['items.masterItem', 'purchasingItems'])
    ->first();

if (!$req) {
    echo "[ERROR] Request '$REQUEST_NUMBER' tidak ditemukan!\n\n";
    exit;
}

// ── APPROVAL REQUEST
echo "=== APPROVAL REQUEST ===\n";
echo "  ID               : {$req->id}\n";
echo "  Request Number   : {$req->request_number}\n";
echo "  Status DB        : {$req->status}\n";
echo "  Purchasing Status: " . ($req->purchasing_status ?? 'NULL') . "\n";
echo "  Workflow ID      : {$req->workflow_id}\n";
echo "  Created At       : {$req->created_at}\n\n";

// ── ITEMS
echo "=== APPROVAL REQUEST ITEMS (" . $req->items->count() . " item) ===\n\n";

foreach ($req->items as $idx => $item) {
    $itemNo = $idx + 1;
    $masterName = optional($item->masterItem)->name ?? '(null)';

    echo "--- Item #$itemNo ---\n";
    echo "  item_id       : {$item->id}\n";
    echo "  master_item   : $masterName (ID: {$item->master_item_id})\n";
    echo "  status        : {$item->status}\n";
    echo "  unit_price    : " . ($item->unit_price ?? 'NULL') . "\n";
    echo "  total_price   : " . ($item->total_price ?? 'NULL') . "\n";
    echo "  approved_by   : " . ($item->approved_by ?? 'NULL') . "\n\n";

    // STEPS
    $steps = ApprovalItemStep::where('approval_request_id', $req->id)
        ->where('approval_request_item_id', $item->id)
        ->orderBy('step_number')
        ->get();

    echo "  STEPS (" . $steps->count() . " step):\n";
    if ($steps->isEmpty()) {
        echo "    (tidak ada steps)\n";
    }
    foreach ($steps as $step) {
        $icon = ($step->status === 'approved') ? '[OK]'
              : (($step->status === 'pending')  ? '[PENDING]'
              : (($step->status === 'skipped')  ? '[SKIP]'
              : (($step->status === 'rejected') ? '[REJECT]' : '[' . strtoupper($step->status) . ']')));
        echo "    $icon Step #{$step->step_number} | phase={$step->step_phase} | type={$step->step_type} | {$step->step_name}\n";
        echo "         status={$step->status} | approver=" . ($step->approved_by ?? 'NULL') . " | action=" . ($step->required_action ?? '-') . "\n";
    }

    // PURCHASING ITEM
    $pi = $req->purchasingItems->firstWhere('master_item_id', $item->master_item_id);
    echo "\n  PURCHASING ITEM:\n";
    if ($pi) {
        echo "    PI ID     : {$pi->id}\n";
        echo "    PI Status : {$pi->status}\n";
        echo "    PO Number : " . ($pi->po_number ?? 'NULL') . "\n";
        echo "    GRN Date  : " . ($pi->grn_date ?? 'NULL') . "\n";
    } else {
        echo "    >> TIDAK ADA purchasing item untuk item ini!\n";
    }
    echo "\n";
}

// ── DIAGNOSIS (logika sama persis dengan ReportController)
echo "=== DIAGNOSIS (simulasi ReportController) ===\n\n";

foreach ($req->items as $item) {
    $masterName = optional($item->masterItem)->name ?? '-';
    $pi = $req->purchasingItems->firstWhere('master_item_id', $item->master_item_id);

    if ($pi) {
        $code = $pi->status;
    } elseif (in_array($item->status, ['in_purchasing', 'approved', 'in_release'])) {
        $code = $item->status;
    } else {
        $code = 'pending_approval';
    }

    $labels = [
        'pending_approval' => 'Menunggu Approval  [MASALAH]',
        'in_purchasing'    => 'Menunggu Proses    [OK]',
        'approved'         => 'Menunggu Proses    [OK]',
        'in_release'       => 'Menunggu Proses    [OK]',
        'unprocessed'      => 'Belum diproses     [OK]',
        'benchmarking'     => 'Pemilihan vendor   [OK]',
        'selected'         => 'Proses PR & PO     [OK]',
        'po_issued'        => 'Proses di vendor   [OK]',
        'grn_received'     => 'Barang diterima    [OK]',
        'done'             => 'Selesai            [OK]',
    ];
    $label = $labels[$code] ?? strtoupper($code);

    echo "  Item #{$item->id} ($masterName)\n";
    echo "    item->status          : {$item->status}\n";
    echo "    PI exists             : " . ($pi ? "YA (PI status: {$pi->status})" : "TIDAK") . "\n";
    echo "    process_code (report) : $code\n";
    echo "    Tampil di laporan     : $label\n";

    if ($code === 'pending_approval') {
        echo "\n    >> KENAPA pending_approval?\n";

        $pendingSteps = ApprovalItemStep::where('approval_request_id', $req->id)
            ->where('approval_request_item_id', $item->id)
            ->where('status', 'pending')
            ->get();

        if ($pendingSteps->isNotEmpty()) {
            echo "       Ada " . $pendingSteps->count() . " step yang masih PENDING:\n";
            foreach ($pendingSteps as $s) {
                echo "       - Step #{$s->step_number} [{$s->step_phase}] {$s->step_name} (type:{$s->step_type})\n";
            }
        } else {
            echo "       Tidak ada step pending ditemukan.\n";
        }

        $notDone = ApprovalItemStep::where('approval_request_id', $req->id)
            ->where('approval_request_item_id', $item->id)
            ->whereNotIn('status', ['approved', 'skipped'])
            ->count();
        echo "       Steps belum done/skip : $notDone\n";
        echo "       item->status adalah   : '{$item->status}'\n";
        echo "       (Harus 'in_purchasing'/'approved'/'in_release' agar masuk purchasing)\n";
    }
    echo "\n";
}

// ── SARAN FIX
echo "=== SARAN TINDAKAN ===\n\n";

$needFix = false;
foreach ($req->items as $item) {
    $pi = $req->purchasingItems->firstWhere('master_item_id', $item->master_item_id);

    $openApprovalSteps = ApprovalItemStep::where('approval_request_id', $req->id)
        ->where('approval_request_item_id', $item->id)
        ->where('step_phase', 'approval')
        ->whereNotIn('status', ['approved', 'skipped'])
        ->count();

    $hasPurchasingOrReleaseSteps = ApprovalItemStep::where('approval_request_id', $req->id)
        ->where('approval_request_item_id', $item->id)
        ->whereIn('step_phase', ['purchasing', 'release'])
        ->exists();

    if ($openApprovalSteps === 0
        && $hasPurchasingOrReleaseSteps
        && !in_array($item->status, ['in_purchasing', 'in_release', 'approved'])
    ) {
        echo "  [!] Item #{$item->id}: semua approval SELESAI tapi status masih '{$item->status}'\n";
        echo "      Solusi: update item status ke 'in_purchasing'\n";
        echo "      Command: App\Models\ApprovalRequestItem::find({$item->id})->update(['status' => 'in_purchasing']);\n\n";
        $needFix = true;
    }

    if ($openApprovalSteps === 0
        && !$hasPurchasingOrReleaseSteps
        && !in_array($item->status, ['approved', 'rejected', 'cancelled'])
    ) {
        echo "  [!] Item #{$item->id}: workflow lama, semua step OK tapi status '{$item->status}'\n";
        echo "      Solusi: update item status ke 'approved'\n";
        echo "      Command: App\Models\ApprovalRequestItem::find({$item->id})->update(['status' => 'approved']);\n\n";
        $needFix = true;
    }

    // Cek apakah PI seharusnya ada tapi tidak ada
    if (in_array($item->status, ['in_purchasing']) && !$pi) {
        echo "  [!] Item #{$item->id}: status 'in_purchasing' tapi TIDAK ADA PurchasingItem!\n";
        echo "      Solusi: buat purchasing item manual\n";
        echo "      Command: App\Models\PurchasingItem::firstOrCreate(['approval_request_id'=>{$req->id},'master_item_id'=>{$item->master_item_id}],['quantity'=>{$item->quantity},'status'=>'unprocessed']);\n\n";
        $needFix = true;
    }
}

if (!$needFix) {
    echo "  [OK] Tidak ada masalah terdeteksi.\n";
    echo "       Jika masih tampil salah, coba: php artisan view:clear\n";
}

echo "\n========================================================\n";
echo " SELESAI\n";
echo "========================================================\n\n";
