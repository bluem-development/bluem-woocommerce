<?php

namespace Unit;

use Bluem\Wordpress\Support\BluemSupportReportTrace;
use PHPUnit\Framework\TestCase;

final class BluemSupportReportTraceTest extends TestCase
{
    public function testItDropsTheCollectorFrameAndKeepsSafeFrameFields(): void
    {
        $trace = [
            ['function' => 'collector', 'file' => '/plugin/bluem.php', 'line' => 10, 'args' => ['secret']],
            ['class' => 'Example', 'type' => '->', 'function' => 'handle', 'file' => '/plugin/example.php', 'line' => 42],
            ['function' => 'render', 'file' => '/plugin/view.php', 'line' => 8],
        ];

        self::assertSame([
            [
                'function' => 'Example->handle',
                'file' => '/plugin/example.php',
                'line' => 42,
            ],
            [
                'function' => 'render',
                'file' => '/plugin/view.php',
                'line' => 8,
            ],
        ], (new BluemSupportReportTrace())->format($trace));
    }
}
