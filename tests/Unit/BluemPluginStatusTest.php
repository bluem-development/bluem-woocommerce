<?php

namespace Unit;

use Bluem\Wordpress\Support\BluemPluginStatus;
use PHPUnit\Framework\TestCase;

final class BluemPluginStatusTest extends TestCase
{
    public function testItDetectsSupportedPlugins(): void
    {
        $status = new BluemPluginStatus();
        $plugins = ['woocommerce/woocommerce.php', 'gravityforms/gravityforms.php'];

        self::assertTrue($status->isWooCommerceActive($plugins));
        self::assertFalse($status->isContactForm7Active($plugins));
        self::assertTrue($status->isGravityFormsActive($plugins));
    }

    public function testItDetectsPermalinkConfiguration(): void
    {
        $status = new BluemPluginStatus();

        self::assertTrue($status->hasPermalinks('/%postname%/'));
        self::assertFalse($status->hasPermalinks(''));
        self::assertFalse($status->hasPermalinks(null));
    }
    public function testLegacyAndFilteredPluginComparisonsRemainDistinct(): void
    {
        $status = new BluemPluginStatus();
        self::assertTrue($status->isWooCommerceActive([true], false));
        self::assertFalse($status->isWooCommerceActive([true]));
        self::assertTrue($status->isContactForm7Active([true]));
        self::assertFalse($status->isGravityFormsActive([true]));
        self::assertFalse($status->hasPermalinks(false));
        self::assertFalse($status->hasPermalinks('0'));
    }
}
