<?php
require_once __DIR__ . '/Bluem_Payment_Gateway_Interface.php';

use Bluem\BluemPHP\Bluem;

abstract class Bluem_Payment_Gateway extends WC_Payment_Gateway implements Bluem_Payment_Gateway_Interface
{
    const PAYMENT_STATUS_SUCCESS = "Success";
    const PAYMENT_STATUS_FAILURE = "Failure";

    /**
     * This boolean will cause more output to be generated for testing purposes. Keep it at false for the production environment or final testing
     */
    const VERBOSE = false;

    /**
     * @var Stdclass
     */
    protected $bluem_config;

    /**
     * @var Bluem
     */
    protected $bluem;

    public function __construct($id, $method_title, $method_description, $callbackURL, $icon='')
    {
        // must be lowercase and with underscores for spaces
        $this->id = $id;
        $this->icon = $icon;
        $this->method_title = $method_title;
        $this->method_description_content = $method_description;
        $this->method_description = $method_description;

        // gateways can support subscriptions, refunds, saved payment methods,
        // but we support only payments at the moment
        $this->supports = array(
            'products'
        );

        // Load the settings.
        $this->init_settings();
        // Method with all the options fields
        $this->init_form_fields();

        $this->title = $this->get_option( 'title' ) ?? $this->method_title;
        $this->description = $this->get_option( 'description' ) ?? $this->method_description_content;


        $this->bluem_config = bluem_woocommerce_get_config();
        $this->bluem_config->merchantReturnURLBase = $callbackURL;

        $this->bluem_config = $this->methodSpecificConfigurationMixin($this->bluem_config);


        if ( $this->validateAndEnableBluemConfiguration() ) {
            $this->enabled = $this->get_option( 'enabled' );

            // This action hook saves the settings
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
                $this,
                'process_admin_options'
            ) );

            // ********** CREATING plugin URLs for specific functions **********
            // using WooCommerce's builtin webhook possibilities. This action creates an accessible URL wc-api/bluem_payments_webhook and one for the callback as well
            // reference: https://rudrastyh.com/woocommerce/payment-gateway-plugin.html#gateway_class

            // add_action( 'woocommerce_api_'.$this->id.'_webhook', array( $this, $this->id.'_webhook' ), 5 );
            // add_action( 'woocommerce_api_'.$this->id.'_callback', array( $this, $this->id.'_callback' ) );
            // @todo: should be implemented on a specific payment gateway instead of here, as the webhook & callback actions can differ.
            // The functions can be implemented generically (on bank_based level) but the action should be registered concretely

            // ********** Allow filtering Orders based on TransactionID **********
            add_filter(
                'woocommerce_order_data_store_cpt_get_orders_query',
                function ( $query, $query_vars ) {
                    if ( ! empty( $query_vars['bluem_transactionid'] ) ) {
                        $query['meta_query'][] = array(
                            'key'   => 'bluem_transactionid',
                            'value' => esc_attr( $query_vars['bluem_transactionid'] ),
                        );
                    }

                    return $query;
                },
                10,
                2
            );

            // ********** Allow filtering Orders based on EntranceCode **********
            add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', function ( $query, $query_vars ) {
                if ( ! empty( $query_vars['bluem_entrancecode'] ) ) {
                    $query['meta_query'][] = array(
                        'key'   => 'bluem_entrancecode',
                        'value' => esc_attr( $query_vars['bluem_entrancecode'] ),
                    );
                }

                return $query;
            }, 9, 2 );
        }
    }

    /**
     * Define payment fields
     */
    public function payment_fields()
    {
        //
    }

    /**
     * Payment fields validation
     */
    public function validate_fields()
    {
        //
    }

    /**
     * Create plugin options page in admin interface
     */
    public function init_form_fields()
    {
        $this->form_fields = apply_filters( 'wc_offline_form_fields', [
            'enabled'     => [
                'title'       => __('Inschakelen/uitschakelen'),
                'label'       => 'Enable '.$this->method_title,
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no'
            ],
            'title'       => [
                'title'       => __('Weergegeven titel'),
                'type'        => 'text',
                'description' => __('Dit is de titel die de gebruiker ziet bij het afrekenen.'),
                'default'     => $this->method_title,
            ],
            'description' => [
                'title'       => __('Beschrijving'),
                'type'        => 'textarea',
                'description' => __('Dit is de beschrijving die de gebruiker ziet tijdens het afrekenen.'),
                'default'     => $this->description
            ]
        ] );
    }

    /**
     * Thank you page.
     */
    protected function thank_you_page( string $order_id )
    {
        $order = wc_get_order( $order_id );

        $url = $order->get_checkout_order_received_url();

        $options = get_option( 'bluem_woocommerce_options' );
        if ( isset( $options['paymentCompleteRedirectType'] ) ) {
            if ( $options['paymentCompleteRedirectType'] === "custom"
                 && ! empty( $options['paymentCompleteRedirectCustomURL'] )
            ) {
                $url = site_url( $options['paymentCompleteRedirectCustomURL'] );
            } else {
                $url = $order->get_checkout_order_received_url();
            }
        }

        if ( ! $order->has_status( 'failed' ) ) {
            wp_safe_redirect( $url );
            exit;
        }
    }

    /**
     *
     * @return bool
     */
    protected function validateAndEnableBluemConfiguration(): bool
    {
        try {
            $this->bluem        = new Bluem( $this->bluem_config );
        } catch ( Exception $e ) {
            return false;
        }
        return true;
    }

    protected function methodSpecificConfigurationMixin($config)
    {
        // override this in subclasses
        return $config;
    }

    public function process_payment( $order_id )
    {
        //
    }
}
