<?php

namespace Unit;

use Bluem\Wordpress\Payments\BluemPaymentTransition;
use PHPUnit\Framework\TestCase;

final class BluemPaymentTransitionTest extends TestCase
{
    public function testInProgressDoesNotResolveMessagesOrWriteOrders(): void
    {
        $service = new BluemPaymentTransition(static function (): string {
            throw new \LogicException('No message should be requested');
        });
        $order = new class {
            public function get_status() { return 'pending'; }
            public function update_status($status, $note) { throw new \LogicException('No update allowed'); }
        };
        foreach (['New', 'Open', 'Pending'] as $status) {
            self::assertNull($service->apply($order, $status));
        }
    }

    public function testComposesOrderAndNoteProviderWithoutWordPress(): void
    {
        $calls = [];
        $service = new BluemPaymentTransition(static function ($status, $context) use (&$calls): string {
            $calls[] = [$status, $context];
            return 'injected note';
        });
        $order = new class {
            public array $updates = [];
            public function get_status() { return 'pending'; }
            public function update_status($status, $note) { $this->updates[] = [$status, $note]; }
        };
        self::assertSame('processing', $service->apply($order, 'Success', 'webhook'));
        self::assertSame([['Success', 'webhook']], $calls);
        self::assertSame([['processing', 'injected note']], $order->updates);
    }

    public function testNoteProviderFailurePropagatesBeforeWriting(): void
    {
        $service = new BluemPaymentTransition(static function (): string { throw new \RuntimeException('note failed'); });
        $order = new class {
            public function get_status() { return 'pending'; }
            public function update_status($status, $note) { throw new \LogicException('Must not write'); }
        };
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('note failed');
        $service->apply($order, 'Success');
    }
}
