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

    require_once __DIR__ . '/../../gateways/Bluem_Bank_Based_Payment_Gateway.php';
}

namespace Unit {
    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\TestCase;

    final class BluemPaymentStatusTransitionTest extends TestCase
    {
        #[DataProvider('pendingOrderStatusProvider')]
        public function testPendingOrdersUseOnlyTerminalBluemStatuses(string $bluemStatus, ?string $expectedOrderStatus): void
        {
            self::assertSame(
                $expectedOrderStatus,
                \Bluem_Bank_Based_Payment_Gateway::resolvePaymentStatusTransition($bluemStatus, 'pending')
            );
        }

        public static function pendingOrderStatusProvider(): array
        {
            return [
                'success' => ['Success', 'processing'],
                'failure' => ['Failure', 'failed'],
                'cancelled' => ['Cancelled', 'cancelled'],
                'expired' => ['Expired', 'failed'],
                'new remains pending' => ['New', null],
                'open remains pending' => ['Open', null],
                'pending remains pending' => ['Pending', null],
                'unknown fails closed' => ['UnexpectedStatus', 'failed'],
            ];
        }

        #[DataProvider('nonPendingOrderStatusProvider')]
        public function testSuccessAndFailureCannotRewriteNonPendingOrders(
            string $currentOrderStatus,
            ?string $expectedOrderStatus
        ): void {
            self::assertSame(
                $expectedOrderStatus,
                \Bluem_Bank_Based_Payment_Gateway::resolvePaymentStatusTransition('Success', $currentOrderStatus)
            );
            self::assertSame(
                $expectedOrderStatus,
                \Bluem_Bank_Based_Payment_Gateway::resolvePaymentStatusTransition('Failure', $currentOrderStatus)
            );
        }

        public static function nonPendingOrderStatusProvider(): array
        {
            return [
                'processing' => ['processing', null],
                'on hold' => ['on-hold', null],
                'completed' => ['completed', null],
                'failed' => ['failed', null],
                'cancelled' => ['cancelled', null],
            ];
        }

        public function testInProgressStatusesNeverFailAnOrderRegardlessOfCurrentState(): void
        {
            foreach (['pending', 'processing', 'on-hold', 'completed', 'failed', 'cancelled'] as $currentOrderStatus) {
                foreach (['New', 'Open', 'Pending'] as $bluemStatus) {
                    self::assertNull(
                        \Bluem_Bank_Based_Payment_Gateway::resolvePaymentStatusTransition(
                            $bluemStatus,
                            $currentOrderStatus
                        ),
                        "{$bluemStatus} changed {$currentOrderStatus}"
                    );
                }
            }
        }
    }
}
