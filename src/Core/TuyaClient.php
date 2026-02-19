<?php

declare(strict_types=1);

namespace Tuya\Core;

use Tuya\Core\Contracts\CacheAdapterInterface;
use Tuya\Core\Contracts\HttpClientInterface;
use Tuya\Core\Dto\AccessTokenResponse;

class TuyaClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheAdapterInterface $cache,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $apiUrl,
        private readonly int $cacheTtl,
    ) {
    }

    public function getAccessToken(): AccessTokenResponse
    {
        if ($this->cache->has('access_token')) {
            $cached = $this->cache->get('access_token');
            if (is_array($cached) && isset($cached['access_token'], $cached['uid'], $cached['expires_at']) && $cached['expires_at'] > time()) {
                return new AccessTokenResponse(
                    accessToken: (string) $cached['access_token'],
                    uid: (string) $cached['uid'],
                    expiresAt: (int) $cached['expires_at'],
                    raw: $cached,
                );
            }
        }

        $timestamp = (int) round(microtime(true) * 1000);
        $nonce = $this->generateUUID();
        $urlPath = '/v1.0/token?grant_type=1';
        $sign = $this->generateSignature($timestamp, $nonce, '', $urlPath);

        $headers = [
            'client_id'   => $this->clientId,
            't'           => (string) $timestamp,
            'nonce'       => $nonce,
            'sign'        => $sign,
            'sign_method' => 'HMAC-SHA256',
        ];

        $response = $this->httpClient->get($this->apiUrl . $urlPath, $headers);
        $body = $response['body'] ?? [];
        $result = $body['result'] ?? [];

        $accessToken = (string) ($result['access_token'] ?? '');
        $uid = (string) ($result['uid'] ?? '');
        $expiresIn = (int) ($result['expire_time'] ?? $this->cacheTtl);
        $expiresAt = time() + $expiresIn;

        $payload = [
            'access_token' => $accessToken,
            'uid'          => $uid,
            'expires_at'   => $expiresAt,
        ];

        $this->cache->set('access_token', $payload, $this->cacheTtl);

        return new AccessTokenResponse(
            accessToken: $accessToken,
            uid: $uid,
            expiresAt: $expiresAt,
            raw: $payload,
        );
    }

    private function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
        );
    }

    private function generateSignature(
        int $timestamp,
        string $nonce,
        string $accessToken,
        string $urlPath,
        string $method = 'GET',
        string $bodyData = '',
    ): string {
        $contentSha256 = hash('sha256', $bodyData);
        $stringToSign = strtoupper($method) . "\n" . $contentSha256 . "\n\n" . $urlPath;
        $stringToHash = $this->clientId . $accessToken . $timestamp . $nonce . $stringToSign;

        return strtoupper(hash_hmac('sha256', $stringToHash, $this->clientSecret));
    }
}

