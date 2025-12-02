<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_product_returns_product_with_available_stock(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 100,
            'reserved' => 10,
            'sold' => 5,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertJson([
            'msg' => 'Product get successfully',
            'product' => [
                'id' => $product->id,
                'name' => 'Test Product',
                'price' => '99.99',
                'available_stock' => 85,
                'total_stock' => 100,
            ]
        ]);
    }

    public function test_get_product_returns_404_for_non_existent_product(): void
    {
        $response = $this->getJson('/api/products/999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Product not found',
            ]);
    }
}

