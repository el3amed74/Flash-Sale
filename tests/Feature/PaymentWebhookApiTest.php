<?php

namespace Tests\Feature;

use App\Models\Hold;
use App\Models\Order;
use App\Models\PaymentWebhookLog;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookApiTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_webhook_processes_payment_success(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 100,
            'reserved' => 5,
            'sold' => 0,
        ]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'qty' => 5,
            'status' => Hold::STATUS_ACTIVE,
            'expires_at' => now()->addMinutes(2),
        ]);

        $order = Order::create([
            'hold_id' => $hold->id,
            'product_id' => $product->id,
            'qty' => 5,
            'total_price' => 499.95,
            'status' => Order::STATUS_PENDING,
        ]);

        $idempotencyKey = 'webhook-key-123';

        $response = $this->postJson('/api/payments/webhook', [
            'idempotency_key' => $idempotencyKey,
            'order_id' => $order->id,
            'status' => 'success',
            'payment_reference' => 'pay_ref_123',
            'provider' => 'test_provider',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'processed' => true,
                'status' => 'processed_success',
                'order_id' => $order->id,
            ]);

        // Check order is marked as paid
        $order->refresh();
        $this->assertEquals(Order::STATUS_PAID, $order->status);
        $this->assertEquals('pay_ref_123', $order->payment_reference);

        // Check stock is transferred from reserved to sold
        $product->refresh();
        $this->assertEquals(0, $product->reserved);
        $this->assertEquals(5, $product->sold);
    }

    public function test_webhook_processes_payment_failure(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 100,
            'reserved' => 5,
            'sold' => 0,
        ]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'qty' => 5,
            'status' => Hold::STATUS_ACTIVE,
            'expires_at' => now()->addMinutes(2),
        ]);

        $order = Order::create([
            'hold_id' => $hold->id,
            'product_id' => $product->id,
            'qty' => 5,
            'total_price' => 499.95,
            'status' => Order::STATUS_PENDING,
        ]);

        $idempotencyKey = 'webhook-key-456';

        $response = $this->postJson('/api/payments/webhook', [
            'idempotency_key' => $idempotencyKey,
            'order_id' => $order->id,
            'status' => 'failed',
            'provider' => 'test_provider',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'processed' => true,
                'status' => 'processed_failure',
                'order_id' => $order->id,
            ]);

        // Check order is marked as cancelled
        $order->refresh();
        $this->assertEquals(Order::STATUS_CANCELLED, $order->status);

        // Check stock is released
        $product->refresh();
        $this->assertEquals(0, $product->reserved);
    }

    public function test_webhook_is_idempotent(): void
    {
        $product = Product::create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock' => 100,
            'reserved' => 5,
            'sold' => 0,
        ]);

        $hold = Hold::create([
            'product_id' => $product->id,
            'qty' => 5,
            'status' => Hold::STATUS_ACTIVE,
            'expires_at' => now()->addMinutes(2),
        ]);

        $order = Order::create([
            'hold_id' => $hold->id,
            'product_id' => $product->id,
            'qty' => 5,
            'total_price' => 499.95,
            'status' => Order::STATUS_PENDING,
        ]);

        $idempotencyKey = 'webhook-key-789';

        // First webhook
        $firstResponse = $this->postJson('/api/payments/webhook', [
            'idempotency_key' => $idempotencyKey,
            'order_id' => $order->id,
            'status' => 'success',
            'payment_reference' => 'pay_ref_789',
        ]);

        $firstResponse->assertStatus(200);

        // Second webhook with same idempotency key
        $secondResponse = $this->postJson('/api/payments/webhook', [
            'idempotency_key' => $idempotencyKey,
            'order_id' => $order->id,
            'status' => 'success',
            'payment_reference' => 'pay_ref_789',
        ]);

        $secondResponse->assertStatus(200)
            ->assertJson([
                'processed' => true,
                'message' => 'Webhook already processed',
            ]);

        // Should only have one webhook log
        $this->assertDatabaseCount('payment_webhook_logs', 1);
    }

    public function test_webhook_handles_out_of_order_scenario(): void
    {
        $idempotencyKey = 'webhook-key-out-of-order';

        // Webhook arrives before order is created
        $response = $this->postJson('/api/payments/webhook', [
            'idempotency_key' => $idempotencyKey,
            'order_id' => null,
            'status' => 'success',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'processed' => false,
                'status' => 'processed_failure',
                'message' => 'Order ID is required',
            ]);
    }
}

