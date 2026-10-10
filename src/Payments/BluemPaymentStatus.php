<?php

namespace Bluem\Wordpress\Payments;

/**
 * Canonical Bluem payment status decisions.
 *
 * This class deliberately has no WordPress or WooCommerce dependency. Gateway
 * adapters can use it for order transitions while mandate and request flows
 * can reuse the status classification as they are moved into services.
 */
final class BluemPaymentStatus
{
    public const SUCCESS = 'Success';
    public const FAILURE = 'Failure';
    public const CANCELLED = 'Cancelled';
    public const EXPIRED = 'Expired';
    public const NEW = 'New';
    public const OPEN = 'Open';
    public const PENDING = 'Pending';

    /** @var list<string> */
    private const IN_PROGRESS = [self::NEW, self::OPEN, self::PENDING];

    public static function isInProgress(string $status): bool
    {
        return in_array($status, self::IN_PROGRESS, true);
    }

    /**
     * Resolve the WooCommerce order transition for a Bluem payment status.
     *
     * Terminal Success and Failure results only change pending orders. The
     * existing cancellation, expiry, and unknown-status behavior is preserved.
     */
    public static function resolveOrderTransition(string $paymentStatus, string $currentOrderStatus): ?string
    {
        return match ($paymentStatus) {
            self::SUCCESS => $currentOrderStatus === 'pending' ? 'processing' : null,
            self::FAILURE => $currentOrderStatus === 'pending' ? 'failed' : null,
            self::CANCELLED => 'cancelled',
            self::EXPIRED => 'failed',
            default => self::isInProgress($paymentStatus) ? null : 'failed',
        };
    }
}
