<?php

namespace Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BluemRequestWriteAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        require dirname(__DIR__) . '/Support/RequestPersistenceBootstrap.php';
    }

    #[DataProvider('writeResults')]
    public function testUpdateKeepsDatabaseResultAndLogContract(mixed $result, bool $expected): void
    {
        $db = new class($result) {
            public string $prefix = 'custom_';
            public array $events = [];
            public function __construct(private mixed $result) {}
            public function update($table, $data, $where) {
                $this->events[] = ['update', $table, $data, $where];
                return $this->result;
            }
            public function insert($table, $data) {
                $this->events[] = ['insert', $table, $data];
                return false; // A failed log never changes the successful update result.
            }
        };
        $GLOBALS['wpdb'] = $db;
        $data = ['status' => 'Pending', 'description' => '<note>'];
        self::assertSame($expected, \bluem_db_update_request(17, $data));
        self::assertSame(['update', 'custom_bluem_requests', $data, ['id' => 17]], $db->events[0]);
        self::assertCount($expected ? 2 : 1, $db->events);
        if ($expected) {
            self::assertSame('custom_bluem_requests_log', $db->events[1][1]);
            self::assertSame('Transaction updated. New data: ' . json_encode($data), $db->events[1][2]['description']);
            self::assertSame(17, $db->events[1][2]['request_id']);
        }
    }

    public static function writeResults(): array
    {
        return [[1, true], [2, true], [0, false], [false, false]];
    }

    public function testCreateKeepsLinkGuardOrderAndRequestIdWhenLogAndLinkFail(): void
    {
        $db = new class {
            public string $prefix = 'custom_';
            public int $insert_id = 0;
            public array $events = [];
            public function insert($table, $data) {
                $this->events[] = [$table, (array) $data];
                $this->insert_id = count($this->events) === 1 ? 17 : 0;
                return count($this->events) === 1 ? 1 : false;
            }
        };
        $GLOBALS['wpdb'] = $db;
        self::assertSame(17, \bluem_db_create_request(['order_id' => 9]));
        self::assertSame(['custom_bluem_requests', 'custom_bluem_requests_links', 'custom_bluem_requests_log'], array_column($db->events, 0));
        self::assertSame(17, $db->events[1][1]['request_id']);
        self::assertSame(17, $db->events[2][1]['request_id']);
        self::assertSame('Request created', $db->events[2][1]['description']);
        $db->events = [];
        $GLOBALS['bluem_test_options']['bluem_db_version'] = 1.2;
        self::assertSame(17, \bluem_db_create_request((object) ['order_id' => 9]));
        self::assertSame(['custom_bluem_requests', 'custom_bluem_requests_log'], array_column($db->events, 0));
    }

    public function testUpdateDatabaseExceptionsPropagateWithoutLogging(): void
    {
        $GLOBALS['wpdb'] = new class {
            public string $prefix = 'custom_';
            public function update($table, $data, $where) { throw new \RuntimeException('update failed'); }
            public function insert($table, $data) { throw new \LogicException('must not log'); }
        };
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('update failed');
        \bluem_db_update_request(17, ['status' => 'Success']);
    }
}
