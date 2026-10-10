<?php

namespace Unit;

use Bluem\Wordpress\Payments\BluemPaymentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BluemPaymentStatusTest extends TestCase
{
    #[DataProvider('inProgressStatusProvider')]
    public function testRecognizesEveryKnownInProgressStatus(string $status): void
    {
        self::assertTrue(BluemPaymentStatus::isInProgress($status));
    }

    public static function inProgressStatusProvider(): array
    {
        return [
            'new' => [BluemPaymentStatus::NEW],
            'open' => [BluemPaymentStatus::OPEN],
            'pending' => [BluemPaymentStatus::PENDING],
        ];
    }

    #[DataProvider('orderTransitionProvider')]
    public function testResolvesOrderTransitions(
        string $bluemStatus,
        string $currentOrderStatus,
        ?string $expectedOrderStatus
    ): void {
        self::assertSame(
            $expectedOrderStatus,
            BluemPaymentStatus::resolveOrderTransition($bluemStatus, $currentOrderStatus)
        );
    }

    public static function orderTransitionProvider(): array
    {
        return [
            'success processes a pending order' => [BluemPaymentStatus::SUCCESS, 'pending', 'processing'],
            'failure fails a pending order' => [BluemPaymentStatus::FAILURE, 'pending', 'failed'],
            'cancelled cancels an order' => [BluemPaymentStatus::CANCELLED, 'processing', 'cancelled'],
            'expired fails an order' => [BluemPaymentStatus::EXPIRED, 'processing', 'failed'],
            'new does not change an order' => [BluemPaymentStatus::NEW, 'pending', null],
            'open does not change an order' => [BluemPaymentStatus::OPEN, 'pending', null],
            'pending does not change an order' => [BluemPaymentStatus::PENDING, 'pending', null],
            'success does not rewrite processing' => [BluemPaymentStatus::SUCCESS, 'processing', null],
            'failure does not rewrite completed' => [BluemPaymentStatus::FAILURE, 'completed', null],
            'unsupported status preserves fail-closed behavior' => ['UnexpectedStatus', 'pending', 'failed'],
        ];
    }

    public function testDoesNotClassifyUnsupportedStatusesAsInProgress(): void
    {
        self::assertFalse(BluemPaymentStatus::isInProgress('BankSelected'));
    }
}
