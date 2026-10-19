<?php

namespace Unit;

use Bluem\Wordpress\Requests\BluemRequestValidator;
use Bluem\Wordpress\Requests\BluemRequestWriter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BluemRequestWriterTest extends TestCase
{
    #[DataProvider('orderReferences')]
    public function testCreatePreservesOrderReferenceRulesAndInsertId(array $data, bool $linked): void
    {
        $db = new class {
            public string $prefix = 'injected_';
            public int $insert_id = 17;
            public array $inserts = [];
            public function insert($table, $data) { $this->inserts[] = [$table, $data]; return 1; }
        };
        $events = [];
        $writer = new BluemRequestWriter($db, new BluemRequestValidator(),
            static function ($id, $orderId) use (&$events, $db) {
                $events[] = ['link', $id, $orderId];
                $db->insert_id = 93;
                return -1;
            },
            static function ($id, $operation, $request) use (&$events, $db) {
                $events[] = ['log', $id, $operation];
                $db->insert_id = 201;
                return false;
            }
        );
        self::assertSame(17, $writer->create($data));
        self::assertSame([['injected_bluem_requests', $data]], $db->inserts);
        self::assertSame($linked ? [['link', 17, $data['order_id']], ['log', 17, 'created']] : [['log', 17, 'created']], $events);
    }

    public static function orderReferences(): array
    {
        return [
            'missing' => [[], false], 'null' => [['order_id' => null], false],
            'empty' => [['order_id' => ''], false], 'integer' => [['order_id' => 9], true],
            'legacy zero' => [['order_id' => 0], true],
            'legacy string zero' => [['order_id' => '0'], true],
        ];
    }

    public function testFailedWritesNeverInvokeSideEffects(): void
    {
        $db = new class {
            public string $prefix = 'injected_';
            public function insert($table, $data) { return false; }
            public function update($table, $data, $where) { return 0; }
        };
        $fail = static function () { throw new \LogicException('No side effect allowed'); };
        $writer = new BluemRequestWriter($db, new BluemRequestValidator(), $fail, $fail);
        self::assertSame(-1, $writer->create(['order_id' => 9]));
        self::assertFalse($writer->update(17, ['status' => 'Pending']));
    }

    public function testAuditExceptionsStillPropagateAfterWriting(): void
    {
        $db = new class {
            public string $prefix = 'injected_';
            public function update($table, $data, $where) { return 1; }
        };
        $writer = new BluemRequestWriter($db, new BluemRequestValidator(), static fn() => -1,
            static function () { throw new \RuntimeException('log failed'); });
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('log failed');
        $writer->update(17, ['status' => 'Success']);
    }
}
