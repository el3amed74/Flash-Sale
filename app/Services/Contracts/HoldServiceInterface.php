<?php

namespace App\Services\Contracts;

use App\DTOs\CreateHoldDTO;
use App\Models\Hold;

interface HoldServiceInterface
{
    public function createHold(CreateHoldDTO $dto): Hold;

    public function releaseExpiredHolds(int $limit = 100): int;
}

