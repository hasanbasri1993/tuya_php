<?php

declare(strict_types=1);

namespace Tuya\Core\Exceptions;

use RuntimeException;

class TuyaApiException extends RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        private readonly array $response = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): array
    {
        return $this->response;
    }
}

