<?php

declare(strict_types=1);

namespace Tuya\Tests\Unit\Core\Dto;

use PHPUnit\Framework\TestCase;
use Tuya\Core\Dto\AccessTokenResponse;

final class AccessTokenResponseTest extends TestCase
{
    public function test_access_token_response_holds_data(): void
    {
        $raw = ['foo' => 'bar'];
        $dto = new AccessTokenResponse('token123', 'uid456', 1234567890, $raw);

        self::assertSame('token123', $dto->accessToken);
        self::assertSame('uid456', $dto->uid);
        self::assertSame(1234567890, $dto->expiresAt);
        self::assertSame($raw, $dto->raw);
    }
}

