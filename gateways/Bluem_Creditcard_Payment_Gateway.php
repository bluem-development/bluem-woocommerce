<?php

include_once __DIR__ . '/Bluem_Bank_Based_Payment_Gateway.php';

class Bluem_Creditcard_Payment_Gateway extends Bluem_Bank_Based_Payment_Gateway
{    
    public function __construct() {
        
        parent::__construct(
            'bluem_payments_creditcard',
            __('Bluem betalingen via Credit Card'),
            __('Betaal gemakkelijk, snel en veilig via Credit Card'),
            home_url( 'wc-api/bluem_payments_callback' ),
        );

        $options = get_option( 'bluem_woocommerce_options' );
        if ( isset( $options['paymentsCreditcardBrandID'] ) ) {
            $this->bankSpecificBrandID = $options['paymentsCreditcardBrandID'] ??
                                         ($options['paymentBrandID'] ?? ''); // legacy brandID support
        }
    }
}

