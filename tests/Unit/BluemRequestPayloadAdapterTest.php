<?php

namespace Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tests\Support\RequestPayloadDatabase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BluemRequestPayloadAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        require dirname(__DIR__) . '/Support/RequestPersistenceBootstrap.php';
        require dirname(__DIR__) . '/Support/RequestPayloadDatabase.php';
        $GLOBALS['wpdb'] = new RequestPayloadDatabase();
    }

    #[DataProvider('payloads')]
    public function testPayloadNormalizationMergeAndLogging(string $existing, mixed $data, string $expected): void
    {
        $db = $GLOBALS['wpdb'];
        $db->results = [(object) ['id' => 17, 'payload' => $existing]];
        self::assertNull(\bluem_db_put_request_payload(17, $data));
        self::assertSame([['payload_bluem_requests', ['payload' => $expected], ['id' => 17]]], $db->updates);
        self::assertCount(1, $db->logs);
        self::assertSame(17, $db->logs[0][1]['request_id']);
    }

    public static function payloads(): array
    {
        return [
            'object preserves unrelated fields' => ['{"keep":1,"overwrite":"old"}', ['overwrite' => false, 'added' => null], '{"keep":1,"overwrite":false,"added":null}'],
            'array repaired' => ['[1,2]', ['flag' => true], '{"flag":true}'],
            'invalid repaired' => ['{broken', ['flag' => true], '{"flag":true}'],
            'empty repaired' => ['', [], '{}'],
            'null repaired' => ['null', [], '{}'],
            'scalar repaired' => ['42', [], '{}'],
            'string repaired' => ['"text"', [], '{}'],
            'object changes accepted' => ['{}', (object) ['nested' => ['one' => 1]], '{"nested":{"one":1}}'],
        ];
    }

    public function testMissingRequestDoesNotWriteOrLog(): void
    {
        self::assertNull(\bluem_db_put_request_payload(17, ['flag' => true]));
        self::assertSame([], $GLOBALS['wpdb']->updates);
        self::assertSame([], $GLOBALS['wpdb']->logs);
    }

    public function testFailedUpdateStillReturnsNullWithoutLogging(): void
    {
        $db = $GLOBALS['wpdb'];
        $db->results = [(object) ['payload' => '{}']];
        $db->updateResult = false;
        self::assertNull(\bluem_db_put_request_payload(17, ['flag' => true]));
        self::assertCount(1, $db->updates);
        self::assertSame([], $db->logs);
    }

    public function testUpdateExceptionsPropagate(): void
    {
        $db = $GLOBALS['wpdb'];
        $db->results = [(object) ['payload' => '{}']];
        $db->throwOnUpdate = true;
        $this->expectException(\RuntimeException::class);
        \bluem_db_put_request_payload(17, ['flag' => true]);
    }
}
