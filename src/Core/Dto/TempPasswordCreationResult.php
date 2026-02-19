<?php

declare(strict_types=1);

namespace Tuya\Core\Dto;

final class TempPasswordCreationResult
{
    public function __construct(
        public readonly string $plainPassword,
        public readonly array $response,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->plainPassword !== '' && !empty($this->response['success']);
    }

    public function isLimitReached(): bool
    {
        return isset($this->response['code']) && (int) $this->response['code'] === 2303;
    }
}

