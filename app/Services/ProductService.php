<?php

namespace App\Services;

use App\DTOs\ProductResponseDTO;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Contracts\ProductServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductService implements ProductServiceInterface
{
    private const CACHE_TTL = 10; // seconds
    private const CACHE_PREFIX = 'product:stock:';

    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {
    }

    public function getProduct(int $productId): ?ProductResponseDTO
    {
        $product = $this->productRepository->findById($productId);

        if (! $product) {
            return null;
        }

        $availableStock = $this->getAvailableStock($productId);

        return new ProductResponseDTO(
            id: $product->id,
            name: $product->name,
            price: number_format($product->price, 2, '.', ''),
            availableStock: $availableStock,
            totalStock: $product->stock,
        );
    }

    public function getAvailableStock(int $productId): int
    {
        $cacheKey = self::CACHE_PREFIX.$productId;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($productId) {
            $stock = $this->productRepository->getAvailableStock($productId);

            Log::debug('Product stock fetched from database', [
                'product_id' => $productId,
                'available_stock' => $stock,
            ]);

            return $stock;
        });
    }

    /**
     * Invalidate product stock cache
     */
    public function invalidateStockCache(int $productId): void
    {
        $cacheKey = self::CACHE_PREFIX.$productId;
        Cache::forget($cacheKey);

        Log::debug('Product stock cache invalidated', [
            'product_id' => $productId,
        ]);
    }
}

