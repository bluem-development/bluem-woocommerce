<?php
// @todo: add Woo Product update key if necessary, check https://docs.woocommerce.com/document/create-a-plugin/
// @todo: Localize all error messages to english primarily
// @todo: finish docblocking

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Bluem\BluemPHP\Bluem as Bluem;

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'bluem_add_gateway_class_payments', 12 );
function bluem_add_gateway_class_payments( $gateways ) {
    $gateways[] = Bluem_iDEAL_Payment_Gateway::class; // your class name is here
    $gateways[] = Bluem_PayPal_Payment_Gateway::class;
    $gateways[] = Bluem_Creditcard_Payment_Gateway::class;
    $gateways[] = Bluem_Sofort_Payment_Gateway::class;
    $gateways[] = Bluem_CarteBancaire_Payment_Gateway::class;
    return $gateways;
}

/*
 * The gateway class itself, please note that it is inside plugins_loaded action hook
 */
if ( bluem_woocommerce_is_woocommerce_active() ) {
    add_action( 'plugins_loaded', 'bluem_init_payment_gateway_class' );
}


function bluem_init_payment_gateway_class() {
    include_once __DIR__ . '/gateways/Bluem_iDEAL_Payment_Gateway.php';
    include_once __DIR__ . '/gateways/Bluem_PayPal_Payment_Gateway.php';
    include_once __DIR__ . '/gateways/Bluem_Creditcard_Payment_Gateway.php';
    include_once __DIR__ . '/gateways/Bluem_Sofort_Payment_Gateway.php';
    include_once __DIR__ . '/gateways/Bluem_CarteBancaire_Payment_Gateway.php';
}

function bluem_woocommerce_payments_settings_section() {
    echo '<p><a id="tab_payments"></a>
<strong>Let op: naast het instellen van de onderstaande functies moet je ook de betaalmethoden activeren bij de
<a href="'. (home_url().'wp-admin/admin.php?page=wc-settings&tab=checkout') .'" target="_blank">WooCommerce instellingen voor Betalingen</a>.
</strong><br>
    Je kan hier belangrijke gegevens instellen rondom ePayments-transacties om gemakkelijk betalingen te ontvangen.</p>
    <p>Lees <a href="'.BLUEM_WOOCOMMERCE_MANUAL_URL.'" target="_blank">de handleiding</a> voor meer informatie.</p>';
}

function bluem_woocommerce_get_payments_option( $key ) {
    $options = bluem_woocommerce_get_payments_options();
    if ( array_key_exists( $key, $options ) ) {
        return $options[ $key ];
    }

    return false;
}

function bluem_woocommerce_get_payments_options() {
    return [
        'paymentsIDEALBrandID'                   => [
            'key'         => 'paymentsIDEALBrandID',
            'title'       => 'bluem_paymentsIDEALBrandID',
            'name'        => 'BrandID voor iDEAL',
            'description' => 'Het Bluem BrandID voor betalingen via iDEAL Payments',
            'default'     => ''
        ],
        'paymentsUseDebtorWallet'  => [
            'key'         => 'paymentsUseDebtorWallet',
            'title'       => 'bluem_paymentsUseDebtorWallet',
            'name'        => 'Selecteer bank methode',
            'description' => "Wil je dat er in deze website al een bank moet worden geselecteerd bij de Checkout procedure, in plaats van in de Bluem Portal? Indien je 'Gebruik eigen checkout' selecteert, wordt er een veld toegevoegd aan de WooCommerce checkout pagina waar je een van de beschikbare banken kan selecteren.",
            'type'        => 'select',
            'default'     => '0',
            'options'     => [ '0' => 'Gebruik Bluem Portal (standaard)', '1' => 'Gebruik eigen checkout' ],
        ],
        'paymentsCreditcardBrandID'                   => [
            'key'         => 'paymentsCreditcardBrandID',
            'title'       => 'bluem_paymentsCreditcardBrandID',
            'name'        => 'BrandID voor CreditCard',
            'description' => 'Het Bluem BrandID voor betalingen via CreditCard Payments',
            'default'     => ''
        ],
        'paymentsPayPalBrandID'                   => [
            'key'         => 'paymentsPayPalBrandID',
            'title'       => 'bluem_paymentsPayPalBrandID',
            'name'        => 'BrandID voor PayPal',
            'description' => 'Het Bluem BrandID voor betalingen via PayPal Payments',
            'default'     => ''
        ],
        'paymentsSofortBrandID'                   => [
            'key'         => 'paymentsSofortBrandID',
            'title'       => 'bluem_paymentsSofortBrandID',
            'name'        => 'BrandID voor SOFORT',
            'description' => 'Het Bluem BrandID voor betalingen via SOFORT Payments',
            'default'     => ''
        ],
        'paymentsCarteBancaireBrandID'                   => [
            'key'         => 'paymentsCarteBancaireBrandID',
            'title'       => 'bluem_paymentsCarteBancaireBrandID',
            'name'        => 'BrandID voor Carte Bancaire',
            'description' => 'Het Bluem BrandID voor betalingen via Carte Bancaire Payments',
            'default'     => ''
        ],
        'paymentCompleteRedirectType'      => [
            'key'         => 'paymentCompleteRedirectType',
            'title'       => 'bluem_paymentCompleteRedirectType',
            'name'        => 'Waarheen verwijzen na succesvolle betaling?',
            'description' => 'Als de gebruiker heeft betaald, waar moet dan naar verwezen worden?',
            'type'        => 'select',
            'default'     => 'order_details',
            'options'     => [
                'order_details' => 'Pagina met Order gegevens (standaard)',
                'custom'        => 'Eigen URL (vul hieronder in)'
            ]
        ],
        'paymentCompleteRedirectCustomURL' => [
            'key'         => 'paymentCompleteRedirectCustomURL',
            'title'       => 'bluem_paymentCompleteRedirectCustomURL',
            'name'        => 'Eigen interne URL om klant naar te verwijzen',
            'description' => "Indien hierboven 'Eigen URL' is gekozen, vul hier dan de URL in waarnaar doorverwezen moet worden. Je kan bijv. <code>thanks</code> invullen om de klant naar <strong>" . site_url( "thanks" ) . "</strong> te verwijzen",
            'type'        => 'text',
            'default'     => ''
        ],
    ];
}

