<?php

namespace App\Exceptions;

use Exception;

class HoldExpiredException extends Exception
{
    public function __construct(
        string $message = 'Hold has expired',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

