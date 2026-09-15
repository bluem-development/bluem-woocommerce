<?php

namespace Unit;

use Bluem\BluemPHP\Transport\HttpTransportInterface;
use Bluem\BluemPHP\Transport\HttpTransportResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BluemRequestPersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        require dirname(__DIR__) . '/Support/RequestPersistenceBootstrap.php';
    }

    #[DataProvider('creationCases')]
    public function testCreationReturnsRequestIdDespiteSubsequentInserts(bool $withOrder, bool $logSucceeds): void
    {
        $db = new class($logSucceeds) {
            public string $prefix = 'test_';
            public int $insert_id = 0;
            public array $inserts = [];
            public function __construct(private bool $logSucceeds) {}
            public function insert($table, $data) {
                $this->inserts[$table] = (array) $data;
                $this->insert_id = match ($table) {
                    'test_bluem_requests' => 17,
                    'test_bluem_requests_links' => 93,
                    'test_bluem_requests_log' => $this->logSucceeds ? 201 : 0,
                };
                return $this->insert_id > 0 ? 1 : false;
            }
        };
        $GLOBALS['wpdb'] = $db;
        $request = ['transaction_id' => 'test-transaction'];
        if ($withOrder) {
            $request['order_id'] = 123;
        }

        self::assertSame(17, \bluem_db_create_request($request));
        self::assertSame(17, $db->inserts['test_bluem_requests_log']['request_id']);
        if ($withOrder) {
            self::assertSame(17, $db->inserts['test_bluem_requests_links']['request_id']);
        } else {
            self::assertArrayNotHasKey('test_bluem_requests_links', $db->inserts);
        }
    }

    public static function creationCases(): array
    {
        return [[true, true], [false, true], [true, false], [false, false]];
    }

    public function testFailedCreationDoesNotWriteLinksOrLogs(): void
    {
        $db = new class {
            public string $prefix = 'test_';
            public array $tables = [];
            public function insert($table, $data) {
                $this->tables[] = $table;
                return false;
            }
        };
        $GLOBALS['wpdb'] = $db;
        self::assertSame(-1, \bluem_db_create_request(['order_id' => 123]));
        self::assertSame(['test_bluem_requests'], $db->tables);
    }

    #[DataProvider('refreshCases')]
    public function testAdminRefreshPersistsStatusAndOnlyAppliesTerminalOrderUpdates(
        string $type,
        string $status,
        ?string $expectedOrderStatus
    ): void {
        $request = (object) [
            'id' => 17, 'order_id' => 123, 'type' => $type, 'status' => 'created',
            'transaction_id' => 'TESTTRANSACTION', 'entrance_code' => 'TESTENTRANCE', 'payload' => '',
        ];
        $db = new class($request) {
            public string $prefix = 'test_';
            public array $updates = [];
            public function __construct(public object $request) {}
            public function prepare($sql, ...$args) { return $sql; }
            public function get_results($sql) { return [$this->request]; }
            public function update($table, $data, $where) {
                $this->updates[] = [$table, $data, $where];
                return 1;
            }
            public function insert($table, $data) { return 1; }
        };
        $order = new class {
            public array $updates = [];
            public array $notes = [];
            public function update_status($status, $note) { $this->updates[] = $status; }
            public function add_order_note($note) { $this->notes[] = $note; }
        };
        $GLOBALS['wpdb'] = $db;
        $GLOBALS['bluem_test_orders'] = [123 => $order];
        $transport = new class($type, $status) implements HttpTransportInterface {
            public int $calls = 0;
            public function __construct(private string $type, private string $status) {}
            public function send(string $url, array $headers, string $body): HttpTransportResponse {
                ++$this->calls;
                $status = $this->status;
                $xml = $this->type === 'mandates'
                    ? '<EMandateInterface type="StatusUpdate"><EMandateStatusUpdate><EMandateStatus><Status>' . $status . '</Status></EMandateStatus></EMandateStatusUpdate></EMandateInterface>'
                    : '<EPaymentInterface type="StatusUpdate"><PaymentStatusUpdate><Status>' . $status . '</Status></PaymentStatusUpdate></EPaymentInterface>';
                return new HttpTransportResponse(200, $xml);
            }
        };
        $GLOBALS['bluem_test_transport'] = $transport;

        \bluem_update_request_by_id(17);

        self::assertSame(1, $transport->calls);
        self::assertSame([['test_bluem_requests', ['status' => $status], ['id' => 17]]], $db->updates);
        self::assertSame($expectedOrderStatus === null ? [] : [$expectedOrderStatus], $order->updates);
        self::assertCount($status === 'Success' ? 1 : 0, $order->notes);
    }

    public static function refreshCases(): iterable
    {
        foreach (['mandates', 'ideal', 'creditcard', 'paypal', 'sofort', 'cartebancaire'] as $type) {
            foreach ([
                'New' => null, 'Open' => null, 'Pending' => null,
                'Success' => 'processing', 'Failure' => 'failed',
                'Cancelled' => 'cancelled', 'Expired' => 'failed',
            ] as $status => $target) {
                yield "$type $status" => [$type, $status, $target];
            }
        }
    }
}
