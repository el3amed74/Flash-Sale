<?php

namespace App\Services\Contracts;

use App\DTOs\CreateOrderDTO;
use App\Models\Order;

interface OrderServiceInterface
{
    public function createOrder(CreateOrderDTO $dto): Order;
}

