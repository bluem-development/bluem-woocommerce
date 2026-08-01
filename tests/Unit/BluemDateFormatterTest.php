<?php

namespace Unit;

use Bluem\Wordpress\Presentation\BluemDateFormatter;
use PHPUnit\Framework\TestCase;

final class BluemDateFormatterTest extends TestCase
{
    public function testItFormatsDatesInThePluginTimezone(): void
    {
        $formatter = new BluemDateFormatter();

        self::assertSame(
            '01-01-2026 01:00:00',
            $formatter->format('2026-01-01 00:00:00 UTC')
        );
    }

    public function testItSupportsCustomFormatsAndTimezones(): void
    {
        $formatter = new BluemDateFormatter('UTC');

        self::assertSame(
            '2026/01/01 00:00',
            $formatter->format('2026-01-01 00:00:00 UTC', 'Y/m/d H:i')
        );
    }

    public function testInvalidDatesReturnAnEmptyString(): void
    {
        self::assertSame('', (new BluemDateFormatter())->format('not-a-date'));
    }
}
