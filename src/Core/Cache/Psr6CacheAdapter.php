<?php

declare(strict_types=1);

namespace Tuya\Core\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Tuya\Core\Contracts\CacheAdapterInterface;

final class Psr6CacheAdapter implements CacheAdapterInterface
{
    public function __construct(
        private readonly CacheItemPoolInterface $pool,
        private readonly string $prefix = 'tuya_',
    ) {
    }

    public function get(string $key): ?array
    {
        $item = $this->pool->getItem($this->prefixed($key));
        if (!$item->isHit()) {
            return null;
        }

        $value = $item->get();

        return is_array($value) ? $value : null;
    }

    public function set(string $key, array $data, int $ttl): void
    {
        $item = $this->pool->getItem($this->prefixed($key));
        $item->set($data);
        $item->expiresAfter($ttl);
        $this->pool->save($item);
    }

    public function forget(string $key): void
    {
        $this->pool->deleteItem($this->prefixed($key));
    }

    public function has(string $key): bool
    {
        $item = $this->pool->getItem($this->prefixed($key));

        return $item->isHit();
    }

    private function prefixed(string $key): string
    {
        return $this->prefix . $key;
    }
}

