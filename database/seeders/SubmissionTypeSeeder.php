<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubmissionType;

class SubmissionTypeSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['name' => 'Barang', 'code' => 'BRG', 'description' => 'Pengajuan pengadaan barang', 'is_active' => true],
            ['name' => 'Jasa', 'code' => 'JSA', 'description' => 'Pengajuan pengadaan jasa', 'is_active' => true],
        ];

        foreach ($data as $row) {
            SubmissionType::updateOrCreate(
                ['code' => $row['code']],
                $row
            );
        }
    }
}
