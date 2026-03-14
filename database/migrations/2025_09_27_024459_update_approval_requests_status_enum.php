<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop existing check constraint if any, then alter column for PostgreSQL
        DB::statement("ALTER TABLE approval_requests DROP CONSTRAINT IF EXISTS approval_requests_status_check");
        DB::statement("ALTER TABLE approval_requests ALTER COLUMN status TYPE VARCHAR(255)");
        DB::statement("ALTER TABLE approval_requests ADD CONSTRAINT approval_requests_status_check CHECK (status IN ('pending', 'on progress', 'approved', 'rejected', 'cancelled'))");
        DB::statement("ALTER TABLE approval_requests ALTER COLUMN status SET DEFAULT 'on progress'");
        DB::statement("ALTER TABLE approval_requests ALTER COLUMN status SET NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE approval_requests DROP CONSTRAINT IF EXISTS approval_requests_status_check");
        DB::statement("ALTER TABLE approval_requests ADD CONSTRAINT approval_requests_status_check CHECK (status IN ('pending', 'approved', 'rejected', 'cancelled'))");
        DB::statement("ALTER TABLE approval_requests ALTER COLUMN status SET DEFAULT 'pending'");
    }
};
