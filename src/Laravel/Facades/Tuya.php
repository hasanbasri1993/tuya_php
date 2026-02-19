<?php

declare(strict_types=1);

namespace Tuya\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Tuya\Core\SmartLock;
use Tuya\Core\TuyaClient;

/**
 * @method static \Tuya\Core\Dto\AccessTokenResponse getAccessToken()
 * @method static SmartLock smartLock()
 */
final class Tuya extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return TuyaClient::class;
    }

    public static function smartLock(): SmartLock
    {
        /** @var SmartLock $lock */
        $lock = app(SmartLock::class);

        return $lock;
    }
}

