<?php

include_once __DIR__ . '/Bluem_Bank_Based_Payment_Gateway.php';

class Bluem_PayPal_Payment_Gateway extends Bluem_Bank_Based_Payment_Gateway
{    
    public function __construct() {
        
        parent::__construct(
            'bluem_payments_paypal',
            __('Bluem betalingen via PayPal'),
            __('Betaal gemakkelijk, snel en veilig via PayPal')
        );

        $options = get_option( 'bluem_woocommerce_options' );
        if ( isset( $options['paymentsPayPalBrandID'] ) ) {
            $this->bankSpecificBrandID = $options['paymentsPayPalBrandID'] ??
                                         ($options['paymentBrandID'] ?? ''); // legacy brandID support
        }
    }
}
