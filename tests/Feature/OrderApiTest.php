<?php

namespace Tests\Feature;

use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_successfully(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 100,
            'reserved' => 0,
            'sold' => 0,
        ]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'qty' => 5,
            'status' => Hold::STATUS_ACTIVE,
            'expires_at' => now()->addMinutes(2),
        ]);

        $response = $this->postJson('/api/orders', [
            'hold_id' => $hold->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'order_id',
                'hold_id',
                'product_id',
                'qty',
                'total_price',
                'status',
            ])
            ->assertJson([
                'hold_id' => $hold->id,
                'product_id' => $product->id,
                'qty' => 5,
                'status' => Order::STATUS_PENDING,
            ]);

        // Check that hold is marked as used
        $hold->refresh();
        $this->assertEquals(Hold::STATUS_USED, $hold->status);
    }

    public function test_create_order_fails_with_expired_hold(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 100,
            'reserved' => 0,
            'sold' => 0,
        ]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'qty' => 5,
            'status' => Hold::STATUS_ACTIVE,
            'expires_at' => now()->subMinute(), // Expired
        ]);

        $response = $this->postJson('/api/orders', [
            'hold_id' => $hold->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Hold has expired or is no longer active',
            ]);
    }

    public function test_create_order_fails_with_already_used_hold(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 100,
            'reserved' => 0,
            'sold' => 0,
        ]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'qty' => 5,
            'status' => Hold::STATUS_USED,
            'expires_at' => now()->addMinutes(2),
            'used_at' => now(),
        ]);

        $response = $this->postJson('/api/orders', [
            'hold_id' => $hold->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Hold has already been used to create an order',
            ]);
    }
}

