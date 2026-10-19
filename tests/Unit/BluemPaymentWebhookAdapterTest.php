<?php

namespace Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BluemPaymentWebhookAdapterTest extends TestCase
{
    private function probe(string $status, string $current, string $entrance = 'entrance'): array
    {
        $process = proc_open([PHP_BINARY, dirname(__DIR__) . '/Support/payment-webhook-probe.php', $status, $current, $entrance],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $errors);
        self::assertSame('', $errors);
        return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    }

    #[DataProvider('statuses')]
    public function testOriginalWebhookOrderEffects(string $status, string $current, ?string $target, ?string $note): void
    {
        $result = $this->probe($status, $current);
        self::assertSame(200, $result['http']);
        self::assertSame('OK', $result['body']);
        self::assertSame(['orderby' => 'date', 'order' => 'DESC', 'bluem_transactionid' => 'fixture-transaction'], $result['query']);
        $events = [['get_status'], ['get_user_id'], ['get_user_meta', 7]];
        if ($status === 'Success' && $current === 'processing') {
            $events[] = ['add_order_note', 'Received payment completed webhook notification, but status not updated - it was already processing'];
        } elseif ($target !== null) {
            $events[] = ['get_status'];
            $events[] = ['update_status', $target, $note];
        }
        self::assertSame($events, $result['events']);
        self::assertSame($target ?? $current, $result['status']);
    }

    public static function statuses(): iterable
    {
        foreach (['pending', 'processing', 'completed', 'failed'] as $current) {
            foreach (['Success', 'Failure', 'Cancelled', 'Expired', 'New', 'Open', 'Pending', 'Unexpected'] as $status) {
                $target = match ($status) {
                    'Success' => $current === 'pending' ? 'processing' : null,
                    'Failure' => $current === 'pending' ? 'failed' : null,
                    'Cancelled' => 'cancelled',
                    'Expired', 'Unexpected' => 'failed',
                    default => null,
                };
                $note = match ($status) {
                    'Success' => 'Payment succeeded and was approved via webhook',
                    'Cancelled' => 'Payment was canceled via webhook',
                    'Expired' => 'Payment expired via webhook',
                    default => 'Payment failed: error or unknown status via webhook',
                };
                yield "$current $status" => [$status, $current, $target, $note];
            }
        }
    }

    public function testMissingCorrelationStillStopsBeforeOrderLookup(): void
    {
        $result = $this->probe('Success', 'pending', '');
        self::assertSame(400, $result['http']);
        self::assertSame('Error: Missing payment correlation data', $result['body']);
        self::assertNull($result['query']);
        self::assertSame([], $result['events']);
    }
}
