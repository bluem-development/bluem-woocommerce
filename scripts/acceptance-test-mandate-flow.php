<?php

if (!defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_order')) {
    WP_CLI::error('WooCommerce must be active before running the mandate acceptance test.');
}

$admin = get_user_by('login', getenv('WP_ACCEPTANCE_ADMIN_USER') ?: 'wordpress');
if (!$admin) {
    WP_CLI::error('The acceptance administrator could not be found.');
}
wp_set_current_user($admin->ID);

$order_id = (int)get_option('bluem_acceptance_fixture_order_id', 0);
$order = wc_get_order($order_id);
if (!$order) {
    WP_CLI::error('The acceptance fixture order could not be found.');
}

$order->update_status('pending', 'Reset by Bluem mandate acceptance test');
$order->set_payment_method('bluem_mandates');
$order->save();

if (!class_exists('Bluem_Mandates_Payment_Gateway')) {
    WP_CLI::error('The Bluem mandate gateway was not loaded.');
}

$gateway = new Bluem_Mandates_Payment_Gateway();
$result = $gateway->process_payment($order_id);
if (!is_array($result) || ($result['result'] ?? '') !== 'success') {
    WP_CLI::error('Mandate request creation failed: ' . wp_json_encode($result));
}

$order = wc_get_order($order_id);
$mandate_id = (string)$order->get_meta('bluem_mandateid');
if ($mandate_id === '') {
    WP_CLI::error('Mandate request did not persist a mandate ID.');
}

$request = bluem_db_get_request_by_transaction_id_and_type($mandate_id, 'mandates');
if (!$request) {
    WP_CLI::error('Mandate request was not persisted in the Bluem request table.');
}

// Keep this callback assertion deterministic even when the preceding browser
// settings flow has persisted optional mandate-limit fields. The callback
// should exercise the unlimited-mandate path in this fixture.
$options = get_option('bluem_woocommerce_options', []);
$options['localInstrumentCode'] = 'CORE';
$options['maxAmountEnabled'] = '0';
$options['maxAmountFactor'] = '1';
update_option('bluem_woocommerce_options', $options, false);

$callback_url = home_url('wc-api/bluem_mandates_callback/?mandateID=' . rawurlencode($mandate_id));
$callback_url = str_replace('http://localhost:8000', 'http://wordpress', $callback_url);
$callback_response = wp_remote_get($callback_url, [
    'timeout' => 15,
    'redirection' => 0,
    'headers' => ['Host' => 'localhost:8000'],
]);
if (is_wp_error($callback_response)) {
    WP_CLI::error('Mocked mandate callback request failed: ' . $callback_response->get_error_message());
}

$order = wc_get_order($order_id);
$request = bluem_db_get_request_by_id((string)$request->id);
if ($order->get_status() !== 'processing' || !$request || $request->status !== 'Success') {
    WP_CLI::error(sprintf(
        'Mandate callback did not complete: order=%s request=%s.',
        $order->get_status(),
        $request->status ?? 'missing'
    ));
}

WP_CLI::success(sprintf('Bluem mandate flow passed: mandate %s, order %d, final status processing.', $mandate_id, $order_id));
