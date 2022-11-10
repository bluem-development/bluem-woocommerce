<?php

// @todo: add Woo Product update key if necessary, check https://docs.woocommerce.com/document/create-a-plugin/
// @todo: Localize all error messages to english primarily
// @todo: finish docblocking

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty(session_id()) ) {
    session_start();
}

use Bluem\BluemPHP\Bluem;

add_action( 'parse_request', 'bluem_mandate_shortcode_execute' );

/**
 * This function is called POST from the form rendered on a page or post
 *
 * @return void
 */
function bluem_mandate_shortcode_execute()
{
    if (substr($_SERVER["REQUEST_URI"], - 43) !== "bluem-woocommerce/mandate_shortcode_execute") {
        return;
    }

    global $current_user;

    if (isset($_POST['bluem-submitted']))
    {
        $debtorReference = "";

        $bluem_config = bluem_woocommerce_get_config();

        $bluem_config->merchantReturnURLBase = home_url(
            "bluem-woocommerce/mandate_shortcode_callback"
        );

        // Check for recurring mode
        if ($bluem_config->sequenceType === 'RCUR')
        {
            if (!empty($_SESSION['bluem_debtorreference']))
            {
                $debtorReference = $_SESSION['bluem_debtorreference'];

                $db_query = [
                    'debtor_reference' => $debtorReference,
                    'user_id' => get_current_user_id(),
                    'status' => 'Success',
                ];

                // Check for a successful transaction
                $db_results = bluem_db_get_requests_by_keyvalues($db_query);

                if ($db_results !== false && is_array($db_results) && sizeof($db_results) > 0) {
                    $mandateID = $db_results[0]->transaction_id;

                    $_SESSION['bluem_mandateId'] = $mandateID;

                    if (!empty($current_user)) {
                        if (current_user_can('edit_user', $current_user->ID)) {
                            update_user_meta( $current_user->ID, "bluem_mandates_validated", true );
                            update_user_meta( $current_user->ID, "bluem_latest_mandate_id", $mandateID );
                        }
                    }

                    wp_redirect( home_url( $bluem_config->thanksPageURL ) . "?result=true" );

                    exit;
                }
            }
            elseif (!empty($_COOKIE['bluem_debtorreference']))
            {
                $debtorReference = $_COOKIE['bluem_debtorreference'];

                $db_query = [
                    'debtor_reference' => $debtorReference,
                    'user_id' => get_current_user_id(),
                    'status' => 'Success',
                ];

                // Check for a successful transaction
                $db_results = bluem_db_get_requests_by_keyvalues($db_query);

                if ($db_results !== false && is_array($db_results) && sizeof($db_results) > 0) {
                    $mandateID = $db_results[0]->transaction_id;

                    $_SESSION['bluem_mandateId'] = $mandateID;

                    if (!empty($current_user)) {
                        if (current_user_can('edit_user', $current_user->ID)) {
                            update_user_meta( $current_user->ID, "bluem_mandates_validated", true );
                            update_user_meta( $current_user->ID, "bluem_latest_mandate_id", $mandateID );
                        }
                    }

                    wp_redirect( home_url( $bluem_config->thanksPageURL ) . "?result=true" );

                    exit;
                }
            }
            else
            {
                if (!empty($_POST["bluem_debtorReference"]))
                {
                    $debtorReference = sanitize_text_field( $_POST["bluem_debtorReference"] );

                    $_SESSION['bluem_debtorreference'] = $debtorReference;

                    $db_query = [
                        'debtor_reference' => $debtorReference,
                        'user_id' => get_current_user_id(),
                        'status' => 'Success',
                    ];

                    // Check for a successful transaction
                    $db_results = bluem_db_get_requests_by_keyvalues($db_query);

                    if ($db_results !== false && is_array($db_results) && sizeof($db_results) > 0) {
                        $mandateID = $db_results[0]->transaction_id;

                        $_SESSION['bluem_mandateId'] = $mandateID;

                        if (!empty($current_user)) {
                            if (current_user_can('edit_user', $current_user->ID)) {
                                update_user_meta( $current_user->ID, "bluem_mandates_validated", true );
                                update_user_meta( $current_user->ID, "bluem_latest_mandate_id", $mandateID );
                            }
                        }

                        wp_redirect( home_url( $bluem_config->thanksPageURL ) . "?result=true" );

                        exit;
                    }
                }
                else
                {
                    if (is_user_logged_in()) {
                        $debtorReference = $current_user->user_nicename();

                        $_SESSION['bluem_debtorreference'] = $debtorReference;
                    }
                }
            }
        }
        elseif ($bluem_config->sequenceType === 'OOFF')
        {
            if (!empty($_POST["bluem_debtorReference"])) {
                $debtorReference = sanitize_text_field( $_POST["bluem_debtorReference"] );

                $_SESSION['bluem_debtorreference'] = $debtorReference;
            } else {
                if ( is_user_logged_in() ) {
                    $debtorReference = $current_user->user_nicename();

                    $_SESSION['bluem_debtorreference'] = $debtorReference;
                }
            }
        }

        $preferences = get_option( 'bluem_woocommerce_options' );

        // Convert UTF-8 to ISO
        if (!empty($bluem_config->eMandateReason)) {
            $bluem_config->eMandateReason = utf8_decode($bluem_config->eMandateReason);
        } else {
            $bluem_config->eMandateReason = "Incasso machtiging " . $debtorReference;
        }

        $bluem = new Bluem( $bluem_config );

        $mandate_id_counter = get_option( 'bluem_woocommerce_mandate_id_counter' );

        if ( ! isset( $mandate_id_counter ) ) {
            $mandate_id_counter = $preferences['mandate_id_counter'];
        }

        $mandate_id = $mandate_id_counter + 1;

        update_option( 'bluem_woocommerce_mandate_id_counter', $mandate_id );

        $request = $bluem->CreateMandateRequest(
            $debtorReference,
            $current_user->ID,
            $mandate_id
        );

        // Save the necessary data to later request more information and refer to this transaction
        $_SESSION['bluem_mandateId'] = $request->mandateID;
        $_SESSION['bluem_entranceCode'] = $request->entranceCode;

        if (!empty($current_user))
        {
            if (current_user_can('edit_user', $current_user->ID)) {
                update_user_meta(
                    $current_user->ID,
                    "bluem_latest_mandate_entrance_code",
                    $request->entranceCode . ""
                );
            }
        }

        // Actually perform the request.
        $response = $bluem->PerformRequest( $request );

        if ( ! isset( $response->EMandateTransactionResponse->TransactionURL ) ) {
            $msg = "Er ging iets mis bij het aanmaken van de transactie.<br>
            Vermeld onderstaande informatie aan het websitebeheer:";

            if ( isset( $response->EMandateTransactionResponse->Error->ErrorMessage ) ) {
                $msg .= "<br>" .
                        $response->EMandateTransactionResponse->Error->ErrorMessage;
            } elseif ( get_class( $response ) == "Bluem\BluemPHP\ErrorBluemResponse" ) {
                $msg .= "<br>" .
                        $response->Error();
            } else {
                $msg .= "<br>Algemene fout";
            }
            bluem_error_report_email(
                [
                    'service'  => 'mandates',
                    'function' => 'shortcode_execute',
                    'message'  => $msg
                ]
            );
            bluem_dialogs_render_prompt( $msg );
            exit;
        }

        $mandate_id = $response->EMandateTransactionResponse->MandateID . "";

        $_SESSION['bluem_mandateId'] = $mandate_id;

        if (!empty($current_user))
        {
            if (current_user_can('edit_user', $current_user->ID)) {
                update_user_meta(
                    $current_user->ID,
                    "bluem_latest_mandate_id",
                    $mandate_id
                );
            }
        }

        // redirect cast to string, necessary for AJAX response handling
        $transactionURL = ( $response->EMandateTransactionResponse->TransactionURL . "" );

        $_SESSION['bluem_recentTransactionURL'] = $transactionURL;

        $db_creation_result = bluem_db_create_request(
            [
                'entrance_code'    => $request->entranceCode,
                'transaction_id'   => $request->mandateID,
                'transaction_url'  => $transactionURL,
                'user_id'          => get_current_user_id(),
                'timestamp'        => date( "Y-m-d H:i:s" ),
                'description'      => "Mandate request",
                'debtor_reference' => $debtorReference,
                'type'             => "mandates",
                'order_id'         => "",
                'payload'          => json_encode(
                    [
                        'created_via'        => 'shortcode',
                        'environment'        => $bluem->environment,
                        'created_mandate_id' => $mandate_id,
                    ]
                )
            ]
        );

        if ( ob_get_length() !== false && ob_get_length() > 0 ) {
            ob_clean();
        }

        ob_start();
        wp_redirect( $transactionURL );

        exit;
    }
    exit;
}

