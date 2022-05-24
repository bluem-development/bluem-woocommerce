<?php
// @todo: add Woo Product update key if necessary, check https://docs.woocommerce.com/document/create-a-plugin/
// @todo: Localize all error messages to english primarily
// @todo: finish docblocking

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Bluem\BluemPHP\Bluem;

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'bluem_add_gateway_class_mandates', 11 );
function bluem_add_gateway_class_mandates( $gateways ) {
    $gateways[] = 'Bluem_Gateway_Mandates'; // your class name is here

    return $gateways;
}

function bluem_woocommerce_get_mandates_option( $key ) {
    $options = bluem_woocommerce_get_mandates_options();
    if ( array_key_exists( $key, $options ) ) {
        return $options[ $key ];
    }

    return false;
}

function bluem_woocommerce_get_mandates_options() {
    return [
        'brandID'       => [
            'key'         => 'brandID',
            'title'       => 'bluem_brandID',
            'name'        => 'Bluem Brand ID',
            'description' => 'Wat is je Bluem eMandates BrandID? Je hebt deze ontvangen door Bluem.',
            'default'     => ''
        ],
        'merchantID'    => [
            'key'         => 'merchantID',
            'title'       => 'bluem_merchantID',
            'name'        => 'Incassant merchantID (benodigd voor machtigingen op Productie)',
            'description' => 'Het merchantID, te vinden op het contract dat je hebt met de bank voor ontvangen van incasso machtigingen. <strong>Dit is essentieel: zonder dit gegeven zal een klant geen machtiging kunnen afsluiten op productie</strong>.',
            'default'     => ''
        ],
        'merchantSubId' => [
            'key'         => 'merchantSubId',
            'title'       => 'bluem_merchantSubId',
            'name'        => 'Bluem Merchant Sub ID',
            'default'     => '0',
            'description' => 'Hier hoef je waarschijnlijk niks aan te veranderen.',
            'type'        => 'select',
            'options'     => [ '0' => '0' ]
        ],

        'thanksPage'          => [
            'key'     => 'thanksPage',
            'title'   => 'bluem_thanksPage',
            'name'    => 'Waar wordt de gebruiker uiteindelijk naar verwezen?',
            'type'    => 'select',
            'options' => [
                'order_page' => "Detailpagina van de zojuist geplaatste bestelling (standaard)"
            ],
        ],
        'eMandateReason'      => [
            'key'         => 'eMandateReason',
            'title'       => 'bluem_eMandateReason',
            'name'        => 'Reden voor Machtiging',
            'description' => 'Een bondige beschrijving van incasso weergegeven bij afgifte.',
            'default'     => 'Incasso machtiging'
        ],
        'localInstrumentCode' => [
            'key'         => 'localInstrumentCode',
            'title'       => 'bluem_localInstrumentCode',
            'name'        => 'Type incasso machtiging afgifte',
            'description' => 'Kies type incassomachtiging. Neem bij vragen hierover contact op met Bluem.',
            'type'        => 'select',
            'default'     => 'CORE',
            'options'     => [ 'CORE' => 'CORE machtiging', 'B2B' => 'B2B machtiging (zakelijk)' ]
        ],

        // RequestType = Issuing (altijd)
        'requestType'         => [
            'key'         => 'requestType',
            'title'       => 'bluem_requestType',
            'name'        => 'Bluem Request Type',
            'description' => '',
            'type'        => 'select',
            'default'     => 'Issuing',
            'options'     => [ 'Issuing' => 'Issuing (standaard)' ]
        ],

        'sequenceType'        => [
            'key'         => 'sequenceType',
            'title'       => 'bluem_sequenceType',
            'name'        => 'Type incasso sequentie',
            'description' => '',
            'type'        => 'select',
            'default'     => 'RCUR',
            'options'     => [ 'RCUR' => 'Doorlopende machtiging (recurring)', 'OOFF' => 'Eenmalige machtiging (one-time)' ]
        ],

        'successMessage' => [
            'key'         => 'successMessage',
            'title'       => 'bluem_successMessage',
            'name'        => 'Melding bij succesvolle machtiging via shortcode formulier',
            'description' => 'Een bondige beschrijving volstaat.',
            'default'     => 'Uw machtiging is succesvol ontvangen. Hartelijk dank.'
        ],
        'errorMessage'   => [
            'key'         => 'errorMessage',
            'title'       => 'bluem_errorMessage',
            'name'        => 'Melding bij gefaalde machtiging via shortcode formulier',
            'description' => 'Een bondige beschrijving volstaat.',
            'default'     => 'Er is een fout opgetreden. De incassomachtiging is geannuleerd.'
        ],

        'purchaseIDPrefix'         => [
            'key'         => 'purchaseIDPrefix',
            'title'       => 'bluem_purchaseIDPrefix',
            'name'        => 'Automatisch Voorvoegsel bij klantreferentie',
            'description' => "Welke korte tekst moet voor de debtorReference weergegeven worden bij een transactie in de Bluem incassomachtiging portaal. Dit kan handig zijn om Bluem transacties makkelijk te kunnen identificeren.",
            'type'        => 'text',
            'default'     => ''
        ],
        'debtorReferenceFieldName' => [
            'key'         => 'debtorReferenceFieldName',
            'title'       => 'bluem_debtorReferenceFieldName',
            'name'        => 'Label voor klantreferentie bij invulformulier shortcode',
            'description' => "Indien je de Machtigingen shortcode gebruikt: Welk label moet bij het invulveld in het formulier komen te staan? Dit kan bijvoorbeeld 'volledige naam' of 'klantnummer' zijn. <strong>Laat dit veld leeg om alleen een knop weer te geven</strong>.",
            'type'        => 'text',
            'default'     => ''
        ],
        'thanksPageURL'            => [
            'key'         => 'thanksPageURL',
            'title'       => 'bluem_thanksPageURL',
            'name'        => 'Slug van bedankpagina',
            'description' => "Indien je de Machtigingen shortcode gebruikt: Op welke pagina wordt de shortcode geplaatst? Dit is een slug, dus als je <code>thanks</code> invult, wordt de gehele URL: " . site_url( "thanks" ) . ".",
            'type'        => 'text',
            'default'     => ''
        ],
        'instantMandatesResponseURI'            => [
            'key'         => 'instantMandatesResponseURI',
            'title'       => 'bluem_instantMandatesResponseURI',
            'name'        => 'URI voor InstantMandates',
            'description' => "Indien je InstantMandates gebruikt: De <code>response</code> URI na een request. Dit kan een externe URL of een Deep Link zijn. We geven de querystrings <code>result</code> en indien van toepassing <code>reason</code> mee waarmee je de status kan opvangen.",
            'type'        => 'text',
            'default'     => ''
        ],
        'mandate_id_counter'       => [
            'key'         => 'mandate_id_counter',
            'title'       => 'bluem_mandate_id_counter',
            'name'        => 'Begingetal mandaat ID\'s',
            'description' => "Op welk getal wil je mandaat op dit moment nummeren? Dit getal wordt vervolgens automatisch opgehoogd.",
            'type'        => 'text',
            'default'     => '1'
        ],
        'maxAmountEnabled'         => [
            'key'         => 'maxAmountEnabled',
            'title'       => 'bluem_maxAmountEnabled',
            'name'        => 'Check op maximale bestelwaarde voor incassomachtigingen',
            'description' => "Wil je dat er bij zakelijke incassomachtigingen een check wordt uitgevoerd op de maximale waarde van de incasso, indien er een beperkte bedrag incasso machtiging is afgegeven? Zet dit gegeven dan op 'wel checken'. Er wordt dan een foutmelding gegeven als een klant een bestelling plaatst met een toegestaan bedrag dat lager is dan het orderbedrag (vermenigvuldigd met het volgende gegeven, de factor). Is de machtiging onbeperkt of anders groter dan het orderbedrag, dan wordt de machtiging geaccepteerd.",
            'type'        => 'select',
            'default'     => '1',
            'options'     => [ '1' => 'Wel checken op MaxAmount', '0' => 'Niet checken op MaxAmount' ],
        ],

        //Bij B2B krijgen wij terug of de gebruiker een maximaal mandaatbedrag heeft afgegeven.
        // Dit mandaat bedrag wordt vergeleken met de orderwaarde. De orderwaarde plus
        // onderstaand percentage moet lager zijn dan het maximale mandaatbedrag.
        // Geef hier het percentage aan.
        'maxAmountFactor'          => [
            'key'         => 'maxAmountFactor',
            'title'       => 'bluem_maxAmountFactor',
            'name'        => 'Welke factor van de bestelling mag het maximale bestelbedrag zijn?',
            'description' => "Als er een max amount wordt meegestuurd, wat is dan het maximale bedrag wat wordt toegestaan? Gebaseerd op de order grootte.",
            'type'        => 'number',
            'attrs'       => [ 'step' => '0.01', 'min' => '0.00', 'max' => '999.00', 'placeholder' => '1.00' ],
            'default'     => '1.00'
        ],
        'useMandatesDebtorWallet'  => [
            'key'         => 'useMandatesDebtorWallet',
            'title'       => 'bluem_useMandatesDebtorWallet',
            'name'        => 'Selecteer bank in Bluem Portal?',
            'description' => "Wil je dat er in deze website al een bank moet worden geselecteerd bij de Checkout procedure, in plaats van in de Bluem Portal? Indien je 'Gebruik eigen checkout' selecteert, wordt er een veld toegevoegd aan de WooCommerce checkout pagina waar je een van de beschikbare banken kan selecteren.",
            'type'        => 'select',
            'default'     => '0',
            'options'     => [ '0' => 'Gebruik Bluem Portal (standaard)', '1' => 'Gebruik eigen checkout' ],
        ],
    ];
}


