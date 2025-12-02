<?php

namespace App\DTOs;

readonly class ProductResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $price,
        public int $availableStock,
        public int $totalStock,
    ) {
    }

    
}

