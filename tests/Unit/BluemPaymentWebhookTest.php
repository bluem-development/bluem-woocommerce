<?php

namespace Unit;

use Bluem\Wordpress\Payments\BluemPaymentTransition;
use Bluem\Wordpress\Payments\BluemPaymentWebhook;
use PHPUnit\Framework\TestCase;

final class BluemPaymentWebhookTest extends TestCase
{
    public function testAlreadyProcessingUsesOnlyTheInjectedNote(): void
    {
        $transition = new BluemPaymentTransition(static function (): string { throw new \LogicException('Must not transition'); });
        $handler = new BluemPaymentWebhook($transition, static fn($status): string => 'already ' . $status);
        $order = new class {
            public array $notes = [];
            public function add_order_note($note) { $this->notes[] = $note; }
        };
        $handler->apply($order, 'Success', 'processing');
        self::assertSame(['already processing'], $order->notes);
    }

    public function testInProgressAndCompletedSuccessHaveNoSideEffects(): void
    {
        $fail = static function (): string { throw new \LogicException('No note allowed'); };
        $handler = new BluemPaymentWebhook(new BluemPaymentTransition($fail), $fail);
        $order = new \stdClass(); // No order operations should be called.
        foreach (['New', 'Open', 'Pending'] as $status) {
            $handler->apply($order, $status, 'pending');
        }
        $handler->apply($order, 'Success', 'completed');
        self::assertSame([], get_object_vars($order));
    }

    public function testTransitionRetainsSecondStatusReadWhenSnapshotIsPending(): void
    {
        $fail = static function (): string { throw new \LogicException('No note allowed'); };
        $handler = new BluemPaymentWebhook(new BluemPaymentTransition($fail), $fail);
        $order = new class {
            public int $reads = 0;
            public function get_status() { ++$this->reads; return 'processing'; }
            public function update_status($status, $note) { throw new \LogicException('Must not update'); }
        };
        $handler->apply($order, 'Success', 'pending');
        self::assertSame(1, $order->reads);
    }

    public function testNoteExceptionsPropagateToTheTransportAdapter(): void
    {
        $handler = new BluemPaymentWebhook(new BluemPaymentTransition(static fn() => ''),
            static function (): string { throw new \RuntimeException('note failed'); });
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('note failed');
        $order = new class {
            public function add_order_note($note) { throw new \LogicException('Must not write the note'); }
        };
        $handler->apply($order, 'Success', 'processing');
    }
}
