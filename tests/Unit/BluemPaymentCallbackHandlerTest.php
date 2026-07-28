<?php

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', __DIR__);
    }

    if (! class_exists('WC_Payment_Gateway')) {
        abstract class WC_Payment_Gateway
        {
        }
    }

    if (! function_exists('esc_html__')) {
        function esc_html__($text, $domain = 'default')
        {
            return $text;
        }
    }

    require_once __DIR__ . '/../../gateways/Bluem_Bank_Based_Payment_Gateway.php';

    final class BluemCallbackTestOrder
    {
        /** @var string */
        private $status;

        /** @var array<int, array{status: string, note: string}> */
        public array $updates = [];

        public function __construct(string $status)
        {
            $this->status = $status;
        }

        public function get_status(): string
        {
            return $this->status;
        }

        public function update_status(string $status, string $note): void
        {
            $this->updates[] = [
                'status' => $status,
                'note' => $note,
            ];
            $this->status = $status;
        }
    }
}

namespace Unit {
    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\TestCase;

    final class BluemPaymentCallbackHandlerTest extends TestCase
    {
        #[DataProvider('correlationProvider')]
        public function testPaymentCorrelationMustContainBothIdentifiers($transactionID, $entranceCode, bool $expected): void
        {
            self::assertSame(
                $expected,
                \Bluem_Bank_Based_Payment_Gateway::hasValidPaymentCorrelation($transactionID, $entranceCode)
            );
        }

        public static function correlationProvider(): array
        {
            return [
                'valid identifiers' => ['transaction-123', 'entrance-123', true],
                'missing transaction' => [null, 'entrance-123', false],
                'empty transaction' => ['', 'entrance-123', false],
                'whitespace transaction' => ['   ', 'entrance-123', false],
                'missing entrance' => ['transaction-123', null, false],
                'empty entrance' => ['transaction-123', '', false],
                'whitespace entrance' => ['transaction-123', '   ', false],
                'array transaction rejected' => [[], 'entrance-123', false],
            ];
        }

        #[DataProvider('terminalTransitionProvider')]
        public function testHandlerAppliesTerminalStatusesToPendingOrders(
            string $paymentStatus,
            string $expectedOrderStatus,
            string $expectedMessage
        ): void {
            $order = new \BluemCallbackTestOrder('pending');

            $result = \Bluem_Bank_Based_Payment_Gateway::applyPaymentStatusTransition(
                $order,
                $paymentStatus,
                'callback'
            );

            self::assertSame($expectedOrderStatus, $result);
            self::assertSame(
                [['status' => $expectedOrderStatus, 'note' => $expectedMessage]],
                $order->updates
            );
        }

        public static function terminalTransitionProvider(): array
        {
            return [
                'success' => ['Success', 'processing', 'Payment has been received (callback)'],
                'failure' => ['Failure', 'failed', 'Payment has expired'],
                'cancelled' => ['Cancelled', 'cancelled', 'Payment has been canceled'],
                'expired' => ['Expired', 'failed', 'Payment has expired'],
                'unknown fails closed' => ['UnexpectedStatus', 'failed', 'Payment failed: error or unknown status'],
            ];
        }

        public function testInProgressCallbackDoesNotUpdateOrder(): void
        {
            foreach (['New', 'Open', 'Pending'] as $paymentStatus) {
                $order = new \BluemCallbackTestOrder('pending');

                self::assertNull(
                    \Bluem_Bank_Based_Payment_Gateway::applyPaymentStatusTransition($order, $paymentStatus)
                );
                self::assertSame([], $order->updates, $paymentStatus);
            }
        }

        public function testSuccessAndFailureDoNotRewriteAnAlreadyAdvancedOrder(): void
        {
            foreach (['processing', 'completed', 'failed', 'cancelled'] as $currentStatus) {
                foreach (['Success', 'Failure'] as $paymentStatus) {
                    $order = new \BluemCallbackTestOrder($currentStatus);

                    self::assertNull(
                        \Bluem_Bank_Based_Payment_Gateway::applyPaymentStatusTransition($order, $paymentStatus)
                    );
                    self::assertSame([], $order->updates, "{$paymentStatus} rewrote {$currentStatus}");
                }
            }
        }

        public function testWebhookUsesWebhookSpecificStatusMessage(): void
        {
            $order = new \BluemCallbackTestOrder('pending');

            self::assertSame(
                'processing',
                \Bluem_Bank_Based_Payment_Gateway::applyPaymentStatusTransition($order, 'Success', 'webhook')
            );
            self::assertSame('Payment succeeded and was approved via webhook', $order->updates[0]['note']);
        }
    }
}
