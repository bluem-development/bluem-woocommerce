<?php
namespace Unit;

use Bluem\BluemPHP\Responses\ErrorBluemResponse;
use Bluem\BluemPHP\Responses\MandateTransactionBluemResponse;
use Bluem\BluemPHP\Transport\HttpTransportInterface;
use Bluem\BluemPHP\Transport\HttpTransportResponse;
use Bluem\Wordpress\Support\BluemMandateDiagnostics;
use PHPUnit\Framework\TestCase;

final class BluemMandateDiagnosticsTest extends TestCase
{
    public function testTransportRecordsHttpMetadataWithoutRetainingBodies(): void
    {
        $transport = new class implements HttpTransportInterface {
            public function send(string $url, array $headers, string $body): HttpTransportResponse {
                return new HttpTransportResponse(401, 'private-response');
            }
        };
        $diagnostics = new BluemMandateDiagnostics();
        $response = $diagnostics->wrap($transport)->send('https://api.invalid', ['secret-header'], 'secret-request');
        self::assertSame('private-response', $response->body);
        $context = $diagnostics->context(new ErrorBluemResponse('Unauthorized: check your access credentials.'));
        self::assertSame(401, $context['http_status']);
        self::assertSame(16, $context['response_bytes']);
        self::assertTrue($context['transport_attempted']);
        self::assertStringNotContainsString('private-response', json_encode($diagnostics));
        self::assertStringNotContainsString('secret-request', json_encode($context));
    }

    public function testTransportExceptionIsPreservedAndMarkedAsAttempted(): void
    {
        $transport = new class implements HttpTransportInterface {
            public function send(string $url, array $headers, string $body): HttpTransportResponse {
                throw new \RuntimeException('Connection failed');
            }
        };
        $diagnostics = new BluemMandateDiagnostics();
        try { $diagnostics->wrap($transport)->send('', [], ''); self::fail('Expected transport failure'); }
        catch (\RuntimeException $error) { self::assertSame('Connection failed', $error->getMessage()); }
        self::assertTrue($diagnostics->context()['transport_attempted']);
        self::assertNull($diagnostics->context()['http_status']);
    }

    public function testXmlSummaryDoesNotContainSensitiveValues(): void
    {
        $response = new MandateTransactionBluemResponse('<EMandateInterface><EMandateErrorResponse><Error><ErrorMessage>Invalid mandate</ErrorMessage></Error><DebtorIBAN>private-iban</DebtorIBAN></EMandateErrorResponse></EMandateInterface>');
        $context = (new BluemMandateDiagnostics())->context($response);
        self::assertSame('Invalid mandate', $context['api_error']);
        self::assertSame('EMandateInterface', $context['response_root']);
        self::assertSame(['EMandateErrorResponse'], $context['response_elements']);
        self::assertStringNotContainsString('private-iban', json_encode($context));
    }

    public function testErrorTextRedactsCredentialsAndEmbeddedXml(): void
    {
        $message = "The value 'private-validation-value' is invalid. " . 'token=private-token accessToken=private-access entranceCode=private-code owner@example.invalid <DebtorAccountName>private-name</DebtorAccountName>';
        $safe = BluemMandateDiagnostics::redact($message);
        foreach (['private-validation-value', 'private-token', 'private-access', 'private-code', 'owner@example.invalid', 'private-name'] as $secret) {
            self::assertStringNotContainsString($secret, $safe);
        }
    }
}
