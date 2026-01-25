<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProcurementType;

class ProcurementTypeSeeder extends Seeder
{
    /**
     * Seed procurement types:
     * - BARANG_BARU: Pengadaan Barang Baru
     * - PEREMAJAAN: Peremajaan/Renewal
     */
    public function run(): void
    {
        $types = [
            [
                'name' => 'Pengadaan Barang Baru',
                'code' => 'BARANG_BARU',
                'description' => 'Pengadaan barang atau aset baru yang belum pernah dimiliki sebelumnya. Memerlukan justifikasi kebutuhan dan approval yang lebih ketat.',
                'is_active' => true,
            ],
            [
                'name' => 'Peremajaan',
                'code' => 'PEREMAJAAN',
                'description' => 'Penggantian atau pembaharuan barang/aset yang sudah ada (existing). Proses approval lebih sederhana karena sudah ada aset sebelumnya.',
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            ProcurementType::updateOrCreate(
                ['code' => $type['code']],
                $type
            );
        }

        $this->command->info('✅ Procurement types seeded: BARANG_BARU, PEREMAJAAN');
    }
}
