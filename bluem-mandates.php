<?php


// @todo: add Woo Product update key if necessary, check https://docs.woocommerce.com/document/create-a-plugin/
// @todo: Localize all error messages to english primarily
// @todo: finish docblocking

if (!defined('ABSPATH')) {
    exit;
}



use Bluem\BluemPHP\Integration;
use Carbon\Carbon;

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'bluem_add_gateway_class_mandates', 11);
function bluem_add_gateway_class_mandates($gateways)
{
    $gateways[] = 'Bluem_Gateway_Mandates'; // your class name is here
    return $gateways;
}

function bluem_woocommerce_get_mandates_option($key)
{
    $options = bluem_woocommerce_get_mandates_options();
    if (array_key_exists($key, $options)) {
        return $options[$key];
    }
    return false;
}

function bluem_woocommerce_get_mandates_options()
{
    return [
        'brandID' => [
            'key' => 'brandID',
            'title' => 'bluem_brandID',
            'name' => 'Bluem Brand ID',
            'description' => 'Wat is je Bluem eMandates BrandID? Gegeven door Bluem',
            'default' => ''
        ],
        'merchantID' => [
            'key' => 'merchantID',
            'title' => 'bluem_merchantID',
            'name' => 'Incassant merchantID (benodigd voor machtigingen op Productie)',
            'description' => 'Het merchantID, te vinden op het contract dat je hebt met de bank voor ontvangen van incasso machtigingen. <strong>Dit is essentieel: zonder dit gegeven zal een klant geen machtiging kunnen afsluiten op productie</strong>',
            'default' => ''
        ],
        'merchantSubId' => [
            'key' => 'merchantSubId',
            'title' => 'bluem_merchantSubId',
            'name' => 'Bluem Merchant Sub ID',
            'default' => '0',
            'description' => 'Hier hoef je waarschijnlijk niks aan te veranderen',
            'type' => 'select',
            'options' => ['0' => '0']
        ],

        'thanksPage' => [
            'key' => 'thanksPage',
            'title' => 'bluem_thanksPage',
            'name' => 'Waar wordt de gebruiker uiteindelijk naar verwezen?',
            'type' => 'select',
            'options' => [
                'order_page' => "Detailpagina van de zojuist geplaatste bestelling (standaard)"
            ],
        ],
        'eMandateReason' => [
            'key' => 'eMandateReason',
            'title' => 'bluem_eMandateReason',
            'name' => 'Reden voor Machtiging',
            'description' => 'Een bondige beschrijving van incasso weergegeven bij afgifte',
            'default' => 'Incasso machtiging'
        ],
        'localInstrumentCode' => [
            'key' => 'localInstrumentCode',
            'title' => 'bluem_localInstrumentCode',
            'name' => 'Type incasso machtiging afgifte',
            'description' => 'Kies type incassomachtiging. Neem bij vragen hierover contact op met Bluem.',
            'type' => 'select',
            'default' => 'CORE',
            'options' => ['CORE' => 'CORE machtiging', 'B2B' => 'B2B machtiging (zakelijk)']
        ],

        // RequestType = Issuing (altijd)
        'requestType' => [
            'key' => 'requestType',
            'title' => 'bluem_requestType',
            'name' => 'Bluem Request Type',
            'description' => '',
            'type' => 'select',
            'default' => 'Issuing',
            'options' => ['Issuing' => 'Issuing (standaard)']
        ],

        // SequenceType = RCUR (altijd)
        'sequenceType' => [
            'key' => 'sequenceType',
            'title' => 'bluem_sequenceType',
            'name' => 'Type incasso sequentie',
            'description' => '',
            'type' => 'select',
            'default' => 'RCUR',
            'options' => ['RCUR' => 'Recurring machtiging']
        ],

        'successMessage' => [
            'key' => 'successMessage',
            'title' => 'bluem_successMessage',
            'name' => 'Melding bij succesvolle machtiging via shortcode formulier',
            'description' => 'Een bondige beschrijving volstaat.',
            'default' => 'Uw machtiging is succesvol ontvangen. Hartelijk dank.'
        ],
        'errorMessage' => [
            'key' => 'errorMessage',
            'title' => 'bluem_errorMessage',
            'name' => 'Melding bij gefaalde machtiging via shortcode formulier',
            'description' => 'Een bondige beschrijving volstaat.',
            'default' => 'Er is een fout opgetreden. De incassomachtiging is geannuleerd.'
        ],

        'purchaseIDPrefix' => [
            'key' => 'purchaseIDPrefix',
            'title' => 'bluem_purchaseIDPrefix',
            'name' => 'Automatisch Voorvoegsel bij klantreferentie',
            'description' => "Welke korte tekst moet voor de debtorReference weergegeven worden bij een transactie in de Bluem incassomachtiging portaal. Dit kan handig zijn om Bluem transacties makkelijk te kunnen identificeren.",
            'type' => 'text',
            'default' => ''
        ],
        'debtorReferenceFieldName' => [
            'key' => 'debtorReferenceFieldName',
            'title' => 'bluem_debtorReferenceFieldName',
            'name' => 'Shortcode label voor klantreferentie bij invulformulier',
            'description' => "Welk label moet bij het invulveld in het formulier komen te staan? Dit kan bijvoorbeeld 'volledige naam' of 'klantnummer' zijn.",
            'type' => 'text',
            'default' => ''
        ],
        'thanksPageURL'=> [
            'key'=>'thanksPageURL',
            'title'=>'bluem_thanksPageURL',
            'name'=>'URL van bedankpagina',
            'description'=>"Indien je de Machtigingen shortcode gebruikt: Op welke pagina wordt de shortcode geplaatst? Dit is een slug, dus als je <code>thanks</code> invult, wordt de gehele URL: ".site_url("thanks"),
            'type'=>'text',
            'default'=>''
        ],
        'mandate_id_counter' => [
            'key' => 'mandate_id_counter',
            'title' => 'bluem_mandate_id_counter',
            'name' => 'Begingetal mandaat ID\'s',
            'description' => "Op welk getal wil je mandaat op idt moment nummeren? Dit getal wordt vervolgens automatisch opgehoogd.",
            'type' => 'text',
            'default' => '1'
        ],
        'maxAmountEnabled' => [
            'key' => 'maxAmountEnabled',
            'title' => 'bluem_maxAmountEnabled',
            'name' => 'Check op maximale bestelwaarde voor incassomachtigingen',
            'description' => "Wil je dat er een check wordt uitgevoerd op de maximale waarde van de incasso, indien er een beperkte bedrag incasso machtiging is afgegeven? Zet dit gegeven dan op 'wel checken'. Er wordt dan een foutmelding gegeven als een klant een bestelling plaatst met een toegestaan bedrag dat lager is dan het orderbedrag (vermenigvuldigd met het volgende gegeven, de factor). Is de machtiging onbeperkt of anders groter dan het orderbedrag, dan wordt de machtiging geaccepteerd.",
            'type' => 'select',
            'default' => '0',
            'options' => ['0' => 'Niet checken op MaxAmount', '1' => 'Wel checken op MaxAmount'],
        ],

        //Bij B2B krijgen wij terug of de gebruiker een maximaal mandaatbedrag heeft afgegeven.
        // Dit mandaat bedrag wordt vergeleken met de orderwaarde. De orderwaarde plus
        // onderstaand percentage moet lager zijn dan het maximale mandaatbedrag.
        // Geef hier het percentage aan.
        'maxAmountFactor' => [
            'key' => 'maxAmountFactor',
            'title' => 'bluem_maxAmountFactor',
            'name' => 'Welke factor van de bestelling mag het maximale bestelbedrag zijn?',
            'description' => "Als er een max amount wordt meegestuurd, wat is dan het maximale bedrag wat wordt toegestaan? Gebaseerd op de order grootte.",
            'type' => 'number',
            'attrs' => ['step' => '0.01', 'min' => '0.00', 'max' => '999.00', 'placeholder' => '1.00'],
            'default' => '1.00'
        ],
        'useMandatesDebtorWallet' => [
            'key' => 'useMandatesDebtorWallet',
            'title' => 'bluem_useMandatesDebtorWallet',
            'name' => 'Selecteer bank in Bluem Portal?',
            'description' => "Wil je dat er in deze website al een bank moet worden geselecteerd bij de Checkout procedure, in plaats van in de Bluem Portal? Indien je 'Gebruik eigen checkout' selecteert, wordt er een veld toegevoegd aan de WooCommerce checkout pagina waar je een van de beschikbare banken kan selecteren.",
            'type' => 'select',
            'default' => '0',
            'options' => ['0' => 'Gebruik Bluem Portal (standaard)', '1' => 'Gebruik eigen checkout'],
        ],
    ];
}


