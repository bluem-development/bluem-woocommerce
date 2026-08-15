<?php

namespace Bluem\Wordpress\Presentation;

use Closure;

final class BluemRequestTypeLabeler
{
    private readonly Closure $translate;

    public function __construct(?Closure $translate = null)
    {
        $this->translate = $translate ?? static fn(string $label): string => $label;
    }

    /**
     * Return a readable label for a request type stored in the request table.
     */
    public function label($type): string
    {
        $type = strtolower(trim((string) $type));

        $labels = [
            'ideal' => 'iDEAL',
            'creditcard' => 'Credit card',
            'paypal' => 'PayPal',
            'sofort' => 'SOFORT',
            'cartebancaire' => 'Carte Bancaire',
            'mandates' => 'eMandate',
            'payments' => 'Payment',
            'identity' => 'iDIN',
        ];

        if (array_key_exists($type, $labels)) {
            return ($this->translate)($labels[$type]);
        }

        return $type !== '' ? ucfirst($type) : ($this->translate)('Unknown');
    }
}
