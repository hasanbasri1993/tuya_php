<?php

declare(strict_types=1);

namespace Tuya\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Tuya\Core\Contracts\HttpClientInterface;
use Tuya\Core\Dto\AccessTokenResponse;
use Tuya\Core\SmartLock;
use Tuya\Core\TuyaClient;

final class SmartLockTest extends TestCase
{
    public function test_encrypt_numeric_password_roundtrip_for_valid_ticket(): void
    {
        $client = $this->createMock(TuyaClient::class);
        $http = $this->createMock(HttpClientInterface::class);

        $clientSecret = str_repeat('s', 32);
        $key = random_bytes(32);
        $ticketKey = bin2hex(openssl_encrypt($key, 'AES-256-ECB', $clientSecret, OPENSSL_RAW_DATA));

        $smartLock = new SmartLock($client, $http, $clientSecret, 'https://api.tuya.test', 'client-id');

        $debug = [];
        $encrypted = $smartLock->encryptNumericPassword('123456', $ticketKey, $debug);

        self::assertIsString($encrypted);
        self::assertSame([], $debug);
    }

    public function test_get_password_ticket_calls_http_client(): void
    {
        $client = $this->createMock(TuyaClient::class);
        $client->method('getAccessToken')->willReturn(
            new AccessTokenResponse('token123', 'uid456', time() + 3600, [])
        );

        $http = $this->createMock(HttpClientInterface::class);
        $http->expects(self::once())
            ->method('post')
            ->with(
                'https://api.tuya.test/v1.0/devices/dev123/door-lock/password-ticket',
                $this->arrayHasKey('access_token'),
                '{}'
            )
            ->willReturn([
                'status' => 200,
                'headers' => [],
                'body' => ['ticket' => 'abc'],
            ]);

        $smartLock = new SmartLock($client, $http, str_repeat('s', 32), 'https://api.tuya.test', 'client-id');
        $result = $smartLock->getPasswordTicket('dev123');

        self::assertSame(['ticket' => 'abc'], $result);
    }
}

