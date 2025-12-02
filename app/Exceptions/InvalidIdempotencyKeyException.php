<?php

namespace App\Exceptions;

use Exception;

class InvalidIdempotencyKeyException extends Exception
{
    public function __construct(
        string $message = 'Invalid idempotency key',
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

