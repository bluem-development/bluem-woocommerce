<?php

namespace Unit;

use Bluem\Wordpress\Support\BluemModuleStatus;
use PHPUnit\Framework\TestCase;

final class BluemModuleStatusTest extends TestCase
{
    public function testItPreservesModuleEnablementBehavior(): void
    {
        self::assertTrue((new BluemModuleStatus([]))->isEnabled('payments'));
        self::assertTrue((new BluemModuleStatus(['payments_enabled' => '1']))->isEnabled('payments'));
        self::assertFalse((new BluemModuleStatus(['payments_enabled' => '0']))->isEnabled('payments'));
        self::assertFalse((new BluemModuleStatus(false))->isEnabled('payments'));
    }
    public function testPreservesLegacyOptionValueComparison(): void
    {
        foreach ([1, true, '01', 1.0] as $value) {
            self::assertTrue((new BluemModuleStatus(['payments_enabled' => $value]))->isEnabled('payments'));
        }
        self::assertTrue((new BluemModuleStatus(['payments_enabled' => null]))->isEnabled('payments'));
        self::assertFalse((new BluemModuleStatus(['payments_enabled' => false]))->isEnabled('payments'));
    }
}
