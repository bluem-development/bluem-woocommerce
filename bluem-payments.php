<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'bluem_add_gateway_class_payments', 12);
function bluem_add_gateway_class_payments($gateways)
{
    $gateways[] = Bluem_iDEAL_Payment_Gateway::class;
    $gateways[] = Bluem_PayPal_Payment_Gateway::class;
    $gateways[] = Bluem_Creditcard_Payment_Gateway::class;
    $gateways[] = Bluem_Sofort_Payment_Gateway::class;
    $gateways[] = Bluem_CarteBancaire_Payment_Gateway::class;

    return $gateways;
}

/*
 * The gateway class itself, please note that it is inside plugins_loaded action hook
 */
if (bluem_woocommerce_is_woocommerce_active()) {
    add_action('plugins_loaded', 'bluem_init_payment_gateway_class');
}


function bluem_init_payment_gateway_class()
{
    include_once __DIR__ . '/gateways/Bluem_iDEAL_Payment_Gateway.php';
    include_once __DIR__ . '/gateways/Bluem_PayPal_Payment_Gateway.php';
    include_once __DIR__ . '/gateways/Bluem_Creditcard_Payment_Gateway.php';
    include_once __DIR__ . '/gateways/Bluem_Sofort_Payment_Gateway.php';
    include_once __DIR__ . '/gateways/Bluem_CarteBancaire_Payment_Gateway.php';
}

function bluem_woocommerce_payments_settings_section()
{
    echo '<p><a id="tab_payments"></a>
<strong>Note: in addition to configuring the functions below, you must also activate the payment methods in the
<a href="' . (esc_url(home_url()) . 'wp-admin/admin.php?page=wc-settings&tab=checkout') . '" target="_blank">WooCommerce payment settings</a>.
</strong><br>
    Here you can configure important details for ePayments transactions so you can easily receive payments.</p>
    <p>Read <a href="' . esc_url(BLUEM_WOOCOMMERCE_MANUAL_URL) . '" target="_blank">the manual</a> for more information.</p>';
}

function bluem_woocommerce_get_payments_option($key)
{
    $options = bluem_woocommerce_get_payments_options();
    if (array_key_exists($key, $options)) {
        return $options[$key];
    }

    return false;
}

function bluem_woocommerce_get_payments_options()
{
    return [
        'paymentsIDEALBrandID' => [
            'key' => 'paymentsIDEALBrandID',
            'title' => 'bluem_paymentsIDEALBrandID',
            'name' => 'BrandID for iDEAL',
            'description' => 'The Bluem BrandID for payments via iDEAL Payments',
            'default' => '',
        ],
        'paymentsUseDebtorWallet' => [
            'key' => 'paymentsUseDebtorWallet',
            'title' => 'bluem_paymentsUseDebtorWallet',
            'name' => 'Select bank method',
            'description' => "Do you want a bank to be selected on this website during checkout instead of in the Bluem Portal? If you select 'Use own checkout', a field will be added to the WooCommerce checkout page where you can select one of the available banks.",
            'type' => 'select',
            'default' => '0',
            'options' => [
                '0' => 'Use Bluem Portal (default)',
                '1' => 'Use own checkout',
            ],
        ],
        'paymentsCreditcardBrandID' => [
            'key' => 'paymentsCreditcardBrandID',
            'title' => 'bluem_paymentsCreditcardBrandID',
            'name' => 'BrandID for CreditCard',
            'description' => 'The Bluem BrandID for payments via CreditCard Payments',
            'default' => '',
        ],
        'paymentsPayPalBrandID' => [
            'key' => 'paymentsPayPalBrandID',
            'title' => 'bluem_paymentsPayPalBrandID',
            'name' => 'BrandID for PayPal',
            'description' => 'The Bluem BrandID for payments via PayPal Payments',
            'default' => '',
        ],
        'paymentsSofortBrandID' => [
            'key' => 'paymentsSofortBrandID',
            'title' => 'bluem_paymentsSofortBrandID',
            'name' => 'BrandID for SOFORT',
            'description' => 'The Bluem BrandID for payments via SOFORT Payments',
            'default' => '',
        ],
        'paymentsCarteBancaireBrandID' => [
            'key' => 'paymentsCarteBancaireBrandID',
            'title' => 'bluem_paymentsCarteBancaireBrandID',
            'name' => 'BrandID for Carte Bancaire',
            'description' => 'The Bluem BrandID for payments via Carte Bancaire Payments',
            'default' => '',
        ],
        'paymentCompleteRedirectType' => [
            'key' => 'paymentCompleteRedirectType',
            'title' => 'bluem_paymentCompleteRedirectType',
            'name' => 'Where to redirect after a successful payment?',
            'description' => 'When the user has paid, where should they be redirected?',
            'type' => 'select',
            'default' => 'order_details',
            'options' => [
                'order_details' => 'Page with order details (default)',
                'custom' => 'Custom URL (enter below)',
            ],
        ],
        'paymentCompleteRedirectCustomURL' => [
            'key' => 'paymentCompleteRedirectCustomURL',
            'title' => 'bluem_paymentCompleteRedirectCustomURL',
            'name' => 'Custom internal URL to redirect the customer to',
            'description' => "If 'Custom URL' was chosen above, enter the URL to redirect to here. For example, you can enter <code>thanks</code> to redirect the customer to <strong>" . site_url('thanks') . '</strong>',
            'type' => 'text',
            'default' => '',
        ],
    ];
}

