<?php

// Loaded only by process-isolated adapter tests. Hooks are registered, not run.
define('ABSPATH', dirname(__DIR__, 2) . '/');
define('BLUEM_SENTRY_ENABLED', false);

$GLOBALS['bluem_settings_test_options'] = [
    'active_plugins' => [],
    'admin_email' => 'owner+<test>@example.invalid',
    'bluem_woocommerce_options' => false,
];
$GLOBALS['bluem_settings_test_translation_prefix'] = 'translated: ';

function add_action(...$args) {}
function add_filter(...$args) {}
function add_shortcode(...$args) {}
function register_activation_hook(...$args) {}
function register_deactivation_hook(...$args) {}
function get_option($key, $default = false) { return $GLOBALS['bluem_settings_test_options'][$key] ?? $default; }
function esc_html__($text, $domain = '') {
    if ($domain !== 'bluem') {
        throw new RuntimeException('Unexpected translation domain');
    }
    return htmlspecialchars($GLOBALS['bluem_settings_test_translation_prefix'] . $text, ENT_QUOTES);
}
function esc_attr($text) { return htmlspecialchars((string) $text, ENT_QUOTES); }

require dirname(__DIR__, 2) . '/bluem.php';
