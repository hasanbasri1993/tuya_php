<?php

declare(strict_types=1);

namespace Tuya\Tests\Unit\Core\Enums;

use PHPUnit\Framework\TestCase;
use Tuya\Core\Enums\TuyaRegion;

final class TuyaRegionTest extends TestCase
{
    public function test_enum_has_expected_regions(): void
    {
        $values = array_map(static fn (TuyaRegion $r) => $r->value, TuyaRegion::cases());

        self::assertContains('eu', $values);
        self::assertContains('us', $values);
        self::assertContains('cn', $values);
        self::assertContains('in', $values);
    }
}

