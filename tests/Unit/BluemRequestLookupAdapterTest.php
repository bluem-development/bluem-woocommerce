<?php

namespace Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tests\Support\RequestLookupDatabase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BluemRequestLookupAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        require dirname(__DIR__) . '/Support/RequestPersistenceBootstrap.php';
        require dirname(__DIR__) . '/Support/RequestLookupDatabase.php';
        $GLOBALS['wpdb'] = new RequestLookupDatabase();
    }

    #[DataProvider('lookups')]
    public function testLegacyLookupContract(string $function, array $arguments, string $suffix, array $values, bool $many): void
    {
        $db = $GLOBALS['wpdb'];
        $first = (object) ['id' => 17];
        $second = (object) ['id' => 23];
        $db->results = [$first, $second];

        self::assertSame($many ? [$first, $second] : $first, $function(...$arguments));
        self::assertSame('SELECT * FROM `custom_bluem_requests`' . $suffix,
            preg_replace('/\s+/', ' ', $db->prepared[0][0]));
        self::assertSame($values, $db->prepared[0][1]);
        self::assertSame(['prepared-query'], $db->queries);
        self::assertSame(1, $db->showErrorsCalls);
    }

    public static function lookups(): array
    {
        return [
            ['bluem_db_get_request_by_id', ['17'], ' WHERE `id` = %s', ['17'], false],
            ['bluem_db_get_request_by_debtor_reference', ['debtor'], ' WHERE `debtor_reference` = %s', ['debtor'], false],
            ['bluem_db_get_request_by_transaction_id', ['tx'], ' WHERE `transaction_id` = %s', ['tx'], false],
            ['bluem_db_get_request_by_transaction_id_and_type', ['tx', 'identity'], ' WHERE `transaction_id` = %s AND `type` = %s', ['tx', 'identity'], false],
            ['bluem_db_get_request_by_transaction_id_and_entrance_code', ['tx', 'entrance'], ' WHERE `transaction_id` = %s AND `entrance_code` = %s', ['tx', 'entrance'], false],
            ['bluem_db_get_requests_by_keyvalue', ['order_id', 9, 'timestamp', 'desc', 2], ' WHERE `order_id` = %s ORDER BY `timestamp` DESC LIMIT %d', [9, 2], true],
            ['bluem_db_get_requests_by_keyvalues', [['type' => 'identity', 'user_id' => 8]], ' WHERE `type` = %s AND `user_id` = %s', ['identity', 8], true],
            ['bluem_db_get_requests_by_user_id', [], ' WHERE `user_id` = %s', [42], true],
            ['bluem_db_get_requests_by_user_id', [0], ' WHERE `user_id` = %s', [0], true],
            ['bluem_db_get_requests_by_user_id_and_type', [null, 'identity'], ' WHERE `user_id` = %s AND `type` = %s ORDER BY `timestamp` DESC', [42, 'identity'], true],
            ['bluem_db_get_requests_by_user_id_and_type', [8], ' WHERE `user_id` = %s ORDER BY `timestamp` DESC', [8], true],
            ['bluem_db_get_most_recent_request', [], ' WHERE `user_id` = %d AND `type` = %s ORDER BY `timestamp` DESC LIMIT 1', [42, 'mandates'], false],
            ['bluem_db_get_most_recent_request', [0, 'identity'], ' WHERE `user_id` = %d AND `type` = %s ORDER BY `timestamp` DESC LIMIT 1', [0, 'identity'], false],
        ];
    }

    public function testMissingRecordsKeepDistinctLegacyReturnShapes(): void
    {
        foreach ([[], false] as $results) {
            $GLOBALS['wpdb']->results = $results;
            self::assertFalse(\bluem_db_get_request_by_id('17'));
            self::assertFalse(\bluem_db_get_request_by_transaction_id('tx'));
            self::assertSame([], \bluem_db_get_requests_by_user_id());
            self::assertSame([], \bluem_db_get_requests_by_user_id_and_type());
            self::assertFalse(\bluem_db_get_most_recent_request());
            self::assertSame($results, \bluem_db_get_requests_by_keyvalues());
        }
    }

    public function testDatabaseAndCurrentUserAreResolvedOnEveryCall(): void
    {
        \bluem_db_get_requests_by_user_id();
        $GLOBALS['wpdb'] = new RequestLookupDatabase();
        $GLOBALS['wpdb']->prefix = 'second_blog_';
        $GLOBALS['current_user']->ID = 99;
        \bluem_db_get_requests_by_user_id();
        self::assertSame([['SELECT * FROM `second_blog_bluem_requests` WHERE `user_id` = %s', [99]]], $GLOBALS['wpdb']->prepared);
    }
}
