<?php

namespace Unit;

use Bluem\Wordpress\Presentation\BluemAdminTabResolver;
use PHPUnit\Framework\TestCase;

final class BluemAdminTabResolverTest extends TestCase
{
    public function testItReturnsTheSelectedTab(): void
    {
        self::assertSame('payments', (new BluemAdminTabResolver())->resolve('payments', 'general'));
    }

    public function testItFallsBackWhenNoTabWasSelected(): void
    {
        self::assertSame('general', (new BluemAdminTabResolver())->resolve(null, 'general'));
        self::assertNull((new BluemAdminTabResolver())->resolve(null));
    }
}
