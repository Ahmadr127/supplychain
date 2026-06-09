<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TsCategory;

class TsCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'IT / Perangkat Lunak & Keras', 'description' => 'Kebutuhan teknis terkait komputer, software, atau jaringan'],
        ];

        foreach ($categories as $cat) {
            TsCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }
    }
}

