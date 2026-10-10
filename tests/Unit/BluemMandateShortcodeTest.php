<?php
namespace Unit;

use Bluem\BluemPHP\Responses\ErrorBluemResponse;
use Bluem\BluemPHP\Responses\MandateStatusBluemResponse;
use Bluem\BluemPHP\Responses\MandateTransactionBluemResponse;
use Bluem\Wordpress\Support\BluemMandateDiagnostics;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BluemMandateShortcodeTest extends TestCase
{
    protected function setUp(): void { require dirname(__DIR__) . '/Support/MandateShortcodeBootstrap.php'; }

    private function execute(): string
    {
        $bufferLevel = ob_get_level();
        try { \bluem_mandate_shortcode_execute_request(new BluemMandateDiagnostics()); }
        catch (\MandateTestStop $stop) { return $stop->getMessage(); }
        finally { while (ob_get_level() > $bufferLevel) { ob_end_clean(); } }
        self::fail('Expected redirect or error prompt');
    }

    public function testSdkErrorIsReportedInsteadOfGeneralError(): void
    {
        $GLOBALS['mandate_response'] = new class('Unauthorized: check your access credentials.') extends ErrorBluemResponse {};
        self::assertStringContainsString('Unauthorized', $this->execute());
        $report = $GLOBALS['mandate_reports'][0];
        self::assertStringContainsString('Unauthorized', $report['api_error']);
        self::assertSame('41', $report['mandate_id']);
        self::assertArrayNotHasKey('mandate_created', $GLOBALS);
        self::assertStringNotContainsString('private-entrance', json_encode($report));
    }

    #[DataProvider('creationCases')]
    public function testCreationUsesUserPropertyAndConfiguredCounter(bool $guest, string $mode): void
    {
        $GLOBALS['mandate_config']->sequenceType = $mode;
        if ($guest) { $GLOBALS['current_user'] = null; $_POST['bluem_debtorReference'] = "guest\\'ref"; }
        $GLOBALS['mandate_response'] = new MandateTransactionBluemResponse('<EMandateInterface><EMandateTransactionResponse><MandateID>41</MandateID><TransactionURL>https://bank.invalid/sign</TransactionURL></EMandateTransactionResponse></EMandateInterface>');
        self::assertSame('https://bank.invalid/sign', $this->execute());
        self::assertSame([$guest ? "guest'ref" : 'member', $guest ? 0 : 7, 41], $GLOBALS['mandate_arguments']);
        self::assertSame(41, $GLOBALS['mandate_options']['bluem_woocommerce_mandate_id_counter']);
        self::assertSame('41', $GLOBALS['mandate_created']['transaction_id']);
    }
    public static function creationCases(): array { return [[false, 'OOFF'], [false, 'RCUR'], [true, 'OOFF']]; }

    #[DataProvider('invalidResponses')]
    public function testInvalidTransactionResponsesAreNotPersistedOrRedirected(string $url, string $id): void
    {
        $GLOBALS['mandate_response'] = new MandateTransactionBluemResponse('<EMandateInterface><EMandateTransactionResponse><MandateID>' . $id . '</MandateID><TransactionURL>' . $url . '</TransactionURL></EMandateTransactionResponse></EMandateInterface>');
        self::assertStringContainsString('Invalid mandate response', $this->execute());
        self::assertCount(1, $GLOBALS['mandate_reports']);
        self::assertArrayNotHasKey('mandate_created', $GLOBALS);
    }
    public static function invalidResponses(): array { return [['', '41'], ['https://bank.invalid/sign', ''], ['javascript:alert(1)', '41']]; }

    #[DataProvider('badPayloads')]
    public function testSuccessfulCallbackRepairsNonObjectPayloads(string $payload): void
    {
        $GLOBALS['mandate_record'] = (object) ['id' => 1, 'entrance_code' => 'private-entrance', 'status' => 'Open', 'user_id' => 7, 'payload' => $payload];
        $GLOBALS['mandate_response'] = new MandateStatusBluemResponse('<EMandateInterface><EMandateStatusUpdate><EMandateStatus><Status>Success</Status><PurchaseID>purchase</PurchaseID><AcceptanceReport><DebtorIBAN>private-bank-data</DebtorIBAN></AcceptanceReport></EMandateStatus></EMandateStatusUpdate></EMandateInterface>');
        try { \bluem_mandate_shortcode_callback_request(new BluemMandateDiagnostics()); }
        catch (\MandateTestStop $stop) { self::assertStringContainsString('result=true', $stop->getMessage()); }
        self::assertSame('purchase', json_decode($GLOBALS['mandate_updates'][1]['payload'])->purchaseID);
        self::assertSame([], $GLOBALS['mandate_reports']);
        self::assertSame($GLOBALS['mandate_updates'], $GLOBALS['mandate_notification_updates']);
    }
    public static function badPayloads(): array { return [['invalid'], ['null'], ['[]'], ['42']]; }

    public function testConfigurationExceptionEndsWithDiagnosticReport(): void
    {
        $GLOBALS['mandate_client_exception'] = new \RuntimeException('Invalid configuration token=private-token');
        try { \bluem_mandate_shortcode_callback(); }
        catch (\MandateTestStop $stop) { self::assertStringContainsString('contact', $stop->getMessage()); }
        self::assertCount(1, $GLOBALS['mandate_reports']);
        self::assertSame('RuntimeException', $GLOBALS['mandate_reports'][0]['exception_class']);
        self::assertStringNotContainsString('private-token', json_encode($GLOBALS['mandate_reports']));
    }

    public function testFailedPersistenceStopsBeforeBankRedirect(): void
    {
        $GLOBALS['mandate_insert_result'] = -1;
        $GLOBALS['mandate_response'] = new MandateTransactionBluemResponse('<EMandateInterface><EMandateTransactionResponse><MandateID>41</MandateID><TransactionURL>https://bank.invalid/sign</TransactionURL></EMandateTransactionResponse></EMandateInterface>');
        try { \bluem_mandate_shortcode_execute(); }
        catch (\MandateTestStop $stop) { self::assertStringContainsString('contact', $stop->getMessage()); }
        self::assertSame('persist_request', $GLOBALS['mandate_reports'][0]['phase']);
        self::assertStringContainsString('Could not persist', $GLOBALS['mandate_reports'][0]['exception_message']);
    }

    public function testMalformedStatusResponseDoesNotUpdateRequest(): void
    {
        $GLOBALS['mandate_record'] = (object) ['id' => 1, 'entrance_code' => 'private-entrance', 'status' => 'Open', 'user_id' => 7, 'payload' => '{}'];
        $GLOBALS['mandate_response'] = new MandateStatusBluemResponse('<EMandateInterface><EMandateStatusUpdate/></EMandateInterface>');
        try { \bluem_mandate_shortcode_callback_request(new BluemMandateDiagnostics()); }
        catch (\MandateTestStop $stop) { self::assertStringContainsString('Missing mandate status', $stop->getMessage()); }
        self::assertSame([], $GLOBALS['mandate_updates']);
        self::assertSame('41', $GLOBALS['mandate_reports'][0]['mandate_id']);
    }

    public function testMalformedDebtorReferenceIsReportedBeforeApiCall(): void
    {
        $GLOBALS['current_user'] = null;
        $_POST['bluem_debtorReference'] = ['invalid'];
        try { \bluem_mandate_shortcode_execute(); }
        catch (\MandateTestStop $stop) { self::assertStringContainsString('contact', $stop->getMessage()); }
        self::assertArrayNotHasKey('mandate_arguments', $GLOBALS);
        self::assertCount(1, $GLOBALS['mandate_reports']);
    }

    public function testTraceLineNumbersAndZeroAreRendered(): void
    {
        ob_start();
        \bluem_render_obj_row_recursive('trace', [['line' => 202], ['line' => 0]]);
        $html = ob_get_clean();
        self::assertStringContainsString('202', $html);
        self::assertMatchesRegularExpression('/0<br>/', $html);
    }
}
