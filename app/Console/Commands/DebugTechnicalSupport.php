<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ApprovalRequestItem;

class DebugTechnicalSupport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ts:debug {id : ID dari ApprovalRequestItem} {--spec= : Isi spesifikasi baru jika ingin mengupdate langsung}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug dan update spesifikasi Technical Support untuk item tertentu lewat CLI/Terminal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        $spec = $this->option('spec');

        $item = ApprovalRequestItem::with(['approvalRequest.requester', 'masterItem.itemCategory', 'tsCategory'])
            ->find($id);

        if (!$item) {
            $this->error("Item dengan ID {$id} tidak ditemukan.");
            return Command::FAILURE;
        }

        $this->info("=== INFO ITEM TECHNICAL SUPPORT ===");
        $this->line("Item ID          : " . $item->id);
        $this->line("Request Number   : " . ($item->approvalRequest->request_number ?? '-'));
        $this->line("Requester        : " . ($item->approvalRequest->requester->name ?? '-'));
        $this->line("Nama Barang      : " . ($item->masterItem->name ?? '-'));
        $this->line("Needs TS         : " . ($item->needs_ts ? 'YA' : 'TIDAK'));
        $this->line("Status TS        : " . strtoupper($item->ts_status ?? 'pending'));
        $this->line("Kategori TS      : " . ($item->tsCategory->name ?? '-'));
        $this->line("Spesifikasi Awal : " . ($item->specification ?? '-'));
        $this->line("Spesifikasi TS   : " . ($item->ts_specification ?? '-'));
        $this->line("===================================");

        if ($spec !== null) {
            if (empty($spec)) {
                $this->error("Error: Spesifikasi baru tidak boleh kosong jika menggunakan opsi --spec.");
                return Command::FAILURE;
            }

            if (!$item->needs_ts) {
                $this->error("Error: Item ini tidak dikonfigurasi untuk membutuhkan spesifikasi Technical Support.");
                return Command::FAILURE;
            }

            $this->warn("Sedang mengupdate spesifikasi...");
            $item->update([
                'ts_specification' => $spec,
                'ts_status' => 'done',
            ]);

            $this->info("✅ Berhasil mengupdate spesifikasi TS!");
            $this->line("Spesifikasi Baru: " . $spec);
            
            // Reload and verify
            $item->refresh();
            $this->line("Status TS Terbaru: " . strtoupper($item->ts_status));
        } else {
            $this->info("Gunakan opsi --spec=\"spesifikasi baru\" untuk memperbarui spesifikasi langsung dari terminal.");
            $this->line("Contoh: php artisan ts:debug {$id} --spec=\"Processor Intel Core i5, RAM 16GB\"");
        }

        return Command::SUCCESS;
    }
}
