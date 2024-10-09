<?php
if ( ! defined( 'ABSPATH' ) ) exit;
include_once __DIR__ . '/Bluem_Bank_Based_Payment_Gateway.php';

class Bluem_PayPal_Payment_Gateway extends Bluem_Bank_Based_Payment_Gateway
{
    public function __construct()
    {
        parent::__construct(
            'bluem_payments_paypal',
            esc_html__('Bluem payments via PayPal', 'bluem'),
            esc_html__('Pay easily, quickly and safely via PayPal', 'bluem')
        );

        $options = get_option('bluem_woocommerce_options');

        if (!empty($options['paymentsPayPalBrandID'])) {
            $this->setBankSpecificBrandID($options['paymentsPayPalBrandID']);
        } elseif (!empty($options['paymentBrandID'])) {
            $this->setBankSpecificBrandID($options['paymentBrandID']); // legacy brandID support
        }
    }
}
