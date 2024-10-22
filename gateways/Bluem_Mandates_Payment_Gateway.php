<?php
if (!defined('ABSPATH')) exit;

use Bluem\BluemPHP\Bluem;
use Bluem\BluemPHP\Responses\ErrorBluemResponse;

include_once __DIR__ . '/Bluem_Payment_Gateway.php';

class Bluem_Mandates_Payment_Gateway extends Bluem_Payment_Gateway
{
    protected $_show_fields = false;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $methodDescription = esc_html__('eMandate Payment Gateway voor WordPress - WooCommerce.', 'bluem');

        parent::__construct(
            'bluem_mandates',
            esc_html__('Bluem Digitaal Incassomachtiging (eMandate)', 'bluem'),
            $methodDescription,
            home_url('wc-api/bluem_mandates_callback')
        );

        if (isset($this->bluem_config->localInstrumentCode) && $this->bluem_config->localInstrumentCode === "B2B") {
            $this->method_title = esc_html__('Bluem Zakelijke Incassomachtiging (eMandate)', 'bluem');
        } else {
            $this->method_title = esc_html__('Bluem Particuliere Incassomachtiging (eMandate)', 'bluem');
        }

        $this->has_fields = true;

        $options = get_option('bluem_woocommerce_options');

        if (!empty($options['mandatesUseDebtorWallet']) && $options['mandatesUseDebtorWallet'] == '1') {
            $this->_show_fields = true;
        }

