<?php

namespace Unit;

use Bluem\Wordpress\Settings\BluemMandateSettings;
use PHPUnit\Framework\TestCase;

final class BluemMandateSettingsTest extends TestCase
{
    public function testItReturnsMandateSettingsAndPreservesMissingOptionCompatibility(): void
    {
        $settings = new BluemMandateSettings([
            'brandID' => ['default' => ''],
        ]);

        self::assertSame(['default' => ''], $settings->get('brandID'));
        self::assertFalse($settings->get('missing'));
    }
}