/*
 * The gateway class itself, please note that it is inside plugins_loaded action hook
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    add_action( 'plugins_loaded', 'bluem_init_mandate_gateway_class' );
}

function bluem_init_mandate_gateway_class() {
    class Bluem_Gateway_Mandates extends WC_Payment_Gateway {
        /**
         * This boolean will cause more output to be generated for testing purposes. Keep it at false for the production environment or final testing
         */
        private const VERBOSE = false;

        /**
         * Class constructor
         */
        public function __construct() {
            $this->id                 = 'bluem_mandates'; // payment gateway plugin ID
            $this->icon               = ''; // URL of the icon that will be displayed on checkout page near your gateway name
            $this->has_fields         = true; // in case you need a custom credit card form
            $this->method_title       = 'Bluem Digitaal Incassomachtiging (eMandate)';
            $this->method_description = 'Bluem eMandate Payment Gateway voor WordPress - WooCommerce. Alle instellingen zijn in te stellen onder <a href="' . admin_url( 'options-general.php?page=bluem' ) . '">Instellingen &rarr; Bluem</a>';

            // gateways can support subscriptions, refunds, saved payment methods,
            // but in this version we support only payments
            $this->supports = array(
                'products'
            );

            // Load the settings.
            $this->init_settings();
            
            // Method with all the options fields
            $this->init_form_fields();

            $this->title = $this->get_option( 'title' );
            $this->description = $this->get_option( 'description' );

            // ********** CREATING Bluem Configuration **********
            $this->bluem_config = bluem_woocommerce_get_config();

            if ( isset( $this->bluem_config->localInstrumentCode ) && $this->bluem_config->localInstrumentCode == "B2B" ) {
                $this->method_title = 'Bluem Zakelijke Incassomachtiging (eMandate)';
            } else {
                $this->method_title = 'Bluem Particuliere Incassomachtiging (eMandate)';
            }

            $this->bluem_config->merchantReturnURLBase = home_url(
                'wc-api/bluem_mandates_callback'
            );

            $this->enabled = $this->get_option( 'enabled' );

            // This action hook saves the settings
            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                array( $this, 'process_admin_options' )
            );

            // ********** CREATING plugin URLs for specific functions **********
            add_action(
                'woocommerce_api_bluem_mandates_webhook',
                array( $this, 'mandates_webhook' ),
                5
            );
            add_action(
                'woocommerce_api_bluem_mandates_callback',
                array( $this, 'mandates_callback' )
            );

            // ********** Allow filtering Orders based on MandateID **********
            add_filter(
                'woocommerce_order_data_store_cpt_get_orders_query',
                function ( $query, $query_vars ) {
                    if ( ! empty( $query_vars['bluem_mandateid'] ) ) {
                        $query['meta_query'][] = array(
                            'key'   => 'bluem_mandateid',
                            'value' => esc_attr( $query_vars['bluem_mandateid'] ),
                        );
                    }

                    return $query;
                },
                10,
                2
            );
        }

        /**
         * Generic thank you page that redirects to the specific order page.
         *
         * @param [type] $order_id
         *
         * @return void
         */
        public function bluem_thankyou( $order_id ) {
            $order = wc_get_order( $order_id );
            
            $url = $order->get_checkout_order_received_url();

            if ( ! $order->has_status( 'failed' ) ) {
                wp_safe_redirect( $url );
                exit;
            }

            // @todo: add alternative route?
        }

        /**
         * Create plugin options page in admin interface
         *
         * @return void
         */
        public function init_form_fields() {
            $this->form_fields = apply_filters( 'wc_offline_form_fields', [
                'enabled'     => [
                    'title'       => 'Enable/Disable',
                    'label'       => 'Activeer de Bluem eMandate Gateway',
                    'type'        => 'checkbox',
                    'description' => '',
                    'default'     => 'no'
                ],
                'title'       => [
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
            ] );
        }

        /**
         * Check if a valid mandate already exists for this user
         *
         * @param  $order Order object
         */
        private function _checkExistingMandate( $order ) {
            global $current_user;
            
            $order_id = $order->get_id();

            $user_id = $current_user->ID;
            
            $retrieved_request_from_db = false;
            
            $reason = "";
            
            $ready = false;
            
            if (!empty($user_id))
            {
                $request = bluem_db_get_most_recent_request( $user_id, "mandates" );

                if ( $request !== false ) {
                    $bluem_latest_mandate_entrance_code = $request->entrance_code;
                    $bluem_latest_mandate_id = $request->transaction_id;
                    
                    $retrieved_request_from_db = true;
                    
                    $ready = true;
                } else {
                    // no latest request found, also trying in user metadata (legacy)
                    $user_meta = get_user_meta( $user_id );

                    $bluem_latest_mandate_id = null;
                    if ( !empty( $user_meta['bluem_latest_mandate_id'] ) ) {
                        $bluem_latest_mandate_id = $user_meta['bluem_latest_mandate_id'][0];
                        
                        $ready = true;
                    }

                    $bluem_latest_mandate_entrance_code = null;
                    if ( !empty( $user_meta['bluem_latest_mandate_entrance_code'] ) ) {
                        $bluem_latest_mandate_entrance_code = $user_meta['bluem_latest_mandate_entrance_code'][0];
                        
                        $ready = true;
                    }
                }
            }

            if ( $ready
                 && ! is_null( $bluem_latest_mandate_id )
                 && $bluem_latest_mandate_id !== ""
                 && ! is_null( $bluem_latest_mandate_entrance_code )
                 && $bluem_latest_mandate_entrance_code !== ""
            ) {
                $existing_mandate_response = $this->bluem->MandateStatus(
                    $bluem_latest_mandate_id,
                    $bluem_latest_mandate_entrance_code
                );

                if ( $existing_mandate_response->Status() == false ) {
                    $reason = "No / invalid bluem response for existing mandate";
                    // existing mandate response is not at all valid, 
                    // continue with actual mandate process
                } else {
                    if ( $existing_mandate_response->EMandateStatusUpdate->EMandateStatus->Status . "" === "Success"
                    ) {
                        if ( $this->validateMandate(
                            $existing_mandate_response,
                            $order,
                            false,
                            false,
                            false
                        )
                        ) {
                            // successfully used previous mandate in current order,
                            // lets annotate that order with the corresponding metadata
                            update_post_meta(
                                $order_id,
                                'bluem_entrancecode',
                                $bluem_latest_mandate_entrance_code
                            );
                            update_post_meta(
                                $order_id,
                                'bluem_mandateid',
                                $bluem_latest_mandate_id
                            );

                            if ( $retrieved_request_from_db ) {
                                bluem_db_request_log(
                                    $request->id,
                                    "Utilized this request for a
                                    payment for another order {$order_id}"
                                );

                                bluem_db_create_link(
                                    $request->id,
                                    $order_id,
                                    "order"
                                );

                                $cur_payload = json_decode( $request->payload );
                                if ( ! isset( $cur_payload->linked_orders ) ) {
                                    $cur_payload->linked_orders = [];
                                }
                                $cur_payload->linked_orders[] = $order_id;

                                bluem_db_update_request(
                                    $request->id,
                                    [
                                        'payload' => json_encode( $cur_payload )
                                    ]
                                );
                            }

                            return array(
                                'result'   => 'success',
                                'redirect' => $order->get_checkout_order_received_url()
                            );
                        } else {
                            $reason = "Existing mandate found, but not valid";
                        }
                    } else {
                        $reason = "Existing mandate is not a successful mandate";
                    }
                }
            } else {
                $reason = "Not ready, no metadata";
            }

            return array(
                'result'  => 'fail',
                'message' => "{$reason}"
            );
        }

        /**
         * Process payment through Bluem portal
         *
         * @param String $order_id
         *
         * @return void
         */
        public function process_payment( $order_id ) {
            global $current_user;

            $verbose = false;

            $this->bluem = new Bluem( $this->bluem_config );
            $order = wc_get_order( $order_id );

            // $user_id = $order->get_user_id();
            // $user_id = get_post_meta($order_id, '_customer_user', true);
            // improved retrieval of user id:
            $user_id = $current_user->ID;

            $settings = get_option( 'bluem_woocommerce_options' );

            $check = $this->_checkExistingMandate( $order );

            if ( isset( $check['result'] ) && $check['result'] === "success" ) {
                return array(
                    'result'   => 'success',
                    'redirect' => $order->get_checkout_order_received_url()
                );
                // @todo Possibly allow different redirect after fast checkout with existing, valid, mandate.
            }

            $order_id = $order->get_order_number();
            // update: added prefixed order ID for retries of mandate requests
            $prefixed_order_id = date( "His" ) . $order_id;
            $mandate_id        = $this->bluem->CreateMandateId(
                $prefixed_order_id,
                $user_id
            );

            $request = $this->bluem->CreateMandateRequest(
                $user_id,
                $order_id,
                $mandate_id
            );


            // allow third parties to add additional data to the request object through this additional action
            $request = apply_filters(
                'bluem_woocommerce_enhance_mandate_request',
                $request
            );

            $response = $this->bluem->PerformRequest( $request );
            if ( self::VERBOSE ) {
                var_dump( $order_id );
                var_dump( $user_id );
                var_dump( $mandate_id );
                var_dump( $response );
                die();
            }

            if ( is_a( $response, "Bluem\BluemPHP\Responses\ErrorBluemResponse", false ) ) {
                throw new Exception( "An error occured in the payment method. Please contact the webshop owner with this message:  " . $response->error() );
            }

            $attrs = $response->EMandateTransactionResponse->attributes();

            if ( ! isset( $attrs['entranceCode'] ) ) {
                throw new Exception( "An error occured in reading the transaction response. Please contact the webshop owner" );
            }
            $entranceCode = $attrs['entranceCode'] . "";


            update_post_meta( $order_id, 'bluem_entrancecode', $entranceCode );
            update_post_meta( $order_id, 'bluem_mandateid', $mandate_id );

            // https://docs.woocommerce.com/document/managing-orders/
            // Possible statuses: 'pending', 'processing', 'on-hold', 'completed', 'refunded, 'failed', 'cancelled',

            // Remove cart
            global $woocommerce;
            $woocommerce->cart->empty_cart();
            $order->update_status( 'pending', __( 'Awaiting Bluem eMandate Signature', 'wc-gateway-bluem' ) );

            if ( isset( $response->EMandateTransactionResponse->TransactionURL ) ) {

                // redirect cast to string, for AJAX response handling
                $transactionURL = ( $response->EMandateTransactionResponse->TransactionURL . "" );

                // Logging transaction
                $raw_request_object = [
                    'entrance_code'    => $entranceCode,
                    'transaction_id'   => $mandate_id,
                    'transaction_url'  => $transactionURL,
                    'user_id'          => get_current_user_id(),
                    'timestamp'        => date( "Y-m-d H:i:s" ),
                    'description'      => "Mandate request {$order_id} {$user_id}",
                    'debtor_reference' => "",
                    'type'             => "mandates",
                    'order_id'         => $order_id,
                    'payload'          => json_encode(
                        [
                            'environment'           => $this->bluem->environment,
                            'order_amount'          => $order->get_total(),
                            'created_mandate_id'    => $mandate_id,
                            'local_instrument_code' => $this->bluem_config->localInstrumentCode,
                            'issuing_type'          => $this->bluem_config->requestType,
                            'sequence_type'         => $this->bluem_config->sequenceType,
                            'linked_orders'         => [ $order_id ]
                        ]
                    )
                ];

                bluem_db_create_request(
                    $raw_request_object
                );

                return array(
                    'result'   => 'success',
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
        public function mandates_webhook() {
            exit;
            // todo: update this

            $statusUpdateObject = $this->bluem->Webhook();

            $entranceCode = $statusUpdateObject->entranceCode . "";
            $mandateID    = $statusUpdateObject->EMandateStatus->MandateID . "";

            $webhook_status = $statusUpdateObject->EMandateStatus->Status . "";

            $order = $this->getOrder( $mandateID );
            if ( is_null( $order ) ) {
                echo "Error: No order found";
                exit;
            }
            $order_status = $order->get_status();

            if ( self::VERBOSE ) {
                echo "order_status: {$order_status}" . PHP_EOL;
                echo "webhook_status: {$webhook_status}" . PHP_EOL;
            }

            $user_id   = $user_id = $order->get_user_id();
            $user_meta = get_user_meta( $user_id );

            // Todo: if maxamount comes back from webhook (it should) then it can be accessed here
            // if (isset($user_meta['bluem_latest_mandate_amount'][0])) {
            // 	$mandate_amount = $user_meta['bluem_latest_mandate_amount'][0];
            // } else {
            // }

            if ( isset( $statusUpdateObject->EMandateStatus->AcceptanceReport->MaxAmount ) ) {
                $mandate_amount = (float) ( $statusUpdateObject->EMandateStatus->AcceptanceReport->MaxAmount . "" );
            } else {
                $mandate_amount = (float) 0.0;    // mandate amount is not set, so it is unlimited
            }
            if ( self::VERBOSE ) {
                var_dump( $mandate_amount );
                echo PHP_EOL;
            }

            $settings = get_option( 'bluem_woocommerce_options' );

            if ( $settings['localInstrumentCode'] !== "B2B" ) {
                $maxAmountEnabled = true;
            } else {
                $maxAmountEnabled = ( isset( $settings['maxAmountEnabled'] ) ? ( $settings['maxAmountEnabled'] == "1" ) : false );
            }


            if ( self::VERBOSE ) {
                echo "mandate_amount: {$mandate_amount}" . PHP_EOL;
            }


            if ( $maxAmountEnabled ) {
                $maxAmountFactor = 1.0;
                if ( $maxAmountEnabled ) {
                    $maxAmountFactor = ( isset( $settings['maxAmountFactor'] ) ? (float) ( $settings['maxAmountFactor'] ) : false );
                }

                $mandate_successful = false;

                if ( $mandate_amount !== 0.0 ) {
                    $order_price      = $order->get_total();
                    $max_order_amount = (float) ( $order_price * $maxAmountFactor );
                    if ( self::VERBOSE ) {
                        echo "max_order_amount: {$max_order_amount}" . PHP_EOL;
                    }

                    if ( $mandate_amount >= $max_order_amount ) {
                        $mandate_successful = true;
                        if ( self::VERBOSE ) {
                            echo "mandate is enough" . PHP_EOL;
                        }
                    } else {
                        if ( self::VERBOSE ) {
                            echo "mandate is too small" . PHP_EOL;
                        }
                    }
                }
            } else {
                $mandate_successful = true;
            }

            if ( $webhook_status === "Success" ) {
//                if ($order_status === "processing") {
//                    // order is already marked as processing, nothing more is necessary
//                } else
                if ( $order_status === "pending" ) {
                    // check if maximum of order does not exceed mandate size based on user metadata
                    if ( $mandate_successful ) {
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
            } elseif ( $webhook_status === "Cancelled" ) {
                $order->update_status( 'cancelled', __( 'Machtiging is geannuleerd; via webhook', 'wc-gateway-bluem' ) );
            }
//            elseif ($webhook_status === "Open" || $webhook_status == "Pending") {
            // if the webhook is still open or pending, nothing has to be done as of yet
//            } 
            elseif ( $webhook_status === "Expired" ) {
                $order->update_status( 'failed', __( 'Machtiging is verlopen; via webhook', 'wc-gateway-bluem' ) );
            } else {
                $order->update_status( 'failed', __( 'Machtiging is gefaald: fout of onbekende status; via webhook', 'wc-gateway-bluem' ) );
            }
            exit;
        }

        /**
         * Retrieve an order based on its mandate_id in metadata from the WooCommerce store
         *
         * @param String $mandateID
         *
         */
        private function getOrder( string $mandateID ) {
            $orders = wc_get_orders( array(
                'orderby'         => 'date',
                'order'           => 'DESC',
                'bluem_mandateid' => $mandateID
            ) );
            if ( count( $orders ) == 0 ) {
                return null;
            }

            return $orders[0];
        }


        /**
         * mandates_Callback function after Mandate process has been completed by the user
         * @return function [description]
         */
        public function mandates_callback() {
            $this->bluem = new Bluem( $this->bluem_config );

            if ( ! isset( $_GET['mandateID'] ) ) {
                $errormessage = "Fout: geen juist mandaat id teruggekregen bij mandates_callback. Neem contact op met de webshop en vermeld je contactgegevens.";
                bluem_error_report_email(
                    [
                        'service'  => 'mandates',
                        'function' => 'mandates_callback',
                        'message'  => $errormessage
                    ]
                );
                bluem_dialogs_render_prompt( $errormessage );
                exit;
            }

            if ( $_GET['mandateID'] == "" ) {
                $errormessage = "Fout: geen juist mandaat id teruggekregen bij mandates_callback. Neem contact op met de webshop en vermeld je contactgegevens.";
                bluem_error_report_email(
                    [
                        'service'  => 'mandates',
                        'function' => 'mandates_callback',
                        'message'  => $errormessage
                    ]
                );
                bluem_dialogs_render_prompt( $errormessage );
                exit;
            }
            $mandateID = $_GET['mandateID'];

            $order = $this->getOrder( $mandateID );
            if ( is_null( $order ) ) {
                $errormessage = "Fout: mandaat niet gevonden in webshop. Neem contact op met de webshop en vermeld de code {$mandateID} bij je gegevens.";
                bluem_error_report_email(
                    [
                        'service'  => 'mandates',
                        'function' => 'mandates_callback',
                        'message'  => $errormessage
                    ]
                );
                bluem_dialogs_render_prompt( $errormessage );
                exit;
            }

            $request_from_db = bluem_db_get_request_by_transaction_id_and_type(
                $mandateID,
                "mandates"
            );

            if ( $request_from_db == false ) {
                // @todo: give an error, as this transaction has clearly not been saved

                $entranceCode = $order->get_meta( 'bluem_entrancecode' );
            }

            $entranceCode = $request_from_db->entrance_code;

            $response = $this->bluem->MandateStatus( $mandateID, $entranceCode );

            if ( ! $response->Status() ) {
                $errormessage = "Fout bij opvragen status: " . $response->Error() . "<br>Neem contact op met de webshop en vermeld deze status";
                bluem_error_report_email(
                    [
                        'service'  => 'mandates',
                        'function' => 'mandates_callback',
                        'message'  => $errormessage
                    ]
                );
                bluem_dialogs_render_prompt( $errormessage );
                exit;
            }

            if ( self::VERBOSE ) {
                var_dump( "mandateid: " . $mandateID );
                var_dump( "entrancecode: " . $entranceCode );
                echo "<hr>";
                var_dump( $response );
                echo "<hr>";
            }

            $statusUpdateObject = $response->EMandateStatusUpdate;
            $statusCode         = $statusUpdateObject->EMandateStatus->Status . "";

            // $request_from_db = bluem_db_get_request_by_transaction_id($mandateID);
            if ( $statusCode !== $request_from_db->status ) {
                bluem_db_update_request(
                    $request_from_db->id,
                    [
                        'status' => $statusCode
                    ]
                );
            }
            if ( $statusCode === "Success" ) {
                if ( $request_from_db->id !== "" ) {
                    $new_data = [];
                    if ( isset( $response->EMandateStatusUpdate->EMandateStatus->PurchaseID ) ) {
                        $new_data['purchaseID'] = $response
                                                      ->EMandateStatusUpdate->EMandateStatus->PurchaseID . "";
                    }
                    if ( isset( $response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport ) ) {
                        $new_data['report'] = $response
                            ->EMandateStatusUpdate->EMandateStatus->AcceptanceReport;
                    }
                    if ( count( $new_data ) > 0 ) {
                        bluem_db_put_request_payload(
                            $request_from_db->id,
                            $new_data
                        );
                    }
                }
                $this->validateMandate(
                    $response, $order, true, true,
                    true, $mandateID, $entranceCode
                );
            } elseif ( $statusCode === "Pending" ) {
                bluem_dialogs_render_prompt(
                    "<p>Uw machtiging wacht op goedkeuring van
                    een andere ondertekenaar namens uw organisatie.<br>
                    Deze persoon dient in te loggen op internet bankieren
                    en deze machtiging mede goed te keuren.
                    Hierna is de machtiging goedgekeurd en zal dit automatisch
                    reflecteren op deze site.</p>"
                );
                exit;
            } elseif ( $statusCode === "Cancelled" ) {
                $order->update_status(
                    'cancelled',
                    __( 'Machtiging is geannuleerd', 'wc-gateway-bluem' )
                );

                bluem_transaction_notification_email(
                    $request_from_db->id
                );
                bluem_dialogs_render_prompt( "Je hebt de mandaat ondertekening geannuleerd" );
                // terug naar order pagina om het opnieuw te proberen?
                exit;
            } elseif ( $statusCode === "Open" || $statusCode == "Pending" ) {
                bluem_dialogs_render_prompt( "De mandaat ondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch." );
                // callback pagina beschikbaar houden om het opnieuw te proberen?
                // is simpelweg SITE/wc-api/bluem_callback?mandateID=$mandateID
                exit;
            } elseif ( $statusCode === "Expired" ) {
                $order->update_status(
                    'failed',
                    __(
                        'Machtiging is verlopen',
                        'wc-gateway-bluem'
                    )
                );

                bluem_transaction_notification_email(
                    $request_from_db->id
                );

                bluem_dialogs_render_prompt(
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
                $errormessage = "Fout: Onbekende of foutieve status teruggekregen: {$statusCode}
                    <br>Neem contact op met de webshop en vermeld deze status";
                bluem_error_report_email(
                    [
                        'service'  => 'mandates',
                        'function' => 'mandates_callback',
                        'message'  => $errormessage
                    ]
                );

                bluem_dialogs_render_prompt(
                    $errormessage
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
         *
         * @return void
         */
        private function validateMandate( $response, $order, $block_processing = false, $update_metadata = true, $redirect = true, $mandate_id = null, $entrance_code = null ) {
            $maxAmountResponse = $this->bluem->GetMaximumAmountFromTransactionResponse( $response );
            $user_id           = $order->get_user_id();

            $mandate_id = $response->EMandateStatusUpdate->EMandateStatus->MandateID . "";

            $settings         = get_option( 'bluem_woocommerce_options' );
            $maxAmountEnabled = ( isset( $settings['maxAmountEnabled'] ) ? ( $settings['maxAmountEnabled'] == "1" ) : false );
            if ( $maxAmountEnabled ) {
                $maxAmountFactor = ( isset( $settings['maxAmountFactor'] ) ? (float) ( $settings['maxAmountFactor'] ) : false );
            } else {
                $maxAmountFactor = 1.0;
            }

            $successful_mandate = false;

            $request_id      = "";
            $request_from_db = false;
            if ( ! is_null( $mandate_id ) ) {
                $request_from_db = bluem_db_get_request_by_transaction_id_and_type(
                    $mandate_id,
                    "mandates"
                );

                $request_id = $request_from_db->id;
            }

            if ( $maxAmountEnabled ) {

                // NextDeli specific: estimate 10% markup on order total:
                $order_total_plus = (float) $order->get_total() * $maxAmountFactor;

                if ( self::VERBOSE ) {
                    if ( $maxAmountResponse->amount === 0.0 ) {
                        echo "No max amount set";
                    } else {
                        echo "MAX AMOUNT SET AT {$maxAmountResponse->amount} {$maxAmountResponse->currency}";
                    }
                    echo "<hr>";
                    echo "Totaalbedrag: ";
                    var_dump( (float) $order->get_total() );
                    echo " | totaalbedrag +10 procent: ";
                    var_dump( $order_total_plus );
                    echo "<hr>";
                }

                if ( isset( $maxAmountResponse->amount ) && $maxAmountResponse->amount !== 0.0 ) {
                    if ( $update_metadata ) {
                        if ( self::VERBOSE ) {
                            echo "<br>updating user meta: bluem_latest_mandate_amount to value {$maxAmountResponse->amount} - result: ";
                        }
                        update_user_meta(
                            $user_id,
                            'bluem_latest_mandate_amount',
                            $maxAmountResponse->amount
                        );
                    }
                    $allowed_margin = ( $order_total_plus <= $maxAmountResponse->amount );
                    if ( self::VERBOSE ) {
                        echo "binnen machtiging marge?";
                        var_dump( $allowed_margin );
                    }

                    if ( $allowed_margin ) {
                        $successful_mandate = true;
                    } else {
                        if ( $block_processing ) {
                            $order->update_status( 'pending', __( 'Machtiging moet opnieuw ondertekend worden, want mandaat bedrag is te laag', 'wc-gateway-bluem' ) );

                            $url                     = $order->get_checkout_payment_url();
                            $order_total_plus_string = str_replace( ".", ",", ( "" . round( $order_total_plus, 2 ) ) );
                            bluem_dialogs_render_prompt(
                                "<p>Het automatische incasso mandaat dat je hebt afgegeven is niet toereikend voor de incassering van het factuurbedrag van jouw bestelling.</p>
                            <p>De geschatte factuurwaarde van jouw bestelling is EUR {$order_total_plus_string}. Het mandaat voor de automatische incasso die je hebt ingesteld is EUR {$maxAmountResponse->amount}. Ons advies is om jouw mandaat voor automatische incasso te verhogen of voor 'onbeperkt' te kiezen.</p>" .
                                "<p><a href='{$url}' target='_self'>Klik hier om terug te gaan naar de betalingspagina en een nieuw mandaat af te geven</a></p>",
                                false
                            );

                            bluem_db_request_log(
                                $request_id,
                                "User tried to give use this mandate with maxamount
                                    &euro; {$maxAmountResponse->amount}, but the Order <a href='" .
                                admin_url( "post.php?post=" . $order->get_id() . "&action=edit" ) .
                                "' target='_self'>ID " . $order->get_id() . "</a> grand
                                    total including correction is &euro; {$order_total_plus_string}.
                                    The user is prompted to create a new mandate to fulfill this order."
                            );


                            exit;
                        }
                    }
                } else {
                    if ( $update_metadata ) {
                        if ( self::VERBOSE ) {
                            echo "<br>updating user meta: bluem_latest_mandate_amount to value 0 - result: ";
                        }
                        update_user_meta( $user_id, 'bluem_latest_mandate_amount', 0 );
                    }
                    $successful_mandate = true;
                }
            } else {
                // no maxamount check, so just continue;
                $successful_mandate = true;
            }

            if ( $update_metadata ) {
                if ( self::VERBOSE ) {
                    echo "<br>updating user meta: bluem_latest_mandate_validated to value {$successful_mandate} - result: ";
                }
                update_user_meta(
                    $user_id,
                    'bluem_latest_mandate_validated',
                    $successful_mandate
                );
            }

            if ( $successful_mandate ) {
                if ( $update_metadata ) {
                    if ( $mandate_id !== "" ) {
                        if ( self::VERBOSE ) {
                            echo "<br>updating user meta: bluem_latest_mandate_id to value {$mandate_id} - result: ";
                        }
                        update_user_meta(
                            $user_id,
                            'bluem_latest_mandate_id',
                            $mandate_id
                        );
                    }
                    if ( $entrance_code !== "" ) {
                        if ( self::VERBOSE ) {
                            echo "<br>updating user meta: entranceCode to value {$entrance_code} - result: ";
                        }
                        update_user_meta(
                            $user_id,
                            'bluem_latest_mandate_entrance_code',
                            $entrance_code
                        );
                    }
                }

                if ( self::VERBOSE ) {
                    echo "mandaat is succesvol, order kan worden aangepast naar machtiging_goedgekeurd";
                }

                $order->update_status(
                    'processing',
                    __(
                        "Machtiging (mandaat ID {$mandate_id}, verzoek ID {$request_id}
                        is gelukt en goedgekeurd",
                        'wc-gateway-bluem'
                    )
                );

                bluem_transaction_notification_email(
                    $request_id
                );

                do_action(
                    'bluem_woocommerce_valid_mandate_callback',
                    $user_id,
                    $response
                );

                if ( $redirect ) {
                    if ( self::VERBOSE ) {
                        die();
                    }
                    $this->bluem_thankyou( $order->get_id() );
                } else {
                    return true;
                }
            }
        }
    }

    add_action( 'bluem_woocommerce_valid_mandate_callback', 'bluem_woocommerce_valid_mandate_callback_function', 10, 2 );

    function bluem_woocommerce_valid_mandate_callback_function( $user_id, $response ) {
        // do something with the response, use this in third-party extensions of this system
    }


    add_action( 'show_user_profile', 'bluem_woocommerce_mandates_show_extra_profile_fields', 2 );
    add_action( 'edit_user_profile', 'bluem_woocommerce_mandates_show_extra_profile_fields' );

    function bluem_woocommerce_mandates_show_extra_profile_fields( $user ) {
        $bluem_requests = bluem_db_get_requests_by_user_id_and_type( $user->ID, "mandates" ); ?>
        <table class="form-table">
            <a id="user_mandates"></a>

            <?php if ( isset( $bluem_requests ) && count( $bluem_requests ) > 0 ) { ?>
                <tr>
                    <th>
                        Digitale Incassomachtigingen
                    </th>
                    <td>
                        <?php
                        bluem_render_requests_list( $bluem_requests ); ?>
                    </td>
                </tr>
            <?php } else {
                // legacy code?>
                <tr>
                    <th><label for="bluem_latest_mandate_id">Meest recente MandateID</label></th>
                    <td>
                        <input type="text" name="bluem_latest_mandate_id" id="bluem_latest_mandate_id"
                               value="<?php echo esc_attr( get_user_meta( $user->ID, 'bluem_latest_mandate_id', true ) ); ?>"
                               class="regular-text"/><br/>
                        <span class="description">Hier wordt het meest recente mandate ID geplaatst; en gebruikt bij het doen van een volgende checkout.</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="bluem_latest_mandate_entrance_code">Meest recente EntranceCode</label></th>

                    <td>
                        <input type="text" name="bluem_latest_mandate_entrance_code"
                               id="bluem_latest_mandate_entrance_code"
                               value="<?php echo esc_attr( get_user_meta( $user->ID, 'bluem_latest_mandate_entrance_code', true ) ); ?>"
                               class="regular-text"/><br/>
                        <span class="description">Hier wordt het meest recente entrance_code geplaatst; en gebruikt bij het doen van een volgende checkout.</span>
                    </td>
                </tr>
                <tr>
                    <th><label for="bluem_latest_mandate_amount">Omvang laatste machtiging</label></th>
                    <td>
                        <input type="text" name="bluem_latest_mandate_amount" id="bluem_latest_mandate_amount"
                               value="<?php echo esc_attr( get_user_meta( $user->ID, 'bluem_latest_mandate_amount', true ) ); ?>"
                               class="regular-text"/><br/>
                        <span class="description">Dit is de omvang van de laatste machtiging</span>
                    </td>
                </tr>

                <?php
            } ?>
            <tr>
                <th><label for="bluem_mandates_validated">Machtiging via shortcode / InstantMandates valide?</label></th>
                <td>
                    <?php
                    $curValidatedVal = (int) esc_attr(
                        get_user_meta(
                            $user->ID,
                            'bluem_mandates_validated',
                            true
                        )
                    ); ?>
                    <select name="bluem_mandates_validated" id="bluem_mandates_validated">
                        <option value="1" <?php if ( $curValidatedVal == 1 ) {
                            echo "selected";
                        } ?>>
                            Ja
                        </option>
                        <option value="0" <?php if ( $curValidatedVal == 0 ) {
                            echo "selected";
                        } ?>>
                            Nee
                        </option>
                    </select><br/>
                    <span class="description">Is een machtiging via shortcode of InstantMandates doorgekomen? Indien van toepassing kan je dit hier overschrijven</span>
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

    function bluem_woocommerce_mandates_save_extra_profile_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }

        update_user_meta(
            $user_id,
            'bluem_latest_mandate_id',
            esc_attr( sanitize_text_field( $_POST['bluem_latest_mandate_id'] ) )
        );
        update_user_meta(
            $user_id,
            'bluem_latest_mandate_entrance_code',
            esc_attr( sanitize_text_field( $_POST['bluem_latest_mandate_entrance_code'] ) )
        );
        update_user_meta(
            $user_id,
            'bluem_latest_mandate_amount',
            esc_attr( sanitize_text_field( $_POST['bluem_latest_mandate_amount'] ) )
        );
        update_user_meta(
            $user_id,
            'bluem_mandates_validated',
            esc_attr( sanitize_text_field( $_POST['bluem_mandates_validated'] ) )
        );
    }
}

function bluem_woocommerce_mandates_settings_section() {
    $mandate_id_counter = get_option( 'bluem_woocommerce_mandate_id_counter' );

    // The below code is useful when you want the mandate_id to start counting at a fixed minimum.
    // This is what had to be implemented for H2OPro; one of the first clients.
    // @todo: convert to action so it can be overriden by third-party developers such as H2OPro.
    if ( home_url() == "https://www.h2opro.nl" && (int) ( $mandate_id_counter . "" ) < 111100 ) {
        $mandate_id_counter += 111000;
        update_option( 'bluem_woocommerce_mandate_id_counter', $mandate_id_counter );
    }
    echo '<p><a id="tab_mandates"></a> Hier kan je alle belangrijke gegevens instellen rondom Digitale Incassomachtigingen.</p>';
}

// ********************** Mandate specific

function bluem_woocommerce_settings_render_brandID() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'brandID' ) );
}

function bluem_woocommerce_settings_render_merchantID() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'merchantID' ) );
}

function bluem_woocommerce_settings_render_merchantSubId() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'merchantSubId' ) );
}

