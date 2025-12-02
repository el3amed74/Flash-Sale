<?php

namespace App\DTOs;

readonly class CreateHoldDTO
{
    public function __construct(
        public int $productId,
        public int $qty,
        public ?string $idempotencyKey = null,
    ) {
    }

}

