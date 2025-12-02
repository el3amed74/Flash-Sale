<?php

namespace Tests\Feature;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HoldApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_hold_successfully(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 100,
            'reserved' => 0,
            'sold' => 0,
        ]);

        $response = $this->postJson('/api/holds', [
            'product_id' => $product->id,
            'qty' => 5,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'hold_id',
                'expires_at',
            ]);

        $this->assertDatabaseHas('holds', [
            'product_id' => $product->id,
            'qty' => 5,
            'status' => Hold::STATUS_ACTIVE,
        ]);

        // Check that reserved count increased
        $product->refresh();
        $this->assertEquals(5, $product->reserved);
    }

    public function test_create_hold_fails_with_insufficient_stock(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 10,
            'reserved' => 5,
            'sold' => 0,
        ]);

        $response = $this->postJson('/api/holds', [
            'product_id' => $product->id,
            'qty' => 10, // Only 5 available (10 - 5)
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Insufficient stock. Available: 5, Requested: 10',
            ]);
    }

    public function test_create_hold_with_idempotency_key_returns_existing_hold(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 100,
            'reserved' => 0,
            'sold' => 0,
        ]);

        $idempotencyKey = 'test-key-123';

        $firstResponse = $this->postJson('/api/holds', [
            'product_id' => $product->id,
            'qty' => 5,
            'idempotency_key' => $idempotencyKey,
        ]);

        $firstResponse->assertStatus(201);
        $firstHoldId = $firstResponse->json('hold_id');

        $secondResponse = $this->postJson('/api/holds', [
            'product_id' => $product->id,
            'qty' => 5,
            'idempotency_key' => $idempotencyKey,
        ]);

        $secondResponse->assertStatus(201);
        $this->assertEquals($firstHoldId, $secondResponse->json('hold_id'));

        // Should only have one hold
        $this->assertDatabaseCount('holds', 1);
    }
}