add_action( 'parse_request', 'bluem_mandate_mandate_shortcode_callback' );
/**
 * This function is executed at a callback GET request with a given mandateId. This is then, together with the entranceCode in Session, sent for a SUD to the Bluem API.
 *
 * @return void
 */
function bluem_mandate_mandate_shortcode_callback()
{
    if (strpos($_SERVER["REQUEST_URI"], "bluem-woocommerce/mandate_shortcode_callback") === false) {
        return;
    }

    global $current_user;

    $bluem_config = bluem_woocommerce_get_config();

    $bluem_config->merchantReturnURLBase = home_url( 'wc-api/bluem_mandates_callback' );

    try {
        $bluem = new Bluem( $bluem_config );
    } catch ( Exception $e ) {
        // @todo: deal with incorrectly setup Bluem
    }

    // @todo: .. then use request-based approach soon as first check, then fallback to user meta check.
    if (!empty($current_user->ID)) {
        $mandateID = get_user_meta( $current_user->ID, "bluem_latest_mandate_id", true );
        $entranceCode = get_user_meta( $current_user->ID, "bluem_latest_mandate_entrance_code", true );
    } else {
        $mandateID = $_SESSION['bluem_mandateId'];
        $entranceCode = $_SESSION['bluem_entranceCode'];
    }

    if (!isset($_GET['mandateID'])) {
        if ($bluem_config->thanksPageURL !== "") {
            wp_redirect( home_url( $bluem_config->thanksPageURL ) . "?result=false&reason=error" );
            // echo "<p>Er is een fout opgetreden. De incassomachtiging is geannuleerd.</p>";
            return;
        }
        $errormessage = "Fout: geen juist mandaat id teruggekregen bij callback. Neem contact op met de webshop en vermeld je contactgegevens.";
        bluem_error_report_email(
            [
                'service'  => 'mandates',
                'function' => 'shortcode_callback',
                'message'  => $errormessage
            ]
        );
        bluem_dialogs_render_prompt( $errormessage );
        exit;
    }

    if (empty($entranceCode)) {
        $errormessage = "Fout: Entrancecode is niet set; kan dus geen mandaat opvragen";
        bluem_error_report_email(
            [
                'service'  => 'mandates',
                'function' => 'shortcode_callback',
                'message'  => $errormessage
            ]
        );
        bluem_dialogs_render_prompt( $errormessage );
        exit;
    }

    $response = $bluem->MandateStatus( $mandateID, $entranceCode );

    if (!$response->Status()) {
        $errormessage = "Fout bij opvragen status: " . $response->Error() . "
        <br>Neem contact op met de webshop en vermeld deze status";
        bluem_error_report_email(
            [
                'service'  => 'mandates',
                'function' => 'shortcode_callback',
                'message'  => $errormessage
            ]
        );
        bluem_dialogs_render_prompt( $errormessage );
        exit;
    }

    $statusUpdateObject = $response->EMandateStatusUpdate;
    $statusCode = $statusUpdateObject->EMandateStatus->Status . "";

    $request_from_db = bluem_db_get_request_by_transaction_id_and_type(
        $mandateID,
        "mandates"
    );

    if ($statusCode !== $request_from_db->status) {
        bluem_db_update_request(
            $request_from_db->id,
            [
                'status' => $statusCode
            ]
        );
        // also update locally for email notification
        $request_from_db->status = $statusCode;
    }

    bluem_transaction_notification_email(
        $request_from_db->id
    );

    // Handling the response.
    if ($statusCode === "Success")
    {
        // Define a cookie so that this will be recognised the next time
        setcookie('bluem_debtorreference', $debtorReference, time()+60*60*24*30, '/', $_SERVER['SERVER_NAME'], false, true);

        if (!empty($current_user)) {
            if (current_user_can('edit_user', $current_user->ID)) {
                update_user_meta( $current_user->ID, "bluem_mandates_validated", true );
            }
        }

        if ($request_from_db->payload !== "") {
            try {
                $newPayload = json_decode( $request_from_db->payload );
            } catch ( Throwable $th ) {
                $newPayload = new Stdclass;
            }
        } else {
            $newPayload = new Stdclass;
        }

        if (isset($response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport)) {
            $newPayload->purchaseID = $response->EMandateStatusUpdate->EMandateStatus->PurchaseID . "";
            $newPayload->report = $response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport;

            bluem_db_update_request(
                $request_from_db->id,
                [
                    'payload' => json_encode( $newPayload )
                ]
            );
        }

        $request_from_db = bluem_db_get_request_by_transaction_id_and_type(
            $mandateID,
            "mandates"
        );

        wp_redirect( home_url( $bluem_config->thanksPageURL ) . "?result=true" );
        exit;
    }
    elseif ($statusCode === "Cancelled")
    {
        // "Je hebt de mandaat ondertekening geannuleerd";
        wp_redirect( home_url( $bluem_config->thanksPageURL ) . "?result=false&reason=cancelled" );
        exit;
    }
    elseif ($statusCode === "Open" || $statusCode == "Pending")
    {
        // "De mandaat ondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch."
        wp_redirect( home_url( $bluem_config->thanksPageURL ) . "?result=false&reason=open" );
        exit;
    }
    elseif ($statusCode === "Expired")
    {
        // "Fout: De mandaat of het verzoek daartoe is verlopen";
        wp_redirect( home_url( $bluem_config->thanksPageURL ) . "?result=false&reason=expired" );
        exit;
    }
    else
    {
        // "Fout: Onbekende of foutieve status";
        bluem_error_report_email(
            [
                'service'  => 'mandates',
                'function' => 'shortcode_callback',
                'message'  => "Fout: Onbekende of foutieve status teruggekregen: {$statusCode}<br>Neem contact op met de webshop en vermeld deze status; gebruiker wel doorverwezen terug naar site"
            ]
        );
        wp_redirect( home_url( $bluem_config->thanksPageURL ) . "?result=false&reason=error" );
        exit;
    }
}

