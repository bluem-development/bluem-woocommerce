<?php
namespace Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BluemMandateNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        require dirname(__DIR__) . '/Support/RequestPersistenceBootstrap.php';
        $GLOBALS['wpdb'] = new class {
            public string $prefix = 'test_';
            public array $rows = [];
            public array $updated = [];
            public function show_errors() {}
            public function prepare($query, ...$args) { return $query; }
            public function get_results($query) { return $this->rows; }
            public function update($table, $data, $where) { $this->updated[] = $data; return 1; }
            public function insert($table, $data) { return 1; }
        };
    }

    public function testFactoryWrapsConfiguredTransportAndPreservesSdkErrors(): void
    {
        $GLOBALS['bluem_test_transport'] = new class implements \Bluem\BluemPHP\Transport\HttpTransportInterface {
            public function send(string $url, array $headers, string $body): \Bluem\BluemPHP\Transport\HttpTransportResponse {
                return new \Bluem\BluemPHP\Transport\HttpTransportResponse(401, 'unauthorized');
            }
        };
        $diagnostics = new \Bluem\Wordpress\Support\BluemMandateDiagnostics();
        $client = \bluem_woocommerce_create_client(\bluem_woocommerce_get_config(), $diagnostics);
        $response = $client->MandateStatus('41', 'privateEntrance');
        self::assertInstanceOf(\Bluem\BluemPHP\Responses\ErrorBluemResponse::class, $response);
        self::assertSame(401, $diagnostics->context($response)['http_status'], $response->Error());
        self::assertStringContainsString('Unauthorized', $diagnostics->context($response)['api_error']);
    }

    #[DataProvider('notificationMarkers')]
    public function testSentNotificationMarkerPreventsDuplicateEmail(string $payload): void
    {
        $GLOBALS['wpdb']->rows = [(object) ['payload' => $payload]];
        self::assertFalse(\bluem_transaction_notification_email('1'));
    }
    public static function notificationMarkers(): array { return [['{"sent_notification":true}'], ['{"sent_notification":"true"}']]; }

    #[DataProvider('invalidPayloads')]
    public function testNotificationPayloadWriteRepairsInvalidJson(string $payload): void
    {
        $GLOBALS['wpdb']->rows = [(object) ['payload' => $payload]];
        \bluem_db_put_request_payload(1, ['sent_notification' => true]);
        self::assertSame('{"sent_notification":true}', $GLOBALS['wpdb']->updated[0]['payload']);
    }
    public static function invalidPayloads(): array { return [['invalid'], ['null'], ['[]'], ['42']]; }

    public function testMissingRequestPayloadWriteIsSafeNoOp(): void
    {
        \bluem_db_put_request_payload(1, ['sent_notification' => true]);
        self::assertSame([], $GLOBALS['wpdb']->updated);
    }
}
