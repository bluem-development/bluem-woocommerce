<?php

namespace Unit;

use Bluem\Wordpress\Presentation\BluemRequestTypeLabeler;
use PHPUnit\Framework\TestCase;

final class BluemRequestTypeLabelerTest extends TestCase
{
    public function testKnownTypesUseTheConfiguredTranslator(): void
    {
        $labeler = new BluemRequestTypeLabeler(
            static fn(string $label): string => '[' . $label . ']'
        );

        self::assertSame('[iDEAL]', $labeler->label(' IDEAL '));
        self::assertSame('[eMandate]', $labeler->label('mandates'));
    }

    public function testUnknownAndMissingTypesHaveSafeFallbacks(): void
    {
        $labeler = new BluemRequestTypeLabeler();

        self::assertSame('Legacytype', $labeler->label('legacytype'));
        self::assertSame('Unknown', $labeler->label(null));
    }
}
