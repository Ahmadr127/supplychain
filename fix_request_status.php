<?php

/**
 * fix_request_status.php
 * 
 * Jalankan dengan:
 *   php artisan tinker --execute="require base_path('fix_request_status.php');"
 * 
 * Script ini memperbaiki approval_requests yang status-nya salah:
 *   - Item sudah in_purchasing / in_release  → request harusnya 'in_purchasing'
 *   - Semua item approved                    → request harusnya 'approved'
 * 
 * Mode:
 *   DRY_RUN = true  → hanya tampilkan data yang perlu difix (tidak ubah DB)
 *   DRY_RUN = false → eksekusi perubahan ke DB
 */

use App\Models\ApprovalRequest;

$DRY_RUN = false; // Ganti ke false untuk eksekusi nyata

echo "\n";
echo "========================================\n";
echo " FIX APPROVAL REQUEST STATUS\n";
echo " Mode: " . ($DRY_RUN ? "DRY RUN (tidak mengubah DB)" : "EKSEKUSI NYATA") . "\n";
echo "========================================\n\n";

$requests = ApprovalRequest::whereIn('status', ['on progress', 'approved'])
    ->with('items')
    ->get();

echo "Total request ditemukan: " . $requests->count() . "\n\n";

$fixed     = 0;
$skipped   = 0;
$noItems   = 0;

foreach ($requests as $req) {
    $items = $req->items;

    if ($items->isEmpty()) {
        $noItems++;
        continue;
    }

    $allApproved     = $items->every(fn($item) => $item->status === 'approved');
    $anyRejected     = $items->contains(fn($item) => $item->status === 'rejected');
    $anyInPurchasing = $items->contains(fn($item) => in_array($item->status, ['in_purchasing', 'in_release']));

    $correctStatus = 'on progress';

    if ($anyRejected) {
        $correctStatus = 'rejected';
    } elseif ($allApproved) {
        $correctStatus = 'approved';
    } elseif ($anyInPurchasing && !$anyRejected) {
        $correctStatus = 'in_purchasing';
    }

    if ($req->status === $correctStatus) {
        $skipped++;
        continue;
    }

    // Tampilkan info
    $itemSummary = $items->map(fn($i) => $i->status)->unique()->implode(', ');
    echo sprintf(
        "[%s] Request #%d (%s):\n  Status saat ini : %s\n  Status benar    : %s\n  Item statuses   : %s\n\n",
        $DRY_RUN ? 'DRY RUN' : 'FIXED',
        $req->id,
        $req->request_number ?? '-',
        $req->status,
        $correctStatus,
        $itemSummary
    );

    if (!$DRY_RUN) {
        $req->update(['status' => $correctStatus]);
    }

    $fixed++;
}

echo "----------------------------------------\n";
echo "Perlu difix  : $fixed\n";
echo "Sudah benar  : $skipped\n";
echo "Tanpa item   : $noItems\n";
echo "----------------------------------------\n";

if ($DRY_RUN && $fixed > 0) {
    echo "\n[!] Ini hanya DRY RUN.\n";
    echo "    Ubah \$DRY_RUN = false; lalu jalankan ulang untuk eksekusi.\n";
} elseif (!$DRY_RUN) {
    echo "\n[OK] Selesai. $fixed request berhasil diperbaiki.\n";
} else {
    echo "\n[OK] Tidak ada data yang perlu difix.\n";
}

echo "\n";
