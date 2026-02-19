<?php

declare(strict_types=1);

namespace Tuya\Tests\Unit\Core\Contracts;

use PHPUnit\Framework\TestCase;
use Tuya\Core\Contracts\HttpClientInterface;

final class HttpClientInterfaceTest extends TestCase
{
    public function test_interface_exists(): void
    {
        self::assertTrue(interface_exists(HttpClientInterface::class));
    }

    public function test_signatures_match_expected_contract(): void
    {
        $reflection = new \ReflectionClass(HttpClientInterface::class);

        foreach (['get', 'post', 'delete'] as $methodName) {
            self::assertTrue($reflection->hasMethod($methodName));

            $method = $reflection->getMethod($methodName);
            self::assertSame('array', (string) $method->getReturnType());
            self::assertTrue($method->getReturnType() !== null && !$method->getReturnType()->allowsNull());

            $params = $method->getParameters();
            self::assertGreaterThanOrEqual(1, count($params));
            self::assertSame('string', (string) $params[0]->getType());
        }
    }
}

