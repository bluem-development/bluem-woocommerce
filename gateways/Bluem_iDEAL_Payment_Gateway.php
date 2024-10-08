<?php
if ( ! defined( 'ABSPATH' ) ) exit;
include_once __DIR__ . '/Bluem_Bank_Based_Payment_Gateway.php';

class Bluem_iDEAL_Payment_Gateway extends Bluem_Bank_Based_Payment_Gateway
{
    protected $_show_fields = false;

    public function __construct()
    {
        parent::__construct(
            'bluem_payments_ideal',
            esc_html__('Bluem payments via iDEAL', 'bluem'),
            esc_html__('Pay easily, quickly and safely via iDEAL', 'bluem')
        );

        $this->has_fields = true;

        $options = get_option('bluem_woocommerce_options');

        if (!empty($options['paymentsIDEALBrandID'])) {
            $this->setBankSpecificBrandID($options['paymentsIDEALBrandID']);
        } elseif (!empty($options['paymentBrandID'])) {
            $this->setBankSpecificBrandID($options['paymentBrandID']); // legacy brandID support
        }

        if (!empty($options['paymentsUseDebtorWallet']) && $options['paymentsUseDebtorWallet'] == '1') {
            $this->_show_fields = true;
        }
    }

    /**
     * Define payment fields
     */
    public function payment_fields()
    {
        if($this->bluem === null) {
            return;
        }

        $BICs = $this->bluem->retrieveBICsForContext( "Payments" );

        $description = $this->get_description();

        $options = [];

        if ($description) {
            echo wpautop(wptexturize($description)); // @codingStandardsIgnoreLine.
        }

        // Loop through BICS
        foreach ($BICs as $BIC) {
            $options[ $BIC->issuerID ] = $BIC->issuerName;
        }

        // Check for options
        if ($this->_show_fields && !empty($options)) {
            woocommerce_form_field('bluem_payments_ideal_bic', array(
                'type' => 'select',
                'required' => true,
                'label' => esc_html__('Select a bank:', 'bluem'),
                'options' => $options
            ), '');
        }
    }

    /**
     * Payment fields validation
     * @TODO
     */
    public function validate_fields()
    {
        return true;
    }
}
