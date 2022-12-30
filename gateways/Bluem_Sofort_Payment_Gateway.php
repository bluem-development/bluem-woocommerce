<?php

include_once __DIR__ . '/Bluem_Bank_Based_Payment_Gateway.php';

class Bluem_Sofort_Payment_Gateway extends Bluem_Bank_Based_Payment_Gateway
{
    public function __construct()
    {
        parent::__construct(
            'bluem_payments_sofort',
            __('Bluem betalingen via SOFORT'),
            __('Betaal gemakkelijk, snel en veilig via SOFORT')
        );

        $options = get_option( 'bluem_woocommerce_options' );
        if ( !empty( $options['paymentsSofortBrandID'] ) ) {
            $this->setBankSpecificBrandID($options['paymentsSofortBrandID']);
        } elseif ( !empty( $options['paymentBrandID'] ) ) {
            $this->setBankSpecificBrandID($options['paymentBrandID']); // legacy brandID support
        }
    }
}
