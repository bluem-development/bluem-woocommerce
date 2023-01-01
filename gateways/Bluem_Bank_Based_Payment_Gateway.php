<?php

use Carbon\Carbon;

include_once __DIR__ . '/Bluem_Payment_Gateway.php';

abstract class Bluem_Bank_Based_Payment_Gateway extends Bluem_Payment_Gateway
{
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
        if ( empty( $callback ) ) {
            $callback = home_url( 'wc-api/' . $this->id . '_callback' );
        }
        parent::__construct(
            $id, $title, $description, $callback, $icon
        );

        /**
         * Set payment identifier.
        */
        $this->setPaymentIdentifier($this->id);

        // ********** CREATING plugin URLs for specific functions **********
        // adding specific functions for Bank based plugins.
        // The functions webhook and callback NEED TO BE defined in this class though,
        // as they are equal per bank based payment gateway
        add_action('woocommerce_api_' . $this->id . '_callback', array( $this, 'bluem_bank_payments_callback' ));
        add_action('woocommerce_api_' . $this->id . '_webhook', array( $this, 'bluem_bank_payments_webhook' ));
    }

    /**
     * Get bank specific brandID.
     */
    protected function getBankSpecificBrandID()
    {
        return $this->bankSpecificBrandID;
    }

    /**
     * Get payment identifier.
     */
    protected function getPaymentIdentifier()
    {
        $identifier = str_replace('bluem_', '', $this->paymentIdentifier);
        $identifier = str_replace('payments_', '', $identifier);

        return $identifier;
    }

    /**
     * Define bank specific brandID.
     */
    protected function setBankSpecificBrandID($brandID)
    {
        $this->bankSpecificBrandID = $brandID;
    }

    /**
     * Define payment identifier.
     */
    protected function setPaymentIdentifier($identifier)
    {
        $this->paymentIdentifier = $identifier;
    }

    /**
     * Configuring a specific brandID for payments
     */
    protected function methodSpecificConfigurationMixin( $config )
    {
        if ( !empty($config->bankSpecificBrandID) ) {
            $config->brandID = $config->bankSpecificBrandID;
        }
        if ( !empty( $config->paymentBrandID ) ) {
            $config->brandID = $config->paymentBrandID;
            // @todo: do this within the Bluem object in a smart way so we don't have to mix in
        }

        return $config;
    }

    /**
     * Process payment.
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order( $order_id );

        $user_id   = $order->get_user_id();
        $user_meta = get_user_meta( $user_id );

        $order_id    = $order->get_id();
        $customer_id = get_post_meta( $order_id, '_customer_user', true );

        $entranceCode = $this->bluem->CreateEntranceCode();

        update_post_meta( $order_id, 'bluem_entrancecode', $entranceCode );
        if ( ! is_null( $customer_id ) && $customer_id !== "" && (int)$customer_id !== 0 ) {
            $description = "Klant $customer_id Bestelling $order_id";
        } else {
            $description = "Bestelling $order_id";
        }

        $debtorReference = $order_id;
        $amount          = $order->get_total();
        $currency        = "EUR"; // get dynamically from order
        $dueDateTime     = Carbon::now()->addDay();

        try {
            $request = $this->bluem->CreatePaymentRequest(
                $description,
                $debtorReference,
                $amount,
                $dueDateTime,
                $currency,
                $entranceCode,
                home_url( sprintf( 'wc-api/' . $this->id . '_callback?entranceCode=%s', $entranceCode ) ),
                str_replace( '-', '', $request->paymentReference )
            );
        } catch ( Exception $e ) {
            // @todo: handle exception
        }

        if ( !empty( $this->bankSpecificBrandID ) ) {
            $request->setBrandId($this->getBankSpecificBrandID());
        }

        // temp overrides
        $request->paymentReference = str_replace( '-', '', $request->paymentReference );
        $request->type_identifier  = "createTransaction";
        $request->dueDateTime      = $dueDateTime->format( BLUEM_LOCAL_DATE_FORMAT ) . ".000Z";
        $request->debtorReturnURL  = home_url( sprintf( 'wc-api/' . $this->id . '_callback?entranceCode=%s', $entranceCode ) );

        $payload = json_encode( [
            'environment'       => $this->bluem_config->environment,
            'amount'            => $amount,
            'method'            => $this->bankSpecificBrandID,
            'currency'          => $currency,
            'due_date'          => $request->dueDateTime,
            'payment_reference' => $request->paymentReference
        ] );

        // allow third parties to add additional data to the request object through this additional action
        $request = apply_filters(
            'bluem_woocommerce_enhance_payment_request',
            $request
        );

        try {
            $response = $this->bluem->PerformRequest( $request );
        } catch ( Exception $e ) {
            // @todo: handle exception
        }
        // Possible statuses: 'pending', 'processing', 'on-hold', 'completed', 'refunded, 'failed', 'cancelled',

        $order->update_status( 'pending', __( 'Awaiting Bluem Payment Signature', 'wc-gateway-bluem' ) );

        if ( isset( $response->PaymentTransactionResponse->TransactionURL ) ) {
            $order->add_order_note( __( "Betalingsproces geïnitieerd" ) );

            $transactionID = "" . $response->PaymentTransactionResponse->TransactionID;
            update_post_meta( $order_id, 'bluem_transactionid', $transactionID );
            $paymentReference = "" . $response->PaymentTransactionResponse->paymentReference;
            update_post_meta( $order_id, 'bluem_payment_reference', $paymentReference );
            $debtorReference = "" . $response->PaymentTransactionResponse->debtorReference;
            update_post_meta( $order_id, 'bluem_debtor_Reference', $debtorReference );

            // redirect cast to string, for AJAX response handling
            $transactionURL = ( $response->PaymentTransactionResponse->TransactionURL . "" );

            bluem_db_create_request(
                [
                    'entrance_code'    => $entranceCode,
                    'transaction_id'   => $transactionID,
                    'transaction_url'  => $transactionURL,
                    'user_id'          => get_current_user_id(),
                    'timestamp'        => date( "Y-m-d H:i:s" ),
                    'description'      => $description,
                    'debtor_reference' => $debtorReference,
                    'type'             => $this->getPaymentIdentifier(),
                    'order_id'         => $order_id,
                    'payload'          => $payload,
                ]
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
     * payments_Webhook action
     *
     * @return void
     */
    public function bluem_bank_payments_webhook()
    {
        if ( $_GET['env'] && is_string( $_GET['env'] )
             && in_array(
                 sanitize_text_field( $_GET['env'] ),
                 [ 'test', 'prod' ]
             )
        ) {
            $env = sanitize_text_field( $_GET['env'] );
        } else {
            $env = "test";
        }

        try {
            $this->bluem->Webhook();
        } catch ( Exception $e ) {
            // @todo: handle exception
        }

        // @todo: continue webhook specifics
        // @todo: remove obsolete mandate references, this code below is to be deleted

        $entranceCode  = $statusUpdateObject->entranceCode . "";
        $transactionID = $statusUpdateObject->PaymentStatus->MandateID . "";

        $webhook_status = $statusUpdateObject->PaymentStatus->Status . "";

        $order = $this->getOrder( $transactionID );
        if ( is_null( $order ) ) {
            echo "Error: No order found";
            exit;
        }
        $order_status = $order->get_status();

        if ( self::VERBOSE ) {
            echo "order_status: $order_status" . PHP_EOL;
            echo "webhook_status: $webhook_status" . PHP_EOL;
        }

        $user_id   = $user_id = $order->get_user_id();
        $user_meta = get_user_meta( $user_id );

        // Todo: if max amount comes back from webhook (it should) then it can be accessed here
        // if (isset($user_meta['bluem_latest_mandate_amount'][0])) {
        // 	$mandate_amount = $user_meta['bluem_latest_mandate_amount'][0];
        // } else {
        // }


        if ( isset( $statusUpdateObject->PaymentStatus->AcceptanceReport->MaxAmount ) ) {
            $mandate_amount = (float) ( $statusUpdateObject->PaymentStatus->AcceptanceReport->MaxAmount . "" );
        } else {
            $mandate_amount = 0.0;    // mandate amount is not set, so it is unlimited
        }
        if ( self::VERBOSE ) {
            var_dump( $mandate_amount );
            echo PHP_EOL;
            die();
        }

        if ( self::VERBOSE ) {
            echo "mandate_amount: $mandate_amount" . PHP_EOL;
        }

        $mandate_successful = false;

        if ( $mandate_amount !== 0.0 ) {
            $order_price      = $order->get_total();
            $max_order_amount = ( $order_price * 1.1 );
            if ( self::VERBOSE ) {
                echo "max_order_amount: $max_order_amount" . PHP_EOL;
            }

            if ( $mandate_amount >= $max_order_amount ) {
                $mandate_successful = true;
                if ( self::VERBOSE ) {
                    echo "payment is enough" . PHP_EOL;
                }
            } else if ( self::VERBOSE ) {
                echo "payment is too small" . PHP_EOL;
            }
        }
        if ( $webhook_status === "Success" ) {
//                if ($order_status === "processing") {
            // order is already marked as processing, nothing more is necessary
//                } else
            // check if maximum of order does not exceed mandate size based on user metadata
            if ( ( $order_status === "pending" ) && $mandate_successful ) {
                $order->update_status( 'processing', __( 'Betaling is gelukt en goedgekeurd; via webhook', 'wc-gateway-bluem' ) );
            }
        } elseif ( $webhook_status === "Cancelled" ) {
            $order->update_status( 'cancelled', __( 'Betaling is geannuleerd; via webhook', 'wc-gateway-bluem' ) );

//            elseif ($webhook_status === "Open" || $webhook_status == "Pending") {
            // if the webhook is still open or pending, nothing has to be done yet
        } elseif ( $webhook_status === "Expired" ) {
            $order->update_status( 'failed', __( 'Betaling is verlopen; via webhook', 'wc-gateway-bluem' ) );
        } else {
            $order->update_status( 'failed', __( 'Betaling is gefaald: fout of onbekende status; via webhook', 'wc-gateway-bluem' ) );
        }
        exit;
    }

    public function getOrderByEntranceCode( $entranceCode )
    {
        $orders = wc_get_orders( array(
            'orderby'            => 'date',
            'order'              => 'DESC',
            'bluem_entrancecode' => $entranceCode
        ) );
        if ( count( $orders ) == 0 ) {
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
    private function getOrder( string $transactionID )
    {
        $orders = wc_get_orders( array(
            'orderby'             => 'date',
            'order'               => 'DESC',
            'bluem_transactionid' => $transactionID
        ) );
        if ( count( $orders ) == 0 ) {
            return null;
        }

        return $orders[0];
    }

    /**
     * payment_Callback function after payment process has been completed by the user
     * @return void
     * @throws Exception
     */
    public function bluem_bank_payments_callback()
    {
        if ( ! isset( $_GET['entranceCode'] ) ) {
            $errormessage = "Fout: geen juiste entranceCode teruggekregen bij payment_callback. Neem contact op met de webshop en vermeld je contactgegevens.";
            bluem_error_report_email(
                [
                    'service'  => 'payments',
                    'function' => 'payments_callback',
                    'message'  => $errormessage
                ]
            );
            bluem_dialogs_render_prompt( $errormessage );
            exit;
        }

        $entranceCode = sanitize_text_field( $_GET['entranceCode'] );

        $order = $this->getOrderByEntranceCode( $entranceCode );

        if ( is_null( $order ) ) {
            $errormessage = "Fout: order niet gevonden in webshop.
            Neem contact op met de webshop en vermeld de code $entranceCode bij je gegevens.";
            bluem_error_report_email(
                [
                    'service'  => 'payments',
                    'function' => 'payments_callback',
                    'message'  => $errormessage
                ]
            );
            bluem_dialogs_render_prompt( $errormessage );
            exit;
        }
        $user_id = $order->get_user_id();

        $transactionID = $order->get_meta( 'bluem_transactionid' );
        if ( $transactionID == "" ) {
            $errormessage = "No transaction ID found. Neem contact op met de webshop en vermeld de code $entranceCode bij je gegevens.";
            bluem_error_report_email(
                [
                    'service'  => 'payments',
                    'function' => 'payments_callback',
                    'message'  => $errormessage
                ]
            );
            bluem_dialogs_render_prompt( $errormessage );
            die();
        }

        $response = $this->bluem->PaymentStatus( $transactionID, $entranceCode );

        if ( ! $response->Status() ) {
            $errormessage = "Fout bij opvragen status: " . $response->Error() . "<br>Neem contact op met de webshop en vermeld deze status";
            bluem_error_report_email(
                [
                    'service'  => 'payments',
                    'function' => 'payments_callback',
                    'message'  => $errormessage,
                    'response' => $response
                ]
            );
            bluem_dialogs_render_prompt( $errormessage );
            exit;
        }

        if ( self::VERBOSE ) {
            var_dump( "mandateid: " . $transactionID );
            var_dump( "entrancecode: " . $entranceCode );
            echo "<hr>";
            var_dump( $response );
            echo "<hr>";
        }

        $statusUpdateObject = $response->PaymentStatusUpdate;
        $statusCode         = $statusUpdateObject->Status . "";

        $request_from_db = bluem_db_get_request_by_transaction_id( $transactionID );

        if ( $statusCode !== $request_from_db->status ) {
            bluem_db_update_request(
                $request_from_db->id,
                [
                    'status' => $statusCode
                ]
            );
        }

        if ( $statusCode === self::PAYMENT_STATUS_SUCCESS ) {
            $order->update_status( 'processing', __( 'Betaling is binnengekomen', 'wc-gateway-bluem' ) );

            $order->add_order_note( __( "Betalingsproces voltooid" ) );

            bluem_transaction_notification_email(
                $request_from_db->id
            );

            // Remove cart
            global $woocommerce;
            $woocommerce->cart->empty_cart();

            $this->thank_you_page( $order->get_id() );
        } elseif ( $statusCode === self::PAYMENT_STATUS_FAILURE ) {
            $order->update_status( 'failed', __( 'Betaling is verlopen', 'wc-gateway-bluem' ) );
            $order->add_order_note( __( "Betalingsproces niet voltooid" ) );
            bluem_transaction_notification_email(
                $request_from_db->id
            );
            $errormessage = "Er ging iets mis bij het betalen,
                of je hebt het betaalproces niet voltooid.
                <br>Probeer opnieuw te betalen vanuit je bestellingsoverzicht
                of neem contact op met de webshop
                als het probleem zich blijft voordoen.";
            bluem_error_report_email(
                [
                    'order_id' => $order->get_id(),
                    'service'  => 'payments',
                    'function' => 'payments_callback',
                    'message'  => $errormessage
                ]
            );
            bluem_dialogs_render_prompt(
                $errormessage
            );
            exit;
        } elseif ( $statusCode === "Cancelled" ) {
            $order->update_status( 'cancelled', __( 'Betaling is geannuleerd', 'wc-gateway-bluem' ) );


            bluem_transaction_notification_email(
                $request_from_db->id
            );
            bluem_dialogs_render_prompt( "Je hebt de betaling geannuleerd" );
            // terug naar order pagina om het opnieuw te proberen?
            exit;
        } elseif ( $statusCode === "Open" || $statusCode == "Pending" ) {
            bluem_transaction_notification_email(
                $request_from_db->id
            );
            bluem_dialogs_render_prompt( "De betaling is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch." );
            // callback pagina beschikbaar houden om het opnieuw te proberen?
            // is simpelweg SITE/wc-api/bluem_callback?transactionID=$transactionID
            exit;
        } elseif ( $statusCode === "Expired" ) {
            $order->update_status( 'failed', __( 'Betaling is verlopen', 'wc-gateway-bluem' ) );
            bluem_transaction_notification_email(
                $request_from_db->id
            );

            bluem_dialogs_render_prompt( "Fout: De betaling of het verzoek daartoe is verlopen" );
            exit;
        } else {
            $order->update_status( 'failed', __( 'Betaling is gefaald: fout of onbekende status', 'wc-gateway-bluem' ) );
            bluem_transaction_notification_email(
                $request_from_db->id
            );
            bluem_dialogs_render_prompt( "Fout: Onbekende of foutieve status teruggekregen: $statusCode<br>Neem contact op met de webshop en vermeld deze status" );
            exit;
        }
        exit;
    }
}
