<?php

declare(strict_types=1);

namespace Tuya\Core\Dto;

final class DeviceInfoResponse
{
    public function __construct(
        public readonly string $deviceId,
        public readonly ?string $name,
        public readonly bool $online,
        public readonly array $raw,
    ) {
    }
}

