<?php

namespace Unit;

use Bluem\Wordpress\Settings\BluemPaymentSettings;
use PHPUnit\Framework\TestCase;

final class BluemPaymentSettingsTest extends TestCase
{
    public function testItReturnsPaymentSettingsAndPreservesMissingOptionCompatibility(): void
    {
        $settings = new BluemPaymentSettings([
            'paymentsIDEALBrandID' => ['default' => ''],
        ]);

        self::assertSame(['default' => ''], $settings->get('paymentsIDEALBrandID'));
        self::assertFalse($settings->get('missing'));
    }
}
