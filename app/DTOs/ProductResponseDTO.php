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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'available_stock' => $this->availableStock,
            'total_stock' => $this->totalStock,
        ];
    }
    
}

