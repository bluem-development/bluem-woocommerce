<?php

if (! defined('WP_CLI') || ! WP_CLI || ! function_exists('wc_get_order')) {
    WP_CLI::error('WooCommerce must be active before running the request-flow acceptance test.');
}

$bluemOptions = get_option('bluem_woocommerce_options', []);
$bluemOptions['environment'] = 'test';
$bluemOptions['senderID'] = 'S0001';
$bluemOptions['test_accessToken'] = 'ci-acceptance-token';
$bluemOptions['production_accessToken'] = 'ci-acceptance-production-token';
$bluemOptions['payments_enabled'] = '1';
$bluemOptions['paymentsIDEALBrandID'] = 'Payment';
update_option('bluem_woocommerce_options', $bluemOptions, false);

$order_id = (int) get_option('bluem_acceptance_fixture_order_id', 0);
$order = wc_get_order($order_id);
if (! $order) {
    WP_CLI::error('The acceptance fixture order could not be found.');
}

$order->update_status('pending', 'Reset by Bluem request-flow acceptance test');
$order->delete_meta_data('bluem_transactionid');
$order->delete_meta_data('bluem_entrancecode');
$order->save();

$gateway = new Bluem_iDEAL_Payment_Gateway();
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

$callback_url = home_url('wc-api/bluem_payments_ideal_callback/?entranceCode=' . rawurlencode($entrance_code));
$callback_url = str_replace('http://localhost:8000', 'http://wordpress', $callback_url);
$callback_response = wp_remote_get($callback_url, [
    'timeout' => 15,
    'redirection' => 0,
    'headers' => ['Host' => 'localhost:8000'],
]);
if (is_wp_error($callback_response)) {
    WP_CLI::error('Mocked Bluem callback request failed: ' . $callback_response->get_error_message());
}

WP_CLI::log('Callback HTTP status: ' . wp_remote_retrieve_response_code($callback_response));
WP_CLI::log('Callback location: ' . (wp_remote_retrieve_header($callback_response, 'location') ?: ''));
WP_CLI::log('Callback response: ' . wp_strip_all_tags(wp_remote_retrieve_body($callback_response)));

$order = new WC_Order($order->get_id());
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
