<?php

declare(strict_types=1);

namespace Bluem\Wordpress\Support;

use Bluem\BluemPHP\Interfaces\BluemResponseInterface;
use Bluem\BluemPHP\Transport\HttpTransportInterface;
use Bluem\BluemPHP\Transport\HttpTransportResponse;
use Bluem\Wordpress\Observability\BluemSentry;
use SimpleXMLElement;

/** Collect technical context without retaining request or response bodies. */
final class BluemMandateDiagnostics
{
    public ?int $httpStatus = null;
    public ?int $responseBytes = null;
    public bool $transportAttempted = false;
    public string $mandateId = '';
    public string $phase = 'initialize';

    public function wrap(HttpTransportInterface $transport): HttpTransportInterface
    {
        return new class($transport, $this) implements HttpTransportInterface {
            public function __construct(
                private HttpTransportInterface $transport,
                private BluemMandateDiagnostics $diagnostics
            ) {}

            public function send(string $url, array $headers, string $body): HttpTransportResponse
            {
                $this->diagnostics->transportAttempted = true;
                $response = $this->transport->send($url, $headers, $body);
                $this->diagnostics->httpStatus = $response->statusCode;
                $this->diagnostics->responseBytes = strlen($response->body);
                return $response;
            }
        };
    }

    public static function errorMessage(mixed $response): string
    {
        foreach (['EMandateTransactionResponse', 'EMandateErrorResponse'] as $node) {
            if (isset($response->{$node}->Error->ErrorMessage)) {
                return self::redact((string) $response->{$node}->Error->ErrorMessage);
            }
        }

        // Use the interface rather than an exact class name: SDK namespaces changed.
        return $response instanceof BluemResponseInterface
            ? self::redact($response->Error())
            : '';
    }

    public static function redact(string $message): string
    {
        // Do not expose XML values embedded in schema validation errors.
        $message = preg_replace('/<[^>]+>[^<]*/s', '[xml] ', $message) ?? '';
        // libxml describes invalid submitted values in quoted prose, too.
        $message = preg_replace('/\b[Vv]alue\s+([\'"]).*?\1/s', 'value [redacted]', $message) ?? '';
        $message = preg_replace('/\b(?:password|secret|token|access[_-]?token|api[_-]?key|entrance[_-]?code)\s*[=:]\s*[^\s,;]+/i', '[secret]', $message) ?? '';
        return BluemSentry::redactMessage($message);
    }

    public function context(mixed $response = null): array
    {
        $context = [
            'phase' => $this->phase,
            'response_class' => get_debug_type($response),
            'transport_attempted' => $this->transportAttempted,
            'http_status' => $this->httpStatus,
            'response_bytes' => $this->responseBytes,
            'mandate_id' => $this->mandateId,
        ];
        if ($response !== null) {
            $context['api_error'] = self::errorMessage($response);
            $context['transaction_url_present'] = trim((string) ($response->EMandateTransactionResponse->TransactionURL ?? '')) !== '';
            if ($response instanceof SimpleXMLElement) {
                // Names and presence only; no debtor data, URLs or entrance codes.
                $context['response_root'] = $response->getName();
                $context['response_elements'] = array_map(
                    static fn (SimpleXMLElement $node): string => $node->getName(),
                    iterator_to_array($response->children(), false)
                );
            }
        }
        return $context;
    }
}
