<?php

namespace Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tests\Support\RequestLogLinkDatabase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BluemRequestLogLinkAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        require dirname(__DIR__) . '/Support/RequestPersistenceBootstrap.php';
        require dirname(__DIR__) . '/Support/RequestLogLinkDatabase.php';
        $GLOBALS['wpdb'] = new RequestLogLinkDatabase();
    }

    public function testLogPreservesFieldsUtcAndRawResult(): void
    {
        $db = $GLOBALS['wpdb'];
        foreach ([1, 0, false] as $result) {
            $db->insertResult = $result;
            $before = gmdate('Y-m-d H:i:s');
            self::assertSame($result, \bluem_db_request_log(17, 'description', ['ignored' => true]));
            [$table, $data] = $db->inserts[array_key_last($db->inserts)];
            self::assertSame('custom_bluem_requests_log', $table);
            self::assertSame(['request_id', 'description', 'timestamp', 'user_id'], array_keys($data));
            self::assertSame(17, $data['request_id']);
            self::assertSame('description', $data['description']);
            self::assertSame(42, $data['user_id']);
            self::assertGreaterThanOrEqual($before, $data['timestamp']);
            self::assertLessThanOrEqual(gmdate('Y-m-d H:i:s'), $data['timestamp']);
        }
    }

    public function testLinkVersionBoundaryAndInsertResults(): void
    {
        $db = $GLOBALS['wpdb'];
        foreach ([false, '1.1', '1.2'] as $version) {
            $GLOBALS['bluem_test_options']['bluem_db_version'] = $version;
            self::assertSame(-1, \bluem_db_create_link(17, 8));
        }
        self::assertSame([], $db->inserts);
        $GLOBALS['bluem_test_options']['bluem_db_version'] = '1.21';
        self::assertSame(73, \bluem_db_create_link(17, 8));
        self::assertSame(['custom_bluem_requests_links', ['request_id' => 17, 'item_id' => 8, 'item_type' => 'order']], $db->inserts[0]);
        foreach ([0, false] as $result) {
            $db->insertResult = $result;
            self::assertSame(-1, \bluem_db_create_link(17, 8, 'custom'));
        }
        self::assertSame('custom', $db->inserts[2][1]['item_type']);
    }

    #[DataProvider('readHelpers')]
    public function testReadsPreserveQueryAndReturnShapes(string $helper, string $table, string $where): void
    {
        $db = $GLOBALS['wpdb'];
        foreach ([[], [(object) ['id' => 3]], false, null] as $result) {
            $db->results = $result;
            self::assertSame($result, $helper(17));
        }
        self::assertSame(['SELECT * FROM `custom_' . $table . '` WHERE ' . $where . ' ORDER BY `timestamp` DESC', [17]], $db->prepared[0]);
    }

    public static function readHelpers(): array
    {
        return [
            ['bluem_db_get_logs_for_request', 'bluem_requests_log', '`request_id` = %d'],
            ['bluem_db_get_links_for_request', 'bluem_requests_links', '`request_id` = %d'],
            ['bluem_db_get_links_for_order', 'bluem_requests_links', "`item_id` = %d AND `item_type` = 'order'"],
        ];
    }

    public function testAdaptersResolveDatabaseAndUserOnEachCall(): void
    {
        \bluem_db_request_log(1, 'first');
        $GLOBALS['wpdb'] = new RequestLogLinkDatabase();
        $GLOBALS['wpdb']->prefix = 'other_';
        $GLOBALS['current_user']->ID = 99;
        \bluem_db_request_log(2, 'second');
        self::assertSame('other_bluem_requests_log', $GLOBALS['wpdb']->inserts[0][0]);
        self::assertSame(99, $GLOBALS['wpdb']->inserts[0][1]['user_id']);
        \bluem_db_get_links_for_order(8);
        self::assertStringContainsString('`other_bluem_requests_links`', $GLOBALS['wpdb']->prepared[0][0]);
    }

    #[DataProvider('throwingHelpers')]
    public function testDatabaseExceptionsPropagate(string $helper, array $arguments): void
    {
        $GLOBALS['wpdb']->throw = true;
        $this->expectException(\RuntimeException::class);
        $helper(...$arguments);
    }

    public static function throwingHelpers(): array
    {
        return [
            ['bluem_db_request_log', [17, 'description']],
            ['bluem_db_create_link', [17, 8]],
            ['bluem_db_get_logs_for_request', [17]],
            ['bluem_db_get_links_for_request', [17]],
            ['bluem_db_get_links_for_order', [8]],
        ];
    }
}
