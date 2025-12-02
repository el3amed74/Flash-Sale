<?php

namespace App\Services;

use App\DTOs\CreateOrderDTO;
use App\Exceptions\HoldAlreadyUsedException;
use App\Exceptions\HoldExpiredException;
use App\Models\Hold;
use App\Models\Order;
use App\Repositories\Contracts\HoldRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Contracts\OrderServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService implements OrderServiceInterface
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private HoldRepositoryInterface $holdRepository,
        private ProductRepositoryInterface $productRepository
    ) {
    }

    public function createOrder(CreateOrderDTO $dto): Order
    {
        return DB::transaction(function () use ($dto) {
            // Lock and validate hold
            $hold = $this->holdRepository->findById($dto->holdId);

            if (! $hold) {
                throw new \RuntimeException("Hold not found: {$dto->holdId}");
            }

            // Check if hold is already used (must check before isActive() since used holds are not active)
            if ($hold->status === Hold::STATUS_USED) {
                $existingOrder = $this->orderRepository->findByHoldId($dto->holdId);

                Log::warning('Order creation failed - hold already used', [
                    'hold_id' => $dto->holdId,
                    'existing_order_id' => $existingOrder?->id,
                ]);

                throw new HoldAlreadyUsedException('Hold has already been used to create an order');
            }

            // Check if hold is expired or inactive (but not used, since we already checked that)
            if (! $hold->isActive()) {
                Log::warning('Order creation failed - hold expired or inactive', [
                    'hold_id' => $dto->holdId,
                    'status' => $hold->status,
                    'expires_at' => $hold->expires_at,
                ]);

                throw new HoldExpiredException('Hold has expired or is no longer active');
            }

            // Get product for price calculation
            $product = $this->productRepository->findById($hold->product_id);

            if (! $product) {
                throw new \RuntimeException("Product not found: {$hold->product_id}");
            }

            // Calculate total price
            $totalPrice = $product->price * $hold->qty;

            // Create order
            $order = $this->orderRepository->create([
                'hold_id' => $hold->id,
                'product_id' => $hold->product_id,
                'qty' => $hold->qty,
                'total_price' => $totalPrice,
                'status' => Order::STATUS_PENDING,
            ]);

            // Mark hold as used
            $this->holdRepository->markAsUsed($hold->id);

            Log::info('Order created successfully', [
                'order_id' => $order->id,
                'hold_id' => $dto->holdId,
                'product_id' => $hold->product_id,
                'qty' => $hold->qty,
                'total_price' => $totalPrice,
            ]);

            return $order;
        });
    }
}

