<?php

declare(strict_types=1);

namespace Tuya\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Tuya\Core\Contracts\CacheAdapterInterface;
use Tuya\Core\Contracts\HttpClientInterface;
use Tuya\Core\Dto\AccessTokenResponse;
use Tuya\Core\TuyaClient;

final class TuyaClientTest extends TestCase
{
    public function test_fetches_and_caches_access_token(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $cache = $this->createMock(CacheAdapterInterface::class);

        $cache->expects(self::exactly(2))
            ->method('has')
            ->with('access_token')
            ->willReturnOnConsecutiveCalls(false, true);

        $cache->expects(self::once())
            ->method('set')
            ->with(
                'access_token',
                $this->callback(fn (array $data) => $data['access_token'] === 'abc123' && $data['uid'] === 'uid456'),
                3600
            );

        $cache->expects(self::once())
            ->method('get')
            ->with('access_token')
            ->willReturn([
                'access_token' => 'abc123',
                'uid'          => 'uid456',
                'expires_at'   => time() + 3600,
            ]);

        $http->expects(self::once())
            ->method('get')
            ->with(
                'https://api.tuya.test/v1.0/token?grant_type=1',
                $this->arrayHasKey('client_id')
            )
            ->willReturn([
                'status' => 200,
                'headers' => [],
                'body' => [
                    'result' => [
                        'access_token' => 'abc123',
                        'uid'          => 'uid456',
                        'expire_time'  => 3600,
                    ],
                ],
            ]);

        $client = new TuyaClient(
            httpClient: $http,
            cache: $cache,
            clientId: 'client-id',
            clientSecret: 'secret',
            apiUrl: 'https://api.tuya.test',
            cacheTtl: 3600,
        );

        $first = $client->getAccessToken();
        $second = $client->getAccessToken();

        self::assertInstanceOf(AccessTokenResponse::class, $first);
        self::assertSame('abc123', $first->accessToken);
        self::assertSame('uid456', $first->uid);
        self::assertSame('abc123', $second->accessToken);
    }
}

