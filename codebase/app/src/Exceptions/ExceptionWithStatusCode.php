<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ExceptionWithStatusCode extends Exception
{
    public function __construct(string $message, int $statusCode, Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}