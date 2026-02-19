<?php

declare(strict_types=1);

namespace Tuya\Tests\Unit\Core\Contracts;

use PHPUnit\Framework\TestCase;
use Tuya\Core\Contracts\CacheAdapterInterface;

final class CacheAdapterInterfaceTest extends TestCase
{
    public function test_interface_exists(): void
    {
        self::assertTrue(interface_exists(CacheAdapterInterface::class));
    }

    public function test_signatures_match_expected_contract(): void
    {
        $reflection = new \ReflectionClass(CacheAdapterInterface::class);

        self::assertTrue($reflection->hasMethod('get'));
        self::assertTrue($reflection->hasMethod('set'));
        self::assertTrue($reflection->hasMethod('forget'));
        self::assertTrue($reflection->hasMethod('has'));

        $get = $reflection->getMethod('get');
        self::assertSame('get', $get->getName());
        self::assertSame('?array', (string) $get->getReturnType());
        self::assertTrue($get->getReturnType() !== null && $get->getReturnType()->allowsNull());

        $params = $get->getParameters();
        self::assertCount(1, $params);
        self::assertSame('string', (string) $params[0]->getType());

        $set = $reflection->getMethod('set');
        $setParams = $set->getParameters();
        self::assertCount(3, $setParams);
        self::assertSame('string', (string) $setParams[0]->getType());
        self::assertSame('array', (string) $setParams[1]->getType());
        self::assertSame('int', (string) $setParams[2]->getType());

        $forget = $reflection->getMethod('forget');
        $forgetParams = $forget->getParameters();
        self::assertCount(1, $forgetParams);
        self::assertSame('string', (string) $forgetParams[0]->getType());

        $has = $reflection->getMethod('has');
        $hasParams = $has->getParameters();
        self::assertCount(1, $hasParams);
        self::assertSame('string', (string) $hasParams[0]->getType());
        self::assertSame('bool', (string) $has->getReturnType());
    }
}

