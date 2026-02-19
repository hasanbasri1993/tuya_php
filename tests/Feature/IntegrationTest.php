<?php

declare(strict_types=1);

namespace Tuya\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Tuya\Core\TuyaClient;

final class IntegrationTest extends TestCase
{
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

    public function test_tuya_service_provider_is_auto_registered(): void
    {
        $this->app->register(\Tuya\Laravel\TuyaServiceProvider::class);
        $this->assertInstanceOf(TuyaClient::class, $this->app->make(TuyaClient::class));
    }
}

