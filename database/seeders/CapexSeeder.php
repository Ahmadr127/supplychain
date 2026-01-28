<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Capex;
use App\Models\CapexItem;
use App\Models\Department;

class CapexSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = (int) date('Y');
        
        // Get all departments (except Direktur)
        $departments = Department::where('code', '!=', 'DIR')->get();
        
        // Sample capex items per department
        $capexItemsData = [
            'IT' => [
                ['item_name' => 'Server Rack 42U', 'category' => 'Infrastructure', 'budget_amount' => 75000000],
                ['item_name' => 'UPS 10KVA', 'category' => 'Infrastructure', 'budget_amount' => 45000000],
                ['item_name' => 'Switch Managed 48 Port', 'category' => 'Network', 'budget_amount' => 25000000],
                ['item_name' => 'Firewall Appliance', 'category' => 'Security', 'budget_amount' => 85000000],
                ['item_name' => 'PC Desktop (10 unit)', 'category' => 'Hardware', 'budget_amount' => 120000000],
                ['item_name' => 'Laptop Staff (5 unit)', 'category' => 'Hardware', 'budget_amount' => 75000000],
                ['item_name' => 'License Microsoft 365', 'category' => 'Software', 'budget_amount' => 35000000],
                ['item_name' => 'NAS Storage 24TB', 'category' => 'Storage', 'budget_amount' => 55000000],
            ],
            'PGD' => [
                ['item_name' => 'Printer Multifungsi A3', 'category' => 'Office Equipment', 'budget_amount' => 25000000],
                ['item_name' => 'Mesin Fotocopy', 'category' => 'Office Equipment', 'budget_amount' => 85000000],
                ['item_name' => 'Komputer Kerja (3 unit)', 'category' => 'Hardware', 'budget_amount' => 36000000],
                ['item_name' => 'Scanner Dokumen', 'category' => 'Office Equipment', 'budget_amount' => 15000000],
                ['item_name' => 'Filling Cabinet (5 unit)', 'category' => 'Furniture', 'budget_amount' => 12500000],
            ],
            'KEU' => [
                ['item_name' => 'Mesin Hitung Uang', 'category' => 'Finance Equipment', 'budget_amount' => 8500000],
                ['item_name' => 'Brankas Besar', 'category' => 'Security', 'budget_amount' => 35000000],
                ['item_name' => 'Komputer Akuntansi (4 unit)', 'category' => 'Hardware', 'budget_amount' => 48000000],
                ['item_name' => 'Printer Khusus Slip', 'category' => 'Office Equipment', 'budget_amount' => 12000000],
                ['item_name' => 'Software Akuntansi', 'category' => 'Software', 'budget_amount' => 45000000],
                ['item_name' => 'UPS Komputer (4 unit)', 'category' => 'Infrastructure', 'budget_amount' => 8000000],
            ],
            'KEP' => [
                ['item_name' => 'Bed Pasien Electric (10 unit)', 'category' => 'Medical Equipment', 'budget_amount' => 350000000],
                ['item_name' => 'Monitor Pasien (5 unit)', 'category' => 'Medical Equipment', 'budget_amount' => 125000000],
                ['item_name' => 'Infusion Pump (10 unit)', 'category' => 'Medical Equipment', 'budget_amount' => 85000000],
                ['item_name' => 'Syringe Pump (10 unit)', 'category' => 'Medical Equipment', 'budget_amount' => 65000000],
                ['item_name' => 'Trolley Obat (5 unit)', 'category' => 'Furniture', 'budget_amount' => 25000000],
                ['item_name' => 'Kursi Roda (5 unit)', 'category' => 'Medical Equipment', 'budget_amount' => 15000000],
                ['item_name' => 'Stretcher (3 unit)', 'category' => 'Medical Equipment', 'budget_amount' => 45000000],
                ['item_name' => 'Nebulizer (10 unit)', 'category' => 'Medical Equipment', 'budget_amount' => 15000000],
                ['item_name' => 'Suction Pump (5 unit)', 'category' => 'Medical Equipment', 'budget_amount' => 35000000],
                ['item_name' => 'ECG Machine (2 unit)', 'category' => 'Medical Equipment', 'budget_amount' => 80000000],
            ],
        ];

        foreach ($departments as $department) {
            // Create Capex header for this department and year
            $capex = Capex::firstOrCreate(
                [
                    'department_id' => $department->id,
                    'fiscal_year' => $currentYear,
                ],
                [
                    'status' => 'active',
                    'notes' => "Anggaran CapEx {$department->name} Tahun {$currentYear}",
                    'created_by' => 1, // Admin
                ]
            );

            // Create capex items if department has data
            if (isset($capexItemsData[$department->code])) {
                $sequence = 1;
                foreach ($capexItemsData[$department->code] as $itemData) {
                    // Generate unique CapEx ID Number
                    $capexIdNumber = sprintf('CAPEX-%s-%d-%03d', $department->code, $currentYear, $sequence);
                    
                    CapexItem::firstOrCreate(
                        ['capex_id_number' => $capexIdNumber],
                        [
                            'capex_id' => $capex->id,
                            'item_name' => $itemData['item_name'],
                            'description' => "Pengadaan {$itemData['item_name']} untuk {$department->name}",
                            'category' => $itemData['category'],
                            'budget_amount' => $itemData['budget_amount'],
                            'used_amount' => 0,
                            'status' => 'available',
                        ]
                    );
                    
                    $sequence++;
                }
            }
        }

        $this->command->info('CapEx data seeded successfully for all departments!');
    }
}
