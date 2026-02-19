<?php

declare(strict_types=1);

namespace Tuya\Tests\Feature\Laravel;

use Orchestra\Testbench\TestCase;
use Tuya\Laravel\TuyaServiceProvider;

final class TuyaConfigTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            TuyaServiceProvider::class,
        ];
    }

    public function test_config_values_are_loaded_from_env(): void
    {
        config()->set('tuya.client_id', 'test-id');
        config()->set('tuya.client_secret', 'test-secret');
        config()->set('tuya.api_url', 'https://api.tuya.test');
        config()->set('tuya.cache_ttl', 1234);

        $this->assertSame('test-id', config('tuya.client_id'));
        $this->assertSame('test-secret', config('tuya.client_secret'));
        $this->assertSame('https://api.tuya.test', config('tuya.api_url'));
        $this->assertSame(1234, config('tuya.cache_ttl'));
    }
}

