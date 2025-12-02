<?php

namespace App\Repositories\Contracts;

use App\Models\Hold;
use Illuminate\Support\Collection;

interface HoldRepositoryInterface
{
    public function create(array $data): Hold;

    public function findById(int $id): ?Hold;

    public function findByIdempotencyKey(string $idempotencyKey): ?Hold;

    public function findByProductIdAndStatus(int $productId, string $status): Collection;

    public function findExpiredHolds(int $limit = 100): Collection;

    public function markAsUsed(int $holdId): bool;

    public function markAsExpired(int $holdId): bool;

    public function releaseHold(int $holdId): bool;
}

