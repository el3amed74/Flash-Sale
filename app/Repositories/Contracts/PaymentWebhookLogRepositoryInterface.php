<?php

namespace App\Repositories\Contracts;

use App\Models\PaymentWebhookLog;

interface PaymentWebhookLogRepositoryInterface
{
    public function findByIdempotencyKey(string $idempotencyKey): ?PaymentWebhookLog;

    public function create(array $data): PaymentWebhookLog;

    public function markAsProcessed(int $logId, string $status): bool;
}

