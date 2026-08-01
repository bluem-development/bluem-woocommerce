<?php

namespace Unit;

use Bluem\Wordpress\Settings\BluemIntegrationSettings;
use PHPUnit\Framework\TestCase;

final class BluemIntegrationSettingsTest extends TestCase
{
    public function testItReturnsIntegrationSettingsAndPreservesMissingOptionCompatibility(): void
    {
        $settings = new BluemIntegrationSettings([
            'gformActive' => ['default' => 'N'],
        ]);

        self::assertSame(['default' => 'N'], $settings->get('gformActive'));
        self::assertFalse($settings->get('missing'));
    }
}
