<?php

include_once __DIR__ . '/Bluem_Bank_Based_Payment_Gateway.php';

class Bluem_iDEAL_Payment_Gateway extends Bluem_Bank_Based_Payment_Gateway
{
    public function __construct()
    {    
        parent::__construct(
            'bluem_payments_ideal',
            __('Bluem betalingen via iDEAL'),
            __('Betaal gemakkelijk, snel en veilig via iDEAL')
        );

        $options = get_option( 'bluem_woocommerce_options' );
        if ( isset( $options['paymentsIDEALBrandID'] ) ) {
            $this->bankSpecificBrandID = $options['paymentsIDEALBrandID'] ??
                                         ($options['paymentBrandID'] ?? ''); // legacy brandID support
        }
    }
}
