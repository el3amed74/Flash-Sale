<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
            'name' => 'Flash Sale: Premium Widget',
            'price' => 49.99,
            'stock' => 100,
            'reserved' => 0,
            'sold' => 0,
        ]);
    }
}
