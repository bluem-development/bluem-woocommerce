<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WooCommerce') || !function_exists('wc_create_order')) {
    WP_CLI::error('WooCommerce is not loaded.');
}

require_once WP_PLUGIN_DIR . '/bluem-woocommerce/gateways/Bluem_Payment_Gateway.php';

use Bluem\Wordpress\Payments\BluemOrderQuery;

// The gateway registers these during normal configured gateway boot. Registering
// the public adapters directly keeps this test independent of merchant secrets.
add_filter(
    'woocommerce_order_query_args',
    [BluemOrderQuery::class, 'mapHposArgs']
);
add_filter(
    'woocommerce_order_data_store_cpt_get_orders_query',
    static function (array $query, array $query_vars): array {
        foreach (['bluem_transactionid', 'bluem_entrancecode', 'bluem_mandateid'] as $key) {
            if (empty($query_vars[$key])) {
                continue;
            }

            $query['meta_query'] = $query['meta_query'] ?? [];
            $query['meta_query'][] = [
                'key' => $key,
                'value' => esc_attr($query_vars[$key]),
            ];
        }

        return $query;
    },
    10,
    2
);

$order = wc_create_order();

if (is_wp_error($order)) {
    WP_CLI::error($order->get_error_message());
}

$order_id = $order->get_id();
$meta = [
    'bluem_transactionid' => 'integration-transaction-' . wp_generate_uuid4(),
    'bluem_entrancecode'  => 'integration-entrance-' . wp_generate_uuid4(),
    'bluem_mandateid'     => 'integration-mandate-' . wp_generate_uuid4(),
];

foreach ($meta as $key => $value) {
    $order->update_meta_data($key, $value);
}
$order->save();

$reloaded_order = wc_get_order($order_id);

if (!$reloaded_order) {
    WP_CLI::error('The order could not be reloaded through WooCommerce CRUD.');
}

foreach ($meta as $key => $value) {
    if ($reloaded_order->get_meta($key) !== $value) {
        $reloaded_order->delete(true);
        WP_CLI::error(sprintf('CRUD metadata mismatch for %s.', $key));
    }

    $matching_orders = wc_get_orders([
        'limit' => -1,
        'return' => 'ids',
        $key => $value,
    ]);

    $matching_order_ids = array_map('intval', $matching_orders);

    if (!in_array($order_id, $matching_order_ids, true)) {
        $reloaded_order->delete(true);
        WP_CLI::error(sprintf('The %s query did not find the order.', $key));
    }
}

$unrelated_order = wc_create_order();
if (is_wp_error($unrelated_order)) {
    $reloaded_order->delete(true);
    WP_CLI::error($unrelated_order->get_error_message());
}

$unrelated_order->update_meta_data('bluem_mandateid', 'integration-unrelated-mandate-' . wp_generate_uuid4());
$unrelated_order->save();

// The callback lookup uses this native WooCommerce query. It must select the
// correlated order even when a newer order with unrelated mandate metadata
// exists, in both legacy and HPOS order storage.
$correlated_order_ids = array_map('intval', wc_get_orders(array_merge([
    'limit' => 2,
    'return' => 'ids',
    'orderby' => 'date',
    'order' => 'DESC',
], BluemOrderQuery::metadataEquals('bluem_mandateid', $meta['bluem_mandateid']))));

if ($correlated_order_ids !== [$order_id]) {
    $unrelated_order->delete(true);
    $reloaded_order->delete(true);
    WP_CLI::error('Native mandate metadata query selected an unrelated order.');
}

$unrelated_order->delete(true);
$reloaded_order->delete(true);

WP_CLI::success(sprintf('Order CRUD, %d Bluem metadata queries, and mandate callback correlation passed.', count($meta)));
