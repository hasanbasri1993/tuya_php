<?php

declare(strict_types=1);

namespace Tuya\Core\Http;

use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Tuya\Core\Contracts\HttpClientInterface;

final class GuzzleHttpClient implements HttpClientInterface
{
    public function __construct(
        private readonly ClientInterface $client,
    ) {
    }

    public function get(string $url, array $headers = []): array
    {
        $response = $this->client->request('GET', $url, [
            'headers' => $headers,
        ]);

        return $this->normalizeResponse($response);
    }

    public function post(string $url, array $headers = [], ?string $body = null): array
    {
        $response = $this->client->request('POST', $url, [
            'headers' => $headers,
            'body'    => $body,
        ]);

        return $this->normalizeResponse($response);
    }

    public function delete(string $url, array $headers = []): array
    {
        $response = $this->client->request('DELETE', $url, [
            'headers' => $headers,
        ]);

        return $this->normalizeResponse($response);
    }

    /**
     * @return array{status:int,headers:array<string,mixed>,body:array<mixed>|array{}}
     */
    private function normalizeResponse(ResponseInterface $response): array
    {
        $rawBody = (string) $response->getBody();
        $decoded = $rawBody !== '' ? json_decode($rawBody, true) : [];

        if (!is_array($decoded)) {
            $decoded = [];
        }

        return [
            'status'  => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body'    => $decoded,
        ];
    }
}

