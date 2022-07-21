<?php

namespace App\Exceptions;

use Throwable;

class FaultyEntityException extends \Exception
{
    /**
     * @var array
     */
    private $errors;

    public function __construct(
        array $errors,
        string $message = "",
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}