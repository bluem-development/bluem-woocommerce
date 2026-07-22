<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/Bluem_Payment_Gateway_Interface.php';

use Bluem\BluemPHP\Bluem;

#[AllowDynamicProperties]
abstract class Bluem_Payment_Gateway extends WC_Payment_Gateway implements Bluem_Payment_Gateway_Interface
{
    /**
     * Custom order query variables used by Bluem callbacks.
     *
     * @var array<string, string>
     */
    private const ORDER_QUERY_META_KEYS = [
        'bluem_transactionid' => 'bluem_transactionid',
        'bluem_entrancecode' => 'bluem_entrancecode',
        'bluem_mandateid'    => 'bluem_mandateid',
    ];

    private static bool $order_query_filters_registered = false;

    public const PAYMENT_STATUS_SUCCESS = "Success";
    public const PAYMENT_STATUS_FAILURE = "Failure";
    public const PAYMENT_STATUS_NEW = "New";

    /**
     * This boolean will cause more output to be generated for testing purposes. Keep it at false for the production environment or final testing
     */
    public const VERBOSE = false;

    /**
     * @var Stdclass
     */
    protected $bluem_config;


    public $id;
    public $title;
    public $icon;
    public $method_description;
    public $method_title;
    public $method_description_content;

    /**
     * @var Bluem
     */
    protected $bluem;

    public function __construct($id, $method_title, $method_description, $callbackURL, $icon = '')
    {
        // must be lowercase and with underscores for spaces
        $this->id = $id;
        $this->icon = $icon;
        $this->method_title = $method_title;
        $this->method_description_content = $method_description;
        $this->method_description = $method_description;

        // gateways can support subscriptions, refunds, saved payment methods,
        // but we support only payments at the moment
        $this->supports = [
            'products',
        ];

        // Load the settings.
        $this->init_settings();
        // Method with all the options fields
        $this->init_form_fields();

        $this->title = $this->get_option('title') ?? $this->method_title;
        $this->description = $this->get_option('description') ?? $this->method_description_content;


        $this->bluem_config = bluem_woocommerce_get_config();
        $this->bluem_config->merchantReturnURLBase = $callbackURL;

        $this->bluem_config = $this->methodSpecificConfigurationMixin($this->bluem_config);


        if ($this->validateAndEnableBluemConfiguration()) {
            $this->enabled = $this->get_option('enabled');

            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [
                $this,
                'process_admin_options',
            ]);

            self::register_order_query_filters();

            // ********** CREATING plugin URLs for specific functions **********
            // using WooCommerce's builtin webhook possibilities. This action creates an accessible URL wc-api/bluem_payments_webhook and one for the callback as well
            // reference: https://rudrastyh.com/woocommerce/payment-gateway-plugin.html#gateway_class

            // add_action( 'woocommerce_api_'.$this->id.'_webhook', array( $this, $this->id.'_webhook' ), 5 );
            // add_action( 'woocommerce_api_'.$this->id.'_callback', array( $this, $this->id.'_callback' ) );
            // @todo: should be implemented on a specific payment gateway instead of here, as the webhook & callback actions can differ.
            // The functions can be implemented generically (on bank_based level) but the action should be registered concretely

        }
    }

    /**
     * Register order query adapters for both WooCommerce data stores.
     *
     * WooCommerce uses different query filters for HPOS and the legacy
     * posts-based order store. Keeping the Bluem query variables stable lets
     * callback code remain unchanged while translating them to order meta in
     * either datastore.
     */
    private static function register_order_query_filters(): void
    {
        if ( self::$order_query_filters_registered ) {
            return;
        }

        add_filter(
            'woocommerce_order_query_args',
            [ self::class, 'add_hpos_order_query_meta' ]
        );
        add_filter(
            'woocommerce_order_data_store_cpt_get_orders_query',
            [ self::class, 'add_legacy_order_query_meta' ],
            10,
            2
        );

        self::$order_query_filters_registered = true;
    }

    /**
     * Translate Bluem query variables for HPOS.
     *
     * @param array<string, mixed> $query_args
     * @return array<string, mixed>
     */
    public static function add_hpos_order_query_meta(array $query_args): array
    {
        foreach ( self::ORDER_QUERY_META_KEYS as $query_var => $meta_key ) {
            if ( empty( $query_args[ $query_var ] ) ) {
                continue;
            }

            if ( ! isset( $query_args['meta_query'] ) || ! is_array( $query_args['meta_query'] ) ) {
                $query_args['meta_query'] = [];
            }

            $query_args['meta_query'][] = [
                'key'     => $meta_key,
                'value'   => sanitize_text_field( (string) $query_args[ $query_var ] ),
                'compare' => '=',
            ];
            unset( $query_args[ $query_var ] );
        }

        return $query_args;
    }

    /**
     * Translate Bluem query variables for the legacy posts-based order store.
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $query_vars
     * @return array<string, mixed>
     */
    public static function add_legacy_order_query_meta(array $query, array $query_vars): array
    {
        foreach ( self::ORDER_QUERY_META_KEYS as $query_var => $meta_key ) {
            if ( empty( $query_vars[ $query_var ] ) ) {
                continue;
            }

            if ( ! isset( $query['meta_query'] ) || ! is_array( $query['meta_query'] ) ) {
                $query['meta_query'] = [];
            }

            $query['meta_query'][] = [
                'key'     => $meta_key,
                'value'   => sanitize_text_field( (string) $query_vars[ $query_var ] ),
                'compare' => '=',
            ];
        }

        return $query;
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
        $this->form_fields = apply_filters('wc_offline_form_fields', [
            'enabled' => [
                'title' => esc_html__('Enable/disable', 'bluem'),
                'label' => 'Enable ' . $this->method_title,
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ],
            'title' => [
                'title' => esc_html__('Displayed title', 'bluem'),
                'type' => 'text',
                'description' => esc_html__('This is the title the user sees during checkout.', 'bluem'),
                'default' => $this->method_title,
            ],
            'description' => [
                'title' => esc_html__('Description', 'bluem'),
                'type' => 'textarea',
                'description' => esc_html__('This is the description the user sees during checkout.', 'bluem'),
                'default' => $this->description,
            ],
        ]);
    }

    /**
     * Thank you page.
     */
    protected function thank_you_page(string $order_id)
    {
        $order = wc_get_order($order_id);

        $url = $order->get_checkout_order_received_url();

        $options = get_option('bluem_woocommerce_options');
        if (isset($options['paymentCompleteRedirectType'])) {
            if ($options['paymentCompleteRedirectType'] === "custom"
                && !empty($options['paymentCompleteRedirectCustomURL'])
            ) {
                $url = site_url($options['paymentCompleteRedirectCustomURL']);
            } else {
                $url = $order->get_checkout_order_received_url();
            }
        }

        if (!$order->has_status('failed')) {
            wp_safe_redirect($url);
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
            $this->bluem = new Bluem($this->bluem_config);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    protected function methodSpecificConfigurationMixin($config)
    {
        // override this in subclasses
        return $config;
    }

    public function process_payment($order_id)
    {
        //
    }
}
