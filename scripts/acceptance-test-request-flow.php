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

$order_id = $order->get_id();
// The callback runs in the WordPress container while this WP-CLI process has
// already cached the pending order. Clear those local caches before asserting
// the database state written by the callback.
clean_post_cache($order_id);
if (class_exists('Automattic\\WooCommerce\\Caches\\OrderCache')) {
    wc_get_container()
        ->get(Automattic\WooCommerce\Caches\OrderCache::class)
        ->remove($order_id);
}
$order = wc_get_order($order_id);
$request = bluem_db_get_request_by_transaction_id($transaction_id);
$order_notes = wc_get_order_notes([
    'order_id' => $order->get_id(),
    'limit' => 3,
    'orderby' => 'date_created',
    'order' => 'DESC',
]);
$recent_notes = array_map(
    static fn ($note) => wp_strip_all_tags($note->content),
    $order_notes
);
WP_CLI::log(sprintf(
    'Callback persistence: order %d status %s; request status %s; recent notes: %s.',
    $order->get_id(),
    $order->get_status(),
    $request->status ?? 'not found',
    $recent_notes ? implode(' | ', $recent_notes) : 'none'
));
if ($order->get_status() !== 'processing') {
    WP_CLI::error(sprintf(
        'Mocked Bluem Success callback did not move order %d to processing. Order status: %s; request status: %s; transaction: %s; entrance code: %s.',
        $order->get_id(),
        $order->get_status(),
        $request->status ?? 'not found',
        $transaction_id,
        $entrance_code
    ));
}

if (! $request || $request->status !== 'Success') {
    WP_CLI::error('Mocked Bluem Success callback did not persist the request status.');
}

WP_CLI::success(sprintf(
    'Bluem request flow passed: transaction %s, order %d, final status %s.',
    $transaction_id,
    $order->get_id(),
    $order->get_status()
));
