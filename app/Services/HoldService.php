<?php

namespace App\Services;

use App\DTOs\CreateHoldDTO;
use App\Exceptions\InsufficientStockException;
use App\Models\Hold;
use App\Repositories\Contracts\HoldRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Contracts\HoldServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HoldService implements HoldServiceInterface
{
    private const HOLD_EXPIRY_MINUTES = 5;

    public function __construct(
        private HoldRepositoryInterface $holdRepository,
        private ProductRepositoryInterface $productRepository
    ) {
    }

    public function createHold(CreateHoldDTO $dto): Hold
    {
        // Check for existing hold with same idempotency key
        if ($dto->idempotencyKey !== null) {
            $existingHold = $this->holdRepository->findByIdempotencyKey($dto->idempotencyKey);

            if ($existingHold && $existingHold->isActive()) {
                Log::info('Hold creation skipped - idempotency key already exists', [
                    'idempotency_key' => $dto->idempotencyKey,
                    'hold_id' => $existingHold->id,
                ]);

                return $existingHold;
            }
        }

        return DB::transaction(function () use ($dto) {
            // Lock product row to prevent race conditions
            $product = $this->productRepository->findByIdWithLock($dto->productId);

            if (! $product) {
                throw new \RuntimeException("Product not found: {$dto->productId}");
            }

            // Check available stock with lock
            $availableStock = $this->productRepository->getAvailableStock($dto->productId);

            if ($availableStock < $dto->qty) {
                Log::warning('Hold creation failed - insufficient stock', [
                    'product_id' => $dto->productId,
                    'requested_qty' => $dto->qty,
                    'available_stock' => $availableStock,
                ]);

                throw new InsufficientStockException(
                    "Insufficient stock. Available: {$availableStock}, Requested: {$dto->qty}"
                );
            }

            // Create hold
            $hold = $this->holdRepository->create([
                'product_id' => $dto->productId,
                'qty' => $dto->qty,
                'status' => Hold::STATUS_ACTIVE,
                'expires_at' => now()->addMinutes(self::HOLD_EXPIRY_MINUTES),
                'idempotency_key' => $dto->idempotencyKey,
            ]);

            // Increment reserved count atomically
            $this->productRepository->incrementReserved($dto->productId, $dto->qty);

            Log::info('Hold created successfully', [
                'hold_id' => $hold->id,
                'product_id' => $dto->productId,
                'qty' => $dto->qty,
                'expires_at' => $hold->expires_at,
            ]);

            return $hold;
        });
    }

    public function releaseExpiredHolds(int $limit = 100): int
    {
        $expiredHolds = $this->holdRepository->findExpiredHolds($limit);
        $releasedCount = 0;

        foreach ($expiredHolds as $hold) {
            try {
                DB::transaction(function () use ($hold, &$releasedCount) {
                    // Mark hold as expired
                    if ($this->holdRepository->markAsExpired($hold->id)) {
                        // Release reserved stock
                        $this->productRepository->decrementReserved($hold->product_id, $hold->qty);

                        $releasedCount++;

                        Log::info('Expired hold released', [
                            'hold_id' => $hold->id,
                            'product_id' => $hold->product_id,
                            'qty' => $hold->qty,
                        ]);
                    }
                });
            } catch (\Exception $e) {
                Log::error('Failed to release expired hold', [
                    'hold_id' => $hold->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($releasedCount > 0) {
            Log::info('Expired holds released', [
                'count' => $releasedCount,
                'total_checked' => $expiredHolds->count(),
            ]);
        }

        return $releasedCount;
    }
}

