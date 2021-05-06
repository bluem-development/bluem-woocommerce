<?php
// @todo: add Woo Product update key if necessary, check https://docs.woocommerce.com/document/create-a-plugin/
// @todo: Localize all error messages to english primarily
// @todo: finish docblocking

if (!defined('ABSPATH')) {
    exit;
}

use Bluem\BluemPHP\Bluem as Bluem;
use Carbon\Carbon;

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'bluem_add_gateway_class_payments', 12);
function bluem_add_gateway_class_payments($gateways)
{
    $gateways[] = 'Bluem_Gateway_Payments'; // your class name is here
    return $gateways;
}

/*
 * The gateway class itself, please note that it is inside plugins_loaded action hook
 */

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('plugins_loaded', 'bluem_init_payment_gateway_class');
}
function bluem_init_payment_gateway_class()
{
    class Bluem_Gateway_Payments extends WC_Payment_Gateway
    {
        /**
         * This boolean will cause more output to be generated for testing purposes. Keep it at false for the production environment or final testing
         */
        private const VERBOSE = false;

        /**
         * Class constructor
         */
        public function __construct()
        {
            // instantiate the helper class that contains many helpful things.
            // $this->core = new Bluem_Helper();

            $this->id = 'bluem_payments'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'Bluem betaling via iDeal';
            $this->method_description = 'Bluem iDeal Payment Gateway voor WordPress - WooCommerce '; // will be displayed on the options page


            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this version we begin with simple payments
            $this->supports = array(
                'products'
            );

            // Load the settings.
            $this->init_settings();
            // Method with all the options fields
            $this->init_form_fields();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');


            $bluem_config = bluem_woocommerce_get_config();
            $bluem_config->merchantReturnURLBase = home_url('wc-api/bluem_payments_callback');

            // specifiek brandID voor payments instellen
            if (isset($bluem_config->paymentBrandID)) {
                $bluem_config->brandID = $bluem_config->paymentBrandID;
            }

            $this->bluem_config = $bluem_config;
            $this->bluem = new Bluem($bluem_config);

            $this->enabled = $this->get_option('enabled');

            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // ********** CREATING plugin URLs for specific functions **********
            // using Woo's builtin webhook possibilities. This action creates an accessible URL wc-api/bluem_payments_webhook and one for the callback as well
            // reference: https://rudrastyh.com/woocommerce/payment-gateway-plugin.html#gateway_class
            add_action('woocommerce_api_bluem_payments_webhook', array($this, 'payments_webhook'), 5);


            add_action('woocommerce_api_bluem_payments_callback', array($this, 'payment_callback'));

            // ********** Allow filtering Orders based on TransactionID **********
            add_filter(
                'woocommerce_order_data_store_cpt_get_orders_query',
                function ($query, $query_vars) {
                    if (!empty($query_vars['bluem_transactionid'])) {
                        $query['meta_query'][] = array(
                            'key' => 'bluem_transactionid',
                            'value' => esc_attr($query_vars['bluem_transactionid']),
                        );
                    }
                    return $query;
                },
                10,
                2
            );


            // ********** Allow filtering Orders based on EntranceCode **********
            add_filter('woocommerce_order_data_store_cpt_get_orders_query', function ($query, $query_vars) {
                if (!empty($query_vars['bluem_entrancecode'])) {
                    $query['meta_query'][] = array(
                        'key' => 'bluem_entrancecode',
                        'value' => esc_attr($query_vars['bluem_entrancecode']),
                    );
                }
                return $query;
            }, 9, 2);
        }

        public function bluem_thankyou($order_id)
        {
            $order = wc_get_order($order_id);
            $url = $order->get_view_order_url();

            $options = get_option('bluem_woocommerce_options');
            if (isset($options['paymentCompleteRedirectType'])) {
                if ($options['paymentCompleteRedirectType'] == "custom"
                    && isset($options['paymentCompleteRedirectCustomURL'])
                    && $options['paymentCompleteRedirectCustomURL']!==""
                ) {
                    $url = site_url($options['paymentCompleteRedirectCustomURL']);
                } else {
                    $url = $order->get_view_order_url();
                }
            }

            if (!$order->has_status('failed')) {
                wp_safe_redirect($url);
                exit;
            }
        }
        /**
         * Create plugin options page in admin interface
         */
        public function init_form_fields()
        {
            $this->form_fields = apply_filters('wc_offline_form_fields', [
                'enabled' => [
                    'title'       => 'Enable/Disable',
                    'label'       => 'Enable Bluem Payment Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ],
                'title' => [
                    'title'       => 'Title',
                    'type'        => 'text',
                    'description' => 'This controls the title which the user sees during checkout.',
                    'default'     => 'iDeal',
                ],
                'description' => [
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'This controls the description which the user sees during checkout.',
                    'default'     => 'Betaal gemakkelijk, snel en veilig via iDeal',
                ]
            ]);
        }


        /**
         * Retrieve header HTML for error/message prompts
         *
         * @return String
         */
        private function getSimpleHeader(): String
        {
            return "<!DOCTYPE html><html><body><div
            style='font-family:Arial,sans-serif;display:block;
            margin:40pt auto; padding:10pt 20pt; border:1px solid #eee;
            background:#fff; max-width:500px;'>";
        }
        /**
         * Retrieve footer HTML for error/message prompt. Can include a simple link back to the webshop home URL.
         *
         * @param Bool $include_link
         * @return String
         */
        private function getSimpleFooter(Bool $include_link = true): String
        {
            return ($include_link ? "<p><a href='" . home_url() . "' target='_self' style='text-decoration:none;'>Ga terug naar de webshop</a></p>" : "") .
                "</div></body></html>";
        }

        /**
         * Render a piece of HTML sandwiched beteween a simple header and footer, with an optionally included link back home
         *
         * @param String $html
         * @param boolean $include_link
         * @return void
         */
        private function renderPrompt(String $html, $include_link = true)
        {
            echo $this->getSimpleHeader();
            echo $html;
            echo $this->getSimpleFooter($include_link);
        }

        /**
         * Process payment through Bluem portal
         *
         * @param String $order_id
         * @return void
         */
        public function process_payment($order_id)
        {
            $order = wc_get_order($order_id);

            $user_id = $order->get_user_id();
            $user_meta = get_user_meta($user_id);

            $order_id = $order->get_order_number();
            $customer_id = get_post_meta($order_id, '_customer_user', true);

            $entranceCode = $this->bluem->CreateEntranceCode();

            update_post_meta($order_id, 'bluem_entrancecode', $entranceCode);
            if (!is_null($customer_id) &&  $customer_id!="" && $customer_id!="0") {
                $description = "Klant {$customer_id} Bestelling {$order_id}";
            } else {
                $description = "Bestelling {$order_id}";
            }

            $debtorReference = "{$order_id}";
            $amount = $order->get_total();
            $currency = "EUR";
            $dueDateTime = Carbon::now()->addDay();

            $request = $this->bluem->CreatePaymentRequest(
                $description,
                $debtorReference,
                $amount,
                $dueDateTime,
                $currency,
                $entranceCode
            );

            // temp overrides
            $request->paymentReference = str_replace('-', '', $request->paymentReference);
            $request->type_identifier = "createTransaction";
            $request->dueDateTime = $dueDateTime->toDateTimeLocalString() . ".000Z";
            $request->debtorReturnURL = home_url("wc-api/bluem_payments_callback?entranceCode={$entranceCode}");

            $payload = json_encode(
                [
                    'environment' => $this->bluem->environment,
                    'amount'=>$amount,
                    'currency'=>$currency,
                    'due_date'=>$request->dueDateTime,
                    'payment_reference'=>$request->paymentReference
                ]
            );

            // allow third parties to add additional data to the request object through this additional action
            $request = apply_filters(
                'bluem_woocommerce_enhance_payment_request',
                $request
            );

            $response = $this->bluem->PerformRequest($request);
            // Possible statuses: 'pending', 'processing', 'on-hold', 'completed', 'refunded, 'failed', 'cancelled',


            // Remove cart
            global $woocommerce;
            $woocommerce->cart->empty_cart();
            $order->update_status('pending', __('Awaiting Bluem Payment Signature', 'wc-gateway-bluem'));

            if (isset($response->PaymentTransactionResponse->TransactionURL)) {
                $order->add_order_note(__("Betalingsproces geinitieerd"));

                $transactionID = "". $response->PaymentTransactionResponse->TransactionID;
                update_post_meta($order_id, 'bluem_transactionid', $transactionID);
                $paymentReference = "". $response->PaymentTransactionResponse->paymentReference;
                update_post_meta($order_id, 'bluem_payment_reference', $paymentReference);
                $debtorReference = "". $response->PaymentTransactionResponse->debtorReference;
                update_post_meta($order_id, 'bluem_debtor_Reference', $debtorReference);

                // redirect cast to string, for AJAX response handling
                $transactionURL = ($response->PaymentTransactionResponse->TransactionURL . "");



                bluem_db_create_request(
                    [
                        'entrance_code' => $entranceCode,
                        'transaction_id' => $transactionID,
                        'transaction_url' => $transactionURL,
                        'user_id' => get_current_user_id(),
                        'timestamp' =>  date("Y-m-d H:i:s"),
                        'description' => $description,
                        'debtor_reference' => $debtorReference,
                        'type' => "payments",
                        'order_id' => $order_id,
                        'payload' => $payload
                    ]
                );

                return array(
                    'result' => 'success',
                    'redirect' => $transactionURL
                );
            } else {
                return array(
                    'result' => 'failure'
                );
            }
        }


        /**
         * payments_Webhook action
         *
         * @return void
         */
        public function payments_webhook()
        {
            if ($_GET['env'] && is_string($_GET['env'])
                && in_array(
                    sanitize_text_field($_GET['env']),
                    ['test','prod']
                )
            ) {
                $env = sanitize_text_field($_GET['env']);
            } else {
                $env = "test";
            }

            $statusUpdateObject = $this->bluem->Webhook();
            echo "Completed webhook";
            var_dump($statusUpdateObject);
            die();
            // @todo: continue webhook specifics

            $entranceCode = $statusUpdateObject->entranceCode . "";
            $transactionID = $statusUpdateObject->PaymentStatus->MandateID . "";

            $webhook_status = $statusUpdateObject->PaymentStatus->Status . "";

            $order = $this->getOrder($transactionID);
            if (is_null($order)) {
                echo "Error: No order found";
                exit;
            }
            $order_status = $order->get_status();

            if (self::VERBOSE) {
                echo "order_status: {$order_status}" . PHP_EOL;
                echo "webhook_status: {$webhook_status}" . PHP_EOL;
            }

            $user_id = $user_id = $order->get_user_id();
            $user_meta = get_user_meta($user_id);

            // Todo: if maxamount comes back from webhook (it should) then it can be accessed here
            // if (isset($user_meta['bluem_latest_mandate_amount'][0])) {
            // 	$mandate_amount = $user_meta['bluem_latest_mandate_amount'][0];
            // } else {
            // }


            if (isset($statusUpdateObject->PaymentStatus->AcceptanceReport->MaxAmount)) {
                $mandate_amount = (float) ($statusUpdateObject->PaymentStatus->AcceptanceReport->MaxAmount . "");
            } else {
                $mandate_amount = (float) 0.0;	// mandate amount is not set, so it is unlimited
            }
            if (self::VERBOSE) {
                var_dump($mandate_amount);
                echo PHP_EOL;
                die();
            }

            if (self::VERBOSE) {
                echo "mandate_amount: {$mandate_amount}" . PHP_EOL;
            }

            $mandate_successful = false;

            if ($mandate_amount !== 0.0) {
                $order_price = $order->get_total();
                $max_order_amount = (float) ($order_price * 1.1);
                if (self::VERBOSE) {
                    "max_order_amount: {$max_order_amount}" . PHP_EOL;
                }

                if ($mandate_amount >= $max_order_amount) {
                    $mandate_successful = true;
                    if (self::VERBOSE) {
                        "mandate is enough" . PHP_EOL;
                    }
                } else {
                    if (self::VERBOSE) {
                        "mandate is too small" . PHP_EOL;
                    }
                }
            }
            if ($webhook_status === "Success") {
                if ($order_status === "processing") {
                    // order is already marked as processing, nothing more is necessary
                } elseif ($order_status === "pending") {
                    // check if maximum of order does not exceed mandate size based on user metadata
                    if ($mandate_successful) {
                        $order->update_status('processing', __('Betaling is gelukt en goedgekeurd; via webhook', 'wc-gateway-bluem'));
                    }
                    // iff order is within size, update to processing
                }
            } elseif ($webhook_status === "Cancelled") {
                $order->update_status('cancelled', __('Betaling is geannuleerd; via webhook', 'wc-gateway-bluem'));
            } elseif ($webhook_status === "Open" || $webhook_status == "Pending") {
                // if the webhook is still open or pending, nothing has to be done as of yet
            } elseif ($webhook_status === "Expired") {
                $order->update_status('failed', __('Betaling is verlopen; via webhook', 'wc-gateway-bluem'));
            } else {
                $order->update_status('failed', __('Betaling is gefaald: fout of onbekende status; via webhook', 'wc-gateway-bluem'));
            }
            exit;
        }

        public function getOrderByEntranceCode($entranceCode)
        {
            $orders = wc_get_orders(array(
                'orderby'   => 'date',
                'order'     => 'DESC',
                'bluem_entrancecode' => $entranceCode
            ));
            if (count($orders) == 0) {
                return null;
            }
            return $orders[0];
        }

        /**
         * Retrieve an order based on its mandate_id in metadata from the WooCommerce store
         *
         * @param String $transactionID
         *
         */
        private function getOrder(String $transactionID)
        {
            $orders = wc_get_orders(array(
                'orderby'   => 'date',
                'order'     => 'DESC',
                'bluem_transactionid' => $transactionID
            ));
            if (count($orders) == 0) {
                return null;
            }
            return $orders[0];
        }


        /**
         * payment_Callback function after payment process has been completed by the user
         * @return function [description]
         */
        public function payment_callback()
        {

            // $this->bluem = new Bluem($this->bluem_config);

            if (!isset($_GET['entranceCode'])) {
                $this->renderPrompt("Fout: geen juiste entranceCode teruggekregen bij payment_callback. Neem contact op met de webshop en vermeld je contactgegevens.");
                exit;
            }
            $entranceCode = sanitize_text_field($_GET['entranceCode']);

            $order = $this->getOrderByEntranceCode($entranceCode);

            if (is_null($order)) {
                $this->renderPrompt("Fout: order niet gevonden in webshop. Neem contact op met de webshop en vermeld de code {$entranceCode} bij je gegevens.");
                exit;
            }
            $user_id = $order->get_user_id();

            $transactionID = $order->get_meta('bluem_transactionid');
            if ($transactionID=="") {
                $this->renderPrompt("No transaction ID found. Neem contact op met de webshop en vermeld de code {$entranceCode} bij je gegevens.");
                die();
            }

            $response = $this->bluem->PaymentStatus($transactionID, $entranceCode);


            if (!$response->Status()) {
                $this->renderPrompt("Fout bij opvragen status: " . $response->Error() . "<br>Neem contact op met de webshop en vermeld deze status");
                exit;
            }

            if (self::VERBOSE) {
                var_dump("mandateid: " . $transactionID);
                var_dump("entrancecode: " . $entranceCode);
                echo "<hr>";
                var_dump($response);
                echo "<hr>";
            }

            $statusUpdateObject = $response->PaymentStatusUpdate;
            $statusCode = $statusUpdateObject->Status . "";

            $request_from_db = bluem_db_get_request_by_transaction_id($transactionID);

            if ($statusCode !== $request_from_db->status) {
                bluem_db_update_request(
                    $request_from_db->id,
                    [
                        'status'=>$statusCode
                        ]
                );
            }



            if ($statusCode === "Success") {
                $order->update_status('processing', __('Betaling is binnengekomen', 'wc-gateway-bluem'));

                $order->add_order_note(__("Betalingsproces voltooid"));

                bluem_transaction_notification_email(
                    $request_from_db->id
                );

                $this->bluem_thankyou($order->get_id());
            } elseif ($statusCode === "Cancelled") {
                $order->update_status('cancelled', __('Betaling is geannuleerd', 'wc-gateway-bluem'));

                
                bluem_transaction_notification_email(
                    $request_from_db->id
                );
                $this->renderPrompt("Je hebt de betaling geannuleerd");
                // terug naar order pagina om het opnieuw te proberen?
                exit;
            } elseif ($statusCode === "Open" || $statusCode == "Pending") {
                bluem_transaction_notification_email(
                    $request_from_db->id
                );
                $this->renderPrompt("De betaling is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch.");
                // callback pagina beschikbaar houden om het opnieuw te proberen?
                // is simpelweg SITE/wc-api/bluem_callback?transactionID=$transactionID
                exit;
            } elseif ($statusCode === "Expired") {
                $order->update_status('failed', __('Betaling is verlopen', 'wc-gateway-bluem'));
                bluem_transaction_notification_email(
                    $request_from_db->id
                );
                
                $this->renderPrompt("Fout: De betaling of het verzoek daartoe is verlopen");
                exit;
            } else {
                $order->update_status('failed', __('Betaling is gefaald: fout of onbekende status', 'wc-gateway-bluem'));
                bluem_transaction_notification_email(
                    $request_from_db->id
                );
                $this->renderPrompt("Fout: Onbekende of foutieve status teruggekregen: {$statusCode}<br>Neem contact op met de webshop en vermeld deze status");
                exit;
            }
        }
    }
}

function bluem_woocommerce_payments_settings_section()
{
    echo '<p><a id="tab_payments"></a>
    Hier kan je alle belangrijke gegevens instellen rondom iDeal transacties. Lees de readme bij de plug-in voor meer informatie.</p>';
}

function bluem_woocommerce_get_payments_option($key)
{
    $options = bluem_woocommerce_get_payments_options();
    if (array_key_exists($key, $options)) {
        return $options[$key];
    }
    return false;
}

function bluem_woocommerce_get_payments_options()
{
    return [
        'paymentBrandID' => [
            'key'=>'paymentBrandID',
            'title' => 'bluem_paymentBrandID',
            'name' => 'Bluem Brand ID voor Payments',
            'description' => 'het paymentBrandID, ontvangen van Bluem specifiek voor betalingen',
            'default' => ''
        ],
        'paymentCompleteRedirectType' => [
            'key' => 'paymentCompleteRedirectType',
            'title' => 'bluem_paymentCompleteRedirectType',
            'name' => 'Waarheen verwijzen na succesvolle betaling?',
            'description' => 'Als de gebruiker heeft betaald, waar moet dan naar verwezen worden?',
            'type' => 'select',
            'default' => 'order_details',
            'options' => [
                'order_details' => 'Pagina met Order gegevens (standaard)',
                'custom' => 'Eigen URL (vul hieronder in)'
            ]
        ],
        'paymentCompleteRedirectCustomURL' => [
            'key' => 'paymentCompleteRedirectCustomURL',
            'title' => 'bluem_paymentCompleteRedirectCustomURL',
            'name' => 'Eigen interne URL om klant naar te verwijzen',
            'description' => "Indien hierboven 'Eigen URL' is gekozen, vul hier dan de URL in waarnaar doorverwezen moet worden. Je kan bijv. <code>thanks</code> invullen om de klant naar <strong>".site_url("thanks"). "</strong> te verwijzen",
            'type' => 'text',
            'default' => ''
        ],
    ];
}

/* payments specific settings */

function bluem_woocommerce_settings_render_paymentBrandID()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option('paymentBrandID')
    );
}


