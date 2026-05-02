<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan nilai 'in_purchasing' ke check constraint status
     * di tabel approval_requests.
     */
    public function up(): void
    {
        // Drop constraint lama
        DB::statement('ALTER TABLE approval_requests DROP CONSTRAINT IF EXISTS approval_requests_status_check');

        // Buat constraint baru dengan tambahan 'in_purchasing'
        DB::statement("
            ALTER TABLE approval_requests
            ADD CONSTRAINT approval_requests_status_check
            CHECK (status IN (
                'pending',
                'on progress',
                'in_purchasing',
                'approved',
                'rejected',
                'cancelled'
            ))
        ");
    }

    public function down(): void
    {
        // Kembalikan constraint lama (tanpa in_purchasing)
        // Pastikan tidak ada data dengan status in_purchasing sebelum rollback
        DB::statement('ALTER TABLE approval_requests DROP CONSTRAINT IF EXISTS approval_requests_status_check');

        DB::statement("
            ALTER TABLE approval_requests
            ADD CONSTRAINT approval_requests_status_check
            CHECK (status IN (
                'pending',
                'on progress',
                'approved',
                'rejected',
                'cancelled'
            ))
        ");
    }
};
