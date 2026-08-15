<?php

namespace Unit;

use Bluem\Wordpress\Requests\BluemEnabledRequestTypeFilter;
use PHPUnit\Framework\TestCase;

final class BluemEnabledRequestTypeFilterTest extends TestCase
{
    public function testIdentityMapsToTheIdinModule(): void
    {
        $checkedModules = [];
        $filter = new BluemEnabledRequestTypeFilter(
            static function (string $moduleId) use (&$checkedModules): bool {
                $checkedModules[] = $moduleId;

                return $moduleId === 'idin';
            }
        );

        self::assertSame(['identity'], array_values($filter->filter(['identity', 'payments'])));
        self::assertSame(['idin', 'payments'], $checkedModules);
    }

    public function testItPreservesTheOriginalArrayKeys(): void
    {
        $filter = new BluemEnabledRequestTypeFilter(
            static fn(string $moduleId): bool => $moduleId !== 'payments'
        );

        self::assertSame(
            [10 => 'ideal', 30 => 'identity'],
            $filter->filter([10 => 'ideal', 20 => 'payments', 30 => 'identity'])
        );
    }
}
