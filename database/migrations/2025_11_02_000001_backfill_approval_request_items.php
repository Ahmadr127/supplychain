<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('approval_request_items')) {
            // Table must exist from previous migration
            return;
        }

        // Copy rows from pivot into approval_request_items if not exists
        DB::table('approval_request_master_items')
            ->orderBy('id')
            ->chunk(1000, function ($rows) {
                foreach ($rows as $row) {
                    $exists = DB::table('approval_request_items')
                        ->where('approval_request_id', $row->approval_request_id)
                        ->where('master_item_id', $row->master_item_id)
                        ->exists();
                    if ($exists) continue;

                    // Map status from parent request
                    $parent = DB::table('approval_requests')->where('id', $row->approval_request_id)->first();
                    $status = 'pending';
                    $approvedBy = null; $approvedAt = null; $rejectedReason = null;
                    if ($parent) {
                        switch ($parent->status) {
                            case 'approved':
                                $status = 'approved';
                                $approvedBy = $parent->approved_by; $approvedAt = $parent->approved_at;
                                break;
                            case 'rejected':
                                $status = 'rejected';
                                $rejectedReason = $parent->rejection_reason;
                                break;
                            case 'on progress':
                                $status = 'pending';
                                break;
                            case 'cancelled':
                                $status = 'cancelled';
                                break;
                            default:
                                $status = 'pending';
                        }
                    }

                    DB::table('approval_request_items')->insert([
                        'approval_request_id' => $row->approval_request_id,
                        'master_item_id' => $row->master_item_id,
                        'quantity' => $row->quantity,
                        'unit_price' => $row->unit_price,
                        'total_price' => $row->total_price,
                        'notes' => $row->notes,
                        'specification' => $row->specification ?? null,
                        'brand' => $row->brand ?? null,
                        'supplier_id' => $row->supplier_id ?? null,
                        'alternative_vendor' => $row->alternative_vendor ?? null,
                        'allocation_department_id' => $row->allocation_department_id ?? null,
                        'letter_number' => $row->letter_number ?? null,
                        'fs_document' => $row->fs_document ?? null,
                        'status' => $status,
                        'approved_by' => $approvedBy,
                        'approved_at' => $approvedAt,
                        'rejected_reason' => $rejectedReason,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        // No rollback for backfilled data to avoid data loss.
    }
};
