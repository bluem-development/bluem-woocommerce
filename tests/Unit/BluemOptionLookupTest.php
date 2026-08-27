<?php

namespace Unit;

use Bluem\Wordpress\Settings\BluemOptionLookup;
use PHPUnit\Framework\TestCase;

final class BluemOptionLookupTest extends TestCase
{
    public function testItReturnsConfiguredOptions(): void
    {
        $lookup = new BluemOptionLookup([
            'environment' => ['default' => 'test'],
            'senderID' => ['default' => ''],
        ]);

        self::assertSame(['default' => 'test'], $lookup->get('environment'));
        self::assertSame(['default' => ''], $lookup->get('senderID'));
    }

    public function testMissingOptionsReturnFalseForCompatibility(): void
    {
        self::assertFalse((new BluemOptionLookup([]))->get('missing'));
    }
}
