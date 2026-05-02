<?php

/**
 * debug_pnm5.php
 * Debug kenapa PNM-5 masih tampil "Menunggu Approval" di laporan
 *
 * Jalankan:
 *   php artisan tinker --execute="require base_path('debug_pnm5.php');"
 */

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestItem;
use App\Models\ApprovalItemStep;
use App\Models\PurchasingItem;

$REQUEST_NUMBER = 'PNM-5'; // Ganti jika perlu

echo "\n";
echo "========================================================\n";
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

// ── APPROVAL REQUEST ──────────────────────────────────────
echo "┌─ APPROVAL REQUEST\n";
echo "│  ID             : {$req->id}\n";
echo "│  Request Number : {$req->request_number}\n";
echo "│  Status         : {$req->status}\n";
echo "│  Purchasing Status: " . ($req->purchasing_status ?? 'NULL') . "\n";
echo "│  Workflow ID    : {$req->workflow_id}\n";
echo "│  Created At     : {$req->created_at}\n";
echo "└────────────────────────────────────────────────────\n\n";

// ── ITEMS ──────────────────────────────────────────────────
echo "┌─ APPROVAL REQUEST ITEMS (" . $req->items->count() . " item)\n";
foreach ($req->items as $idx => $item) {
    $itemNo = $idx + 1;
    echo "│\n";
    echo "│  [$itemNo] Item ID        : {$item->id}\n";
    echo "│      Master Item    : " . ($item->masterItem->name ?? '(null)') . " (ID: {$item->master_item_id})\n";
    echo "│      Status         : {$item->status}\n";
    echo "│      Unit Price     : " . ($item->unit_price ?? 'NULL') . "\n";
    echo "│      Total Price    : " . ($item->total_price ?? 'NULL') . "\n";
    echo "│      Approved By    : " . ($item->approved_by ?? 'NULL') . "\n";
    echo "│      Approved At    : " . ($item->approved_at ?? 'NULL') . "\n";

    // ── STEPS untuk item ini ──
    $steps = ApprovalItemStep::where('approval_request_id', $req->id)
        ->where('approval_request_item_id', $item->id)
        ->orderBy('step_number')
        ->get();

    echo "│\n";
    echo "│      STEPS (" . $steps->count() . " step):\n";
    if ($steps->isEmpty()) {
        echo "│        (tidak ada steps)\n";
    }
    foreach ($steps as $step) {
        $icon = match($step->status) {
            'approved'        => '✅',
            'pending'         => '⏳',
            'pending_purchase'=> '🛒',
            'skipped'         => '⏭️',
            'rejected'        => '❌',
            default           => '❓',
        };
        echo "│        {$icon} Step #{$step->step_number} [{$step->step_phase ?? 'approval'}] {$step->step_name}\n";
        echo "│              Status    : {$step->status}\n";
        echo "│              Type      : {$step->step_type}\n";
        echo "│              Action    : " . ($step->required_action ?? '-') . "\n";
        echo "│              Approver  : " . ($step->approved_by ?? 'NULL') . "\n";
        echo "│              Phase     : " . ($step->step_phase ?? 'NULL') . "\n";
    }

    // ── PURCHASING ITEM untuk item ini ──
    $pi = $req->purchasingItems->firstWhere('master_item_id', $item->master_item_id);
    echo "│\n";
    if ($pi) {
        echo "│      PURCHASING ITEM:\n";
        echo "│        PI ID     : {$pi->id}\n";
        echo "│        PI Status : {$pi->status}\n";
        echo "│        PO Number : " . ($pi->po_number ?? 'NULL') . "\n";
        echo "│        GRN Date  : " . ($pi->grn_date ?? 'NULL') . "\n";
    } else {
        echo "│      PURCHASING ITEM: ❌ TIDAK ADA\n";
    }
}

echo "│\n";
echo "└────────────────────────────────────────────────────\n\n";