function bluem_woocommerce_settings_render_thanksPage() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'thanksPage' ) );
}

function bluem_woocommerce_settings_render_eMandateReason() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'eMandateReason' ) );
}

function bluem_woocommerce_settings_render_localInstrumentCode() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'localInstrumentCode' ) );
}

function bluem_woocommerce_settings_render_requestType() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'requestType' ) );
}

function bluem_woocommerce_settings_render_sequenceType() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'sequenceType' ) );
}

function bluem_woocommerce_settings_render_successMessage() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'successMessage' ) );
}

function bluem_woocommerce_settings_render_errorMessage() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'errorMessage' ) );
}

function bluem_woocommerce_settings_render_purchaseIDPrefix() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'purchaseIDPrefix' ) );
}

function bluem_woocommerce_settings_render_debtorReferenceFieldName() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'debtorReferenceFieldName' ) );
}

function bluem_woocommerce_settings_render_thanksPageURL() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'thanksPageURL' ) );
}

function bluem_woocommerce_settings_render_instantMandatesResponseURI() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'instantMandatesResponseURI' ) );
}

function bluem_woocommerce_settings_render_mandate_id_counter() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'mandate_id_counter' ) );
}

function bluem_woocommerce_settings_render_maxAmountEnabled() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'maxAmountEnabled' ) );
}

