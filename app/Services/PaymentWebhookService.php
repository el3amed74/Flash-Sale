<?php

namespace App\Services;

use App\DTOs\PaymentWebhookDTO;
use App\Exceptions\InvalidIdempotencyKeyException;
use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentWebhookLogRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Contracts\PaymentWebhookServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookService implements PaymentWebhookServiceInterface
{
    public function __construct(
        private PaymentWebhookLogRepositoryInterface $webhookLogRepository,
        private OrderRepositoryInterface $orderRepository,
        private ProductRepositoryInterface $productRepository
    ) {
    }

    public function processWebhook(PaymentWebhookDTO $dto): array
    {
        // Validate idempotency key
        if (empty($dto->idempotencyKey)) {
            throw new InvalidIdempotencyKeyException('Idempotency key is required');
        }

        // Check if webhook was already processed
        $existingLog = $this->webhookLogRepository->findByIdempotencyKey($dto->idempotencyKey);

        if ($existingLog && $existingLog->isProcessed()) {
            Log::info('Webhook already processed - returning cached result', [
                'idempotency_key' => $dto->idempotencyKey,
                'log_id' => $existingLog->id,
                'status' => $existingLog->status,
            ]);

            return [
                'processed' => true,
                'status' => $existingLog->status,
                'order_id' => $existingLog->order_id,
                'message' => 'Webhook already processed',
            ];
        }

        // Create or get webhook log
        $webhookLog = $existingLog ?? $this->webhookLogRepository->create([
            'idempotency_key' => $dto->idempotencyKey,
            'provider' => $dto->provider,
            'order_id' => $dto->orderId,
            'status' => 'queued',
            'payload' => [],
        ]);

        // Handle out-of-order webhook (order doesn't exist yet)
        if ($dto->orderId === null) {
            Log::warning('Webhook received without order_id - cannot process', [
                'idempotency_key' => $dto->idempotencyKey,
            ]);

            $this->webhookLogRepository->markAsProcessed($webhookLog->id, 'processed_failure');

            return [
                'processed' => false,
                'status' => 'processed_failure',
                'message' => 'Order ID is required',
            ];
        }

        // Process webhook
        return DB::transaction(function () use ($dto, $webhookLog) {
            // Lock order to prevent concurrent updates
            $order = $this->orderRepository->findById($dto->orderId);

            if (! $order) {
                Log::warning('Webhook received for non-existent order', [
                    'idempotency_key' => $dto->idempotencyKey,
                    'order_id' => $dto->orderId,
                ]);

                $this->webhookLogRepository->markAsProcessed($webhookLog->id, 'processed_failure');

                return [
                    'processed' => false,
                    'status' => 'processed_failure',
                    'message' => 'Order not found',
                ];
            }

            // Check if order is already in final state
            if (in_array($order->status, [Order::STATUS_PAID, Order::STATUS_CANCELLED])) {
                Log::info('Webhook received for order in final state', [
                    'idempotency_key' => $dto->idempotencyKey,
                    'order_id' => $dto->orderId,
                    'current_status' => $order->status,
                ]);

                $this->webhookLogRepository->markAsProcessed($webhookLog->id, 'processed_success');

                return [
                    'processed' => true,
                    'status' => 'processed_success',
                    'order_id' => $order->id,
                    'order_status' => $order->status,
                    'message' => 'Order already in final state',
                ];
            }

            // Process based on webhook status
            $isSuccess = $dto->status === 'success' || $dto->status === 'paid';

            if ($isSuccess) {
                $this->processPaymentSuccess($order, $dto);
            } else {
                $this->processPaymentFailure($order, $dto);
            }

            // Mark webhook as processed
            $this->webhookLogRepository->markAsProcessed(
                $webhookLog->id,
                $isSuccess ? 'processed_success' : 'processed_failure'
            );

            Log::info('Webhook processed successfully', [
                'idempotency_key' => $dto->idempotencyKey,
                'order_id' => $order->id,
                'status' => $isSuccess ? 'success' : 'failure',
            ]);

            return [
                'processed' => true,
                'status' => $isSuccess ? 'processed_success' : 'processed_failure',
                'order_id' => $order->id,
                'order_status' => $order->fresh()->status,
            ];
        });
    }

    private function processPaymentSuccess(Order $order, PaymentWebhookDTO $dto): void
    {
        // Update order status
        $this->orderRepository->updateStatus(
            $order->id,
            Order::STATUS_PAID,
            $dto->paymentReference
        );

        // Transfer stock from reserved to sold
        $this->productRepository->decrementReserved($order->product_id, $order->qty);
        $this->productRepository->incrementSold($order->product_id, $order->qty);

        Log::info('Payment success processed', [
            'order_id' => $order->id,
            'product_id' => $order->product_id,
            'qty' => $order->qty,
            'payment_reference' => $dto->paymentReference,
        ]);
    }

    private function processPaymentFailure(Order $order, PaymentWebhookDTO $dto): void
    {
        // Update order status
        $this->orderRepository->updateStatus($order->id, Order::STATUS_CANCELLED);

        // Release reserved stock back to available
        $this->productRepository->decrementReserved($order->product_id, $order->qty);

        Log::info('Payment failure processed', [
            'order_id' => $order->id,
            'product_id' => $order->product_id,
            'qty' => $order->qty,
        ]);
    }
}