// ── DIAGNOSIS ──────────────────────────────────────────────
echo "┌─ DIAGNOSIS\n";
foreach ($req->items as $item) {
    $pi = $req->purchasingItems->firstWhere('master_item_id', $item->master_item_id);

    // Logika yg sama dengan ReportController
    if ($pi) {
        $code = $pi->status;
    } elseif (in_array($item->status, ['in_purchasing', 'approved', 'in_release'])) {
        $code = $item->status; // Setelah fix: tampil 'Menunggu Proses'
    } else {
        $code = 'pending_approval'; // Masih menunggu approval
    }

    $label = match($code) {
        'pending_approval' => 'Menunggu Approval  ← MASALAH: belum masuk purchasing',
        'in_purchasing'    => 'Menunggu Proses    ← OK: approval selesai, belum ada PI',
        'approved'         => 'Menunggu Proses    ← OK: approved tapi belum ada PI',
        'in_release'       => 'Menunggu Proses    ← OK: dalam release phase',
        'unprocessed'      => 'Belum diproses     ← OK: PI ada, belum diproses',
        'benchmarking'     => 'Pemilihan vendor   ← OK: benchmarking',
        'selected'         => 'Proses PR & PO     ← OK',
        'po_issued'        => 'Proses di vendor   ← OK',
        'grn_received'     => 'Barang diterima    ← OK',
        'done'             => 'Selesai            ← OK',
        default            => strtoupper($code),
    };

    echo "│  Item #{$item->id} ({$item->masterItem->name ?? '-'})\n";
    echo "│    item->status           : {$item->status}\n";
    echo "│    PI exists              : " . ($pi ? "YA (status: {$pi->status})" : "TIDAK") . "\n";
    echo "│    process_code (report)  : $code\n";
    echo "│    Ditampilkan sebagai    : $label\n";
    echo "│\n";

    // Cek kenapa mungkin masih pending_approval
    if ($code === 'pending_approval') {
        echo "│    ⚠️  KENAPA pending_approval?\n";

        $pendingSteps = ApprovalItemStep::where('approval_request_id', $req->id)
            ->where('approval_request_item_id', $item->id)
            ->where('status', 'pending')
            ->get();

        if ($pendingSteps->isNotEmpty()) {
            echo "│      → Ada " . $pendingSteps->count() . " step yang masih PENDING:\n";
            foreach ($pendingSteps as $s) {
                echo "│        - Step #{$s->step_number} [{$s->step_phase ?? 'approval'}] {$s->step_name} (type: {$s->step_type})\n";
            }
        }

        $allStepsApproved = ApprovalItemStep::where('approval_request_id', $req->id)
            ->where('approval_request_item_id', $item->id)
            ->whereNotIn('status', ['approved', 'skipped'])
            ->count();

        echo "│      → Steps belum approved/skipped: $allStepsApproved\n";
        echo "│      → item->status adalah: '{$item->status}'\n";
        echo "│        (Harus 'in_purchasing', 'approved', atau 'in_release' agar masuk purchasing)\n";
    }
}

echo "└────────────────────────────────────────────────────\n\n";

// ── SARAN FIX ──────────────────────────────────────────────
echo "┌─ SARAN TINDAKAN\n";

$needFix = false;
foreach ($req->items as $item) {
    $pi = $req->purchasingItems->firstWhere('master_item_id', $item->master_item_id);

    // Cek: semua approval step sudah done, tapi item status masih on progress
    $openApprovalSteps = ApprovalItemStep::where('approval_request_id', $req->id)
        ->where('approval_request_item_id', $item->id)
        ->where('step_phase', 'approval')
        ->whereNotIn('status', ['approved', 'skipped'])
        ->count();

    $hasPurchasingSteps = ApprovalItemStep::where('approval_request_id', $req->id)
        ->where('approval_request_item_id', $item->id)
        ->whereIn('step_phase', ['purchasing', 'release'])
        ->exists();

    if ($openApprovalSteps === 0 && $hasPurchasingSteps && !in_array($item->status, ['in_purchasing', 'in_release', 'approved'])) {
        echo "│  ⚠️  Item #{$item->id}: semua approval step SELESAI tapi status masih '{$item->status}'\n";
        echo "│     → Perlu difix: update status ke 'in_purchasing'\n";
        $needFix = true;
    }

    if ($openApprovalSteps === 0 && !$hasPurchasingSteps && $item->status === 'on progress') {
        echo "│  ⚠️  Item #{$item->id}: workflow lama (no release steps), status masih '{$item->status}'\n";
        echo "│     → Perlu difix: update status ke 'approved'\n";
        $needFix = true;
    }
}

if (!$needFix) {
    echo "│  ✅ Tidak ada masalah yang terdeteksi dari sisi step.\n";
    echo "│     Jika masih tampil salah, coba clear cache:\n";
    echo "│       php artisan view:clear\n";
}

echo "└────────────────────────────────────────────────────\n\n";
