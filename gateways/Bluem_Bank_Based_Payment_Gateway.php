<?php

if (! defined('ABSPATH')) {
    exit;
}

include_once __DIR__ . '/Bluem_Payment_Gateway.php';

// possible status constants: 'pending', 'processing', 'on-hold', 'completed', 'refunded, 'failed', 'cancelled'
const BLUEM_WC_STATUS_PENDING = 'pending';
const BLUEM_WC_STATUS_PROCESSING = 'processing';
const BLUEM_WC_STATUS_ON_HOLD = 'on-hold';
const BLUEM_WC_STATUS_COMPLETED = 'completed';
const BLUEM_WC_STATUS_REFUNDED = 'refunded';
const BLUEM_WC_STATUS_FAILED = 'failed';
const BLUEM_WC_STATUS_CANCELLED = 'cancelled';


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
        add_action('woocommerce_api_' . $this->id . '_callback', [ $this, 'bluem_bank_payments_callback' ]);
        add_action('woocommerce_api_' . $this->id . '_webhook', [ $this, 'bluem_bank_payments_webhook' ]);
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
        $identifier = str_replace([ 'bluem_', 'payments_' ], '', $this->paymentIdentifier);

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
     * Resolve the WooCommerce order transition for a Bluem payment status.
     *
     * In-progress statuses must never turn an order into a failure, and a
     * completed or failed payment must not rewrite an order that has already
     * moved beyond the pending state.
     */
    public static function resolvePaymentStatusTransition(string $paymentStatus, string $currentOrderStatus): ?string
    {
        return match ($paymentStatus) {
            self::PAYMENT_STATUS_SUCCESS => $currentOrderStatus === BLUEM_WC_STATUS_PENDING
                ? BLUEM_WC_STATUS_PROCESSING
                : null,
            self::PAYMENT_STATUS_FAILURE => $currentOrderStatus === BLUEM_WC_STATUS_PENDING
                ? BLUEM_WC_STATUS_FAILED
                : null,
            'Cancelled' => BLUEM_WC_STATUS_CANCELLED,
            self::PAYMENT_STATUS_NEW, 'Open', 'Pending' => null,
            'Expired' => BLUEM_WC_STATUS_FAILED,
            default => BLUEM_WC_STATUS_FAILED,
        };
    }

    /**
     * Check that a callback or webhook has enough correlation data to locate
     * exactly one payment order.
     */
    public static function hasValidPaymentCorrelation($transactionID, $entranceCode): bool
    {
        return is_scalar($transactionID)
            && is_scalar($entranceCode)
            && trim((string) $transactionID) !== ''
            && trim((string) $entranceCode) !== '';
    }

    /**
     * Apply a resolved payment transition to a WooCommerce order.
     *
     * @return string|null The new order status, or null when no transition is
     *                     allowed for the current order state.
     */
    public static function applyPaymentStatusTransition($order, string $paymentStatus, string $context = 'callback'): ?string
    {
        $targetOrderStatus = self::resolvePaymentStatusTransition($paymentStatus, $order->get_status());
        if ($targetOrderStatus === null) {
            return null;
        }

        $message = match ($context . ':' . $paymentStatus) {
            'callback:Success' => esc_html__('Payment has been received (callback)', 'bluem'),
            'callback:Failure' => esc_html__('Payment has expired', 'bluem'),
            'callback:Cancelled' => esc_html__('Payment has been canceled', 'bluem'),
            'callback:Expired' => esc_html__('Payment has expired', 'bluem'),
            'webhook:Success' => esc_html__('Payment succeeded and was approved via webhook', 'bluem'),
            'webhook:Cancelled' => esc_html__('Payment was canceled via webhook', 'bluem'),
            'webhook:Expired' => esc_html__('Payment expired via webhook', 'bluem'),
            default => $context === 'webhook'
                ? esc_html__('Payment failed: error or unknown status via webhook', 'bluem')
                : esc_html__('Payment failed: error or unknown status', 'bluem'),
        };

        $order->update_status($targetOrderStatus, $message);

        return $targetOrderStatus;
    }

    /**
     * Configuring a specific brandID for payments
     */
    protected function methodSpecificConfigurationMixin($config)
    {
        if (! empty($config->bankSpecificBrandID)) {
            $config->brandID = $config->bankSpecificBrandID;
        }
        if (! empty($config->paymentBrandID)) {
            $config->brandID = $config->paymentBrandID;
            // @todo: do this within the Bluem object in a smart way so we don't have to mix in
        }

        if (empty($config->brandID)) {
            if (! empty($config->paymentsIDEALBrandID)) {
                $config->brandID = $config->paymentsIDEALBrandID;
            }
            if (! empty($config->paymentsCreditcardBrandID)) {
                $config->brandID = $config->paymentsCreditcardBrandID;
            }
            if (! empty($config->paymentsPayPalBrandID)) {
                $config->brandID = $config->paymentsPayPalBrandID;
            }
            if (! empty($config->paymentsSofortBrandID)) {
                $config->brandID = $config->paymentsSofortBrandID;
            }
            if (! empty($config->paymentsCarteBancaireBrandID)) {
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

        $user_id   = $order->get_user_id();
        $user_meta = get_user_meta($user_id);

        $order_id    = $order->get_id();
        $customer_id = $order->get_customer_id();

        $entranceCode = $this->bluem->CreateEntranceCode();

        $order->update_meta_data('bluem_entrancecode', $entranceCode);
        $order->save();
        if (! is_null($customer_id) && $customer_id !== "" && (int) $customer_id !== 0) {
            $description = sprintf(
                /* translators:
                %1\$s: customer id
                %2\$s: order id
                */
                esc_html__("Customer %1\$s, Order %2\$s", 'bluem'),
                $customer_id,
                $order_id
            );
        } else {
            $description = esc_html__("Order", 'bluem') . " " . $order_id;
        }

        $bluem_payments_ideal_bic = isset($_POST['bluem_payments_ideal_bic']) ? sanitize_text_field(wp_unslash($_POST['bluem_payments_ideal_bic'])) : '';

        $debtorReference = $order_id;
        $amount          = $order->get_total();
        $currency        = self::EURO_CURRENCY; // @todo: get dynamically from order
        $dueDateTime     = (new DateTimeImmutable())->modify('+1 day');

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
            return [
                'exception' => $e->getMessage(),
                'result'    => 'failure',
            ];
        }

        if (! empty($this->bankSpecificBrandID)) {
            $request->setBrandId($this->getBankSpecificBrandID());
        }

        if (! empty($bluem_payments_ideal_bic)) {
            try {
                $request->selectDebtorWallet($bluem_payments_ideal_bic);
            } catch (Exception $e) {
                return [
                    'exception' => $e->getMessage(),
                    'result'    => 'failure',
                ];
            }
        }

        // temp overrides
        $request->paymentReference = str_replace('-', '', $request->paymentReference);
        $request->type_identifier  = "createTransaction";
        $request->dueDateTime      = $dueDateTime->format(BLUEM_LOCAL_DATE_FORMAT) . ".000Z";
        $request->debtorReturnURL  = home_url(sprintf('wc-api/' . $this->id . '_callback?entranceCode=%s', $entranceCode));


        // allow third parties to add additional data to the request object through this additional action
        $request = apply_filters(
            'bluem_woocommerce_enhance_payment_request',
            $request
        );



        try {
            $response = $this->bluem->PerformRequest($request);
        } catch (Exception $e) {
            return [
                'exception' => $e->getMessage(),
                'result'    => 'failure',
            ];
        }



        if (!empty($response->PaymentTransactionResponse->Error)) {
            //            $data = [
            //                'description'=> $description,
            //                'debtorReference'=> $debtorReference,
            //                'amount'=> $amount,
            //                'dueDateTime'=> $dueDateTime->format( 'Y-m-d H:i:s' ),
            //                'currency'=> $currency,
            //                'entranceCode'=> $entranceCode,
            //                'bic?'=>$bluem_payments_ideal_bic,
            //                'returnUrl'=>home_url( sprintf( 'wc-api/' . $this->id . '_callback?entranceCode=%s', $entranceCode ) ),
            //            ];
            //        var_dump("Processing Payment for order id: ", $data);
            //        var_dump($request);
            //        var_dump($request->XmlString());
            //        echo "<HR>";
            //        var_dump($response);
            //        var_dump($response->Status());
            //        var_dump($response->Error());
            //        var_dump($response->asXML());
            //            return;
            return [
                'exception' => $response->PaymentTransactionResponse->Error,
                'result'    => 'failure',
            ];
        }


        // Possible statuses: BLUEM_WC_STATUS_PENDING, 'processing', 'on-hold', 'completed', 'refunded, 'failed', 'cancelled',

        $order->update_status(BLUEM_WC_STATUS_PENDING, esc_html__('Awaiting Bluem Payment Signature', 'bluem'));

        if (isset($response->PaymentTransactionResponse->TransactionURL)) {
            $order->add_order_note(esc_html__("Payment process initiated", 'bluem'));

            $transactionID = "" . $response->PaymentTransactionResponse->TransactionID;
            $order->update_meta_data('bluem_transactionid', $transactionID);
            $paymentReference = "" . $response->PaymentTransactionResponse->paymentReference;
            $order->update_meta_data('bluem_payment_reference', $paymentReference);
            $debtorReference = "" . $response->PaymentTransactionResponse->debtorReference;
            $order->update_meta_data('bluem_debtor_Reference', $debtorReference);
            $order->save();

            // redirect cast to string, for AJAX response handling
            $transactionURL = ($response->PaymentTransactionResponse->TransactionURL . "");

            $payload = wp_json_encode([
                'environment'       => $this->bluem_config->environment,
                'amount'            => $amount,
                'method'            => $this->bankSpecificBrandID,
                'currency'          => $currency,
                'due_date'          => $request->dueDateTime,
                'payment_reference' => $request->paymentReference,
            ], JSON_THROW_ON_ERROR);

            bluem_db_create_request(
                [
                    'entrance_code'    => $entranceCode,
                    'transaction_id'   => $transactionID,
                    'transaction_url'  => $transactionURL,
                    'user_id'          => get_current_user_id(),
                    'timestamp'        => gmdate("Y-m-d H:i:s"),
                    'description'      => $description,
                    'debtor_reference' => $debtorReference,
                    'type'             => $this->getPaymentIdentifier(),
                    'order_id'         => $order_id,
                    'payload'          => $payload,
                ]
            );

            return [
                'result'   => 'success',
                'redirect' => $transactionURL,
            ];
        }

        return [
            'message' => sprintf(
                /* translators: %s: error message returned by Bluem */
                esc_html__('No payment URL received from Bluem. Error: %s', 'bluem'),
                $response->Error() ?? esc_html__('Unknown error', 'bluem')
            ),
            'result' => 'failure',
        ];
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
                $webhook_status = '';
                $entranceCode = '';
                $transactionID = '';

                if (method_exists($webhook, 'getStatus')) {
                    $webhook_status = (string) $webhook->getStatus();
                }
                if (method_exists($webhook, 'getEntranceCode')) {
                    $entranceCode = (string) $webhook->getEntranceCode();
                }
                if (method_exists($webhook, 'getTransactionID')) {
                    $transactionID = (string) $webhook->getTransactionID();
                }

                if (! self::hasValidPaymentCorrelation($transactionID, $entranceCode)) {
                    http_response_code(400);
                    echo esc_html__('Error: Missing payment correlation data', 'bluem');
                    exit;
                }

                $order = $this->getOrder($transactionID);
                if (is_null($order)) {
                    http_response_code(404);
                    echo esc_html__("Error: No order found", 'bluem');
                    exit;
                }
                $order_status = $order->get_status();
                $targetOrderStatus = self::resolvePaymentStatusTransition($webhook_status, $order_status);
                $user_id = $order->get_user_id();

                $user_meta = get_user_meta($user_id);

                if ($webhook_status === "Success") {
                    if ($order_status === BLUEM_WC_STATUS_PROCESSING) {
                        // order is already marked as processing, nothing more is necessary
                        $order->add_order_note(
                            sprintf(
                                /* translators: %s: order status */
                                esc_html__("Received payment completed webhook notification, but status not updated - it was already %s", 'bluem'),
                                $order_status
                            )
                        );
                    } elseif ($targetOrderStatus === BLUEM_WC_STATUS_PROCESSING) {
                        self::applyPaymentStatusTransition($order, $webhook_status, 'webhook');
                    }
                } elseif ($targetOrderStatus !== null) {
                    self::applyPaymentStatusTransition($order, $webhook_status, 'webhook');
                }
                http_response_code(200);
                echo 'OK';
                exit;
            }
        } catch (Exception $e) {
            bluem_error_report_email(
                [
                    'service'  => 'payments',
                    'function' => 'payments_webhook_exception',
                    'message'  => "Exception: " . $e->getMessage(),
                ]
            );
            http_response_code(500);
            echo esc_html__("Error: Exception", 'bluem') . esc_html($e->getMessage());

            exit;
        }
    }

    public function getOrderByEntranceCode($entranceCode)
    {
        $orders = wc_get_orders([
            'orderby'            => 'date',
            'order'              => 'DESC',
            'bluem_entrancecode' => $entranceCode,
        ]);
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
        $orders = wc_get_orders([
            'orderby'             => 'date',
            'order'               => 'DESC',
            'bluem_transactionid' => $transactionID,
        ]);
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
        if (! isset($_GET['entranceCode'])) {
            $errormessage = esc_html__("Error: no valid entranceCode was returned during payment_callback. Please contact the webshop and mention your contact details.", 'bluem');
            bluem_error_report_email(
                [
                    'service'  => 'payments',
                    'function' => 'payments_callback',
                    'message'  => $errormessage,
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
                esc_html__("Error: order not found in webshop.
            Please contact the webshop and mention the code %s with your details.", 'bluem'),
                $entranceCode
            );
            bluem_error_report_email(
                [
                    'service'  => 'payments',
                    'function' => 'payments_callback',
                    'message'  => $errormessage,
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            exit;
        }
        $user_id = $order->get_user_id();

        $transactionID = $order->get_meta('bluem_transactionid', true);
        if (! self::hasValidPaymentCorrelation($transactionID, $entranceCode)) {
            $errormessage = sprintf(
                /* translators: %s: entranceCode */
                esc_html__("No transaction ID found. Please contact the webshop and mention the code %s with your details.", 'bluem'),
                $entranceCode
            );
            bluem_error_report_email(
                [
                    'service'  => 'payments',
                    'function' => 'payments_callback',
                    'message'  => $errormessage,
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            die();
        }

        try {
            $response = $this->bluem->PaymentStatus($transactionID, $entranceCode);
        } catch (Exception $e) {
            $errormessage = sprintf(
                /* translators: %s: error message */
                esc_html__('Error retrieving status: %s. Please contact the webshop and mention this status.', 'bluem'),
                $e->getMessage()
            );
            bluem_error_report_email([
                'service' => 'payments',
                'function' => 'payments_callback',
                'message' => $errormessage,
                'order_id' => $order->get_id(),
                'transactionID' => $transactionID,
                'entranceCode' => $entranceCode,
            ]);
            bluem_dialogs_render_prompt($errormessage);
            exit;
        }

        if (! $response->Status()) {
            $errormessage = sprintf(
                /* translators: %s: error message or status returned by Bluem */
                esc_html__("Error retrieving status: %s. Please contact the webshop and mention this status.", 'bluem'),
                $response->Error()
            );
            bluem_error_report_email(
                [
                    'service'  => 'payments',
                    'function' => 'payments_callback',
                    'message'  => $errormessage,
                    'response' => $response,
                    'transactionID' => $transactionID,
                    'entranceCode' => $entranceCode,
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            exit;
        }

        $statusUpdateObject = $response->PaymentStatusUpdate;
        $statusCode         = $statusUpdateObject->Status . "";

        $request_from_db = bluem_db_get_request_by_transaction_id($transactionID);

        if ($request_from_db && $statusCode !== $request_from_db->status) {
            bluem_db_update_request(
                $request_from_db->id,
                [
                    'status' => $statusCode,
                ]
            );
        }

        $statusBeforeCallback = $order->get_status();
        $targetOrderStatus    = self::resolvePaymentStatusTransition($statusCode, $statusBeforeCallback);

        if ($statusCode === self::PAYMENT_STATUS_SUCCESS) {
            // Only update the payment status if it was not already 'processing'
            if ($targetOrderStatus === BLUEM_WC_STATUS_PROCESSING) {
                self::applyPaymentStatusTransition($order, $statusCode);
            } else {
                $order->add_order_note(sprintf(
                    /* translators: %1$s: actual status %2$s: Entrance Code */
                    esc_html__('Received payment completed callback, but status was already %1$s. EntranceCode: %2$s', 'bluem'),
                    $statusBeforeCallback,
                        $_GET['entranceCode'] ?? ''));
            }

            if ($request_from_db) {
                bluem_transaction_notification_email($request_from_db->id);
            }

            // Remove cart
            global $woocommerce;
            if (isset($woocommerce->cart) && is_object($woocommerce->cart)) {
                $woocommerce->cart->empty_cart();
            }

            $this->thank_you_page($order->get_id());
        } elseif ($statusCode === self::PAYMENT_STATUS_FAILURE) {
            if ($targetOrderStatus === BLUEM_WC_STATUS_FAILED) {
                self::applyPaymentStatusTransition($order, $statusCode);
            }

            $order->add_order_note(esc_html__("Payment process not completed", 'bluem'));
            if ($request_from_db) {
                bluem_transaction_notification_email($request_from_db->id);
            }
            $errormessage = wp_kses_post(__("Something went wrong while paying,
                or you did not complete the payment process.
                <br>Try paying again from your order overview
                or contact the webshop
                if the problem persists.", 'bluem'));
            bluem_error_report_email(
                [
                    'order_id' => $order->get_id(),
                    'service'  => 'payments',
                    'function' => 'payments_callback',
                    'message'  => $errormessage,
                ]
            );
            bluem_dialogs_render_prompt(
                $errormessage
            );
            exit;
        } elseif ($statusCode === "Cancelled") {
            self::applyPaymentStatusTransition($order, $statusCode);


            if ($request_from_db) {
                bluem_transaction_notification_email($request_from_db->id);
            }
            bluem_dialogs_render_prompt(esc_html__("You canceled the payment", 'bluem'));
            // terug naar order pagina om het opnieuw te proberen?
            exit;
        } elseif (in_array($statusCode, [ self::PAYMENT_STATUS_NEW, "Open", "Pending" ], true)) {
            if ($request_from_db) {
                bluem_transaction_notification_email($request_from_db->id);
            }
            bluem_dialogs_render_prompt(esc_html__("The payment has not been confirmed yet. This may take a moment but happens automatically.", 'bluem'));
            // callback pagina beschikbaar houden om het opnieuw te proberen?
            // is simpelweg SITE/wc-api/bluem_callback?transactionID=$transactionID
            exit;
        } elseif ($statusCode === "Expired") {
            self::applyPaymentStatusTransition($order, $statusCode);
            if ($request_from_db) {
                bluem_transaction_notification_email($request_from_db->id);
            }

            bluem_dialogs_render_prompt(esc_html__("Error: the payment or payment request has expired", 'bluem'));
            exit;
        } else {
            self::applyPaymentStatusTransition($order, $statusCode);
            bluem_error_report_email(
                [
                    'service'  => 'payments',
                    'function' => 'payments_callback',
                    'message'  => "Payment failed: error or unknown status: " . ( $statusCode ?: '?' ),
                    'status'   => $statusCode ?: null,
                    'order_id' => $order->get_id(),
                    'order_status' => $order->get_status(),
                    'transactionID' => $transactionID,
                    'entranceCode' => $entranceCode,
                    'response' => $response,
                ]
            );
            if ($request_from_db) {
                bluem_transaction_notification_email($request_from_db->id);
            }
            bluem_dialogs_render_prompt(
                sprintf(
                    /* translators: %s: status code */
                    esc_html__(
                        "Error: unknown or invalid status received: %s.
                        Please contact the webshop and mention this status.",
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
