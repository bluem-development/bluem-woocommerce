<?php

namespace Unit;

use Bluem\Wordpress\Settings\BluemConfigurationBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BluemConfigurationBuilderTest extends TestCase
{
    #[DataProvider('savedValues')]
    public function testSavedValuesKeepTheirTypesAndNullUsesTheDefault($saved, $expected): void
    {
        $config = (new BluemConfigurationBuilder())->build(['setting' => $saved], [
            'setting' => ['default' => 'fallback'],
        ]);
        self::assertSame($expected, $config->setting);
    }

    public static function savedValues(): array
    {
        return [
            ['configured', 'configured'], ['', ''], [false, false], [true, true],
            [0, 0], ['0', '0'], [[], []], [null, 'fallback'],
        ];
    }

    public function testMissingOptionsAndMissingDefaultsKeepLegacyFallbacks(): void
    {
        $config = (new BluemConfigurationBuilder())->build(false, [
            'enabled' => ['default' => '0'],
            'no_default' => [],
            'null_default' => ['default' => null],
            'false_default' => ['default' => false],
        ]);
        self::assertSame([
            'enabled' => '0', 'no_default' => '', 'null_default' => '', 'false_default' => false,
        ], (array) $config);
    }

    public function testFeaturesCanComposeDefinitionsWithoutAWordPressDependency(): void
    {
        $config = (new BluemConfigurationBuilder())->build(['private' => 'ignored'],
            ['shared' => ['default' => 'core'], 'core' => ['default' => 1]],
            ['shared' => ['default' => 'feature'], 'feature' => ['default' => 2]],
            ['shared' => []]
        );
        self::assertSame(['shared' => '', 'core' => 1, 'feature' => 2], (array) $config);
    }

    public function testEachBuildReturnsAFreshConfiguration(): void
    {
        $builder = new BluemConfigurationBuilder();
        $first = $builder->build([], ['environment' => ['default' => 'test']]);
        $first->environment = 'prod';
        self::assertSame('test', $builder->build([], ['environment' => ['default' => 'test']])->environment);
        self::assertSame([], (array) $builder->build(['unknown' => 1]));
    }
}
