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

// Mirror the reported production failure: another customer's pending mandate
// order is created after this one. A callback for the first mandate must never
// promote this newer order merely because it is the most recent order.
$interfering_order_id = (int)get_option('bluem_acceptance_mandate_interferer_order_id', 0);
$interfering_order = $interfering_order_id > 0 ? wc_get_order($interfering_order_id) : null;
if (!$interfering_order) {
    $interfering_order = wc_create_order(['customer_id' => $admin->ID]);
    $interfering_order->set_created_via('bluem-mandate-correlation-acceptance');
    $interfering_order->set_billing_first_name('Other');
    $interfering_order->set_billing_last_name('Customer');
    $interfering_order->set_billing_email('other-customer@example.com');
    $interfering_order->set_billing_country('NL');
    $interfering_order->set_payment_method('bluem_mandates');
    $interfering_order->set_payment_method_title('Bluem eMandate Acceptance');
    $interfering_order->update_meta_data('bluem_mandateid', 'ACCEPTANCE-UNRELATED-MANDATE');
    $interfering_order->update_meta_data('bluem_entrancecode', 'ACCEPTANCE-UNRELATED-ENTRANCE');
    $interfering_order->update_status('pending', 'Newer unrelated mandate order');
    $interfering_order->save();
    update_option('bluem_acceptance_mandate_interferer_order_id', $interfering_order->get_id(), false);
}

if ($interfering_order->get_id() <= $order->get_id()) {
    WP_CLI::error('The unrelated mandate fixture must be newer than the callback target order.');
}

$interfering_order->update_status('pending', 'Reset by Bluem mandate correlation acceptance test');
$interfering_order->save();

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

// The callback is served by a separate WordPress HTTP request. Evict the
// current WP-CLI process' object-cache entries before verifying its writes;
// otherwise wc_get_order() can return the pre-callback objects from this
// process even though the database was correctly updated by the callback.
foreach ([$order_id, $interfering_order->get_id()] as $refresh_order_id) {
    clean_post_cache($refresh_order_id);
    wp_cache_delete($refresh_order_id, 'orders');
}

$order = wc_get_order($order_id);
$interfering_order = wc_get_order($interfering_order->get_id());
$request = bluem_db_get_request_by_id((string)$request->id);
if ($order->get_status() !== 'processing' || !$request || $request->status !== 'Success' || !$interfering_order || $interfering_order->get_status() !== 'pending') {
    WP_CLI::error(sprintf(
        'Mandate callback correlation failed: target=%s unrelated=%s request=%s.',
        $order->get_status(),
        $interfering_order ? $interfering_order->get_status() : 'missing',
        $request->status ?? 'missing'
    ));
}

WP_CLI::success(sprintf('Bluem mandate flow passed: mandate %s updated order %d while newer order %d remained pending.', $mandate_id, $order_id, $interfering_order->get_id()));