/*
 * The gateway class itself, please note that it is inside plugins_loaded action hook
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('plugins_loaded', 'bluem_init_mandate_gateway_class');
}

function bluem_init_mandate_gateway_class()
{
    class Bluem_Gateway_Mandates extends WC_Payment_Gateway
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
            $this->id = 'bluem_mandates'; // payment gateway plugin ID
            $this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields = true; // in case you need a custom credit card form
            $this->method_title = 'Bluem Digitaal Incassomachtiging (eMandate)';
            $this->method_description = 'Bluem eMandate Payment Gateway voor WordPress - WooCommerce. Alle instellingen zijn in te stellen onder <a href="' . admin_url('options-general.php?page=bluem') . '">Instellingen &rarr; Bluem</a>';

            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this version we support only payments
            $this->supports = array(
                'products'
            );

            // Load the settings.
            $this->init_settings();
            // Method with all the options fields
            $this->init_form_fields();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            // ********** CREATING Bluem Configuration **********
            $this->bluem_config = bluem_woocommerce_get_config();

            if (isset($this->bluem_config->localInstrumentCode) && $this->bluem_config->localInstrumentCode=="B2B") {
                $this->method_title = 'Bluem Zakelijke Incassomachtiging (eMandate)';
            }

            $this->bluem_config->merchantReturnURLBase = home_url('wc-api/bluem_mandates_callback');


            $this->enabled = $this->get_option('enabled');

            // This action hook saves the settings
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // ********** CREATING plugin URLs for specific functions **********
            add_action(
                'woocommerce_api_bluem_mandates_webhook',
                array($this, 'mandates_webhook'),
                5
            );
            add_action(
                'woocommerce_api_bluem_mandates_callback',
                array($this, 'mandates_callback')
            );

            // ********** Allow filtering Orders based on MandateID **********
            add_filter('woocommerce_order_data_store_cpt_get_orders_query', function ($query, $query_vars) {
                if (!empty($query_vars['bluem_mandateid'])) {
                    $query['meta_query'][] = array(
                        'key' => 'bluem_mandateid',
                        'value' => esc_attr($query_vars['bluem_mandateid']),
                    );
                }
                return $query;
            }, 10, 2);
        }

        /**
         * Generic thank you page that redirects to the specific order page.
         *
         * @param [type] $order_id
         * @return void
         */
        public function bluem_thankyou($order_id)
        {
            $order = wc_get_order($order_id);
            $url = $order->get_view_order_url();

            if (!$order->has_status('failed')) {
                wp_safe_redirect($url);
                exit;
            }

            // @todo: add alternative route?
        }

        /**
         * Create plugin options page in admin interface
         */
        public function init_form_fields()
        {
            $this->form_fields = apply_filters('wc_offline_form_fields', [
                'enabled' => [
                    'title'       => 'Enable/Disable',
                    'label'       => 'Activeer de Bluem eMandate Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ],
                'title' => [
                    'title'       => 'Titel van betaalmethode',
                    'type'        => 'text',
                    'description' => 'Dit bepaalt de titel die de gebruiker ziet tijdens het afrekenen.',
                    'default'     => 'Incasso machtiging voor zakelijke Rabobank, ING of ABN AMRO rekeningen',
                ],
                'description' => [
                    'title'       => 'Description',
                    'type'        => 'textarea',
                    'description' => 'Dit bepaalt de beschrijving die de gebruiker ziet tijdens het afrekenen.					',
                    'default'     => 'Geef een B2B eMandate af voor een incasso voor je bestelling.',
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
            // @todo: LOG TRANSactions

            $verbose = false;
            $this->bluem = new Integration($this->bluem_config);
            $order = wc_get_order($order_id);

            $user_id = $order->get_user_id();
            $user_meta = get_user_meta($user_id);

            $settings = get_option('bluem_woocommerce_options');
            $maxAmountEnabled = (isset($settings['maxAmountEnabled']) ? ($settings['maxAmountEnabled'] == "1") : false);
            if ($maxAmountEnabled) {
                $maxAmountFactor = (isset($settings['maxAmountFactor']) ? (float)($settings['maxAmountFactor']) : false);
            } else {
                $maxAmountFactor = 1.0;
            }

            $ready = true;
            $bluem_latest_mandate_id = null;
            if (isset($user_meta['bluem_latest_mandate_id']) && $user_meta['bluem_latest_mandate_id'] !== "") {
                $bluem_latest_mandate_id = $user_meta['bluem_latest_mandate_id'][0];
            } else {
                $ready = false;
            }
            $bluem_latest_mandate_amount = null;
            if (isset($user_meta['bluem_latest_mandate_amount']) && $user_meta['bluem_latest_mandate_amount'] !== "") {
                $bluem_latest_mandate_amount = $user_meta['bluem_latest_mandate_amount'][0];
            } else {
                $ready = false;
            }
            $bluem_latest_mandate_entrance_code = null;
            if (isset($user_meta['bluem_latest_mandate_entrance_code']) && $user_meta['bluem_latest_mandate_entrance_code'] !== "") {
                $bluem_latest_mandate_entrance_code = $user_meta['bluem_latest_mandate_entrance_code'][0];
            } else {
                $ready = false;
            }

            if (
                $ready &&
                !is_null($bluem_latest_mandate_id) && !is_null($bluem_latest_mandate_amount) &&
                ($maxAmountEnabled == false || ($maxAmountEnabled &&
                    ($bluem_latest_mandate_amount == 0 ||
                        ($bluem_latest_mandate_amount > 0 && $bluem_latest_mandate_amount  >= (float)$order->get_total() * $maxAmountFactor))))
            ) {
                $existing_mandate_response = $this->bluem->MandateStatus(
                    $bluem_latest_mandate_id,
                    $bluem_latest_mandate_entrance_code
                );

                if ($existing_mandate_response->Status() == false) {
                    // $this->renderPrompt("Fout: geen valide bestaand mandaat gevonden");
                    // exit;
                } else {
                    if ($existing_mandate_response->EMandateStatusUpdate->EMandateStatus->Status . "" === "Success") {
                        if ($this->validateMandate(
                            $existing_mandate_response,
                            $order,
                            false,
                            false,
                            false
                        )) {

                            // successfully used previous mandate in current order, lets annotate that order with the corresponding metadata
                            update_post_meta($order_id, 'bluem_entrancecode', $bluem_latest_mandate_entrance_code);
                            update_post_meta($order_id, 'bluem_mandateid', $bluem_latest_mandate_id);

                            return array(
                                'result' => 'success',
                                'redirect' => $order->get_view_order_url()
                            );
                        } else {
                            // echo "mandaat gevonden maar niet valide";
                        }
                    }
                }
            }

            $order_id = $order->get_order_number();
            $customer_id = get_post_meta($order_id, '_customer_user', true);

            $mandate_id = $this->bluem->CreateMandateId($order_id, $customer_id);

            $request = $this->bluem->CreateMandateRequest(
                $customer_id,
                $order_id,
                $mandate_id
            );


            $payload = json_encode(
                [
                    'environment'=>$this->bluem->environment,
                    'amount'=>$order->get_total(),
                    'created_mandate_id'=>$mandate_id
                ]
            );

            // allow third parties to add additional data to the request object through this additional action
            $request = apply_filters(
                'bluem_woocommerce_enhance_mandate_request',
                $request
            );

            $response = $this->bluem->PerformRequest($request);

            if ($verbose) {
                var_dump($order_id);
                var_dump($customer_id);
                var_dump($mandate_id);
                var_dump($response);
                die();
            }

            if (is_a($response, "Bluem\BluemPHP\ErrorBluemResponse", false)) {
                // var_dump($mandate_id);
                throw new Exception("An error occured in the payment method. Please contact the webshop owner with this message:  " . $response->error());
            }



            $attrs = $response->EMandateTransactionResponse->attributes();

            // var_dump($attrs['entranceCode'] . "");
            if (!isset($attrs['entranceCode'])) {
                throw new Exception("An error occured in reading the transaction response. Please contact the webshop owner");
            }
            $entranceCode = $attrs['entranceCode'] . "";


            update_post_meta($order_id, 'bluem_entrancecode', $entranceCode);
            update_post_meta($order_id, 'bluem_mandateid', $mandate_id);

            // https://docs.woocommerce.com/document/managing-orders/
            // Possible statuses: 'pending', 'processing', 'on-hold', 'completed', 'refunded, 'failed', 'cancelled',

            // Remove cart
            global $woocommerce;
            $woocommerce->cart->empty_cart();
            $order->update_status('pending', __('Awaiting Bluem Mandate Signature', 'wc-gateway-bluem'));

            if (isset($response->EMandateTransactionResponse->TransactionURL)) {

                // redirect cast to string, for AJAX response handling
                $transactionURL = ($response->EMandateTransactionResponse->TransactionURL . "");


                bluem_db_create_request(
                    [
                        'entrance_code'=>$entranceCode,
                        'transaction_id'=>$mandate_id,
                        'transaction_url'=>$transactionURL,
                        'user_id'=>get_current_user_id(),
                        'timestamp'=> date("Y-m-d H:i:s"),
                        'description'=>"Mandate request {$order_id} {$customer_id}",
                        'debtor_reference'=>"",
                        'type'=>"mandates",
                        'order_id'=>$order_id,
                        'payload'=>$payload
                    ]
                );
                return array(
                    'result' => 'success',
                    'redirect' => $transactionURL
                );
            }

            return array(
                'result' => 'failure'
            );
        }

        /**
         * mandates_Webhook action
         *
         * @return void
         */
        public function mandates_webhook()
        {
            exit;
            // todo: update this

            $statusUpdateObject = $this->bluem->Webhook();

            $entranceCode = $statusUpdateObject->entranceCode . "";
            $mandateID = $statusUpdateObject->EMandateStatus->MandateID . "";

            $webhook_status = $statusUpdateObject->EMandateStatus->Status . "";

            $order = $this->getOrder($mandateID);
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

            if (isset($statusUpdateObject->EMandateStatus->AcceptanceReport->MaxAmount)) {
                $mandate_amount = (float) ($statusUpdateObject->EMandateStatus->AcceptanceReport->MaxAmount . "");
            } else {
                $mandate_amount = (float) 0.0;	// mandate amount is not set, so it is unlimited
            }
            if (self::VERBOSE) {
                var_dump($mandate_amount);
                echo PHP_EOL;
            }

            $settings = get_option('bluem_woocommerce_options');
            $maxAmountEnabled = (isset($settings['maxAmountEnabled']) ? ($settings['maxAmountEnabled'] == "1") : false);
            if ($maxAmountEnabled) {
                $maxAmountFactor = (isset($settings['maxAmountFactor']) ? (float)($settings['maxAmountFactor']) : false);
            } else {
                $maxAmountFactor = 1.0;
            }

            if (self::VERBOSE) {
                echo "mandate_amount: {$mandate_amount}" . PHP_EOL;
            }


            if ($maxAmountEnabled) {
                $mandate_successful = false;

                if ($mandate_amount !== 0.0) {
                    $order_price = $order->get_total();
                    $max_order_amount = (float) ($order_price * $maxAmountFactor);
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
            } else {
                $mandate_successful = true;
            }

            if ($webhook_status === "Success") {
                if ($order_status === "processing") {
                    // order is already marked as processing, nothing more is necessary
                } elseif ($order_status === "pending") {
                    // check if maximum of order does not exceed mandate size based on user metadata
                    if ($mandate_successful) {
                        $order->update_status(
                            'processing',
                            __(
                                "Machtiging (Mandaat ID $mandateID) is gelukt
                                 en goedgekeurd; via webhook",
                                'wc-gateway-bluem'
                            )
                        );
                    }
                    // iff order is within size, update to processing
                }
            } elseif ($webhook_status === "Cancelled") {
                $order->update_status('cancelled', __('Machtiging is geannuleerd; via webhook', 'wc-gateway-bluem'));
            } elseif ($webhook_status === "Open" || $webhook_status == "Pending") {
                // if the webhook is still open or pending, nothing has to be done as of yet
            } elseif ($webhook_status === "Expired") {
                $order->update_status('failed', __('Machtiging is verlopen; via webhook', 'wc-gateway-bluem'));
            } else {
                $order->update_status('failed', __('Machtiging is gefaald: fout of onbekende status; via webhook', 'wc-gateway-bluem'));
            }
            exit;
        }

        /**
         * Retrieve an order based on its mandate_id in metadata from the WooCommerce store
         *
         * @param String $mandateID
         *
         */
        private function getOrder(String $mandateID)
        {
            $orders = wc_get_orders(array(
                'orderby'   => 'date',
                'order'     => 'DESC',
                'bluem_mandateid' => $mandateID
            ));
            if (count($orders) == 0) {
                return null;
            }
            return $orders[0];
        }


        /**
         * mandates_Callback function after Mandate process has been completed by the user
         * @return function [description]
         */
        public function mandates_callback()
        {
            $this->bluem = new Integration($this->bluem_config);

            if (!isset($_GET['mandateID'])) {
                $this->renderPrompt("Fout: geen juist mandaat id teruggekregen bij mandates_callback. Neem contact op met de webshop en vermeld je contactgegevens.");
                exit;
            }
            $mandateID = $_GET['mandateID'];


            $order = $this->getOrder($mandateID);
            if (is_null($order)) {
                $this->renderPrompt("Fout: mandaat niet gevonden in webshop. Neem contact op met de webshop en vermeld de code {$mandateID} bij je gegevens.");
                exit;
            }

            $entranceCode = $order->get_meta('bluem_entrancecode');

            $response = $this->bluem->MandateStatus($mandateID, $entranceCode);


            if (!$response->Status()) {
                $this->renderPrompt("Fout bij opvragen status: " . $response->Error() . "<br>Neem contact op met de webshop en vermeld deze status");
                exit;
            }

            if (self::VERBOSE) {
                var_dump("mandateid: " . $mandateID);
                var_dump("entrancecode: " . $entranceCode);
                echo "<hr>";
                var_dump($response);
                echo "<hr>";
            }

            $statusUpdateObject = $response->EMandateStatusUpdate;
            $statusCode = $statusUpdateObject->EMandateStatus->Status . "";

            $statusCode ="Pending";


            
            $request_from_db = bluem_db_get_request_by_transaction_id($mandateID);

            if ($statusCode !== $request_from_db->status) {

                bluem_db_update_request(
                    $request_from_db->id,
                    [
                        'status'=>$statusCode
                    ]
                );
            }


            if ($statusCode === "Success") {
                $this->validateMandate($response, $order, true, true, true, $mandateID, $entranceCode);
            } elseif ($statusCode ==="Pending") {
                $this->renderPrompt(
                    "<p>Uw machtiging wacht op goedkeuring van
                    een andere ondertekenaar namens uw organisatie.<br>
                    Deze persoon dient in te loggen op internet bankieren
                    en deze machtiging mede goed te keuren.
                    Hierna is de machtiging goedgekeurd en zal dit automatisch
                    reflecteren op deze site.</p>"
                );
                exit;
            } elseif ($statusCode === "Cancelled") {
                $order->update_status(
                    'cancelled',
                    __('Machtiging is geannuleerd', 'wc-gateway-bluem')
                );

                $this->renderPrompt("Je hebt de mandaat ondertekening geannuleerd");
                // terug naar order pagina om het opnieuw te proberen?
                exit;
            } elseif ($statusCode === "Open" || $statusCode == "Pending") {
                $this->renderPrompt("De mandaat ondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch.");
                // callback pagina beschikbaar houden om het opnieuw te proberen?
                // is simpelweg SITE/wc-api/bluem_callback?mandateID=$mandateID
                exit;
            } elseif ($statusCode === "Expired") {
                $order->update_status(
                    'failed',
                    __(
                        'Machtiging is verlopen',
                        'wc-gateway-bluem'
                    )
                );

                $this->renderPrompt(
                    "Fout: De mandaat of het verzoek daartoe is verlopen"
                );
                exit;
            } else {
                $order->update_status(
                    'failed',
                    __(
                        'Machtiging is gefaald: fout of onbekende status',
                        'wc-gateway-bluem'
                    )
                );
                //$statusCode == "Failure"
                $this->renderPrompt(
                    "Fout: Onbekende of foutieve status teruggekregen: {$statusCode}
                    <br>Neem contact op met de webshop en vermeld deze status"
                );
                exit;
            }
        }

        /**
         * Validating a given mandate based on MaxAmount given in $response, compared to $order total pricing and some additional parameters
         *
         * @param [type] $response
         * @param [type] $order
         * @param boolean $block_processing
         * @param boolean $update_metadata
         * @param [type] $mandate_id
         * @param [type] $entrance_code
         * @return void
         */
        private function validateMandate($response, $order, $block_processing = false, $update_metadata = true, $redirect = true, $mandate_id = null, $entrance_code = null)
        {
            $maxAmountResponse = $this->bluem->GetMaximumAmountFromTransactionResponse($response);
            $user_id = $order->get_user_id();


            $settings = get_option('bluem_woocommerce_options');
            $maxAmountEnabled = (isset($settings['maxAmountEnabled']) ? ($settings['maxAmountEnabled'] == "1") : false);
            if ($maxAmountEnabled) {
                $maxAmountFactor = (isset($settings['maxAmountFactor']) ? (float)($settings['maxAmountFactor']) : false);
            } else {
                $maxAmountFactor = 1.0;
            }

            $successful_mandate = false;

            if ($update_metadata) {
                if ($mandate_id !=="") {
                    if (self::VERBOSE) {
                        echo "<br>updating user meta: bluem_latest_mandate_id to value {$mandate_id} - result: ";
                    }
                    update_user_meta(
                        $user_id,
                        'bluem_latest_mandate_id',
                        $mandate_id
                    );
                }
                if ($entrance_code !=="") {
                    if (self::VERBOSE) {
                        echo "<br>updating user meta: entranceCode to value {$entrance_code} - result: ";
                    }
                    update_user_meta(
                        $user_id,
                        'bluem_latest_mandate_entrance_code',
                        $entrance_code
                    );
                }
            }

            if ($maxAmountEnabled) {

                // NextDeli specific: estimate 10% markup on order total:
                $order_total_plus = (float) $order->get_total() * $maxAmountFactor;

                if (self::VERBOSE) {
                    if ($maxAmountResponse === 0.0) {
                        echo "No max amount set";
                    } else {
                        echo "MAX AMOUNT SET AT {$maxAmountResponse->amount} {$maxAmountResponse->currency}";
                    }
                    echo "<hr>";
                    echo "Totaalbedrag: ";
                    var_dump((float) $order->get_total());
                    echo " | totaalbedrag +10 procent: ";
                    var_dump($order_total_plus);
                    echo "<hr>";
                }

                if (isset($maxAmountResponse->amount) && $maxAmountResponse->amount !== 0.0) {
                    if ($update_metadata) {
                        if (self::VERBOSE) {
                            echo "<br>updating user meta: bluem_latest_mandate_amount to value {$maxAmountResponse->amount} - result: ";
                        }
                        update_user_meta(
                            $user_id,
                            'bluem_latest_mandate_amount',
                            $maxAmountResponse->amount
                        );
                    }
                    $allowed_margin = ($order_total_plus <= $maxAmountResponse->amount);
                    if (self::VERBOSE) {
                        echo "binnen machtiging marge?";
                        var_dump($allowed_margin);
                    }

                    if ($allowed_margin) {
                        $successful_mandate = true;
                    } else {
                        if ($block_processing) {
                            $order->update_status('pending', __('Machtiging moet opnieuw ondertekend worden, want mandaat bedrag is te laag', 'wc-gateway-bluem'));

                            $url = $order->get_checkout_payment_url();
                            $order_total_plus_string = str_replace(".", ",", ("" . round($order_total_plus, 2)));
                            $this->renderPrompt(
                                "<p>Het automatische incasso mandaat dat je hebt afgegeven is niet toereikend voor de incassering van het factuurbedrag van jouw bestelling.</p>
                            <p>De geschatte factuurwaarde van jouw bestelling is EUR {$order_total_plus_string}. Het mandaat voor de automatische incasso die je hebt ingesteld is EUR {$maxAmountResponse->amount}. Ons advies is om jouw mandaat voor automatische incasso te verhogen of voor 'onbeperkt' te kiezen.</p>" .
                                    "<p><a href='{$url}' target='_self'>Klik hier om terug te gaan naar de betalingspagina en een nieuw mandaat af te geven</a></p>",
                                false
                            );

                            exit;
                        }
                    }
                } else {
                    if ($update_metadata) {
                        if (self::VERBOSE) {
                            echo "<br>updating user meta: bluem_latest_mandate_amount to value 0 - result: ";
                        }
                        update_user_meta($user_id, 'bluem_latest_mandate_amount', 0);
                    }
                    $successful_mandate = true;
                }
            } else {
                // no maxamount check, so just continue;
                $successful_mandate = true;
            }

            if ($update_metadata) {
                if (self::VERBOSE) {
                    echo "<br>updating user meta: bluem_latest_mandate_validated to value {$successful_mandate} - result: ";
                }
                update_user_meta(
                    $user_id,
                    'bluem_latest_mandate_validated',
                    $successful_mandate
                );
            }

            if ($successful_mandate) {
                if (self::VERBOSE) {
                    echo "mandaat is succesvol, order kan worden aangepast naar machtiging_goedgekeurd";
                }
                $order->update_status(
                    'processing',
                    __(
                        "Machtiging (mandaat ID $mandateID)
                        is gelukt en goedgekeurd",
                        'wc-gateway-bluem'
                    )
                );


                do_action(
                    'bluem_woocommerce_valid_mandate_callback',
                    $user_id,
                    $response
                );

                if ($redirect) {
                    if (self::VERBOSE) {
                        die();
                    }
                    $this->bluem_thankyou($order->get_id());
                } else {
                    return true;
                }
            }
        }
    }


    add_action('bluem_woocommerce_valid_mandate_callback', 'bluem_woocommerce_valid_mandate_callback_function', 10, 2);

    function bluem_woocommerce_valid_mandate_callback_function($user_id, $response)
    {
        // do something with the response, use this in third-party extensions of this system
    }


    add_action('show_user_profile', 'bluem_woocommerce_mandates_show_extra_profile_fields');
    add_action('edit_user_profile', 'bluem_woocommerce_mandates_show_extra_profile_fields');

    function bluem_woocommerce_mandates_show_extra_profile_fields($user)
    {
        ?>
        <?php //var_dump($user->ID);
        ?>
        <h2>
            Bluem eMandate Metadata
        </h2>
        <table class="form-table">
            <tr>
                <th><label for="bluem_latest_mandate_id">Meest recente MandateID</label></th>
                <td>
                    <input type="text" name="bluem_latest_mandate_id" id="bluem_latest_mandate_id"
                        value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_latest_mandate_id', true)); ?>"
                        class="regular-text" /><br />
                    <span class="description">Hier wordt het meest recente mandate ID geplaatst; en gebruikt bij het doen van een volgende checkout.</span>
                </td>
            </tr>
            <tr>
                <th><label for="bluem_latest_mandate_entrance_code">Meest recente EntranceCode</label></th>

                <td>
                    <input type="text" name="bluem_latest_mandate_entrance_code" id="bluem_latest_mandate_entrance_code"
                        value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_latest_mandate_entrance_code', true)); ?>"
                        class="regular-text" /><br />
                    <span class="description">Hier wordt het meest recente entrance_code geplaatst; en gebruikt bij het doen van een volgende checkout.</span>
                </td>
            </tr>
            <tr>
                <th><label for="bluem_latest_mandate_amount">Omvang laatste machtiging</label></th>
                <td>
                    <input type="text" name="bluem_latest_mandate_amount" id="bluem_latest_mandate_amount"
                        value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_latest_mandate_amount', true)); ?>"
                        class="regular-text" /><br />
                    <span class="description">Dit is de omvang van de laatste machtiging</span>
                </td>
            </tr>
            <tr>
                <th><label for="bluem_mandates_validated">machtiging via shortcode valide?</label></th>
                <td>
                <?php
                $curValidatedVal = (int) esc_attr(get_user_meta($user->ID, 'bluem_mandates_validated', true));
        // var_dump($curValidatedVal);?>
                    <select name="bluem_mandates_validated" id="bluem_mandates_validated">
                        <option value="1" <?php if ($curValidatedVal == 1) {
            echo "selected";
        } ?>>
                            Ja
                        </option>
                        <option value="0" <?php if ($curValidatedVal == 0) {
            echo "selected";
        } ?>>
                            Nee
                        </option>
                    </select><br />
                    <span class="description">Is een machtiging via shortcode doorgekomen?</span>
                </td>
            </tr>
        </table>

<?php
    }


    add_action(
        'personal_options_update',
        'bluem_woocommerce_mandates_save_extra_profile_fields'
    );
    add_action(
        'edit_user_profile_update',
        'bluem_woocommerce_mandates_save_extra_profile_fields'
    );

    function bluem_woocommerce_mandates_save_extra_profile_fields($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        update_user_meta(
            $user_id,
            'bluem_latest_mandate_id',
            esc_attr(sanitize_text_field($_POST['bluem_latest_mandate_id']))
        );
        update_user_meta(
            $user_id,
            'bluem_latest_mandate_entrance_code',
            esc_attr(sanitize_text_field($_POST['bluem_latest_mandate_entrance_code']))
        );
        update_user_meta(
            $user_id,
            'bluem_latest_mandate_amount',
            esc_attr(sanitize_text_field($_POST['bluem_latest_mandate_amount']))
        );
    }
}

function bluem_woocommerce_mandates_settings_section()
{
    $mandate_id_counter = get_option('bluem_woocommerce_mandate_id_counter');

    // The below code is useful when you want the mandate_id to start counting at a fixed minimum.
    // This is what had to be implemented for H2OPro; one of the first clients.
    // @todo: convert to action so it can be overriden by third-party developers such as H2OPro.
    if (home_url() == "https://www.h2opro.nl" && (int) ($mandate_id_counter . "") < 111100) {
        $mandate_id_counter += 111000;
        update_option('bluem_woocommerce_mandate_id_counter', $mandate_id_counter);
    }

    echo '<p><a id="tab_mandates"></a> Hier kan je alle belangrijke gegevens instellen rondom Machtigingen.</p>';
//  Lees de readme bij de plug-in voor meer informatie.
    // echo "<p>Huidige mandaat ID counter: ";
    // echo $mandate_id_counter;
    // echo "</p>";
    // echo "<p>Huidige Carbon tijd: " . Carbon::now()->timezone('Europe/Amsterdam')->toDateTimeString() . "</p>";
}

// ********************** Mandate specific

function bluem_woocommerce_settings_render_brandID()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('brandID'));
}
function bluem_woocommerce_settings_render_merchantID()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('merchantID'));
}
function bluem_woocommerce_settings_render_merchantSubId()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('merchantSubId'));
}
function bluem_woocommerce_settings_render_thanksPage()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('thanksPage'));
}
function bluem_woocommerce_settings_render_eMandateReason()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('eMandateReason'));
}
function bluem_woocommerce_settings_render_localInstrumentCode()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('localInstrumentCode'));
}
function bluem_woocommerce_settings_render_requestType()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('requestType'));
}
function bluem_woocommerce_settings_render_sequenceType()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('sequenceType'));
}

