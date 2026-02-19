<?php

declare(strict_types=1);

namespace Tuya\Tests\Unit\Core\Cache;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Tuya\Core\Cache\Psr6CacheAdapter;
use Tuya\Core\Contracts\CacheAdapterInterface;

final class Psr6CacheAdapterTest extends TestCase
{
    public function test_implements_cache_adapter_interface(): void
    {
        $pool = $this->createMock(CacheItemPoolInterface::class);
        $adapter = new Psr6CacheAdapter($pool);

        self::assertInstanceOf(CacheAdapterInterface::class, $adapter);
    }

    public function test_set_and_get_via_psr6_pool(): void
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(true);
        $item->method('get')->willReturn(['bar' => 'baz']);

        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->method('getItem')->with($this->equalTo('tuya_foo'))->willReturn($item);
        $pool->expects(self::once())->method('save');

        $adapter = new Psr6CacheAdapter($pool);
        $adapter->set('foo', ['bar' => 'baz'], 60);

        self::assertTrue($adapter->has('foo'));
        self::assertSame(['bar' => 'baz'], $adapter->get('foo'));
    }

    public function test_get_returns_null_when_cache_miss(): void
    {
        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(false);

        $pool = $this->createMock(CacheItemPoolInterface::class);
        $pool->method('getItem')->willReturn($item);

        $adapter = new Psr6CacheAdapter($pool);

        self::assertFalse($adapter->has('foo'));
        self::assertNull($adapter->get('foo'));
    }
}

