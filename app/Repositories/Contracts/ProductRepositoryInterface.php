<?php

namespace App\Repositories\Contracts;

use App\Models\Product;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    public function findByIdWithLock(int $id): ?Product;

    public function getAvailableStock(int $productId): int;

    public function incrementReserved(int $productId, int $qty): bool;

    public function decrementReserved(int $productId, int $qty): bool;

    public function incrementSold(int $productId, int $qty): bool;

    public function decrementSold(int $productId, int $qty): bool;
}