function bluem_woocommerce_settings_render_maxAmountFactor() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'maxAmountFactor' ) );
}

function bluem_woocommerce_settings_render_useMandatesDebtorWallet() {
    bluem_woocommerce_settings_render_input( bluem_woocommerce_get_mandates_option( 'useMandatesDebtorWallet' ) );
}


$bluem_options = get_option( 'bluem_woocommerce_options' );

if ( isset( $bluem_options['useMandatesDebtorWallet'] ) && $bluem_options['useMandatesDebtorWallet'] == "1" ) {

    /**
     * Add add a notice before the payment form - let's use an eror notice. Could also use content, etc.
     *
     * Reference: https://github.com/woothemes/woocommerce/blob/master/templates/checkout/review-order.php
     */
    add_action(
        'woocommerce_review_order_before_payment',
        'bluem_woocommerce_show_checkout_bic_selection'
    );
    function bluem_woocommerce_show_checkout_bic_selection() {
        // ref: https://stackoverflow.com/questions/40480587/woocommerce-checkout-custom-select-field/40480684
        $nonce = wp_create_nonce( "bluem_ajax_nonce" );
        echo "<input type='hidden' id='bluem_ajax_nonce' value='{$nonce}'/>";
        // echo "HIER KOMT DE BANKKEUZE";
        ?>
        <div id="BICselector">
            <label for="BICInput" style="display: block;">
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

    function bluem_woocommerce_validate_checkout_bic_choice( $fields, $errors ) {

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
    add_action( 'init', 'script_enqueuer' );

    function script_enqueuer() {

        // Register the JS file with a unique handle, file location, and an array of dependencies
        wp_register_script( "bluem_woocommerce_bic_retriever", plugin_dir_url( __FILE__ ) . 'js/bluem_woocommerce_bic_retriever.js', array( 'jquery' ) );

        // localize the script to your domain name, so that you can reference the url to admin-ajax.php file easily
        wp_localize_script( 'bluem_woocommerce_bic_retriever', 'myAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

        // enqueue jQuery library and the script you registered above
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'bluem_woocommerce_bic_retriever' );
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
    add_action( "wp_ajax_bluem_retrieve_bics_ajax", "bluem_retrieve_bics_ajax" );
    // add_action("wp_ajax_nopriv_bluem_retrieve_bics_ajax", "function_public_so_no_login");

    // define the function to be fired for logged in users
    function bluem_retrieve_bics_ajax() {

        // nonce check for an extra layer of security, the function will exit if it fails
        //    if ( !wp_verify_nonce( $_REQUEST['nonce'], "bluem_retrieve_bics_ajax_nonce")) {
        //       exit("Woof Woof Woof");
        //    }

        // switch()

        $bluem_config = bluem_woocommerce_get_config();
        $bluem        = new Bluem( $bluem_config );
        $BICs         = $bluem->retrieveBICsForContext( "Mandates" );


        if ( ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) {
            echo json_encode( $BICs );
        } else {
            header( "Location: " . $_SERVER["HTTP_REFERER"] );
        }
        die();
    }
}


add_filter( 'bluem_woocommerce_enhance_mandate_request', 'bluem_woocommerce_enhance_mandate_request_function', 10, 1 );

/**
 * allow third parties to add additional data to the request object through this additional action
 *
 * @param [type] $request
 *
 * @return void
 */
function bluem_woocommerce_enhance_mandate_request_function( $request ) {
    // do something with the Bluem Mandate request, use this in third-party extensions of this system
    return $request;
}
