<?php

namespace Bluem\Wordpress\Payments;

use Closure;

/** Order effects only; the gateway retains transport, correlation and HTTP output. */
final class BluemPaymentWebhook
{
    /** @param Closure(string): string $alreadyProcessingNote Receives the observed order status. */
    public function __construct(
        private readonly BluemPaymentTransition $transition,
        private readonly Closure $alreadyProcessingNote
    ) {
    }

    /** The adapter supplies its pre-metadata order-status snapshot. */
    public function apply($order, string $paymentStatus, string $currentOrderStatus): void
    {
        $target = BluemPaymentStatus::resolveOrderTransition($paymentStatus, $currentOrderStatus);
        if ($paymentStatus === BluemPaymentStatus::SUCCESS) {
            if ($currentOrderStatus === 'processing') {
                $order->add_order_note(($this->alreadyProcessingNote)($currentOrderStatus));
            } elseif ($target === 'processing') {
                $this->transition->apply($order, $paymentStatus, 'webhook');
            }
        } elseif ($target !== null) {
            $this->transition->apply($order, $paymentStatus, 'webhook');
        }
    }
}
