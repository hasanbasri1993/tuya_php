<?php

declare(strict_types=1);

namespace Tuya\Laravel;

use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Tuya\Core\Cache\FileCacheAdapter;
use Tuya\Core\Contracts\CacheAdapterInterface;
use Tuya\Core\Contracts\HttpClientInterface;
use Tuya\Core\Http\GuzzleHttpClient;
use Tuya\Core\SmartLock;
use Tuya\Core\TuyaClient;

final class TuyaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/tuya.php', 'tuya');

        $this->app->singleton(HttpClientInterface::class, fn (): HttpClientInterface => new GuzzleHttpClient(new Client()));

        $this->app->singleton(CacheAdapterInterface::class, fn (): CacheAdapterInterface => new FileCacheAdapter(storage_path('framework/cache/tuya')));

        $this->app->singleton(TuyaClient::class, function ($app): TuyaClient {
            $config = $app['config']->get('tuya');

            return new TuyaClient(
                httpClient: $app->make(HttpClientInterface::class),
                cache: $app->make(CacheAdapterInterface::class),
                clientId: (string) ($config['client_id'] ?? ''),
                clientSecret: (string) ($config['client_secret'] ?? ''),
                apiUrl: (string) ($config['api_url'] ?? ''),
                cacheTtl: (int) ($config['cache_ttl'] ?? 3600),
            );
        });

        $this->app->singleton(SmartLock::class, function ($app): SmartLock {
            $config = $app['config']->get('tuya');

            return new SmartLock(
                client: $app->make(TuyaClient::class),
                httpClient: $app->make(HttpClientInterface::class),
                clientSecret: (string) ($config['client_secret'] ?? ''),
                apiUrl: (string) ($config['api_url'] ?? ''),
                clientId: (string) ($config['client_id'] ?? ''),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/config/tuya.php' => config_path('tuya.php'),
        ], 'tuya-config');
    }
}

