<?php

declare(strict_types=1);

namespace Tuya\Tests\Unit\Core\Exceptions;

use PHPUnit\Framework\TestCase;
use Tuya\Core\Exceptions\AuthenticationException;
use Tuya\Core\Exceptions\EncryptionException;
use Tuya\Core\Exceptions\HttpRequestException;
use Tuya\Core\Exceptions\TuyaApiException;

final class ExceptionsTest extends TestCase
{
    public function test_tuya_api_exception_holds_response_data(): void
    {
        $response = ['error' => 'invalid_token'];
        $e = new TuyaApiException('API error', 400, $response);

        self::assertInstanceOf(\RuntimeException::class, $e);
        self::assertSame('API error', $e->getMessage());
        self::assertSame(400, $e->getCode());
        self::assertSame($response, $e->getResponse());
    }

    public function test_specific_exceptions_extend_base(): void
    {
        self::assertInstanceOf(TuyaApiException::class, new AuthenticationException());
        self::assertInstanceOf(TuyaApiException::class, new EncryptionException());
        self::assertInstanceOf(TuyaApiException::class, new HttpRequestException());
    }
}

