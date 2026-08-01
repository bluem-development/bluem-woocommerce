<?php

namespace Unit;

use Bluem\Wordpress\Identity\BluemAgeCalculator;
use PHPUnit\Framework\TestCase;

final class BluemAgeCalculatorTest extends TestCase
{
    public function testItCalculatesElapsed365DayPeriods(): void
    {
        $calculator = new BluemAgeCalculator();
        $now = strtotime('2026-01-01 00:00:00 UTC');

        self::assertSame(18, $calculator->calculate('2008-01-02 00:00:00 UTC', $now));
        self::assertSame(17, $calculator->calculate('2008-01-07 00:00:00 UTC', $now));
    }

    public function testItAcceptsBluemDateStrings(): void
    {
        $calculator = new BluemAgeCalculator();
        $now = strtotime('2026-01-01 00:00:00 UTC');

        self::assertSame(25, $calculator->calculate('2001-01-01', $now));
    }
}
