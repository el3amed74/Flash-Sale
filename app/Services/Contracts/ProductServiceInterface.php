<?php

namespace App\Services\Contracts;

use App\DTOs\ProductResponseDTO;

interface ProductServiceInterface
{
    public function getProduct(int $productId): ?ProductResponseDTO;

    public function getAvailableStock(int $productId): int;

    public function invalidateStockCache(int $productId): void;
}

