<?php

declare(strict_types=1);

if (! defined('WP_CLI') || ! WP_CLI || ! function_exists('wc_get_order')) {
    WP_CLI::error('WooCommerce must be active before running the request-flow acceptance test.');
}

$order_id = (int) get_option('bluem_acceptance_fixture_order_id', 0);
$order = wc_get_order($order_id);
if (! $order) {
    WP_CLI::error('The acceptance fixture order could not be found.');
}

$order->update_status('pending', 'Reset by Bluem request-flow acceptance test');
$order->delete_meta_data('bluem_transactionid');
$order->delete_meta_data('bluem_entrancecode');
$order->save();

$gateways = WC()->payment_gateways()->payment_gateways();
$gateway = $gateways['bluem_payments_ideal'] ?? null;
if (! $gateway instanceof Bluem_Payment_Gateway) {
    WP_CLI::error('The Bluem iDEAL gateway is not available.');
}

$_POST = [];
$result = $gateway->process_payment($order->get_id());
if (($result['result'] ?? '') !== 'success') {
    WP_CLI::error('Mocked Bluem payment creation failed: ' . wp_json_encode($result));
}

$order = wc_get_order($order->get_id());
$transaction_id = (string) $order->get_meta('bluem_transactionid', true);
$entrance_code = (string) $order->get_meta('bluem_entrancecode', true);
$request = $transaction_id !== '' ? bluem_db_get_request_by_transaction_id($transaction_id) : false;

if ($transaction_id === '' || $entrance_code === '' || ! $request) {
    WP_CLI::error('Mocked Bluem payment did not persist transaction correlation data.');
}

$callback_url = home_url('wc-api/bluem_payments_ideal_callback?entranceCode=' . rawurlencode($entrance_code));
$callback_url = str_replace('http://localhost:8000', 'http://wordpress', $callback_url);
$callback_response = wp_remote_get($callback_url, ['timeout' => 15]);
if (is_wp_error($callback_response)) {
    WP_CLI::error('Mocked Bluem callback request failed: ' . $callback_response->get_error_message());
}

$order = wc_get_order($order->get_id());
if ($order->get_status() !== 'processing') {
    WP_CLI::error('Mocked Bluem Success callback did not move the order to processing. Status: ' . $order->get_status());
}

$request = bluem_db_get_request_by_transaction_id($transaction_id);
if (! $request || $request->status !== 'Success') {
    WP_CLI::error('Mocked Bluem Success callback did not persist the request status.');
}

WP_CLI::success(sprintf(
    'Bluem request flow passed: transaction %s, order %d, final status %s.',
    $transaction_id,
    $order->get_id(),
    $order->get_status()
));
