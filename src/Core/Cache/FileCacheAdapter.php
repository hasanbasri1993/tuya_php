<?php

declare(strict_types=1);

namespace Tuya\Core\Cache;

use Tuya\Core\Contracts\CacheAdapterInterface;

final class FileCacheAdapter implements CacheAdapterInterface
{
    public function __construct(
        private readonly string $directory,
    ) {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0777, true);
        }
    }

    public function get(string $key): ?array
    {
        $path = $this->pathFor($key);
        if (!is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            return null;
        }

        $decoded = json_decode($contents, true);
        if (!is_array($decoded) || !isset($decoded['expires_at'], $decoded['data'])) {
            return null;
        }

        if ($decoded['expires_at'] < time()) {
            @unlink($path);
            return null;
        }

        return is_array($decoded['data']) ? $decoded['data'] : null;
    }

    public function set(string $key, array $data, int $ttl): void
    {
        $payload = [
            'expires_at' => time() + $ttl,
            'data'       => $data,
        ];

        file_put_contents($this->pathFor($key), json_encode($payload));
    }

    public function forget(string $key): void
    {
        $path = $this->pathFor($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    private function pathFor(string $key): string
    {
        $hash = sha1($key);
        return rtrim($this->directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $hash . '.json';
    }
}

