<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CapexIdNumber;
use App\Models\Department;
use App\Models\User;

class CapexIdNumberSeeder extends Seeder
{
    /**
     * Seed sample CapEx ID Numbers for testing.
     */
    public function run(): void
    {
        $year = date('Y');
        $adminUser = User::where('username', 'admin')->first();
        
        // Get departments for assignment
        $itDept = Department::where('code', 'IT')->first();
        $keuDept = Department::where('code', 'KEU')->first();
        $kepDept = Department::where('code', 'KEP')->first();
        $pgdDept = Department::where('code', 'PGD')->first();

        $capexData = [
            // IT Department CapEx
            [
                'capex_number' => "CAPEX-{$year}-001",
                'capex_year' => $year,
                'capex_category' => 'IT Infrastructure',
                'budget_amount' => 500000000, // 500 Juta
                'used_amount' => 150000000,   // 150 Juta used
                'status' => 'active',
                'description' => 'Anggaran pengadaan infrastruktur IT termasuk server, jaringan, dan perangkat keras',
                'department_id' => $itDept?->id,
                'created_by' => $adminUser?->id,
                'approved_by' => $adminUser?->id,
                'approved_at' => now()->subMonths(1),
                'valid_from' => now()->startOfYear(),
                'valid_until' => now()->endOfYear(),
            ],
            [
                'capex_number' => "CAPEX-{$year}-002",
                'capex_year' => $year,
                'capex_category' => 'IT Software',
                'budget_amount' => 200000000, // 200 Juta
                'used_amount' => 50000000,    // 50 Juta used
                'status' => 'active',
                'description' => 'Anggaran pengadaan software dan lisensi',
                'department_id' => $itDept?->id,
                'created_by' => $adminUser?->id,
                'approved_by' => $adminUser?->id,
                'approved_at' => now()->subMonths(1),
                'valid_from' => now()->startOfYear(),
                'valid_until' => now()->endOfYear(),
            ],
            
            // Keperawatan CapEx
            [
                'capex_number' => "CAPEX-{$year}-003",
                'capex_year' => $year,
                'capex_category' => 'Alat Medis',
                'budget_amount' => 1000000000, // 1 Miliar
                'used_amount' => 300000000,    // 300 Juta used
                'status' => 'active',
                'description' => 'Anggaran pengadaan alat-alat medis dan kesehatan',
                'department_id' => $kepDept?->id,
                'created_by' => $adminUser?->id,
                'approved_by' => $adminUser?->id,
                'approved_at' => now()->subMonths(2),
                'valid_from' => now()->startOfYear(),
                'valid_until' => now()->endOfYear(),
            ],
            [
                'capex_number' => "CAPEX-{$year}-004",
                'capex_year' => $year,
                'capex_category' => 'Furniture Medis',
                'budget_amount' => 300000000, // 300 Juta
                'used_amount' => 75000000,    // 75 Juta used
                'status' => 'active',
                'description' => 'Anggaran pengadaan bed, kursi roda, dan furniture medis',
                'department_id' => $kepDept?->id,
                'created_by' => $adminUser?->id,
                'approved_by' => $adminUser?->id,
                'approved_at' => now()->subMonths(2),
                'valid_from' => now()->startOfYear(),
                'valid_until' => now()->endOfYear(),
            ],
            
            // Keuangan CapEx (General)
            [
                'capex_number' => "CAPEX-{$year}-005",
                'capex_year' => $year,
                'capex_category' => 'Operasional Kantor',
                'budget_amount' => 150000000, // 150 Juta
                'used_amount' => 25000000,    // 25 Juta used
                'status' => 'active',
                'description' => 'Anggaran pengadaan peralatan kantor umum',
                'department_id' => $keuDept?->id,
                'created_by' => $adminUser?->id,
                'approved_by' => $adminUser?->id,
                'approved_at' => now()->subMonths(1),
                'valid_from' => now()->startOfYear(),
                'valid_until' => now()->endOfYear(),
            ],
            
            // Pengadaan CapEx
            [
                'capex_number' => "CAPEX-{$year}-006",
                'capex_year' => $year,
                'capex_category' => 'Kendaraan Operasional',
                'budget_amount' => 400000000, // 400 Juta
                'used_amount' => 0,           // Belum terpakai
                'status' => 'active',
                'description' => 'Anggaran pengadaan kendaraan operasional rumah sakit',
                'department_id' => $pgdDept?->id,
                'created_by' => $adminUser?->id,
                'approved_by' => $adminUser?->id,
                'approved_at' => now()->subWeeks(2),
                'valid_from' => now()->startOfYear(),
                'valid_until' => now()->endOfYear(),
            ],
            
            // Small budgets for testing
            [
                'capex_number' => "CAPEX-{$year}-007",
                'capex_year' => $year,
                'capex_category' => 'ATK & Supplies',
                'budget_amount' => 50000000,  // 50 Juta
                'used_amount' => 5000000,     // 5 Juta used
                'status' => 'active',
                'description' => 'Anggaran pengadaan ATK dan supplies kantor',
                'department_id' => $keuDept?->id,
                'created_by' => $adminUser?->id,
                'approved_by' => $adminUser?->id,
                'approved_at' => now()->subWeeks(1),
                'valid_from' => now()->startOfYear(),
                'valid_until' => now()->endOfYear(),
            ],
            [
                'capex_number' => "CAPEX-{$year}-008",
                'capex_year' => $year,
                'capex_category' => 'Peremajaan Komputer',
                'budget_amount' => 100000000, // 100 Juta
                'used_amount' => 20000000,    // 20 Juta used
                'status' => 'active',
                'description' => 'Anggaran khusus untuk peremajaan komputer dan laptop',
                'department_id' => $itDept?->id,
                'created_by' => $adminUser?->id,
                'approved_by' => $adminUser?->id,
                'approved_at' => now()->subWeeks(1),
                'valid_from' => now()->startOfYear(),
                'valid_until' => now()->endOfYear(),
            ],
        ];

        foreach ($capexData as $capex) {
            CapexIdNumber::updateOrCreate(
                ['capex_number' => $capex['capex_number']],
                $capex
            );
        }

        $this->command->info("✅ CapEx ID Numbers seeded: 8 records for year {$year}");
        
        // Show summary
        $this->command->table(
            ['CapEx Number', 'Category', 'Budget', 'Used', 'Remaining'],
            CapexIdNumber::where('capex_year', $year)->get()->map(function ($c) {
                return [
                    $c->capex_number,
                    $c->capex_category,
                    'Rp ' . number_format($c->budget_amount, 0, ',', '.'),
                    'Rp ' . number_format($c->used_amount, 0, ',', '.'),
                    'Rp ' . number_format($c->remaining_amount, 0, ',', '.'),
                ];
            })->toArray()
        );
    }
}
