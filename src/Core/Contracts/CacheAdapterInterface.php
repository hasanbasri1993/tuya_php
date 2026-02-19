<?php

declare(strict_types=1);

namespace Tuya\Core\Contracts;

interface CacheAdapterInterface
{
    public function get(string $key): ?array;

    public function set(string $key, array $data, int $ttl): void;

    public function forget(string $key): void;

    public function has(string $key): bool;
}

