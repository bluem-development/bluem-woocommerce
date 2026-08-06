<?php

namespace Unit;

use Bluem\Wordpress\Users\BluemUserIndexer;
use PHPUnit\Framework\TestCase;

final class BluemUserIndexerTest extends TestCase
{
    public function testItIndexesUsersByTheirWordPressId(): void
    {
        $first = (object) ['ID' => 12, 'user_login' => 'first'];
        $second = (object) ['ID' => 34, 'user_login' => 'second'];

        self::assertSame([
            12 => $first,
            34 => $second,
        ], (new BluemUserIndexer())->index([$first, $second]));
    }
}
