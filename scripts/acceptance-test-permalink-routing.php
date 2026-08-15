<?php

if (!defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_order')) {
    WP_CLI::error('WooCommerce must be active before running the permalink-routing acceptance test.');
}

$original_structure = (string)get_option('permalink_structure', '');

try {
    update_option('permalink_structure', '/%postname%');
    flush_rewrite_rules();

    $pretty_url = bluem_woocommerce_route_url(
        'wc-api/bluem_payments_ideal_callback',
        ['entranceCode' => 'pretty-acceptance']
    );
    if (!str_contains($pretty_url, '/wc-api/bluem_payments_ideal_callback?entranceCode=pretty-acceptance')) {
        WP_CLI::error('Pretty permalinks did not produce the expected Bluem callback URL: ' . $pretty_url);
    }

    update_option('permalink_structure', '');
    flush_rewrite_rules();

    $plain_url = bluem_woocommerce_route_url(
        'wc-api/bluem_payments_ideal_callback',
        ['entranceCode' => 'plain-acceptance']
    );
    $plain_query = [];
    parse_str((string)wp_parse_url($plain_url, PHP_URL_QUERY), $plain_query);

    if (($plain_query['wc-api'] ?? '') !== 'bluem_payments_ideal_callback'
        || ($plain_query['entranceCode'] ?? '') !== 'plain-acceptance') {
        WP_CLI::error('Plain permalinks did not produce a WooCommerce API callback URL: ' . $plain_url);
    }

    $idin_url = bluem_woocommerce_route_url('bluem-woocommerce/idin_shortcode_callback');
    $idin_query = [];
    parse_str((string)wp_parse_url($idin_url, PHP_URL_QUERY), $idin_query);
    if (($idin_query['bluem_idin_shortcode_callback'] ?? '') !== '1') {
        WP_CLI::error('Plain permalinks did not produce a Bluem iDIN callback URL: ' . $idin_url);
    }

    $callback_url = str_replace('http://localhost:8000', 'http://wordpress', $plain_url);
    $response = wp_remote_get($callback_url, [
        'timeout' => 15,
        'redirection' => 0,
        'headers' => ['Host' => 'localhost:8000'],
    ]);
    if (is_wp_error($response)) {
        WP_CLI::error('Plain-permalink Bluem callback request failed: ' . $response->get_error_message());
    }

    $response_body = wp_strip_all_tags(wp_remote_retrieve_body($response));
    $callback_handler_messages = [
        'Error: order not found in webshop',
        'No transaction ID found',
    ];
    $reached_callback_handler = false;

    foreach ($callback_handler_messages as $message) {
        if (str_contains($response_body, $message)) {
            $reached_callback_handler = true;
            break;
        }
    }

    if (wp_remote_retrieve_response_code($response) !== 200
        || !$reached_callback_handler) {
        WP_CLI::error('Plain-permalink Bluem callback did not reach its WooCommerce handler. Response: ' . $response_body);
    }
} finally {
    update_option('permalink_structure', $original_structure);
    flush_rewrite_rules();
}

WP_CLI::success('Bluem callback routing works with both Pretty and Plain permalinks.');