/* payments specific settings */

//function bluem_woocommerce_settings_render_paymentBrandID() {
//    bluem_woocommerce_settings_render_input(
//        bluem_woocommerce_get_payments_option( 'paymentBrandID' )
//    );
//}
function bluem_woocommerce_settings_render_paymentsIDEALBrandID() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option( 'paymentsIDEALBrandID' )
    );
}
function bluem_woocommerce_settings_render_paymentsCreditcardBrandID() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option( 'paymentsCreditcardBrandID' )
    );
}
function bluem_woocommerce_settings_render_paymentsPayPalBrandID() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option( 'paymentsPayPalBrandID' )
    );
}
function bluem_woocommerce_settings_render_paymentsSofortBrandID() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option( 'paymentsSofortBrandID' )
    );
}
function bluem_woocommerce_settings_render_paymentsCarteBancaireBrandID() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option( 'paymentsCarteBancaireBrandID' )
    );
}

function bluem_woocommerce_settings_render_paymentCompleteRedirectType() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option( 'paymentCompleteRedirectType' )
    );
}

function bluem_woocommerce_settings_render_paymentCompleteRedirectCustomURL() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option( 'paymentCompleteRedirectCustomURL' )
    );
}

function bluem_woocommerce_settings_render_paymentsUseDebtorWallet() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option( 'paymentsUseDebtorWallet' )
    );
}


// https://www.skyverge.com/blog/how-to-create-a-simple-woocommerce-payment-gateway/


add_filter( 'bluem_woocommerce_enhance_payment_request', 'bluem_woocommerce_enhance_payment_request_function', 10, 1 );

/**
 * allow third parties to add additional data to the request object through this additional action
 *
 * @param [type] $request
 *
 * @return void
 */
function bluem_woocommerce_enhance_payment_request_function( $request ) {
    // do something with the Bluem payment request, use this in third-party extensions of this system
    return $request;
}

add_action( 'show_user_profile', 'bluem_woocommerce_payments_show_extra_profile_fields', 2 );
add_action( 'edit_user_profile', 'bluem_woocommerce_payments_show_extra_profile_fields' );

function bluem_woocommerce_payments_show_extra_profile_fields( $user ) {
    $bluem_requests = bluem_db_get_requests_by_user_id_and_type( $user->ID, "payments" ); ?>
    <table class="form-table">
        <a id="user_payments"></a>
        <?php

        ?>

        <?php if ( isset( $bluem_requests ) && count( $bluem_requests ) > 0 ) { ?>
            <tr>
                <th>
                    ePayments transacties
                </th>
                <td>
                    <?php
                    bluem_render_requests_list( $bluem_requests ); ?>
                </td>
            </tr>
        <?php } ?>
    </table>
    <?php
}

// $bluem_options = get_option( 'bluem_woocommerce_options' );

// /**
//  * Check for enabled ePayments debtorwallet
//  */
// if ( isset( $bluem_options['paymentsUseDebtorWallet'] ) && $bluem_options['paymentsUseDebtorWallet'] == "1" ) {
//     add_action( "wp_ajax_bluem_retrieve_payments_bics_ajax", "bluem_retrieve_payments_bics_ajax" );

//     // define the function to be fired for logged in users
//     function bluem_retrieve_payments_bics_ajax() {
//         // nonce check for an extra layer of security, the function will exit if it fails
//         //    if ( !wp_verify_nonce( $_REQUEST['nonce'], "bluem_retrieve_bics_ajax_nonce")) {
//         //       exit("Woof Woof Woof");
//         //    }

//         // switch()

//         $bluem_config = bluem_woocommerce_get_config();
//         $bluem = new Bluem( $bluem_config );
//         $BICs = $bluem->retrieveBICsForContext( "Payments" );

//         if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) {
//             echo json_encode( $BICs );
//         } else {
//             header( "Location: " . $_SERVER["HTTP_REFERER"] );
//         }
//         die();
//     }
// }
