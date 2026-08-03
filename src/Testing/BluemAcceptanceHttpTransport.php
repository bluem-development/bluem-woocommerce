<?php

declare(strict_types=1);

namespace Bluem\Wordpress\Testing;

use Bluem\BluemPHP\Transport\HttpTransportInterface;
use Bluem\BluemPHP\Transport\HttpTransportResponse;
use RuntimeException;

/**
 * Local-only transport for the Docker acceptance environment.
 */
final class BluemAcceptanceHttpTransport implements HttpTransportInterface
{
    public function __construct(private readonly string $endpoint)
    {
    }

    public function send(string $url, array $headers, string $body): HttpTransportResponse
    {
        $curl = curl_init();

        if ($curl === false) {
            throw new RuntimeException('Unable to initialize acceptance mock transport');
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_URL => $this->endpoint,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $errorMessage = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            throw new RuntimeException($errorMessage !== '' ? $errorMessage : 'Acceptance mock request failed');
        }

        return new HttpTransportResponse($statusCode, (string) $response);
    }
}