function bluem_woocommerce_settings_render_paymentCompleteRedirectType()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option('paymentCompleteRedirectType')
    );
}

function bluem_woocommerce_settings_render_paymentCompleteRedirectCustomURL()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_payments_option('paymentCompleteRedirectCustomURL')
    );
}




// https://www.skyverge.com/blog/how-to-create-a-simple-woocommerce-payment-gateway/


add_filter('bluem_woocommerce_enhance_payment_request', 'bluem_woocommerce_enhance_payment_request_function', 10, 1);

/**
 * allow third parties to add additional data to the request object through this additional action
 *
 * @param [type] $request
 * @return void
 */
function bluem_woocommerce_enhance_payment_request_function($request)
{
    // do something with the Bluem payment request, use this in third-party extensions of this system
    return $request;
}

add_action('show_user_profile', 'bluem_woocommerce_payments_show_extra_profile_fields', 2);
add_action('edit_user_profile', 'bluem_woocommerce_payments_show_extra_profile_fields');

function bluem_woocommerce_payments_show_extra_profile_fields($user)
{
    $bluem_requests = bluem_db_get_requests_by_user_id_and_type($user->ID, "payments"); ?>
    <table class="form-table">
    <a id="user_payments"></a>
    <?php
        
        ?>

    <?php if (isset($bluem_requests) && count($bluem_requests)>0) { ?>
        <tr>
    <th>
    iDEAL Betalingen
    </th>
    <td>
    <?php
        bluem_render_requests_list($bluem_requests);?>
    </td>
        </tr>
    <?php } ?>
    </table>
    <?php
}
