<?php

// Loaded only inside isolated PHPUnit processes; never replaces a live WP runtime.
define('ABSPATH', dirname(__DIR__, 2) . '/');
define('BLUEM_SENTRY_ENABLED', false);

$GLOBALS['bluem_test_options'] = [
    'active_plugins' => [],
    'bluem_db_version' => 1.5,
    'admin_email' => 'test@example.invalid',
    'bluem_woocommerce_options' => [
        'environment' => 'test',
        'senderID' => 'S0001',
        'test_accessToken' => 'unit-test-token',
        'brandID' => 'Mandate',
        'paymentsIDEALBrandID' => 'Payment',
        'mandates_enabled' => '1',
        'payments_enabled' => '1',
        'idin_enabled' => '0',
    ],
];
$GLOBALS['current_user'] = (object) ['ID' => 42];

function add_action(...$args) {}
function add_filter(...$args) {}
function add_shortcode(...$args) {}
function register_activation_hook(...$args) {}
function register_deactivation_hook(...$args) {}
function get_option($key, $default = false) { return $GLOBALS['bluem_test_options'][$key] ?? $default; }
function apply_filters($hook, $value, ...$args) {
    return $hook === 'bluem_woocommerce_http_transport' ? $GLOBALS['bluem_test_transport'] : $value;
}
function esc_html__($value, $domain = '') { return $value; }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES); }
function __($value, $domain = '') { return $value; }
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES); }
function esc_js($value) { return $value; }
function wp_kses_post($value) { return $value; }
function wp_json_encode($value) { return json_encode($value); }
function site_url($path = '') { return 'https://example.invalid/' . ltrim($path, '/'); }
function wc_get_order($id) { return $GLOBALS['bluem_test_orders'][$id] ?? false; }

require dirname(__DIR__, 2) . '/bluem.php';
