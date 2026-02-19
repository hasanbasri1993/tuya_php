<?php

declare(strict_types=1);

namespace Tuya\Tests\Unit\Core\Cache;

use PHPUnit\Framework\TestCase;
use Tuya\Core\Cache\FileCacheAdapter;
use Tuya\Core\Contracts\CacheAdapterInterface;

final class FileCacheAdapterTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tuya-file-cache-' . uniqid();
        mkdir($this->cacheDir, 0777, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->cacheDir)) {
            foreach (glob($this->cacheDir . DIRECTORY_SEPARATOR . '*') as $file) {
                @unlink($file);
            }
            @rmdir($this->cacheDir);
        }
    }

    public function test_implements_cache_adapter_interface(): void
    {
        $adapter = new FileCacheAdapter($this->cacheDir);
        self::assertInstanceOf(CacheAdapterInterface::class, $adapter);
    }

    public function test_set_and_get_within_ttl(): void
    {
        $adapter = new FileCacheAdapter($this->cacheDir);

        $adapter->set('foo', ['bar' => 'baz'], 60);

        self::assertTrue($adapter->has('foo'));
        self::assertSame(['bar' => 'baz'], $adapter->get('foo'));
    }

    public function test_expired_entry_is_not_returned(): void
    {
        $adapter = new FileCacheAdapter($this->cacheDir);

        $adapter->set('foo', ['bar' => 'baz'], -1);

        self::assertFalse($adapter->has('foo'));
        self::assertNull($adapter->get('foo'));
    }

    public function test_forget_removes_entry(): void
    {
        $adapter = new FileCacheAdapter($this->cacheDir);
        $adapter->set('foo', ['bar' => 'baz'], 60);

        $adapter->forget('foo');

        self::assertFalse($adapter->has('foo'));
        self::assertNull($adapter->get('foo'));
    }
}