        // This action hook saves the settings
        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            array($this, 'process_admin_options')
        );

        // ********** CREATING plugin URLs for specific functions **********
        add_action(
            'woocommerce_api_bluem_mandates_webhook',
            array($this, 'bluem_mandates_webhook'),
            5
        );
        add_action(
            'woocommerce_api_bluem_mandates_callback',
            array($this, 'bluem_mandates_callback')
        );

        // ********** Allow filtering Orders based on MandateID **********
        add_filter(
            'woocommerce_order_data_store_cpt_get_orders_query',
            function ($query, $query_vars) {
                if (!empty($query_vars['bluem_mandateid'])) {
                    $query['meta_query'][] = array(
                        'key' => 'bluem_mandateid',
                        'value' => esc_attr($query_vars['bluem_mandateid']),
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
    public function bluem_thankyou($order_id)
    {
        $order = wc_get_order($order_id);

        $url = $order->get_checkout_order_received_url();

        if (!$order->has_status('failed')) {
            wp_safe_redirect($url);
            exit;
        }

        // @todo: add alternative route?
    }

    /**
     * Create plugin options page in admin interface
     *
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = apply_filters('wc_offline_form_fields', [
            'enabled' => [
                'title' => 'Enable/Disable',
                'label' => esc_html__('Activeer de Bluem eMandate Gateway', 'bluem'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no'
            ],
            'title' => [
                'title' => 'Titel van betaalmethode',
                'type' => 'text',
                'description' => esc_html__('Dit bepaalt de titel die de gebruiker ziet tijdens het afrekenen.', 'bluem'),
                'default' => esc_html__('Incasso machtiging voor zakelijke Rabobank, ING of ABN AMRO rekeningen', 'bluem'),
            ],
            'description' => [
                'title' => 'Description',
                'type' => 'textarea',
                'description' => esc_html__('Dit bepaalt de beschrijving die de gebruiker ziet tijdens het afrekenen.', 'bluem'),
                'default' => esc_html__('Geef een B2B eMandate af voor een incasso voor je bestelling.', 'bluem'),
            ]
        ]);
    }

    /**
     * Check if a valid mandate already exists for this user
     *
     * @param  $order Order object
     */
    private function _checkExistingMandate($order)
    {
        global $current_user;

        $order_id = $order->get_id();

        $user_id = $current_user->ID;

        $retrieved_request_from_db = false;

        $reason = "";

        $ready = false;

        if (!empty($user_id)) {
            $request = bluem_db_get_most_recent_request($user_id, "mandates");

            if ($request !== false) {
                $bluem_latest_mandate_entrance_code = $request->entrance_code;
                $bluem_latest_mandate_id = $request->transaction_id;

                $retrieved_request_from_db = true;

                $ready = true;
            } else {
                // no latest request found, also trying in user metadata (legacy)
                $user_meta = get_user_meta($user_id);

                $bluem_latest_mandate_id = null;
                if (!empty($user_meta['bluem_latest_mandate_id'])) {
                    $bluem_latest_mandate_id = $user_meta['bluem_latest_mandate_id'][0];

                    $ready = true;
                }

                $bluem_latest_mandate_entrance_code = null;
                if (!empty($user_meta['bluem_latest_mandate_entrance_code'])) {
                    $bluem_latest_mandate_entrance_code = $user_meta['bluem_latest_mandate_entrance_code'][0];

                    $ready = true;
                }
            }
        }

        if ($ready
            && !is_null($bluem_latest_mandate_id)
            && $bluem_latest_mandate_id !== ""
            && !is_null($bluem_latest_mandate_entrance_code)
            && $bluem_latest_mandate_entrance_code !== ""
        ) {
            try {
                $existing_mandate_response = $this->bluem->MandateStatus(
                    $bluem_latest_mandate_id,
                    $bluem_latest_mandate_entrance_code
                );
            } catch (Exception $e) {
                return array(
                    'exception' => $e->getMessage(),
                    'result' => 'failure'
                );
            }

            if (!$existing_mandate_response->Status()) {
                $reason = esc_html__("No / invalid bluem response for existing mandate", 'bluem');
                // existing mandate response is not at all valid,
                // continue with actual mandate process
            } elseif (
                $existing_mandate_response->EMandateStatusUpdate->EMandateStatus->Status . "" === "Success"
            ) {
                if ($this->validateMandate(
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

                    if ($retrieved_request_from_db) {
                        bluem_db_request_log(
                            $request->id,
                            sprintf(
                            /* translators: %s: order id */
                                esc_html__('Utilized this request for a payment for another order with ID %s', 'bluem'),
                                $order_id
                            )
                        );

                        bluem_db_create_link(
                            $request->id,
                            $order_id
                        );

                        try {
                            $cur_payload = json_decode($request->payload, false, 512, JSON_THROW_ON_ERROR);

                            if (!isset($cur_payload->linked_orders)) {
                                $cur_payload->linked_orders = [];
                            }

                            $cur_payload->linked_orders[] = $order_id;
                        } catch (Exception $e) {
                            $cur_payload->linked_orders = [];
                        }

                        try {
                            $payload_string = wp_json_encode($cur_payload, JSON_THROW_ON_ERROR);
                        } catch (Exception $e) {
                            $payload_string = '';
                        }

                        bluem_db_update_request(
                            $request->id,
                            [
                                'payload' => $payload_string
                            ]
                        );
                    }

                    return array(
                        'result' => 'success',
                        'redirect' => $order->get_checkout_order_received_url()
                    );
                }

                $reason = esc_html__("Existing mandate found, but not valid", 'bluem');
            } else {
                $reason = esc_html__("Existing mandate is not a successful mandate", 'bluem');
            }
        } else {
            $reason = esc_html__("Not ready, no metadata", 'bluem');
        }

        return array(
            'result' => 'fail',
            'message' => $reason
        );
    }

    /**
     * Define payment fields
     */
    public function payment_fields()
    {
        if ($this->bluem === null) {
            return;
        }

        $BICs = $this->bluem->retrieveBICsForContext("Mandates");

        $description = $this->get_description();

        $options = [];

        if ($description) {
            echo wpautop(wptexturize($description)); // @codingStandardsIgnoreLine.
        }

        // Loop through BICS
        foreach ($BICs as $BIC) {
            $options[$BIC->issuerID] = $BIC->issuerName;
        }

        // Check for options
        if ($this->_show_fields && !empty($options)) {
            woocommerce_form_field('bluem_mandates_bic', array(
                'type' => 'select',
                'required' => true,
                'label' => esc_html__('Selecteer een bank:', 'bluem'),
                'options' => $options,
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

    /**
     * Process payment through Bluem portal
     *
     * @param String $order_id
     *
     * @return array
     */
    public function process_payment($order_id)
    {
        global $current_user;

        // Convert UTF-8 to ISO
        if (!empty($this->bluem_config->eMandateReason)) {
            $this->bluem_config->eMandateReason = mb_convert_encoding($this->bluem_config->eMandateReason, 'ISO-8859-1', 'UTF-8');
        } else {
            $this->bluem_config->eMandateReason = esc_html__("Incasso machtiging", 'bluem');
        }

        try {
            $this->bluem = new Bluem($this->bluem_config);
        } catch (Exception $e) {
            return array(
                'exception' => $e->getMessage(),
                'result' => 'failure'
            );
        }

        $order = wc_get_order($order_id);

        // $user_id = $order->get_user_id();
        // $user_id = get_post_meta($order_id, '_customer_user', true);
        // improved retrieval of user id:
        $user_id = $current_user->ID;

        $settings = get_option('bluem_woocommerce_options');

        $check = $this->_checkExistingMandate($order);

        if (isset($check['result']) && $check['result'] === "success") {
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_order_received_url()
            );
            // @todo Possibly allow different redirect after fast checkout with existing, valid, mandate.
        }

        $bluem_mandates_bic = isset($_POST['bluem_mandates_bic']) ? sanitize_text_field(wp_unslash($_POST['bluem_mandates_bic'])) : '';

        $order_id = $order->get_id();
        // update: added prefixed order ID for retries of mandate requests
        $prefixed_order_id = gmdate("His") . $order_id;
        $mandate_id = $this->bluem->CreateMandateId(
            $prefixed_order_id,
            $user_id
        );

        try {
            $request = $this->bluem->CreateMandateRequest(
                $user_id,
                $order_id,
                $mandate_id
            );
        } catch (Exception $e) {
            return array(
                'exception' => $e->getMessage(),
                'result' => 'failure'
            );
        }

        if (!empty($bluem_mandates_bic)) {
            $request->selectDebtorWallet($bluem_mandates_bic);
        }

        // allow third parties to add additional data to the request object through this additional action
        $request = apply_filters(
            'bluem_woocommerce_enhance_mandate_request',
            $request
        );

        try {
            $response = $this->bluem->PerformRequest($request);
        } catch (Exception $e) {
            return array(
                'exception' => $e->getMessage(),
                'result' => 'failure'
            );
        }

        if ($response instanceof ErrorBluemResponse) {
            throw new RuntimeException(
                esc_html("An error occurred in the payment method. Please contact the webshop owner with this message:  " . $response->error())
            );
        }

        $attrs = $response->EMandateTransactionResponse->attributes();

        if (!isset($attrs['entranceCode'])) {
            throw new RuntimeException("An error occurred in reading the transaction response. Please contact the webshop owner");
        }
        $entranceCode = $attrs['entranceCode'] . "";

        update_post_meta($order_id, 'bluem_entrancecode', esc_attr($entranceCode));
        update_post_meta($order_id, 'bluem_mandateid', esc_attr($mandate_id));

        // https://docs.woocommerce.com/document/managing-orders/
        // Possible statuses: 'pending', 'processing', 'on-hold', 'completed', 'refunded, 'failed', 'cancelled',

        // Remove cart
        global $woocommerce;
        $woocommerce->cart->empty_cart();
        $order->update_status('pending', esc_html__('Awaiting Bluem eMandate Signature', 'bluem'));

        if (isset($response->EMandateTransactionResponse->TransactionURL)) {

            // redirect cast to string, for AJAX response handling
            $transactionURL = ($response->EMandateTransactionResponse->TransactionURL . "");

            // Logging transaction
            $raw_request_object = [
                'entrance_code' => $entranceCode,
                'transaction_id' => $mandate_id,
                'transaction_url' => $transactionURL,
                'user_id' => get_current_user_id(),
                'timestamp' => gmdate("Y-m-d H:i:s"),
                'description' =>
                    sprintf(
                    /* translators: %1/$s: order id, %2/$s: user id
                     */
                        esc_html__('Mandate request for order %1$s by user %2$s', 'bluem'),
                        $order_id,
                        $user_id
                    ),
                'debtor_reference' => "",
                'type' => "mandates",
                'order_id' => $order_id,
                'payload' => wp_json_encode(
                    [
                        'environment' => $this->bluem_config->environment,
                        'order_amount' => $order->get_total(),
                        'created_mandate_id' => $mandate_id,
                        'local_instrument_code' => $this->bluem_config->localInstrumentCode,
                        'issuing_type' => $this->bluem_config->requestType,
                        'sequence_type' => $this->bluem_config->sequenceType,
                        'linked_orders' => [$order_id]
                    ]
                )
            ];

            bluem_db_create_request(
                $raw_request_object
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
    public function bluem_mandates_webhook(): void
    {
        try {
            $webhook = $this->bluem->Webhook();

            if (($webhook->xmlObject ?? null) !== null) {
                if (method_exists($webhook, 'getStatus')) {
                    $webhook_status = $webhook->getStatus();
                } else {
                    $webhook_status = null;
                }

                if (method_exists($webhook, 'getEntranceCode')) {
                    $entranceCode = $webhook->getEntranceCode();
                }
                if (method_exists($webhook, 'getTransactionID')) {
                    $transactionID = $webhook->getTransactionID();
                }
                if (method_exists($webhook, 'getMandateID')) {
                    $mandateID = $webhook->getMandateID();
                }

                $order = $this->getOrder($mandateID);
                if (is_null($order)) {
                    http_response_code(404);
                    esc_html_e("Error: No order found", 'bluem');
                    exit;
                }
                $order_status = $order->get_status();

                $user_id = $user_id = $order->get_user_id();

                $user_meta = get_user_meta($user_id);

                // Todo: if maxamount comes back from webhook (it should) then it can be accessed here
                // if (isset($user_meta['bluem_latest_mandate_amount'][0])) {
                // 	$mandate_amount = $user_meta['bluem_latest_mandate_amount'][0];
                // } else {
                // }

                $acceptanceReport = $webhook->getAcceptanceReportArray();

                if (!empty($acceptanceReport['MaxAmount'])) {
                    $mandate_amount = (float)($acceptanceReport['MaxAmount'] . "");
                } else {
                    $mandate_amount = 0.0;    // mandate amount is not set, so it is unlimited
                }

                $settings = get_option('bluem_woocommerce_options');

                if ($settings['localInstrumentCode'] !== "B2B") {
                    $maxAmountEnabled = true;
                } else {
                    $maxAmountEnabled = (isset($settings['maxAmountEnabled']) && $settings['maxAmountEnabled'] === "1");
                }

                if ($maxAmountEnabled) {
                    $maxAmountFactor = isset($settings['maxAmountFactor'])
                        ? (float)($settings['maxAmountFactor'])
                        : 1.0;

                    $mandate_successful = false;

                    if ($mandate_amount !== 0.0) {
                        $order_price = $order->get_total();
                        $max_order_amount = $order_price * $maxAmountFactor;

                        if ($mandate_amount >= $max_order_amount) {
                            $mandate_successful = true;
                        }
                    }
                } else {
                    $mandate_successful = true;
                }

                if ($webhook_status === "Success") {
                    if ($order_status === "processing") {
                        // order is already marked as processing, nothing more is necessary
                    } else if ($order_status === "pending" && $mandate_successful) {
                        $order->update_status(
                            'processing',
                            printf(
                            /* translators: %s: mandate id */
                                esc_html__('Authorization (Mandate ID %s) was successful and approved; via webhook', 'bluem'),
                                esc_attr($mandateID)
                            )
                        );
                    }
                } elseif ($webhook_status === "Cancelled") {
                    $order->update_status('cancelled', esc_html__('Authorization has been canceled; via webhook', 'bluem'));
                } elseif ($webhook_status === "Open" || $webhook_status == "Pending") {
                    // if the webhook is still open or pending, nothing has to be done yet
                } elseif ($webhook_status === "Expired") {
                    $order->update_status('failed', esc_html__('Authorization has expired; via webhook', 'bluem'));
                } else {
                    $order->update_status('failed', esc_html__('Authorization failed: error or unknown status; via webhook', 'bluem'));
                }
                http_response_code(200);
                echo 'OK';
                exit;
            }
        } catch (Exception $e) {
            http_response_code(500);
            printf(
            /* translators: %s: exception message */
                esc_html__("Error: Exception: %s", 'bluem'),
                esc_html($e->getMessage())
            );
            exit;
        }
    }

    /**
     * Retrieve an order based on its mandate_id in metadata from the WooCommerce store
     *
     * @param String $mandateID
     *
     * @return mixed|null
     */
    private function getOrder(string $mandateID)
    {
        $orders = wc_get_orders(array(
            'orderby' => 'date',
            'order' => 'DESC',
            'bluem_mandateid' => $mandateID
        ));
        if (count($orders) == 0) {
            return null;
        }

        return $orders[0];
    }

    /**
     * mandates_Callback function after Mandate process has been completed by the user
     * @return void
     */
    public function bluem_mandates_callback()
    {
        // $this->bluem = new Bluem( $this->bluem_config );
        // dont recreate it here, it should already exist in the gateway!

        if (!empty(sanitize_text_field(wp_unslash($_GET['mandateID'])))) {
            $errormessage = esc_html__("Fout: geen juist mandaat id teruggekregen bij mandates_callback. Neem contact op met de webshop en vermeld je contactgegevens.", 'bluem');
            bluem_error_report_email(
                [
                    'service' => 'mandates',
                    'function' => 'mandates_callback',
                    'message' => $errormessage
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            exit;
        }

        if ($_GET['mandateID'] == "") {
            $errormessage = esc_html__("Fout: geen juist mandaat id teruggekregen bij mandates_callback. Neem contact op met de webshop en vermeld je contactgegevens.", "bluem");
            bluem_error_report_email(
                [
                    'service' => 'mandates',
                    'function' => 'mandates_callback',
                    'message' => $errormessage
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            exit;
        }
        $mandateID = sanitize_text_field(wp_unslash($_GET['mandateID']));

        $order = $this->getOrder($mandateID);
        if (is_null($order)) {
            $errormessage = sprintf(
            /* translators: %s: error code */
                esc_html__("Fout: mandaat niet gevonden in webshop. Neem contact op met de webshop en vermeld de code %s bij je gegevens.", "bluem"), $mandateID);
            bluem_error_report_email(
                [
                    'service' => 'mandates',
                    'function' => 'mandates_callback',
                    'message' => $errormessage
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            exit;
        }

        $request_from_db = bluem_db_get_request_by_transaction_id_and_type(
            $mandateID,
            "mandates"
        );

        if (!$request_from_db) {
            // @todo: give an error, as this transaction has clearly not been saved

            $entranceCode = $order->get_meta('bluem_entrancecode');
        }

        $entranceCode = $request_from_db->entrance_code;

        try {
            $response = $this->bluem->MandateStatus($mandateID, $entranceCode);
        } catch (Exception $e) {
            $errormessage = sprintf(
            /* translators: %s: error message */
                esc_html__("Fout bij opvragen status: %s. Neem contact op met de webshop en vermeld deze status", "bluem"), $e->getMessage()
            );

            bluem_error_report_email(
                [
                    'service' => 'mandates',
                    'function' => 'mandates_callback',
                    'message' => esc_html($errormessage)
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            exit;
        }

        if (!$response->Status()) {
            $errormessage = sprintf(
            /* translators: %s: error message */
                esc_html__("Fout bij opvragen status: %s. Neem contact op met de webshop en vermeld deze status", "bluem"),
                esc_html($response->Error())
            );
            bluem_error_report_email(
                [
                    'service' => 'mandates',
                    'function' => 'mandates_callback',
                    'message' => esc_html($errormessage)
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            exit;
        }

        $statusUpdateObject = $response->EMandateStatusUpdate;
        $statusCode = $statusUpdateObject->EMandateStatus->Status . "";

        // $request_from_db = bluem_db_get_request_by_transaction_id($mandateID);
        if ($statusCode !== $request_from_db->status) {
            bluem_db_update_request(
                $request_from_db->id,
                [
                    'status' => $statusCode
                ]
            );
        }
        if ($statusCode === "Success") {
            if ($request_from_db->id !== "") {
                $new_data = [];
                if (isset($response->EMandateStatusUpdate->EMandateStatus->PurchaseID)) {
                    $new_data['purchaseID'] = $response
                            ->EMandateStatusUpdate->EMandateStatus->PurchaseID . "";
                }
                if (isset($response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport)) {
                    $new_data['report'] = $response
                        ->EMandateStatusUpdate->EMandateStatus->AcceptanceReport;
                }
                if (count($new_data) > 0) {
                    bluem_db_put_request_payload(
                        $request_from_db->id,
                        $new_data
                    );
                }
            }
            $this->validateMandate(
                $response,
                $order,
                true,
                true,
                true,
                $mandateID,
                $entranceCode
            );
        } elseif ($statusCode === "Pending") {
            bluem_dialogs_render_prompt(
                esc_html__("Uw machtiging wacht op goedkeuring van
                    een andere ondertekenaar namens uw organisatie. 
                    Deze persoon dient in te loggen op internet bankieren
                    en deze machtiging ook goed te keuren.
                    Hierna is de machtiging goedgekeurd en zal dit
                    reageren op deze site.", 'bluem')
            );
            exit;
        } elseif ($statusCode === "Cancelled") {
            $order->update_status(
                'cancelled',
                esc_html__('Goedkeuring is afgebroken of geannuleerd', 'bluem')
            );

            bluem_transaction_notification_email(
                $request_from_db->id
            );
            bluem_dialogs_render_prompt("Je hebt de mandaat ondertekening geannuleerd");
            // terug naar order pagina om het opnieuw te proberen?
            exit;
        } elseif ($statusCode === "Open" || $statusCode == "Pending") {
            bluem_dialogs_render_prompt("De mandaat ondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch.");
            // callback pagina beschikbaar houden om het opnieuw te proberen?
            // is simpelweg SITE/wc-api/bluem_callback?mandateID=$mandateID
            exit;
        } elseif ($statusCode === "Expired") {
            $order->update_status(
                'failed',
                esc_html__('Verzoek is verlopen', 'bluem')
            );

            bluem_transaction_notification_email(
                $request_from_db->id
            );

            bluem_dialogs_render_prompt(
                esc_html__("Fout: De mandaat of het verzoek daartoe is verlopen", 'bluem')
            );
            exit;
        } else {
            $order->update_status(
                'failed',
                esc_html__('Authorization failed: error or unknown status', 'bluem')
            );
            $errormessage = sprintf(
            /* translators: %s: error status code */
                esc_html__("Fout: Onbekende of foutieve status teruggekregen: %s. Neem contact op met de webshop en vermeld deze status", 'bluem'), $statusCode);
            bluem_error_report_email(
                [
                    'service' => 'mandates',
                    'function' => 'mandates_callback',
                    'message' => $errormessage
                ]
            );

            bluem_dialogs_render_prompt(
                $errormessage
            );
            exit;
        }
        exit;
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
     * @return bool
     */
    private function validateMandate($response, $order, $block_processing = false, $update_metadata = true, $redirect = true, $mandate_id = null, $entrance_code = null)
    {
        $maxAmountResponse = $this->bluem->GetMaximumAmountFromTransactionResponse($response);
        $user_id = $order->get_user_id();

        // @todo: remove mandate ID from parameters and get it here:
        $mandate_id = $response->EMandateStatusUpdate->EMandateStatus->MandateID . "";

        $settings = get_option('bluem_woocommerce_options');
        $maxAmountEnabled = (isset($settings['maxAmountEnabled']) && $settings['maxAmountEnabled'] === "1");
        if ($maxAmountEnabled) {
            $maxAmountFactor = (isset($settings['maxAmountFactor']) ? (float)($settings['maxAmountFactor']) : false);
        } else {
            $maxAmountFactor = 1.0;
        }

        $successful_mandate = false;

        $request_id = "";
        $request_from_db = false;
        if (!empty($mandate_id)) {
            $request_from_db = bluem_db_get_request_by_transaction_id_and_type(
                $mandate_id,
                "mandates"
            );

            $request_id = $request_from_db->id;
        }

        if ($maxAmountEnabled) {

            // NextDeli specific: estimate 10% markup on order total:
            $order_total_plus = (float)$order->get_total() * $maxAmountFactor;

//            if (self::VERBOSE) {
//                if ($maxAmountResponse->amount === 0.0) {
//                    echo "No max amount set";
//                } else {
//                    echo "MAX AMOUNT SET AT {$maxAmountResponse->amount} {$maxAmountResponse->currency}";
//                }
//                echo "<hr>";
//                echo "Totaalbedrag: ";
//                var_dump((float) $order->get_total());
//                echo " | totaalbedrag +10 procent: ";
//                var_dump($order_total_plus);
//                echo "<hr>";
//            }

            if (isset($maxAmountResponse->amount) && $maxAmountResponse->amount !== 0.0) {
                if ($update_metadata) {
//                    if (self::VERBOSE) {
//                        echo "<br>updating user meta: bluem_latest_mandate_amount to value {$maxAmountResponse->amount} - result: ";
//                    }
                    update_user_meta(
                        $user_id,
                        'bluem_latest_mandate_amount',
                        $maxAmountResponse->amount
                    );
                }
                $allowed_margin = ($order_total_plus <= $maxAmountResponse->amount);
//                if (self::VERBOSE) {
//                    echo "binnen machtiging marge?";
//                    var_dump($allowed_margin);
//                }

                if ($allowed_margin) {
                    $successful_mandate = true;
                } elseif ($block_processing) {
                    $order->update_status('pending', esc_html__('Authorization must be signed again because the mandate amount is too low', 'bluem'));

                    $url = $order->get_checkout_payment_url();
                    $order_total_plus_string = str_replace(".", ",", ("" . round($order_total_plus, 2)));
                    bluem_dialogs_render_prompt(
                        wp_kses_post(
                            sprintf(
                            /* translators: %1$s: order total plus 10%, %3$s: max allowed amount, %3$s: URL to payment page */
                                __(
                                    '<p>Het automatische incasso mandaat dat je hebt afgegeven is niet toereikend voor de incassering van het factuurbedrag van jouw bestelling.</p>
<p>De geschatte factuurwaarde van jouw bestelling is EUR %1$s. Het mandaat voor de automatische incasso die je hebt ingesteld is EUR %2$s. Ons advies is om jouw mandaat voor automatische incasso te verhogen of voor "onbeperkt" te kiezen.</p><p><a href="%3$s" target="_self">Klik hier om terug te gaan naar de betalingspagina en een nieuw mandaat af te geven</a></p>',
                                    'bluem'),
                                $order_total_plus_string,
                                $maxAmountResponse->amount,
                                esc_url($url)
                            )
                        ),
                        false
                    );

                    bluem_db_request_log(
                        $request_id,
                        sprintf(
                        /* translators: %1$s: order max amount, %2$s: order link, %3$s: order id, %4$s: order amount, */
                            wp_kses_post(__('User tried to give use this mandate with maxamount &euro; %1$s, but the Order <a href="%2$s" target="_self">ID %3$s</a> grand total including correction is &euro; %4$s. The user is prompted to create a new mandate to fulfill this order.', 'bluem')),
                            $maxAmountResponse->amount,
                            esc_url(admin_url("post.php?post=" . $order->get_id() . "&action=edit")),
                            $order->get_id(),
                            $order_total_plus_string
                        )
                    );
                    exit;
                }
            } else {
                if ($update_metadata) {
//                    if (self::VERBOSE) {
//                        echo "<br>updating user meta: bluem_latest_mandate_amount to value 0 - result: ";
//                    }
                    update_user_meta($user_id, 'bluem_latest_mandate_amount', 0);
                }
                $successful_mandate = true;
            }
        } else {
            // no maxamount check, so just continue;
            $successful_mandate = true;
        }

        if ($update_metadata) {
//            if (self::VERBOSE) {
//                echo "<br>updating user meta: bluem_latest_mandate_validated to value {$successful_mandate} - result: ";
//            }
            update_user_meta(
                $user_id,
                'bluem_latest_mandate_validated',
                $successful_mandate
            );
        }

        if ($successful_mandate) {
            if ($update_metadata) {
                if ($mandate_id !== "") {
//                    if (self::VERBOSE) {
//                        echo "<br>updating user meta: bluem_latest_mandate_id to value {$mandate_id} - result: ";
//                    }
                    update_user_meta(
                        $user_id,
                        'bluem_latest_mandate_id',
                        $mandate_id
                    );
                }
                if ($entrance_code !== "") {
//                    if (self::VERBOSE) {
//                        echo "<br>updating user meta: entranceCode to value {$entrance_code} - result: ";
//                    }
                    update_user_meta(
                        $user_id,
                        'bluem_latest_mandate_entrance_code',
                        $entrance_code
                    );
                }
            }

//            if (self::VERBOSE) {
//                echo "mandaat is succesvol, order kan worden aangepast naar machtiging_goedgekeurd";
//            }

            $order->update_status(
                'processing',
                printf(
                /* translators: %1$s: mandate id, %2$s: request id */
                    esc_html__('Authorization (Mandate ID %1$s, Request ID %2$s) has been obtained and approved', 'bluem'),
                    esc_attr($mandate_id), esc_attr($request_id)
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

            if ($redirect) {
                if (self::VERBOSE) {
                    die();
                }
                $this->bluem_thankyou($order->get_id());
            }

            return true;
        }
        return false;
    }
}
