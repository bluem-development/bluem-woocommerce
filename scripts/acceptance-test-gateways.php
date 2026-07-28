<?php

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

$expected = [
    'bluem_payments_ideal',
    'bluem_payments_paypal',
    'bluem_payments_creditcard',
    'bluem_payments_sofort',
    'bluem_payments_cartebancaire',
];

$registered = array_keys(WC()->payment_gateways()->payment_gateways());
$missing = array_values(array_diff($expected, $registered));

if ($missing !== []) {
    WP_CLI::error(
        'Missing Bluem WooCommerce gateways: ' . implode(', ', $missing)
        . '. Registered gateways: ' . implode(', ', $registered)
    );
}

WP_CLI::success('All expected Bluem WooCommerce gateways are registered.');
