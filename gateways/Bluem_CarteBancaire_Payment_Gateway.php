<?php if ( ! defined( 'ABSPATH' ) ) exit;

include_once __DIR__ . '/Bluem_Bank_Based_Payment_Gateway.php';

class Bluem_CarteBancaire_Payment_Gateway extends Bluem_Bank_Based_Payment_Gateway
{
    public function __construct()
    {
        parent::__construct(
            'bluem_payments_cartebancaire',
            esc_html__('Bluem payments via Carte Bancaire', 'bluem'),
            esc_html__('Pay easily, quickly and safely via Carte Bancaire', 'bluem')
        );

        $options = get_option('bluem_woocommerce_options');
        if (!empty($options['paymentsCarteBancaireBrandID'])) {
            $this->setBankSpecificBrandID($options['paymentsCarteBancaireBrandID']);
        } elseif (!empty($options['paymentBrandID'])) {
            $this->setBankSpecificBrandID($options['paymentBrandID']); // legacy brandID support
        }
    }
}
