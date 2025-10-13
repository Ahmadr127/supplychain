<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ItemType;

class ItemTypeCodeSeeder extends Seeder
{
    public function run(): void
    {
        // Set code for existing item types without creating new types
        // Medis => PM, Non-Medis => PNM
        ItemType::where('name', 'Medis')->update(['code' => 'PM']);
        // Handle both naming variants to be safe
        ItemType::whereIn('name', ['Non Medis', 'Non-Medis'])->update(['code' => 'PNM']);
    }
}
