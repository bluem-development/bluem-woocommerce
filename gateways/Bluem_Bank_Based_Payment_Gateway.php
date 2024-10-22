<?php if (!defined('ABSPATH')) {
    exit;
}

include_once __DIR__ . '/Bluem_Payment_Gateway.php';

abstract class Bluem_Bank_Based_Payment_Gateway extends Bluem_Payment_Gateway
{
    private const EURO_CURRENCY = 'EUR';
    /**
     * @var ?string
     */
    protected $bankSpecificBrandID;

    /**
     * @var ?string
     */
    protected $paymentIdentifier;

    /**
     * Constructor.
     */
    public function __construct($id, $title, $description, $callback = null, $icon = '')
    {
        if (empty($callback)) {
            $callback = home_url('wc-api/' . $this->id . '_callback');
        }
        parent::__construct(
            $id,
            $title,
            $description,
            $callback,
            $icon
        );

        /**
         * Set payment identifier.
         */
        $this->setPaymentIdentifier($this->id);

        // ********** CREATING plugin URLs for specific functions **********
        // adding specific functions for Bank based plugins.
        // The functions webhook and callback NEED TO BE defined in this class though,
        // as they are equal per bank based payment gateway
        add_action('woocommerce_api_' . $this->id . '_callback', array($this, 'bluem_bank_payments_callback'));
        add_action('woocommerce_api_' . $this->id . '_webhook', array($this, 'bluem_bank_payments_webhook'));
    }

    /**
     * Get bank specific brandID.
     */
    protected function getBankSpecificBrandID(): ?string
    {
        return $this->bankSpecificBrandID;
    }

    /**
     * Get payment identifier.
     */
    protected function getPaymentIdentifier()
    {
        $identifier = str_replace(array('bluem_', 'payments_'), '', $this->paymentIdentifier);

        return $identifier;
    }

    /**
     * Define bank specific brandID.
     */
    protected function setBankSpecificBrandID($brandID): void
    {
        $this->bankSpecificBrandID = $brandID;
    }

    /**
     * Define payment identifier.
     */
    protected function setPaymentIdentifier($identifier): void
    {
        $this->paymentIdentifier = $identifier;
    }

    /**
     * Configuring a specific brandID for payments
     */
    protected function methodSpecificConfigurationMixin($config)
    {
        if (!empty($config->bankSpecificBrandID)) {
            $config->brandID = $config->bankSpecificBrandID;
        }
        if (!empty($config->paymentBrandID)) {
            $config->brandID = $config->paymentBrandID;
            // @todo: do this within the Bluem object in a smart way so we don't have to mix in
        }

        if (empty($config->brandID)) {
            if (!empty($config->paymentsIDEALBrandID)) {
                $config->brandID = $config->paymentsIDEALBrandID;
            }
            if (!empty($config->paymentsCreditcardBrandID)) {
                $config->brandID = $config->paymentsCreditcardBrandID;
            }
            if (!empty($config->paymentsPayPalBrandID)) {
                $config->brandID = $config->paymentsPayPalBrandID;
            }
            if (!empty($config->paymentsSofortBrandID)) {
                $config->brandID = $config->paymentsSofortBrandID;
            }
            if (!empty($config->paymentsCarteBancaireBrandID)) {
                $config->brandID = $config->paymentsCarteBancaireBrandID;
            }
        }

        return $config;
    }