function bluem_woocommerce_settings_render_successMessage()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('successMessage'));
}

function bluem_woocommerce_settings_render_errorMessage()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('errorMessage'));
}

function bluem_woocommerce_settings_render_purchaseIDPrefix()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('purchaseIDPrefix'));
}

function bluem_woocommerce_settings_render_debtorReferenceFieldName()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('debtorReferenceFieldName'));
}

function bluem_woocommerce_settings_render_thanksPageURL()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('thanksPageURL'));
}

function bluem_woocommerce_settings_render_mandate_id_counter()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('mandate_id_counter'));
}
function bluem_woocommerce_settings_render_maxAmountEnabled()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('maxAmountEnabled'));
}
function bluem_woocommerce_settings_render_maxAmountFactor()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('maxAmountFactor'));
}

function bluem_woocommerce_settings_render_useMandatesDebtorWallet()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('useMandatesDebtorWallet'));
}


$bluem_options = get_option('bluem_woocommerce_options');

if (isset($bluem_options['useMandatesDebtorWallet']) && $bluem_options['useMandatesDebtorWallet']=="1") {

    /**
     * Add add a notice before the payment form - let's use an eror notice. Could also use content, etc.
     *
     * Reference: https://github.com/woothemes/woocommerce/blob/master/templates/checkout/review-order.php
     */
    add_action(
        'woocommerce_review_order_before_payment',
        'bluem_woocommerce_show_checkout_bic_selection'
    );
    function bluem_woocommerce_show_checkout_bic_selection()
    {
        // ref: https://stackoverflow.com/questions/40480587/woocommerce-checkout-custom-select-field/40480684
        $nonce = wp_create_nonce("bluem_ajax_nonce");
        echo "<input type='hidden' id='bluem_ajax_nonce' value='{$nonce}'/>";
        // echo "HIER KOMT DE BANKKEUZE";
        ?>
        <div id="BICselector">
            <label for="bluem_BICInput" style="display: block;">
                Selecteer uw bank:
            <abbr class="required" title="required">*</abbr>
            </label>
            <select name="bluem_BICInput"
            id="BICInput"
            style="display: block; padding:3pt; width:100%;" required>
            </select>
        </div><?php
        // $fields = [];
        //     $fields['order']['bluem_bic'] = array(
        //         'type' => 'select',
        //         'class' => array('form-row-wide'),
        //         'label' => __('Selecteer uw bank'),
        //         'required'=>true,
        //         'options'=>$opts,
        //     );
        // 'placeholder' => _x('FILL IN BICCIE.', 'placeholder', 'woocommerce')
        // return $fields;
    }





    add_action(
        'woocommerce_after_checkout_validation',
        'bluem_woocommerce_validate_checkout_bic_choice',
        10,
        2
    );

    function bluem_woocommerce_validate_checkout_bic_choice($fields, $errors)
    {

        // if ( preg_match( '/\\d/', $fields[ 'billing_first_name' ] ) || preg_match( '/\\d/', $fields[ 'billing_last_name' ] )  ){
        //     $errors->add( 'validation', 'Your first or last name contains a number. Really?' );
        // }
    }


    // show new checkout field
    // Hook in
    // add_filter( 'woocommerce_checkout_fields' , 'bluem_woocommerce_show_checkout_bic_selection' );
    // Our hooked in function - $fields is passed via the filter!
    // function bluem_woocommerce_show_checkout_bic_selection( $fields ) {


    // Fires after WordPress has finished loading, but before any headers are sent.
    add_action('init', 'script_enqueuer');

    function script_enqueuer()
    {

        // Register the JS file with a unique handle, file location, and an array of dependencies
        wp_register_script("bluem_woocommerce_bic_retriever", plugin_dir_url(__FILE__).'js/bluem_woocommerce_bic_retriever.js', array('jquery'));

        // localize the script to your domain name, so that you can reference the url to admin-ajax.php file easily
        wp_localize_script('bluem_woocommerce_bic_retriever', 'myAjax', array( 'ajaxurl' => admin_url('admin-ajax.php')));

        // enqueue jQuery library and the script you registered above
        wp_enqueue_script('jquery');
        wp_enqueue_script('bluem_woocommerce_bic_retriever');
    }


    /**
     * @snippet       Display script @ Checkout - WooCommerce
     * @how-to        Get CustomizeWoo.com FREE
     * @sourcecode    https://businessbloomer.com/?p=532
    */
    // add_action( 'woocommerce_after_checkout_form', 'bluem_woocommerce_payment_changer_event_handler');

    // function bluem_woocommerce_payment_changer_event_handler() {
    // }

    // https://premium.wpmudev.org/blog/using-ajax-with-wordpress/

    // define the actions for the two hooks created, first for logged in users and the next for logged out users
    add_action("wp_ajax_bluem_retrieve_bics_ajax", "bluem_retrieve_bics_ajax");
    // add_action("wp_ajax_nopriv_bluem_retrieve_bics_ajax", "please_login");

    // define the function to be fired for logged in users
    function bluem_retrieve_bics_ajax()
    {

        // nonce check for an extra layer of security, the function will exit if it fails
        //    if ( !wp_verify_nonce( $_REQUEST['nonce'], "bluem_retrieve_bics_ajax_nonce")) {
        //       exit("Woof Woof Woof");
        //    }

        // switch()

        $bluem_config = bluem_woocommerce_get_config();
        $bluem = new Integration($bluem_config);
        $BICs = $bluem->retrieveBICsForContext("Mandates");


        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode($BICs);
        } else {
            header("Location: ".$_SERVER["HTTP_REFERER"]);
        }
        die();
    }

    // define the function to be fired for logged out users
    function please_login()
    {
        echo "You must log in to like";
        die();
    }
}


// $key = $field['key'];




/*  To be added to PROCESS PAYMENT:

// $bluem_options = get_option('bluem_woocommerce_options');
// if(isset($bluem_options['useMandatesDebtorWallet']) && $bluem_options['useMandatesDebtorWallet']=="1") {
// $selected_bic = $checkout->get_value( 'bluem_bic' ));
// // validate bic
// // add to response


 */




add_filter('bluem_woocommerce_enhance_mandate_request', 'bluem_woocommerce_enhance_mandate_request_function', 10, 1);

/**
 * allow third parties to add additional data to the request object through this additional action
 *
 * @param [type] $request
 * @return void
 */
function bluem_woocommerce_enhance_mandate_request_function($request)
{
    // do something with the Bluem Mandate request, use this in third-party extensions of this system
    return $request;
}
