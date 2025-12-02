<?php

namespace App\Services\Contracts;

use App\DTOs\PaymentWebhookDTO;

interface PaymentWebhookServiceInterface
{
    public function processWebhook(PaymentWebhookDTO $dto): array;
}

