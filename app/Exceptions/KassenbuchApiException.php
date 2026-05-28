<?php

namespace App\Exceptions;

use RuntimeException;

class KassenbuchApiException extends RuntimeException
{
    public function __construct(string $message, public readonly int $errorCode = 0)
    {
        parent::__construct($message);
    }
}
