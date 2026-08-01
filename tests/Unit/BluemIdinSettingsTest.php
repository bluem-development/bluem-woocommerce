<?php

namespace Unit;

use Bluem\Wordpress\Settings\BluemIdinSettings;
use PHPUnit\Framework\TestCase;

final class BluemIdinSettingsTest extends TestCase
{
    public function testItReturnsIdinSettingsAndPreservesMissingOptionCompatibility(): void
    {
        $settings = new BluemIdinSettings([
            'IDINBrandID' => ['default' => ''],
        ]);

        self::assertSame(['default' => ''], $settings->get('IDINBrandID'));
        self::assertFalse($settings->get('missing'));
    }
}
