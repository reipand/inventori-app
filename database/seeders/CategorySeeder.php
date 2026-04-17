<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Air Mineral', 'description' => 'Produk air mineral dalam berbagai ukuran kemasan'],
            ['name' => 'Minuman Bersoda', 'description' => 'Minuman berkarbonasi dan bersoda'],
            ['name' => 'Jus Buah', 'description' => 'Minuman jus buah segar dan kemasan'],
            ['name' => 'Minuman Energi', 'description' => 'Minuman penambah energi dan stamina'],
            ['name' => 'Teh', 'description' => 'Minuman teh dalam berbagai varian rasa dan kemasan'],
            ['name' => 'Kopi', 'description' => 'Minuman kopi siap minum dalam kemasan'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['name' => $category['name']], $category);
        }
    }
}