add_shortcode( 'bluem_machtigingsformulier', 'bluem_mandateform' );

/**
 * Rendering the static form
 * Shortcode: `[bluem_machtigingsformulier]`
 *
 * @return void
 */
function bluem_mandateform()
{
    global $current_user;

    $bluem_config = bluem_woocommerce_get_config();

    $bluem_config->merchantReturnURLBase = home_url(
        'wc-api/bluem_mandates_callback'
    );

    $bluem = new Bluem( $bluem_config );

    $user_allowed = apply_filters(
        'bluem_woocommerce_mandate_shortcode_allow_user',
        true
    );

    if (!$user_allowed) {
        return '';
    }

    $validated = false;

    /**
     * Check if user is logged in.
     */
    if (is_user_logged_in())
    {
        $mandateID = get_user_meta( $current_user->ID, "bluem_latest_mandate_id", true );

        $validated_db = get_user_meta( $current_user->ID, "bluem_mandates_validated", true );

        // While be zero (string) when disabled
        if (!empty($mandateID) && $validated_db !== '0')
        {
            // Check for recurring mode
            if ($bluem_config->sequenceType === 'RCUR') {
                $db_query = [
                    'transaction_id' => $mandateID,
                    'user_id' => get_current_user_id(),
                    'status' => 'Success',
                ];

                $db_results = bluem_db_get_requests_by_keyvalues($db_query);

                if ($db_results !== false && is_array($db_results) && sizeof($db_results) > 0) {
                    $mandateID = $db_results[0]->transaction_id;

                    $validated = true;
                }
            }
        }
    }
    else
    {
        /**
         * Visitor not logged in. Check other storages.
         */
        if (!empty($_SESSION['bluem_mandateId']))
        {
            $mandateID = $_SESSION['bluem_mandateId'];

            // Check for recurring mode
            if ($bluem_config->sequenceType === 'RCUR') {
                $db_query = [
                    'transaction_id' => $mandateID,
                    'user_id' => get_current_user_id(),
                    'status' => 'Success',
                ];

                $db_results = bluem_db_get_requests_by_keyvalues($db_query);

                if ($db_results !== false && is_array($db_results) && sizeof($db_results) > 0) {
                    $mandateID = $db_results[0]->transaction_id;

                    $validated = true;
                }
            }
        }
        elseif (!empty($_SESSION['bluem_debtorreference']))
        {
            $debtorReference = $_SESSION['bluem_debtorreference'];

            // Check for recurring mode
            if ($bluem_config->sequenceType === 'RCUR') {
                $db_query = [
                    'debtor_reference' => $debtorReference,
                    'user_id' => get_current_user_id(),
                    'status' => 'Success',
                ];

                $db_results = bluem_db_get_requests_by_keyvalues($db_query);

                if ($db_results !== false && is_array($db_results) && sizeof($db_results) > 0) {
                    $mandateID = $db_results[0]->transaction_id;

                    $validated = true;
                }
            }
        }
        elseif (!empty($_COOKIE['bluem_debtorreference']))
        {
            $debtorReference = $_COOKIE['bluem_debtorreference'];

            // Check for recurring mode
            if ($bluem_config->sequenceType === 'RCUR') {
                $db_query = [
                    'debtor_reference' => $debtorReference,
                    'user_id' => get_current_user_id(),
                    'status' => 'Success',
                ];

                $db_results = bluem_db_get_requests_by_keyvalues($db_query);

                if ($db_results !== false && is_array($db_results) && sizeof($db_results) > 0) {
                    $mandateID = $db_results[0]->transaction_id;

                    $validated = true;
                }
            }
        }
    }

    /**
     * Check if eMandate is valide..
     */
    if ($validated !== false) {
        return "<p>Bedankt voor je machtiging met machtiging ID: <span class='bluem-mandate-id'>$mandateID</span></p>";
    } else {
        $html = '<form action="' . home_url( 'bluem-woocommerce/mandate_shortcode_execute' ) . '" method="post">';
        $html .= '<p>Je moet nog een automatische incasso machtiging afgeven.</p>';

        if (!empty($bluem_config->debtorReferenceFieldName)) {
            $html .= '<p>' . $bluem_config->debtorReferenceFieldName . ' (verplicht) <br/>';
            $html .= '<input type="text" name="bluem_debtorReference" required /></p>';
        } else {
            $html .= '<input type="hidden" name="bluem_debtorReference" value="' . (!empty($current_user->ID) ? $current_user->ID : 'visitor-' . time()) . '"  />';
        }

        $html .= '<p><input type="submit" name="bluem-submitted" class="bluem-woocommerce-button bluem-woocommerce-button-mandates" value="Machtiging proces starten.."></p>';
        $html .= '</form>';

        return $html;
    }
}

add_filter( 'bluem_woocommerce_mandate_shortcode_allow_user', 'bluem_woocommerce_mandate_shortcode_allow_user_function', 10, 1 );

function bluem_woocommerce_mandate_shortcode_allow_user_function( $valid = true )
{
    // do something with the response, use this in third-party extensions of this system
    return $valid;
}
