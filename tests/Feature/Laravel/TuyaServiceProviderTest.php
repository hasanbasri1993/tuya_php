<?php

declare(strict_types=1);

namespace Tuya\Tests\Feature\Laravel;

use Orchestra\Testbench\TestCase;
use Tuya\Core\Contracts\CacheAdapterInterface;
use Tuya\Core\Contracts\HttpClientInterface;
use Tuya\Core\SmartLock;
use Tuya\Core\TuyaClient;
use Tuya\Laravel\TuyaServiceProvider;

final class TuyaServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            TuyaServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('tuya', [
            'client_id' => 'id',
            'client_secret' => 'secretsecretsecretsecret12345678',
            'api_url' => 'https://api.tuya.test',
            'cache_ttl' => 3600,
        ]);
    }

    public function test_bindings_are_registered(): void
    {
        $this->assertInstanceOf(HttpClientInterface::class, $this->app->make(HttpClientInterface::class));
        $this->assertInstanceOf(CacheAdapterInterface::class, $this->app->make(CacheAdapterInterface::class));
        $this->assertInstanceOf(TuyaClient::class, $this->app->make(TuyaClient::class));
        $this->assertInstanceOf(SmartLock::class, $this->app->make(SmartLock::class));
    }
}

