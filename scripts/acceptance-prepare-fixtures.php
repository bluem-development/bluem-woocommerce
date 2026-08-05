<?php

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

if (!class_exists('WooCommerce')) {
    WP_CLI::error('WooCommerce must be active before creating acceptance fixtures.');
}

$admin = get_user_by('login', getenv('WP_ACCEPTANCE_ADMIN_USER') ?: 'wordpress');
if (!$admin) {
    WP_CLI::error('The acceptance administrator could not be found.');
}

$product = null;
$productId = (int) get_option('bluem_acceptance_fixture_product_id', 0);
if ($productId > 0) {
    $product = wc_get_product($productId);
}

if (!$product) {
    $product = new WC_Product_Simple();
    $product->set_name('Bluem Acceptance Fixture Product');
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_regular_price('12.34');
    $product->set_price('12.34');
    $product->set_sku('bluem-acceptance-fixture');
    $product->save();
    update_option('bluem_acceptance_fixture_product_id', $product->get_id(), false);
}

$order = null;
$orderId = (int) get_option('bluem_acceptance_fixture_order_id', 0);
if ($orderId > 0) {
    $order = wc_get_order($orderId);
}

if (!$order) {
    $order = wc_create_order(['customer_id' => $admin->ID]);
    $order->set_created_via('bluem-acceptance');
    $order->set_customer_id($admin->ID);
    $order->set_billing_first_name('Bluem');
    $order->set_billing_last_name('Acceptance');
    $order->set_billing_email('bluem-acceptance@example.com');
    $order->set_billing_country('NL');
    $order->add_product($product, 1);
    $order->set_payment_method('bluem_payments_ideal');
    $order->set_payment_method_title('Bluem iDEAL (Acceptance)');
    $order->calculate_totals();
    $order->update_status('pending', 'Acceptance fixture order');
    $order->save();
    update_option('bluem_acceptance_fixture_order_id', $order->get_id(), false);
}

$order->update_status('pending', 'Acceptance fixture order');
$order->set_payment_method('bluem_payments_ideal');
$order->save();

update_option(
    'woocommerce_bluem_payments_ideal_settings',
    [
        'enabled' => 'yes',
        'title' => 'Bluem iDEAL Acceptance',
        'description' => 'Isolated acceptance payment method',
        'paymentsIDEALBrandID' => 'Payment',
    ],
    false
);

$bluemOptions = get_option('bluem_woocommerce_options', []);
$bluemOptions['paymentsIDEALBrandID'] = 'Payment';
update_option('bluem_woocommerce_options', $bluemOptions, false);

$requestId = (int) get_option('bluem_acceptance_fixture_request_id', 0);
$request = $requestId > 0 ? bluem_db_get_request_by_id((string) $requestId) : false;
if (!$request) {
    $requestId = bluem_db_create_request([
        'entrance_code' => 'ACCEPTANCEENTRANCE1',
        'transaction_id' => 'ACCEPTANCETX1',
        'transaction_url' => 'https://mock-bluem.invalid/payment/transaction/ACCEPTANCETX1',
        'user_id' => $admin->ID,
        'timestamp' => gmdate('Y-m-d H:i:s'),
        'description' => 'Bluem acceptance fixture payment',
        'debtor_reference' => (string) $order->get_id(),
        'type' => 'ideal',
        'order_id' => $order->get_id(),
        'payload' => wp_json_encode([
            'environment' => 'test',
            'amount' => $order->get_total(),
            'method' => 'Payment',
            'currency' => $order->get_currency(),
        ]),
    ]);
    update_option('bluem_acceptance_fixture_request_id', $requestId, false);
} else {
    bluem_db_update_request($request->id, [
        'entrance_code' => 'ACCEPTANCEENTRANCE1',
        'transaction_id' => 'ACCEPTANCETX1',
        'transaction_url' => 'https://mock-bluem.invalid/payment/transaction/ACCEPTANCETX1',
        'status' => 'created',
    ]);
}

WP_CLI::log(sprintf('Acceptance fixture order: %d', $order->get_id()));
WP_CLI::log(sprintf('Acceptance fixture request: %d', $requestId));
WP_CLI::success('WooCommerce and Bluem acceptance fixtures are ready.');
