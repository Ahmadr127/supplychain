<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ItemType;
use App\Models\ItemCategory;
use App\Models\Commodity;
use App\Models\Unit;
use App\Models\MasterItem;

class MasterItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Item Types
        $medisType = ItemType::firstOrCreate(
            ['name' => 'Medis'],
            ['description' => 'Barang medis dan farmasi']
        );
        
        $nonMedisType = ItemType::firstOrCreate(
            ['name' => 'Non Medis'],
            ['description' => 'Barang non medis dan umum']
        );

        // Create Item Categories
        $rumahTanggaCategory = ItemCategory::firstOrCreate(
            ['name' => 'Rumah Tangga'],
            ['description' => 'Barang kebutuhan rumah tangga']
        );
        
        $atkCategory = ItemCategory::firstOrCreate(
            ['name' => 'ATK'],
            ['description' => 'Alat Tulis Kantor']
        );
        
        $inventarisCategory = ItemCategory::firstOrCreate(
            ['name' => 'Inventaris'],
            ['description' => 'Barang inventaris kantor']
        );
        
        $obatCategory = ItemCategory::firstOrCreate(
            ['name' => 'Obat-obatan'],
            ['description' => 'Obat-obatan dan farmasi']
        );
        
        $alatMedisCategory = ItemCategory::firstOrCreate(
            ['name' => 'Alat Medis'],
            ['description' => 'Alat-alat medis']
        );
        
        $makananCategory = ItemCategory::firstOrCreate(
            ['name' => 'Makanan & Minuman'],
            ['description' => 'Makanan dan minuman']
        );
        
        $pembersihCategory = ItemCategory::firstOrCreate(
            ['name' => 'Pembersih'],
            ['description' => 'Barang pembersih dan sanitasi']
        );

        // Create Commodities
        $farmasiCommodity = Commodity::firstOrCreate(
            ['name' => 'Farmasi'],
            ['description' => 'Komoditas farmasi']
        );
        
        $kesehatanCommodity = Commodity::firstOrCreate(
            ['name' => 'Kesehatan'],
            ['description' => 'Komoditas kesehatan']
        );
        
        $kantorCommodity = Commodity::firstOrCreate(
            ['name' => 'Kantor'],
            ['description' => 'Komoditas perkantoran']
        );
        
        $rumahTanggaCommodity = Commodity::firstOrCreate(
            ['name' => 'Rumah Tangga'],
            ['description' => 'Komoditas rumah tangga']
        );
        
        $makananCommodity = Commodity::firstOrCreate(
            ['name' => 'Makanan'],
            ['description' => 'Komoditas makanan']
        );
        
        $teknologiCommodity = Commodity::firstOrCreate(
            ['name' => 'Teknologi'],
            ['description' => 'Komoditas teknologi']
        );

        // Create Units
        $pcsUnit = Unit::firstOrCreate(
            ['name' => 'Pieces'],
            ['description' => 'Satuan per buah']
        );
        
        $boxUnit = Unit::firstOrCreate(
            ['name' => 'Box'],
            ['description' => 'Satuan per box']
        );
        
        $packUnit = Unit::firstOrCreate(
            ['name' => 'Pack'],
            ['description' => 'Satuan per pack']
        );
        
        $botolUnit = Unit::firstOrCreate(
            ['name' => 'Botol'],
            ['description' => 'Satuan per botol']
        );
        
        $stripUnit = Unit::firstOrCreate(
            ['name' => 'Strip'],
            ['description' => 'Satuan per strip']
        );
        
        $tabletUnit = Unit::firstOrCreate(
            ['name' => 'Tablet'],
            ['description' => 'Satuan per tablet']
        );
        
        $kapsulUnit = Unit::firstOrCreate(
            ['name' => 'Kapsul'],
            ['description' => 'Satuan per kapsul']
        );
        
        $literUnit = Unit::firstOrCreate(
            ['name' => 'Liter'],
            ['description' => 'Satuan per liter']
        );
        
        $kgUnit = Unit::firstOrCreate(
            ['name' => 'Kilogram'],
            ['description' => 'Satuan per kilogram']
        );
        
        $gramUnit = Unit::firstOrCreate(
            ['name' => 'Gram'],
            ['description' => 'Satuan per gram']
        );
        
        $meterUnit = Unit::firstOrCreate(
            ['name' => 'Meter'],
            ['description' => 'Satuan per meter']
        );
        
        $rollUnit = Unit::firstOrCreate(
            ['name' => 'Roll'],
            ['description' => 'Satuan per roll']
        );

        // Create Master Items
        $masterItems = [
            // Obat-obatan Medis
            [
                'name' => 'Paracetamol 500mg',
                'code' => 'PAR500',
                'description' => 'Obat penurun demam dan pereda nyeri',
                'hna' => 5000.00,
                'ppn_percentage' => 0.00,
                'item_type_id' => $medisType->id,
                'item_category_id' => $obatCategory->id,
                'commodity_id' => $farmasiCommodity->id,
                'unit_id' => $stripUnit->id,
                'stock' => 150,
                'is_active' => true,
            ],
            [
                'name' => 'Amoxicillin 500mg',
                'code' => 'AMX500',
                'description' => 'Antibiotik untuk infeksi bakteri',
                'hna' => 15000.00,
                'ppn_percentage' => 0.00,
                'item_type_id' => $medisType->id,
                'item_category_id' => $obatCategory->id,
                'commodity_id' => $farmasiCommodity->id,
                'unit_id' => $kapsulUnit->id,
                'stock' => 80,
                'is_active' => true,
            ],
            [
                'name' => 'Termometer Digital',
                'code' => 'TERM001',
                'description' => 'Termometer digital untuk mengukur suhu tubuh',
                'hna' => 25000.00,
                'ppn_percentage' => 0.00,
                'item_type_id' => $medisType->id,
                'item_category_id' => $alatMedisCategory->id,
                'commodity_id' => $kesehatanCommodity->id,
                'unit_id' => $pcsUnit->id,
                'stock' => 25,
                'is_active' => true,
            ],
            
            // ATK Non Medis
            [
                'name' => 'Bolpoin Hitam',
                'code' => 'BP001',
                'description' => 'Bolpoin tinta hitam untuk keperluan kantor',
                'hna' => 2000.00,
                'ppn_percentage' => 0.00,
                'item_type_id' => $nonMedisType->id,
                'item_category_id' => $atkCategory->id,
                'commodity_id' => $kantorCommodity->id,
                'unit_id' => $pcsUnit->id,
                'stock' => 300,
                'is_active' => true,
            ],
            [
                'name' => 'Kertas A4',
                'code' => 'KRT001',
                'description' => 'Kertas HVS A4 70gsm',
                'hna' => 45000.00,
                'ppn_percentage' => 0.00,
                'item_type_id' => $nonMedisType->id,
                'item_category_id' => $atkCategory->id,
                'commodity_id' => $kantorCommodity->id,
                'unit_id' => $boxUnit->id,
                'stock' => 50,
                'is_active' => true,
            ],
            
            // Rumah Tangga
            [
                'name' => 'Sabun Mandi',
                'code' => 'SAB001',
                'description' => 'Sabun mandi batang untuk keperluan rumah tangga',
                'hna' => 8000.00,
                'ppn_percentage' => 0.00,
                'item_type_id' => $nonMedisType->id,
                'item_category_id' => $rumahTanggaCategory->id,
                'commodity_id' => $rumahTanggaCommodity->id,
                'unit_id' => $pcsUnit->id,
                'stock' => 200,
                'is_active' => true,
            ],
            [
                'name' => 'Pembersih Lantai',
                'code' => 'PEM001',
                'description' => 'Cairan pembersih lantai',
                'hna' => 12000.00,
                'ppn_percentage' => 0.00,
                'item_type_id' => $nonMedisType->id,
                'item_category_id' => $pembersihCategory->id,
                'commodity_id' => $rumahTanggaCommodity->id,
                'unit_id' => $literUnit->id,
                'stock' => 60,
                'is_active' => true,
            ],
            
            // Makanan & Minuman
            [
                'name' => 'Nasi Putih',
                'code' => 'NAS001',
                'description' => 'Nasi putih untuk keperluan makan',
                'hna' => 15000.00,
                'ppn_percentage' => 0.00,
                'item_type_id' => $nonMedisType->id,
                'item_category_id' => $makananCategory->id,
                'commodity_id' => $makananCommodity->id,
                'unit_id' => $kgUnit->id,
                'stock' => 100,
                'is_active' => true,
            ],
            
            // Inventaris
            [
                'name' => 'Meja Kantor',
                'code' => 'MEJ001',
                'description' => 'Meja kantor kayu jati',
                'hna' => 500000.00,
                'ppn_percentage' => 0.00,
                'item_type_id' => $nonMedisType->id,
                'item_category_id' => $inventarisCategory->id,
                'commodity_id' => $kantorCommodity->id,
                'unit_id' => $pcsUnit->id,
                'stock' => 15,
                'is_active' => true,
            ],
        ];

        foreach ($masterItems as $item) {
            MasterItem::create($item);
        }
    }
}