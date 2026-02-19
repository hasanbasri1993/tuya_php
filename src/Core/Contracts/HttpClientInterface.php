<?php

declare(strict_types=1);

namespace Tuya\Core\Contracts;

interface HttpClientInterface
{
    public function get(string $url, array $headers = []): array;

    public function post(string $url, array $headers = [], ?string $body = null): array;

    public function delete(string $url, array $headers = []): array;
}

