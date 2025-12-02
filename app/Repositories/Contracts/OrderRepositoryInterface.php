<?php

namespace App\Repositories\Contracts;

use App\Models\Order;

interface OrderRepositoryInterface
{
    public function create(array $data): Order;

    public function findById(int $id): ?Order;

    public function findByHoldId(int $holdId): ?Order;

    public function updateStatus(int $orderId, string $status, ?string $paymentReference = null): bool;
}

