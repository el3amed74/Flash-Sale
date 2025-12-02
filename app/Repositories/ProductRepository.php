<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use App\Repositories\Contracts\ProductRepositoryInterface;

class ProductRepository implements ProductRepositoryInterface
{
    private const MAX_DEADLOCK_RETRIES = 3;

    public function __construct(
        private readonly Product $model
    )
    {}
    
    public function findById(int $id): ?Product
    {
        return $this->model->find($id);
    }

    public function findByIdWithLock(int $id): ?Product
    {
        return $this->model->where('id', $id)->lockForUpdate()->first();
    }

    public function getAvailableStock(int $productId): int
    {
        $product = $this->findByIdWithLock($productId);

        if (! $product) {
            return 0;
        }

        return max(0, $product->stock - $product->reserved - $product->sold);
    }

    public function incrementReserved(int $productId, int $qty): bool
    {
        return $this->retryOnDeadlock(function () use ($productId, $qty) {
            return DB::transaction(function () use ($productId, $qty) {
                $product = $this->findByIdWithLock($productId);

                if (! $product) {
                    return false;
                }

                $product->increment('reserved', $qty);

                Log::info('Product reserved incremented', [
                    'product_id' => $productId,
                    'qty' => $qty,
                    'new_reserved' => $product->fresh()->reserved,
                ]);

                return true;
            });
        });
    }

    public function decrementReserved(int $productId, int $qty): bool
    {
        return $this->retryOnDeadlock(function () use ($productId, $qty) {
            return DB::transaction(function () use ($productId, $qty) {
                $product = $this->findByIdWithLock($productId);

                if (! $product) {
                    return false;
                }

                $product->decrement('reserved', $qty);

                Log::info('Product reserved decremented', [
                    'product_id' => $productId,
                    'qty' => $qty,
                    'new_reserved' => $product->fresh()->reserved,
                ]);

                return true;
            });
        });
    }

    public function incrementSold(int $productId, int $qty): bool
    {
        return $this->retryOnDeadlock(function () use ($productId, $qty) {
            return DB::transaction(function () use ($productId, $qty) {
                $product = $this->findByIdWithLock($productId);

                if (! $product) {
                    return false;
                }

                $product->increment('sold', $qty);

                Log::info('Product sold incremented', [
                    'product_id' => $productId,
                    'qty' => $qty,
                    'new_sold' => $product->fresh()->sold,
                ]);

                return true;
            });
        });
    }

    public function decrementSold(int $productId, int $qty): bool
    {
        return $this->retryOnDeadlock(function () use ($productId, $qty) {
            return DB::transaction(function () use ($productId, $qty) {
                $product = $this->findByIdWithLock($productId);

                if (! $product) {
                    return false;
                }

                $product->decrement('sold', $qty);

                Log::info('Product sold decremented', [
                    'product_id' => $productId,
                    'qty' => $qty,
                    'new_sold' => $product->fresh()->sold,
                ]);

                return true;
            });
        });
    }

    /**
     * Retry operation on deadlock with exponential backoff
     */
    private function retryOnDeadlock(callable $operation, int $attempt = 1): mixed
    {
        try {
            return $operation();
        } catch (QueryException $e) {
            if ($this->isDeadlock($e) && $attempt < self::MAX_DEADLOCK_RETRIES) {
                $delay = pow(2, $attempt) * 100; // Exponential backoff in milliseconds
                usleep($delay * 1000); // Convert to microseconds

                Log::warning('Deadlock detected, retrying', [
                    'attempt' => $attempt,
                    'max_retries' => self::MAX_DEADLOCK_RETRIES,
                    'delay_ms' => $delay,
                ]);

                return $this->retryOnDeadlock($operation, $attempt + 1);
            }

            throw $e;
        }
    }

    private function isDeadlock(QueryException $e): bool
    {
        $errorCode = $e->getCode();
        $errorMessage = $e->getMessage();

        // MySQL deadlock error code is 1213
        return $errorCode === 1213 || str_contains($errorMessage, 'Deadlock found');
    }
}

