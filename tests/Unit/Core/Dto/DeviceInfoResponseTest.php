<?php

declare(strict_types=1);

namespace Tuya\Tests\Unit\Core\Dto;

use PHPUnit\Framework\TestCase;
use Tuya\Core\Dto\DeviceInfoResponse;

final class DeviceInfoResponseTest extends TestCase
{
    public function test_device_info_response_holds_data(): void
    {
        $raw = ['foo' => 'bar'];
        $dto = new DeviceInfoResponse('dev123', 'Front Door', true, $raw);

        self::assertSame('dev123', $dto->deviceId);
        self::assertSame('Front Door', $dto->name);
        self::assertTrue($dto->online);
        self::assertSame($raw, $dto->raw);
    }
}

