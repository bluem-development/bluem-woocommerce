<?php

namespace Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tests\Support\RequestLookupDatabase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BluemRequestListFilterTest extends TestCase
{
    #[DataProvider('filters')]
    public function testRequestListUsesSafeArrayFilters(array $query, array $options, array $expected): void
    {
        require dirname(__DIR__) . '/Support/RequestListFilterBootstrap.php';
        $_GET = $query;
        $GLOBALS['bluem_settings_test_options']['bluem_woocommerce_options'] = $options;
        $db = new RequestLookupDatabase();
        $GLOBALS['wpdb'] = $db;
        set_error_handler(static function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            \bluem_requests_view();
            self::fail('Expected to reach the request-list query and rendering boundary.');
        } catch (\BluemRequestListTestStop) {
            // The query and grouping ran through production code.
        } finally {
            restore_error_handler();
        }
        if ($expected === []) {
            self::assertSame([], $db->prepared);
            self::assertStringNotContainsString(' WHERE ', $db->queries[0]);
        } else {
            self::assertSame($expected, $db->prepared[0][1]);
            self::assertSame(count($expected), substr_count($db->prepared[0][0], '`type` = %s'));
        }
    }

    public static function filters(): array
    {
        $off = array_fill_keys(['mandates_enabled', 'idin_enabled', 'ideal_enabled', 'creditcard_enabled', 'paypal_enabled', 'sofort_enabled', 'cartebancaire_enabled'], '0');
        $identityOnly = array_replace($off, ['idin_enabled' => '1']);
        return [
            'missing' => [[], [], ['mandates']],
            'empty' => [['request_type' => ''], [], ['mandates']],
            'array input' => [['request_type' => ['ideal']], [], ['mandates']],
            'invalid single' => [['request_type' => 'unknown'], [], ['mandates']],
            'invalid compound' => [['request_type' => 'unknown,bogus'], [], ['mandates']],
            'valid single' => [['request_type' => 'ideal'], [], ['ideal']],
            'compound preserves order' => [['request_type' => 'ideal,mandates'], [], ['ideal', 'mandates']],
            'invalid leading compound is reindexed' => [['request_type' => 'unknown,ideal'], [], ['ideal']],
            'sanitized filter' => [['request_type' => '<b>ideal</b>'], [], ['ideal']],
            'sparse enabled fallback' => [[], $identityOnly, ['identity']],
            'no enabled modules' => [[], $off, []],
            'explicit known type remains allowed' => [['request_type' => 'ideal'], $identityOnly, ['ideal']],
        ];
    }
}
