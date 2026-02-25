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
        // Fields: item_name, category, budget_amount, capex_type, priority_scale, month, amount_per_year, pic
        $capexItemsData = [
            'IT' => [
                ['item_name' => 'Server Rack 42U',           'category' => 'I-IT',  'budget_amount' => 75000000,  'capex_type' => 'New',         'priority_scale' => 1, 'month' => 'March',    'amount_per_year' => 75000000,  'pic' => 'Kepala IT'],
                ['item_name' => 'UPS 10KVA',                 'category' => 'I-IT',  'budget_amount' => 45000000,  'capex_type' => 'Replacement',  'priority_scale' => 1, 'month' => 'January',  'amount_per_year' => 45000000,  'pic' => 'Kepala IT'],
                ['item_name' => 'Switch Managed 48 Port',    'category' => 'I-IT',  'budget_amount' => 25000000,  'capex_type' => 'Replacement',  'priority_scale' => 2, 'month' => 'April',    'amount_per_year' => 25000000,  'pic' => 'Staff IT'],
                ['item_name' => 'Firewall Appliance',        'category' => 'I-IT',  'budget_amount' => 85000000,  'capex_type' => 'New',         'priority_scale' => 1, 'month' => 'February', 'amount_per_year' => 85000000,  'pic' => 'Kepala IT'],
                ['item_name' => 'PC Desktop (10 unit)',      'category' => 'I-IT',  'budget_amount' => 120000000, 'capex_type' => 'Replacement',  'priority_scale' => 2, 'month' => 'June',     'amount_per_year' => 120000000, 'pic' => 'Staff IT'],
                ['item_name' => 'Laptop Staff (5 unit)',     'category' => 'I-IT',  'budget_amount' => 75000000,  'capex_type' => 'New',         'priority_scale' => 2, 'month' => 'July',     'amount_per_year' => 75000000,  'pic' => 'Staff IT'],
                ['item_name' => 'License Microsoft 365',     'category' => 'I-IT',  'budget_amount' => 35000000,  'capex_type' => 'Replacement',  'priority_scale' => 3, 'month' => 'January',  'amount_per_year' => 35000000,  'pic' => 'Kepala IT'],
                ['item_name' => 'NAS Storage 24TB',          'category' => 'I-IT',  'budget_amount' => 55000000,  'capex_type' => 'New',         'priority_scale' => 2, 'month' => 'May',      'amount_per_year' => 55000000,  'pic' => 'Staff IT'],
            ],
            'PGD' => [
                ['item_name' => 'Printer Multifungsi A3',    'category' => 'I-PGD', 'budget_amount' => 25000000,  'capex_type' => 'Replacement',  'priority_scale' => 2, 'month' => 'March',    'amount_per_year' => 25000000,  'pic' => 'Kepala Pengadaan'],
                ['item_name' => 'Mesin Fotocopy',            'category' => 'I-PGD', 'budget_amount' => 85000000,  'capex_type' => 'Replacement',  'priority_scale' => 1, 'month' => 'February', 'amount_per_year' => 85000000,  'pic' => 'Kepala Pengadaan'],
                ['item_name' => 'Komputer Kerja (3 unit)',   'category' => 'I-PGD', 'budget_amount' => 36000000,  'capex_type' => 'New',         'priority_scale' => 2, 'month' => 'April',    'amount_per_year' => 36000000,  'pic' => 'Staff Pengadaan'],
                ['item_name' => 'Scanner Dokumen',           'category' => 'I-PGD', 'budget_amount' => 15000000,  'capex_type' => 'New',         'priority_scale' => 3, 'month' => 'June',     'amount_per_year' => 15000000,  'pic' => 'Staff Pengadaan'],
                ['item_name' => 'Filling Cabinet (5 unit)',  'category' => 'I-PGD', 'budget_amount' => 12500000,  'capex_type' => 'New',         'priority_scale' => 3, 'month' => 'August',   'amount_per_year' => 12500000,  'pic' => 'Staff Pengadaan'],
            ],
            'KEU' => [
                ['item_name' => 'Mesin Hitung Uang',         'category' => 'I-KEU', 'budget_amount' => 8500000,   'capex_type' => 'Replacement',  'priority_scale' => 2, 'month' => 'January',  'amount_per_year' => 8500000,   'pic' => 'Kepala Keuangan'],
                ['item_name' => 'Brankas Besar',             'category' => 'I-KEU', 'budget_amount' => 35000000,  'capex_type' => 'New',         'priority_scale' => 1, 'month' => 'March',    'amount_per_year' => 35000000,  'pic' => 'Kepala Keuangan'],
                ['item_name' => 'Komputer Akuntansi (4 unit)', 'category' => 'I-KEU', 'budget_amount' => 48000000, 'capex_type' => 'Replacement', 'priority_scale' => 2, 'month' => 'April',  'amount_per_year' => 48000000,  'pic' => 'Staff Keuangan'],
                ['item_name' => 'Printer Khusus Slip',       'category' => 'I-KEU', 'budget_amount' => 12000000,  'capex_type' => 'Replacement',  'priority_scale' => 3, 'month' => 'May',      'amount_per_year' => 12000000,  'pic' => 'Staff Keuangan'],
                ['item_name' => 'Software Akuntansi',        'category' => 'I-KEU', 'budget_amount' => 45000000,  'capex_type' => 'New',         'priority_scale' => 1, 'month' => 'January',  'amount_per_year' => 45000000,  'pic' => 'Kepala Keuangan'],
                ['item_name' => 'UPS Komputer (4 unit)',     'category' => 'I-KEU', 'budget_amount' => 8000000,   'capex_type' => 'New',         'priority_scale' => 3, 'month' => 'June',     'amount_per_year' => 8000000,   'pic' => 'Staff Keuangan'],
            ],
            'KEP' => [
                ['item_name' => 'Bed Pasien Electric (10 unit)', 'category' => 'I-RI', 'budget_amount' => 350000000, 'capex_type' => 'Replacement', 'priority_scale' => 1, 'month' => 'January', 'amount_per_year' => 350000000, 'pic' => 'Kepala Keperawatan'],
                ['item_name' => 'Monitor Pasien (5 unit)',   'category' => 'I-RI', 'budget_amount' => 125000000, 'capex_type' => 'New',          'priority_scale' => 1, 'month' => 'February', 'amount_per_year' => 125000000, 'pic' => 'Kepala Keperawatan'],
                ['item_name' => 'Infusion Pump (10 unit)',   'category' => 'I-RI', 'budget_amount' => 85000000,  'capex_type' => 'Replacement',  'priority_scale' => 1, 'month' => 'March',    'amount_per_year' => 85000000,  'pic' => 'Staff Keperawatan'],
                ['item_name' => 'Syringe Pump (10 unit)',    'category' => 'I-RI', 'budget_amount' => 65000000,  'capex_type' => 'Replacement',  'priority_scale' => 2, 'month' => 'March',    'amount_per_year' => 65000000,  'pic' => 'Staff Keperawatan'],
                ['item_name' => 'Trolley Obat (5 unit)',     'category' => 'I-RI', 'budget_amount' => 25000000,  'capex_type' => 'New',         'priority_scale' => 2, 'month' => 'April',    'amount_per_year' => 25000000,  'pic' => 'Staff Keperawatan'],
                ['item_name' => 'Kursi Roda (5 unit)',       'category' => 'I-RI', 'budget_amount' => 15000000,  'capex_type' => 'New',         'priority_scale' => 3, 'month' => 'May',      'amount_per_year' => 15000000,  'pic' => 'Staff Keperawatan'],
                ['item_name' => 'Stretcher (3 unit)',        'category' => 'I-RI', 'budget_amount' => 45000000,  'capex_type' => 'New',         'priority_scale' => 2, 'month' => 'June',     'amount_per_year' => 45000000,  'pic' => 'Staff Keperawatan'],
                ['item_name' => 'Nebulizer (10 unit)',       'category' => 'I-RI', 'budget_amount' => 15000000,  'capex_type' => 'New',         'priority_scale' => 3, 'month' => 'July',     'amount_per_year' => 15000000,  'pic' => 'Staff Keperawatan'],
                ['item_name' => 'Suction Pump (5 unit)',     'category' => 'I-RI', 'budget_amount' => 35000000,  'capex_type' => 'Replacement',  'priority_scale' => 2, 'month' => 'August',   'amount_per_year' => 35000000,  'pic' => 'Staff Keperawatan'],
                ['item_name' => 'ECG Machine (2 unit)',      'category' => 'I-RI', 'budget_amount' => 80000000,  'capex_type' => 'New',         'priority_scale' => 1, 'month' => 'September', 'amount_per_year' => 80000000, 'pic' => 'Kepala Keperawatan'],
            ],
        ];

        foreach ($departments as $department) {
            // Create Capex header for this department and year
            $capex = Capex::firstOrCreate(
                [
                    'department_id' => $department->id,
                    'fiscal_year'   => $currentYear,
                ],
                [
                    'status'     => 'active',
                    'notes'      => "Anggaran CapEx {$department->name} Tahun {$currentYear}",
                    'created_by' => 1, // Admin
                ]
            );

            // Create capex items if department has data
            if (isset($capexItemsData[$department->code])) {
                foreach ($capexItemsData[$department->code] as $itemData) {
                    // Format: {seq}/CapEx/{year}/{dept_code}-DEMO — berbeda dari import agar tidak duplikat
                    $capexIdNumber = CapexItem::generateIdNumber($department->code, $currentYear) . '-DEMO';

                    CapexItem::firstOrCreate(
                        ['capex_id_number' => $capexIdNumber],
                        [
                            'capex_id'        => $capex->id,
                            'item_name'       => $itemData['item_name'],
                            'description'     => "Pengadaan {$itemData['item_name']} untuk {$department->name}",
                            'category'        => $itemData['category'],
                            'budget_amount'   => $itemData['budget_amount'],
                            'used_amount'     => 0,
                            'status'          => 'available',
                            // New fields
                            'capex_type'      => $itemData['capex_type'],
                            'priority_scale'  => $itemData['priority_scale'],
                            'month'           => $itemData['month'],
                            'amount_per_year' => $itemData['amount_per_year'],
                            'pic'             => $itemData['pic'],
                        ]
                    );
                }
            }
        }

        $this->command->info('CapEx data seeded successfully for all departments!');
    }
}
