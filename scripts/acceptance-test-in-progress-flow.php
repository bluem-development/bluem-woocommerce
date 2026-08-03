<?php

if (! defined('WP_CLI') || ! WP_CLI || ! function_exists('wc_get_order')) {
    WP_CLI::error('WooCommerce must be active before running the in-progress acceptance test.');
}

$order_id = (int) get_option('bluem_acceptance_fixture_order_id', 0);
$order = wc_get_order($order_id);
if (! $order) {
    WP_CLI::error('The acceptance fixture order could not be found.');
}

$order->update_status('pending', 'Reset by Bluem in-progress acceptance test');
$order->update_meta_data('bluem_transactionid', 'ACCEPTANCEPENDING1');
$order->update_meta_data('bluem_entrancecode', 'ACCEPTANCEPENDINGENTRANCE');
$order->save();

$request_id = (int) get_option('bluem_acceptance_fixture_request_id', 0);
if ($request_id <= 0 || ! bluem_db_get_request_by_id((string) $request_id)) {
    WP_CLI::error('The acceptance fixture request could not be found.');
}

bluem_db_update_request($request_id, [
    'entrance_code' => 'ACCEPTANCEPENDINGENTRANCE',
    'transaction_id' => 'ACCEPTANCEPENDING1',
    'status' => 'created',
]);

$callback_url = home_url('wc-api/bluem_payments_ideal_callback/?entranceCode=ACCEPTANCEPENDINGENTRANCE');
$callback_url = str_replace('http://localhost:8000', 'http://wordpress', $callback_url);
$callback_response = wp_remote_get($callback_url, [
    'timeout' => 15,
    'redirection' => 0,
    'headers' => ['Host' => 'localhost:8000'],
]);
if (is_wp_error($callback_response)) {
    WP_CLI::error('Mocked in-progress callback request failed: ' . $callback_response->get_error_message());
}

$order = wc_get_order($order_id);
$request = bluem_db_get_request_by_id((string) $request_id);
if ($order->get_status() !== 'pending' || ! $request || $request->status !== 'Pending') {
    WP_CLI::error(sprintf(
        'Pending callback changed state unexpectedly: order=%s request=%s.',
        $order->get_status(),
        $request->status ?? 'missing'
    ));
}

WP_CLI::success('Bluem Pending callback kept the order pending and persisted the in-progress status.');
