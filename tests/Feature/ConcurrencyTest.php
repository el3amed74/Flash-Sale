<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_hold_creation_prevents_overselling(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 10,
            'reserved' => 0,
            'sold' => 0,
        ]);

        // Simulate 20 concurrent requests trying to create holds of 5 each
        // Only 2 should succeed (10 stock / 5 qty = 2 holds)
        $responses = [];
        $successCount = 0;
        $failureCount = 0;

        for ($i = 0; $i < 20; $i++) {
            $response = $this->postJson('/api/holds', [
                'product_id' => $product->id,
                'qty' => 5,
            ]);

            if ($response->status() === 201) {
                $successCount++;
            } else {
                $failureCount++;
            }
        }

        // Verify only 2 holds were created
        $this->assertEquals(2, $successCount);
        $this->assertEquals(18, $failureCount);

        // Verify total reserved is exactly 10 (2 holds * 5 qty)
        $product->refresh();
        $this->assertEquals(10, $product->reserved);

        // Verify available stock is 0
        $this->assertEquals(0, $product->stock - $product->reserved - $product->sold);
    }

    public function test_concurrent_hold_creation_with_transactions(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 100,
            'reserved' => 0,
            'sold' => 0,
        ]);

        // Create multiple holds concurrently
        $promises = [];
        for ($i = 0; $i < 10; $i++) {
            $promises[] = $this->postJson('/api/holds', [
                'product_id' => $product->id,
                'qty' => 10,
            ]);
        }

        // Wait for all requests
        $successCount = 0;
        foreach ($promises as $response) {
            if ($response->status() === 201) {
                $successCount++;
            }
        }

        // Verify exactly 10 holds were created (100 stock / 10 qty = 10 holds)
        $this->assertEquals(10, $successCount);

        $product->refresh();
        $this->assertEquals(100, $product->reserved);
    }
}

