<?php

namespace App\Exceptions;

use Exception;

class HoldAlreadyUsedException extends Exception
{
    public function __construct(
        string $message = 'Hold has already been used',
        int $code = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

