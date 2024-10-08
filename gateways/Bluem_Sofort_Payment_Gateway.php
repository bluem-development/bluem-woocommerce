<?php
if ( ! defined( 'ABSPATH' ) ) exit;
include_once __DIR__ . '/Bluem_Bank_Based_Payment_Gateway.php';

class Bluem_Sofort_Payment_Gateway extends Bluem_Bank_Based_Payment_Gateway
{
    public function __construct()
    {
        parent::__construct(
            'bluem_payments_sofort',
            esc_html__('Bluem payments via SOFORT', 'bluem'),
            esc_html__('Pay easily, quickly and safely via SOFORT', 'bluem')
        );

        $options = get_option('bluem_woocommerce_options');
        if (!empty($options['paymentsSofortBrandID'])) {
            $this->setBankSpecificBrandID($options['paymentsSofortBrandID']);
        } elseif (!empty($options['paymentBrandID'])) {
            $this->setBankSpecificBrandID($options['paymentBrandID']); // legacy brandID support
        }
    }
}
