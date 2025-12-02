<?php

namespace App\DTOs;

readonly class CreateOrderDTO
{
    public function __construct(
        public int $holdId,
    ) {
    }

    
}

