<?php
/**
 * Script untuk mendeteksi purchasing_items yang terlanjur 'done' 
 * padahal invoice_number atau grn_date belum diisi (akibat bug di ReleaseApiController).
 *
 * Jalankan: php fix_done_without_invoice.php
 * Untuk fix otomatis: php fix_done_without_invoice.php --fix
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PurchasingItem;
use App\Models\ApprovalRequestItem;

$doFix = in_array('--fix', $argv ?? []);

// Cari item yang status 'done' tapi invoice_number atau grn_date kosong
$brokenItems = PurchasingItem::where('status', 'done')
    ->where(function ($q) {
        $q->whereNull('invoice_number')
          ->orWhereNull('grn_date');
    })
    ->get();

if ($brokenItems->isEmpty()) {
    echo "✅ Tidak ada item bermasalah ditemukan.\n";
    exit(0);
}

echo "⚠️  Ditemukan {$brokenItems->count()} item 'done' tanpa invoice/GRN:\n\n";

foreach ($brokenItems as $item) {
    echo "  ID: {$item->id} | AR: {$item->approval_request_id} | Item: {$item->master_item_id}\n";
    echo "    Status: {$item->status} | Invoice: " . ($item->invoice_number ?? 'NULL') . " | GRN: " . ($item->grn_date ?? 'NULL') . "\n";

    if ($doFix) {
        // Reset ke po_issued (asumsi PO sudah ada)
        $newStatus = !empty($item->po_number) ? 'po_issued' : 'selected';
        $item->update([
            'status' => $newStatus,
            'status_changed_at' => now(),
        ]);

        // Pastikan approval_request_item juga kembali ke in_release
        $ari = ApprovalRequestItem::where('approval_request_id', $item->approval_request_id)
            ->where('master_item_id', $item->master_item_id)
            ->first();

        if ($ari && $ari->status === 'approved') {
            // Cek apakah semua release steps sudah approved
            $releaseStepsApproved = \App\Models\ApprovalItemStep::where('approval_request_id', $item->approval_request_id)
                ->where('master_item_id', $item->master_item_id)
                ->where('step_phase', 'release')
                ->where('status', 'approved')
                ->count();
            
            $releaseStepsTotal = \App\Models\ApprovalItemStep::where('approval_request_id', $item->approval_request_id)
                ->where('master_item_id', $item->master_item_id)
                ->where('step_phase', 'release')
                ->count();
            
            // Approval request item tetap 'approved' karena release steps memang sudah selesai.
            // Purchasing item cukup di-reset ke po_issued agar bisa input invoice.
        }

        echo "    → ✅ Reset ke '{$newStatus}'\n";
    }
    echo "\n";
}

if (!$doFix) {
    echo "ℹ️  Untuk memperbaiki otomatis, jalankan: php fix_done_without_invoice.php --fix\n";
}