function bluem_woocommerce_settings_render_paymentsIDEALBrandID()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option('paymentsIDEALBrandID')
    );
}

function bluem_woocommerce_settings_render_paymentsCreditcardBrandID()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option('paymentsCreditcardBrandID')
    );
}

function bluem_woocommerce_settings_render_paymentsPayPalBrandID()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option('paymentsPayPalBrandID')
    );
}

function bluem_woocommerce_settings_render_paymentsSofortBrandID()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option('paymentsSofortBrandID')
    );
}

function bluem_woocommerce_settings_render_paymentsCarteBancaireBrandID()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option('paymentsCarteBancaireBrandID')
    );
}

function bluem_woocommerce_settings_render_paymentCompleteRedirectType()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option('paymentCompleteRedirectType')
    );
}

function bluem_woocommerce_settings_render_paymentCompleteRedirectCustomURL()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option('paymentCompleteRedirectCustomURL')
    );
}

function bluem_woocommerce_settings_render_paymentsUseDebtorWallet()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option('paymentsUseDebtorWallet')
    );
}


// https://www.skyverge.com/blog/how-to-create-a-simple-woocommerce-payment-gateway/


add_filter('bluem_woocommerce_enhance_payment_request', 'bluem_woocommerce_enhance_payment_request_function', 10, 1);

/**
 * allow third parties to add additional data to the request object through this additional action
 *
 * @param [type] $request
 *
 * @return void
 */
function bluem_woocommerce_enhance_payment_request_function($request)
{
    // do something with the Bluem payment request, use this in third-party extensions of this system
    return $request;
}

add_action('show_user_profile', 'bluem_woocommerce_payments_show_extra_profile_fields', 2);
add_action('edit_user_profile', 'bluem_woocommerce_payments_show_extra_profile_fields');

function bluem_woocommerce_payments_show_extra_profile_fields($user)
{
    $bluem_requests = bluem_db_get_requests_by_user_id_and_type($user->ID, 'payments'); ?>
    <table class="form-table">
        <a id="user_payments"></a>
        <?php
        if (isset($bluem_requests) && count($bluem_requests) > 0) {
            ?>
            <tr>
                <th>
                    <?php esc_html_e('ePayments transacties', 'bluem'); ?>
                </th>
                <td>
                    <?php
                    bluem_render_requests_list($bluem_requests);
            ?>
                </td>
            </tr>
        <?php } ?>
    </table>
    <?php
}
