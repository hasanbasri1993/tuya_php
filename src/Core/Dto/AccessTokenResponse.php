<?php

declare(strict_types=1);

namespace Tuya\Core\Dto;

final class AccessTokenResponse
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $uid,
        public readonly int $expiresAt,
        public readonly array $raw,
    ) {
    }
}

