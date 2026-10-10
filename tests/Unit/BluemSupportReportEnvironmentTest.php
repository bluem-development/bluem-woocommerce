<?php

namespace Unit;

use Bluem\Wordpress\Support\BluemSupportReportEnvironment;
use PHPUnit\Framework\TestCase;

final class BluemSupportReportEnvironmentTest extends TestCase
{
    public function testItCollectsEnvironmentValuesFromInjectedReaders(): void
    {
        $environment = new BluemSupportReportEnvironment(
            static fn(): string => '1.5.4',
            static fn(): string => '2.7.1',
            static fn(): string => '8.4.22',
            static fn(): string => '6.9.0',
            static fn(): string => '10.4.0',
            static fn(): string => 'https://example.test'
        );

        self::assertSame([
            'plugin_version' => '1.5.4',
            'bluem_php_version' => '2.7.1',
            'php_version' => '8.4.22',
            'wordpress_version' => '6.9.0',
            'woocommerce_version' => '10.4.0',
            'site_url' => 'https://example.test',
        ], $environment->collect());
    }
}
