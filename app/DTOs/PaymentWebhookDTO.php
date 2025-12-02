<?php

namespace App\DTOs;

readonly class PaymentWebhookDTO
{
    public function __construct(
        public string $idempotencyKey,
        public ?int $orderId,
        public string $status,
        public ?string $paymentReference = null,
        public ?string $provider = null,
    ) {
    }

    
}

