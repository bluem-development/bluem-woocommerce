<?php

namespace Unit;

use Bluem\Wordpress\Requests\BluemRequestPayloadUpdater;
use Bluem\Wordpress\Requests\BluemRequestRepository;
use Bluem\Wordpress\Requests\BluemRequestValidator;
use Bluem\Wordpress\Requests\BluemRequestWriter;
use PHPUnit\Framework\TestCase;
use Tests\Support\RequestPayloadDatabase;

final class BluemRequestPayloadUpdaterTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__) . '/Support/RequestPayloadDatabase.php';
    }

    public function testInjectedEncoderReceivesMergedObjectWithoutWordPress(): void
    {
        $db = new RequestPayloadDatabase();
        $db->results = [(object) ['payload' => '{"keep":1,"change":2}']];
        $audits = [];
        $writer = new BluemRequestWriter($db, new BluemRequestValidator(), static fn() => -1,
            static function ($id, $operation, $data) use (&$audits) { $audits[] = [$id, $operation, $data]; });
        $encoded = null;
        $service = new BluemRequestPayloadUpdater(new BluemRequestRepository($db), $writer,
            static function ($payload) use (&$encoded) { $encoded = $payload; return 'injected JSON'; });
        $service->update(17, ['change' => null]);
        self::assertInstanceOf(\stdClass::class, $encoded);
        self::assertSame(['keep' => 1, 'change' => null], (array) $encoded);
        self::assertSame([['payload_bluem_requests', ['payload' => 'injected JSON'], ['id' => 17]]], $db->updates);
        self::assertSame([[17, 'updated', ['payload' => 'injected JSON']]], $audits);
    }

    public function testMissingRequestSkipsEncodingAndWriting(): void
    {
        $db = new RequestPayloadDatabase();
        $fail = static function () { throw new \LogicException('Must not be called'); };
        $writer = new BluemRequestWriter($db, new BluemRequestValidator(), $fail, $fail);
        (new BluemRequestPayloadUpdater(new BluemRequestRepository($db), $writer, $fail))->update(17, []);
        self::assertSame([], $db->updates);
    }

    public function testEncodingFailureRetainsExistingWriteBehavior(): void
    {
        $db = new RequestPayloadDatabase();
        $db->results = [(object) ['payload' => '{}']];
        $writer = new BluemRequestWriter($db, new BluemRequestValidator(), static fn() => -1, static fn() => 1);
        (new BluemRequestPayloadUpdater(new BluemRequestRepository($db), $writer, static fn() => false))->update(17, []);
        self::assertSame(false, $db->updates[0][1]['payload']);
    }
}
