<?php

declare(strict_types=1);

namespace Tuya\Tests\Feature\Laravel;

use Orchestra\Testbench\TestCase;
use Tuya\Core\SmartLock;
use Tuya\Core\TuyaClient;
use Tuya\Laravel\Facades\Tuya;
use Tuya\Laravel\TuyaServiceProvider;

final class FacadeTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            TuyaServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Tuya' => Tuya::class,
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

    public function test_facade_resolves_tuya_client(): void
    {
        $this->assertInstanceOf(TuyaClient::class, $this->app->make(TuyaClient::class));
        $this->assertInstanceOf(TuyaClient::class, Tuya::getFacadeRoot());
    }

    public function test_smartlock_helper_returns_instance(): void
    {
        $this->assertInstanceOf(SmartLock::class, Tuya::smartLock());
    }
}

