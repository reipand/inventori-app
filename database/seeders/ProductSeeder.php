<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $airMineral   = Category::where('name', 'Air Mineral')->first();
        $bersoda      = Category::where('name', 'Minuman Bersoda')->first();
        $jusBuah      = Category::where('name', 'Jus Buah')->first();
        $energi       = Category::where('name', 'Minuman Energi')->first();
        $teh          = Category::where('name', 'Teh')->first();
        $kopi         = Category::where('name', 'Kopi')->first();

        $products = [
            [
                'sku'           => 'AM-001',
                'name'          => 'Aqua 600ml',
                'category_id'   => $airMineral->id,
                'unit'          => 'Botol',
                'buy_price'     => 2500,
                'sell_price'    => 3500,
                'min_stock'     => 50,
                'current_stock' => 200,
            ],
            [
                'sku'           => 'AM-002',
                'name'          => 'Aqua 1500ml',
                'category_id'   => $airMineral->id,
                'unit'          => 'Botol',
                'buy_price'     => 4500,
                'sell_price'    => 6000,
                'min_stock'     => 30,
                'current_stock' => 120,
            ],
            [
                'sku'           => 'BS-001',
                'name'          => 'Coca-Cola 330ml',
                'category_id'   => $bersoda->id,
                'unit'          => 'Kaleng',
                'buy_price'     => 6000,
                'sell_price'    => 8000,
                'min_stock'     => 24,
                'current_stock' => 96,
            ],
            [
                'sku'           => 'BS-002',
                'name'          => 'Sprite 600ml',
                'category_id'   => $bersoda->id,
                'unit'          => 'Botol',
                'buy_price'     => 5500,
                'sell_price'    => 7500,
                'min_stock'     => 24,
                'current_stock' => 15,
            ],
            [
                'sku'           => 'JB-001',
                'name'          => 'Pulpy Orange 350ml',
                'category_id'   => $jusBuah->id,
                'unit'          => 'Botol',
                'buy_price'     => 5000,
                'sell_price'    => 7000,
                'min_stock'     => 20,
                'current_stock' => 80,
            ],
            [
                'sku'           => 'ME-001',
                'name'          => 'Extra Joss 150ml',
                'category_id'   => $energi->id,
                'unit'          => 'Kaleng',
                'buy_price'     => 8000,
                'sell_price'    => 11000,
                'min_stock'     => 12,
                'current_stock' => 0,
            ],
            [
                'sku'           => 'TH-001',
                'name'          => 'Teh Botol Sosro 450ml',
                'category_id'   => $teh->id,
                'unit'          => 'Botol',
                'buy_price'     => 4000,
                'sell_price'    => 5500,
                'min_stock'     => 24,
                'current_stock' => 144,
            ],
            [
                'sku'           => 'TH-002',
                'name'          => 'Teh Pucuk Harum 350ml',
                'category_id'   => $teh->id,
                'unit'          => 'Botol',
                'buy_price'     => 3500,
                'sell_price'    => 5000,
                'min_stock'     => 24,
                'current_stock' => 10,
            ],
            [
                'sku'           => 'KP-001',
                'name'          => 'Kopi Good Day 250ml',
                'category_id'   => $kopi->id,
                'unit'          => 'Kaleng',
                'buy_price'     => 7000,
                'sell_price'    => 9500,
                'min_stock'     => 12,
                'current_stock' => 60,
            ],
            [
                'sku'           => 'KP-002',
                'name'          => 'Nescafe Ready to Drink 240ml',
                'category_id'   => $kopi->id,
                'unit'          => 'Kaleng',
                'buy_price'     => 8500,
                'sell_price'    => 11500,
                'min_stock'     => 12,
                'current_stock' => 36,
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(['sku' => $product['sku']], $product);
        }
    }
}
