<?php

namespace Unit;

use Bluem\Wordpress\Requests\BluemRequestLinkRepository;
use Bluem\Wordpress\Requests\BluemRequestLogRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\RequestLogLinkDatabase;

final class BluemRequestLogLinkRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/Support/RequestLogLinkDatabase.php';
    }

    public function testLogUsesInjectedIdentityAndTimestampWithoutWordPress(): void
    {
        $db = new RequestLogLinkDatabase();
        $logs = new BluemRequestLogRepository($db);
        self::assertSame(1, $logs->append(17, 'note', '2026-01-02 03:04:05', null));
        self::assertSame(['custom_bluem_requests_log', [
            'request_id' => 17, 'description' => 'note',
            'timestamp' => '2026-01-02 03:04:05', 'user_id' => null,
        ]], $db->inserts[0]);
    }

    public function testLinkReturnsInsertedIdAndPreservesCustomAssociationType(): void
    {
        $db = new RequestLogLinkDatabase();
        $links = new BluemRequestLinkRepository($db);
        self::assertSame(73, $links->create(17, 9, 'custom'));
        self::assertSame(['custom_bluem_requests_links', [
            'request_id' => 17, 'item_id' => 9, 'item_type' => 'custom',
        ]], $db->inserts[0]);
        $db->insertResult = false;
        self::assertSame(-1, $links->create(17, 9));
    }

    public function testRepositoriesReturnDatabaseRowsWithoutProjection(): void
    {
        $db = new RequestLogLinkDatabase();
        $db->results = [(object) ['id' => 3, 'description' => 'note']];
        self::assertSame($db->results, (new BluemRequestLogRepository($db))->findForRequest(17));
        $links = new BluemRequestLinkRepository($db);
        self::assertSame($db->results, $links->findForRequest(17));
        self::assertSame($db->results, $links->findForOrder(9));
    }
}