    /**
     * Process payment.
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $user_id = $order->get_user_id();
        $user_meta = get_user_meta($user_id);

        $order_id = $order->get_id();
        $customer_id = get_post_meta($order_id, '_customer_user', true);

        $entranceCode = $this->bluem->CreateEntranceCode();

        update_post_meta($order_id, 'bluem_entrancecode', $entranceCode);
        if (!is_null($customer_id) && $customer_id !== "" && (int)$customer_id !== 0) {
            $description = sprintf(
            /* translators:
            %1\$s: customer id
            %2\$s: order id
            */
                esc_html__("Klant %1\$s, Bestelling %2\$s", 'bluem'), $customer_id, $order_id);
        } else {
            $description = esc_html__("Bestelling", 'bluem') . " " . $order_id;
        }

        $bluem_payments_ideal_bic = isset($_POST['bluem_payments_ideal_bic']) ? sanitize_text_field(wp_unslash($_POST['bluem_payments_ideal_bic'])) : '';


        $debtorReference = $order_id;
        $amount = $order->get_total();
        $currency = self::EURO_CURRENCY; // @todo: get dynamically from order
        $dueDateTime = (new DateTimeImmutable())->modify('+1 day');

        try {
            $request = $this->bluem->CreatePaymentRequest(
                $description,
                $debtorReference,
                $amount,
                $dueDateTime->format('Y-m-d H:i:s'),
                $currency,
                $entranceCode,
                home_url(sprintf('wc-api/' . $this->id . '_callback?entranceCode=%s', $entranceCode))
            );
        } catch (Exception $e) {
            return array(
                'exception' => $e->getMessage(),
                'result' => 'failure'
            );
        }

        if (!empty($this->bankSpecificBrandID)) {
            $request->setBrandId($this->getBankSpecificBrandID());
        }

        if (!empty($bluem_payments_ideal_bic)) {
            try {
                $request->selectDebtorWallet($bluem_payments_ideal_bic);
            } catch (Exception $e) {
                return array(
                    'exception' => $e->getMessage(),
                    'result' => 'failure'
                );
            }
        }

        // temp overrides
        $request->paymentReference = str_replace('-', '', $request->paymentReference);
        $request->type_identifier = "createTransaction";
        $request->dueDateTime = $dueDateTime->format(BLUEM_LOCAL_DATE_FORMAT) . ".000Z";
        $request->debtorReturnURL = home_url(sprintf('wc-api/' . $this->id . '_callback?entranceCode=%s', $entranceCode));

        $payload = wp_json_encode([
            'environment' => $this->bluem_config->environment,
            'amount' => $amount,
            'method' => $this->bankSpecificBrandID,
            'currency' => $currency,
            'due_date' => $request->dueDateTime,
            'payment_reference' => $request->paymentReference
        ], JSON_THROW_ON_ERROR);

        // allow third parties to add additional data to the request object through this additional action
        $request = apply_filters(
            'bluem_woocommerce_enhance_payment_request',
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
        // Possible statuses: 'pending', 'processing', 'on-hold', 'completed', 'refunded, 'failed', 'cancelled',

        $order->update_status('pending', esc_html__('Awaiting Bluem Payment Signature', 'bluem'));

        if (isset($response->PaymentTransactionResponse->TransactionURL)) {
            $order->add_order_note(esc_html__("Betalingsproces geïnitieerd", 'bluem'));

            $transactionID = "" . $response->PaymentTransactionResponse->TransactionID;
            update_post_meta($order_id, 'bluem_transactionid', $transactionID);
            $paymentReference = "" . $response->PaymentTransactionResponse->paymentReference;
            update_post_meta($order_id, 'bluem_payment_reference', $paymentReference);
            $debtorReference = "" . $response->PaymentTransactionResponse->debtorReference;
            update_post_meta($order_id, 'bluem_debtor_Reference', $debtorReference);

            // redirect cast to string, for AJAX response handling
            $transactionURL = ($response->PaymentTransactionResponse->TransactionURL . "");

            bluem_db_create_request(
                [
                    'entrance_code' => $entranceCode,
                    'transaction_id' => $transactionID,
                    'transaction_url' => $transactionURL,
                    'user_id' => get_current_user_id(),
                    'timestamp' => gmdate("Y-m-d H:i:s"),
                    'description' => $description,
                    'debtor_reference' => $debtorReference,
                    'type' => $this->getPaymentIdentifier(),
                    'order_id' => $order_id,
                    'payload' => $payload,
                ]
            );

            return array(
                'result' => 'success',
                'redirect' => $transactionURL
            );
        }

        return array(
            'result' => 'failure',
        );
    }

    /**
     * payments_Webhook action
     *
     * @return void
     */
    public function bluem_bank_payments_webhook(): void
    {
        try {
            $webhook = $this->bluem->Webhook();

            if (($webhook->xmlObject ?? null) !== null) {
                if (method_exists($webhook, 'getStatus')) {
                    $webhook_status = $webhook->getStatus();
                }
                if (method_exists($webhook, 'getEntranceCode')) {
                    $entranceCode = $webhook->getEntranceCode();
                }
                if (method_exists($webhook, 'getTransactionID')) {
                    $transactionID = $webhook->getTransactionID();
                }

                $order = $this->getOrder($transactionID);
                if (is_null($order)) {
                    http_response_code(404);
                    echo esc_html__("Error: No order found", 'bluem');
                    exit;
                }
                $order_status = $order->get_status();

                $user_id = $order->get_user_id();

                $user_meta = get_user_meta($user_id);

                if ($webhook_status === "Success") {
                    if ($order_status === "processing") {
                        // order is already marked as processing, nothing more is necessary
                    } elseif ($order_status === "pending") {
                        $order->update_status('processing', esc_html__('Betaling is gelukt en goedgekeurd; via webhook', 'bluem'));
                    }
                } elseif ($webhook_status === "Cancelled") {
                    $order->update_status('cancelled', esc_html__('Betaling is geannuleerd; via webhook', 'bluem'));
                } elseif ($webhook_status === "Open" || $webhook_status === "Pending") {
                    // if the webhook is still open or pending, nothing has to be done yet
                } elseif ($webhook_status === "Expired") {
                    $order->update_status('failed', esc_html__('Betaling is verlopen; via webhook', 'bluem'));
                } else {
                    $order->update_status('failed', esc_html__('Betaling is gefaald: fout of onbekende status; via webhook', 'bluem'));
                }
                http_response_code(200);
                echo 'OK';
                exit;
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo esc_html__("Error: Exception", 'bluem') . esc_html($e->getMessage());
            exit;
        }
    }

    public function getOrderByEntranceCode($entranceCode)
    {
        $orders = wc_get_orders(array(
            'orderby' => 'date',
            'order' => 'DESC',
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
     * @return mixed|null
     */
    private function getOrder(string $transactionID)
    {
        $orders = wc_get_orders(array(
            'orderby' => 'date',
            'order' => 'DESC',
            'bluem_transactionid' => $transactionID
        ));
        if (count($orders) == 0) {
            return null;
        }

        return $orders[0];
    }

    /**
     * payment_Callback function after payment process has been completed by the user
     * @return void
     * @throws Exception
     */
    public function bluem_bank_payments_callback(): void
    {

        echo "Called bluem_bank_payments_callback";
        die();
        if (!isset($_GET['entranceCode'])) {
            $errormessage = esc_html__("Fout: geen juiste entranceCode teruggekregen bij payment_callback. Neem contact op met de webshop en vermeld je contactgegevens.", 'bluem');
            bluem_error_report_email(
                [
                    'service' => 'payments',
                    'function' => 'payments_callback',
                    'message' => $errormessage
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            exit;
        }

        $entranceCode = sanitize_text_field(wp_unslash($_GET['entranceCode']));

        $order = $this->getOrderByEntranceCode($entranceCode);

        if (is_null($order)) {
            $errormessage = sprintf(
            /* translators: %s entrancecode */
                esc_html__("Fout: order niet gevonden in webshop.
            Neem contact op met de webshop en vermeld de code %s bij je gegevens.", 'bluem'),
                $entranceCode);
            bluem_error_report_email(
                [
                    'service' => 'payments',
                    'function' => 'payments_callback',
                    'message' => $errormessage
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            exit;
        }
        $user_id = $order->get_user_id();

        $transactionID = $order->get_meta('bluem_transactionid');
        if (empty($transactionID)) {
            $errormessage = sprintf(
            /* translators: %s: entranceCode */
                esc_html__("Geen transactie ID gevonden. Neem contact op met de webshop en vermeld de code %s bij je gegevens.", 'bluem'), $entranceCode);
            bluem_error_report_email(
                [
                    'service' => 'payments',
                    'function' => 'payments_callback',
                    'message' => $errormessage
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            die();
        }

        $response = $this->bluem->PaymentStatus($transactionID, $entranceCode);

        if (!$response->Status()) {
            $errormessage = sprintf(
            /* translators: %s: error status */
                esc_html__("Fout bij opvragen status: %s. Neem contact op met de webshop en vermeld deze status", 'bluem'), $response->Error());
            bluem_error_report_email(
                [
                    'service' => 'payments',
                    'function' => 'payments_callback',
                    'message' => $errormessage,
                    'response' => $response
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            exit;
        }

        $statusUpdateObject = $response->PaymentStatusUpdate;
        $statusCode = $statusUpdateObject->Status . "";

        $request_from_db = bluem_db_get_request_by_transaction_id($transactionID);

        if ($statusCode !== $request_from_db->status) {
            bluem_db_update_request(
                $request_from_db->id,
                [
                    'status' => $statusCode
                ]
            );
        }

        if ($statusCode === self::PAYMENT_STATUS_SUCCESS) {
            $order->update_status('processing', esc_html__('Payment has been received', 'bluem'));

            $order->add_order_note(esc_html__("Payment process completed", 'bluem'));

            bluem_transaction_notification_email(
                $request_from_db->id
            );

            // Remove cart
            global $woocommerce;
            $woocommerce->cart->empty_cart();

            $this->thank_you_page($order->get_id());
        } elseif ($statusCode === self::PAYMENT_STATUS_FAILURE) {
            $order->update_status('failed', esc_html__('Payment has expired', 'bluem'));
            $order->add_order_note(esc_html__("Payment process not completed", 'bluem'));
            bluem_transaction_notification_email(
                $request_from_db->id
            );
            $errormessage = wp_kses_post(__("Er ging iets mis bij het betalen,
                of je hebt het betaalproces niet voltooid.
                <br>Probeer opnieuw te betalen vanuit je bestellingsoverzicht
                of neem contact op met de webshop
                als het probleem zich blijft voordoen.", 'bluem'));
            bluem_error_report_email(
                [
                    'order_id' => $order->get_id(),
                    'service' => 'payments',
                    'function' => 'payments_callback',
                    'message' => $errormessage
                ]
            );
            bluem_dialogs_render_prompt(
                $errormessage
            );
            exit;
        } elseif ($statusCode === "Cancelled") {
            $order->update_status('cancelled', esc_html__('Payment has been canceled', 'bluem'));


            bluem_transaction_notification_email(
                $request_from_db->id
            );
            bluem_dialogs_render_prompt(esc_html__("Je hebt de betaling geannuleerd", 'bluem'));
            // terug naar order pagina om het opnieuw te proberen?
            exit;
        } elseif ($statusCode === "Open" || $statusCode === "Pending") {
            bluem_transaction_notification_email(
                $request_from_db->id
            );
            bluem_dialogs_render_prompt(esc_html__("De betaling is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch.", 'bluem'));
            // callback pagina beschikbaar houden om het opnieuw te proberen?
            // is simpelweg SITE/wc-api/bluem_callback?transactionID=$transactionID
            exit;
        } elseif ($statusCode === "Expired") {
            $order->update_status('failed', esc_html__('Payment has expired', 'bluem'));
            bluem_transaction_notification_email(
                $request_from_db->id
            );

            bluem_dialogs_render_prompt(esc_html__("Fout: De betaling of het verzoek daartoe is verlopen", 'bluem'));
            exit;
        } else {
            $order->update_status('failed', esc_html__('Payment failed: error or unknown status', 'bluem'));
            bluem_transaction_notification_email(
                $request_from_db->id
            );
            bluem_dialogs_render_prompt(
                sprintf(
                /* translators: %s: status code */
                    esc_html__(
                        "Fout: Onbekende of foutieve status teruggekregen: %s.
                        Neem contact op met de webshop en vermeld deze status",
                        'bluem'
                    ),
                    $statusCode
                )
            );
            exit;
        }
        exit;
    }
}
