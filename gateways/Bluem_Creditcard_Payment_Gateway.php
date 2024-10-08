<?php

if ( ! defined( 'ABSPATH' ) ) exit;

include_once __DIR__ . '/Bluem_Bank_Based_Payment_Gateway.php';

class Bluem_Creditcard_Payment_Gateway extends Bluem_Bank_Based_Payment_Gateway
{
    public function __construct()
    {
        parent::__construct(
            'bluem_payments_creditcard',
            esc_html__('Bluem payments via Credit Card', 'bluem'),
            esc_html__('Pay easily, quickly and safely via Credit Card', 'bluem'),
            home_url('wc-api/bluem_payments_callback')
        );

        $options = get_option('bluem_woocommerce_options');
        if (!empty($options['paymentsCreditcardBrandID'])) {
            $this->setBankSpecificBrandID($options['paymentsCreditcardBrandID']);
        } elseif (!empty($options['paymentBrandID'])) {
            $this->setBankSpecificBrandID($options['paymentBrandID']); // legacy brandID support
        }
    }
}
