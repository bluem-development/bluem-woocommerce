<?php

if (!defined('WP_CLI') || !WP_CLI || !function_exists('do_shortcode')) {
    WP_CLI::error('WordPress must be loaded before running the iDIN acceptance test.');
}

$admin = get_user_by('login', getenv('WP_ACCEPTANCE_ADMIN_USER') ?: 'wordpress');
if (!$admin) {
    WP_CLI::error('The acceptance administrator could not be found.');
}
wp_set_current_user($admin->ID);

$shortcode = do_shortcode('[bluem_identificatieformulier]');
if (!str_contains($shortcode, 'bluem-woocommerce-button-idin')) {
    WP_CLI::error('The iDIN shortcode did not render its entry form.');
}

$result = bluem_idin_execute(null, false, false);
if (!is_array($result) || ($result['result'] ?? false) !== true) {
    WP_CLI::error('iDIN request creation failed: ' . wp_json_encode($result));
}

$request = bluem_db_get_request_by_transaction_id('ACCEPTANCEIDINTX1');
if (!$request || $request->type !== 'identity') {
    WP_CLI::error('iDIN request was not persisted in the Bluem request table.');
}

$callback_url = home_url('bluem-woocommerce/idin_shortcode_callback/?debtorReference=' . rawurlencode((string)$request->debtor_reference));
$callback_url = str_replace('http://localhost:8000', 'http://wordpress', $callback_url);
$callback_response = wp_remote_get($callback_url, [
    'timeout' => 15,
    'redirection' => 0,
    'headers' => ['Host' => 'localhost:8000'],
]);
if (is_wp_error($callback_response)) {
    WP_CLI::error('Mocked iDIN callback request failed: ' . $callback_response->get_error_message());
}

$request = bluem_db_get_request_by_id((string)$request->id);
if (!$request || $request->status !== 'Success') {
    WP_CLI::error('iDIN callback did not persist Success status.');
}

WP_CLI::success('Bluem iDIN flow passed: shortcode rendered, identity request created, and callback persisted Success.');
