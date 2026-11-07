<?php

namespace Bluem\Wordpress\Payments;

use Closure;

/** Applies existing payment decisions; adapters provide translated order notes. */
final class BluemPaymentTransition
{
    /** @param Closure(string, string): string $message Receives payment status and context. */
    public function __construct(private readonly Closure $message)
    {
    }

    /** @param object $order Order exposing get_status() and update_status(). */
    public function apply($order, string $paymentStatus, string $context = 'callback'): ?string
    {
        $target = BluemPaymentStatus::resolveOrderTransition($paymentStatus, $order->get_status());
        if ($target === null) {
            return null;
        }

        $order->update_status($target, ($this->message)($paymentStatus, $context));

        return $target;
    }
}
