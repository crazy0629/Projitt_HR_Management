<?php

namespace App\Exceptions\Coding;

use RuntimeException;

class CodeExecutionException extends RuntimeException
{
    protected ?array $context;

    public function __construct(string $message, int $code = 0, ?array $context = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function context(): array
    {
        return $this->context ?? [];
    }
}
