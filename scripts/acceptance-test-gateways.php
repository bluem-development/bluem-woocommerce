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
    'bluem_mandates',
];

$registered = array_keys(WC()->payment_gateways()->payment_gateways());
$missing = array_values(array_diff($expected, $registered));

if ($missing !== []) {
    WP_CLI::error(
        'Missing Bluem WooCommerce gateways: ' . implode(', ', $missing)
        . '. Registered gateways: ' . implode(', ', $registered)
    );
}

$block_registry = new \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry();
$block_registry->initialize();
$expected_block_gateways = $expected;
$registered_block_gateways = array_keys($block_registry->get_all_registered());
$missing_block_gateways = array_values(array_diff($expected_block_gateways, $registered_block_gateways));

if ($missing_block_gateways !== []) {
    WP_CLI::error(
        'Missing Bluem Cart and Checkout Blocks gateways: ' . implode(', ', $missing_block_gateways)
        . '. Registered gateways: ' . implode(', ', $registered_block_gateways)
    );
}

$mandate_shortcode = do_shortcode('[bluem_machtigingsformulier]');
$idin_shortcode = do_shortcode('[bluem_identificatieformulier]');

if (!str_contains($mandate_shortcode, 'bluem-woocommerce-button-mandates')) {
    WP_CLI::error('The Bluem mandate shortcode did not render its form.');
}

if (!str_contains($idin_shortcode, 'bluem-woocommerce-button-idin')) {
    WP_CLI::error('The Bluem iDIN shortcode did not render its form.');
}

$idealSettings = get_option('woocommerce_bluem_payments_ideal_settings', []);
if (($idealSettings['enabled'] ?? '') !== 'yes' || ($idealSettings['title'] ?? '') !== 'Bluem iDEAL Acceptance') {
    WP_CLI::error('Bluem iDEAL acceptance settings were not persisted.');
}

WP_CLI::success('Classic and Blocks gateways plus mandate and iDIN shortcodes are registered, and iDEAL settings persist.');
