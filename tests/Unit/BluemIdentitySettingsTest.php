<?php

namespace Unit;

use Bluem\Wordpress\Settings\BluemIdentitySettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BluemIdentitySettingsTest extends TestCase
{
    #[DataProvider('values')]
    public function testPreservesExplicitValues(mixed $value): void
    {
        self::assertSame($value, (new BluemIdentitySettings(['key' => $value]))->get('key'));
    }

    public static function values(): array
    {
        return [
            'definition' => [['default' => '', 'type' => 'text']],
            'null' => [null],
            'false' => [false],
            'zero' => [0],
            'empty string' => [''],
        ];
    }

    public function testMissingAndNumericKeysPreserveLookupSemantics(): void
    {
        $settings = new BluemIdentitySettings([7 => null]);
        self::assertFalse($settings->get('missing'));
        self::assertNull($settings->get(7));
        self::assertNull($settings->get('7'));
    }
}
