<?php

include_once __DIR__ . '/Bluem_Bank_Based_Payment_Gateway.php';

class Bluem_CarteBancaire_Payment_Gateway extends Bluem_Bank_Based_Payment_Gateway
{
    public function __construct()
    {
        parent::__construct(
            'bluem_payments_cartebancaire',
            __('Bluem betalingen via Carte Bancaire'),
            __('Betaal gemakkelijk, snel en veilig via Carte Bancaire')
        );

        $options = get_option( 'bluem_woocommerce_options' );
        if ( !empty( $options['paymentsCarteBancaireBrandID'] ) ) {
            $this->setBankSpecificBrandID($options['paymentsCarteBancaireBrandID']);
        } elseif ( !empty( $options['paymentBrandID'] ) ) {
            $this->setBankSpecificBrandID($options['paymentBrandID']); // legacy brandID support
        }
    }
}
