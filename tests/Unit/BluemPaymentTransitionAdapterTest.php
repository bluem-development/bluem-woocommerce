<?php

namespace Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BluemPaymentTransitionAdapterTest extends TestCase
{
    protected function setUp(): void
    {
        require dirname(__DIR__) . '/Support/PaymentTransitionBootstrap.php';
    }

    #[DataProvider('transitions')]
    public function testPreservesTransitionAndNote(string $status, string $current, string $context, ?string $target, ?string $note): void
    {
        $order = new class($current) {
            public array $updates = [];
            public function __construct(public string $status) {}
            public function get_status() { return $this->status; }
            public function update_status($status, $note) {
                $this->updates[] = [$status, $note];
                $this->status = $status;
            }
        };
        self::assertSame($target, \Bluem_Bank_Based_Payment_Gateway::applyPaymentStatusTransition($order, $status, $context));
        self::assertSame($target === null ? [] : [[$target, $note]], $order->updates);
    }

    public static function transitions(): iterable
    {
        $callback = [
            'Success' => 'Payment has been received (callback)',
            'Failure' => 'Payment has expired',
            'Cancelled' => 'Payment has been canceled',
            'Expired' => 'Payment has expired',
        ];
        $webhook = [
            'Success' => 'Payment succeeded and was approved via webhook',
            'Cancelled' => 'Payment was canceled via webhook',
            'Expired' => 'Payment expired via webhook',
        ];
        foreach (['callback', 'webhook', 'custom'] as $context) {
            foreach (['pending', 'processing'] as $current) {
                foreach (['Success', 'Failure', 'Cancelled', 'Expired', 'New', 'Open', 'Pending', 'Unexpected'] as $status) {
                    $target = match ($status) {
                        'Success' => $current === 'pending' ? 'processing' : null,
                        'Failure' => $current === 'pending' ? 'failed' : null,
                        'Cancelled' => 'cancelled',
                        'Expired', 'Unexpected' => 'failed',
                        default => null,
                    };
                    $notes = $context === 'callback' ? $callback : ($context === 'webhook' ? $webhook : []);
                    $note = $notes[$status] ?? ($context === 'webhook'
                        ? 'Payment failed: error or unknown status via webhook'
                        : 'Payment failed: error or unknown status');
                    yield "$context $current $status" => [$status, $current, $context, $target, $target === null ? null : $note];
                }
            }
        }
    }

    public function testRepeatedSuccessDoesNotUpdateAgainButRepeatedCancellationStillDoes(): void
    {
        $order = new class {
            public string $status = 'pending';
            public array $updates = [];
            public function get_status() { return $this->status; }
            public function update_status($status, $note) {
                $this->status = $status;
                $this->updates[] = [$status, $note];
            }
        };
        self::assertSame('processing', \Bluem_Bank_Based_Payment_Gateway::applyPaymentStatusTransition($order, 'Success'));
        self::assertNull(\Bluem_Bank_Based_Payment_Gateway::applyPaymentStatusTransition($order, 'Success'));
        self::assertCount(1, $order->updates);
        self::assertSame('cancelled', \Bluem_Bank_Based_Payment_Gateway::applyPaymentStatusTransition($order, 'Cancelled'));
        self::assertSame('cancelled', \Bluem_Bank_Based_Payment_Gateway::applyPaymentStatusTransition($order, 'Cancelled'));
        self::assertCount(3, $order->updates);
    }

    public function testOrderUpdateExceptionsStillPropagate(): void
    {
        $order = new class {
            public function get_status() { return 'pending'; }
            public function update_status($status, $note) { throw new \RuntimeException('update failed'); }
        };
        $this->expectException(\RuntimeException::class);
        \Bluem_Bank_Based_Payment_Gateway::applyPaymentStatusTransition($order, 'Success');
    }
}
