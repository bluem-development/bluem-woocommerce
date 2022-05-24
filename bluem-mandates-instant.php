<?php

// @todo: add Woo Product update key if necessary, check https://docs.woocommerce.com/document/create-a-plugin/
// @todo: Localize all error messages to english primarily
// @todo: finish docblocking

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! session_id() ) {
    session_start();
}

use Bluem\BluemPHP\Bluem;

add_action( 'parse_request', 'bluem_mandates_instant_request' );

function bluem_mandates_instant_request()
{
    if (strpos($_SERVER["REQUEST_URI"], "bluem-woocommerce/mandate_instant_request") === false) {
        return;
    }
    
    $bluem_config = bluem_woocommerce_get_config();
    
    $debtorReference = !empty($_GET['debtorreference']) ? $_GET['debtorreference'] : '';
        
    if (!empty($debtorReference))
    {
        $debtorReference = sanitize_text_field( $debtorReference );
        
        $db_results = bluem_db_get_requests_by_keyvalues([
            'debtor_reference' => $debtorReference,
            'status' => 'Success',
        ]);
        
        // Check the sequence type or previous success results
        if ($bluem_config->sequenceType === 'OOFF' || sizeof($db_results) == 0)
        {
            $bluem_config->merchantReturnURLBase = home_url(
                "bluem-woocommerce/mandates_instant_callback"
            );

            $preferences = get_option( 'bluem_woocommerce_options' );

            $bluem = new Bluem( $bluem_config );

            $mandate_id_counter = get_option( 'bluem_woocommerce_mandate_id_counter' );

            if ( ! isset( $mandate_id_counter ) ) {
                $mandate_id_counter = $preferences['mandate_id_counter'];
            }

            $mandate_id = $mandate_id_counter + 1;

            update_option( 'bluem_woocommerce_mandate_id_counter', $mandate_id );

            $request = $bluem->CreateMandateRequest(
                $debtorReference,
                $debtorReference,
                $mandate_id
            );
            
            // Save the necessary data to later request more information and refer to this transaction
            $_SESSION['bluem_mandateId'] = $request->mandateID;
            $_SESSION['bluem_entranceCode'] = $request->entranceCode;
            
            // Actually perform the request.
            try {
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

                // redirect cast to string, necessary for AJAX response handling
                $transactionURL = ( $response->EMandateTransactionResponse->TransactionURL . "" );

                $_SESSION['bluem_recentTransactionURL'] = $transactionURL;

                $db_creation_result = bluem_db_create_request(
                    [
                        'entrance_code'    => $request->entranceCode,
                        'transaction_id'   => $request->mandateID,
                        'transaction_url'  => $transactionURL,
                        'user_id'          => 0,
                        'timestamp'        => date( "Y-m-d H:i:s" ),
                        'description'      => "Mandate request",
                        'debtor_reference' => $debtorReference,
                        'type'             => "mandates",
                        'order_id'         => "",
                        'payload'          => json_encode(
                            [
                                'created_via'        => 'instant_request',
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
            } catch (\Exception $e) {
                var_dump($e->getMessage());
            }
        }
        else
        {
            wp_redirect( $bluem_config->instantMandatesResponseURI . "?result=true" );
            exit;
        }
    }
    exit;
}

add_action( 'parse_request', 'bluem_mandates_instant_callback' );

/**
 * This function is executed at a callback GET request with a given mandateId. This is then, together with the entranceCode in Session, sent for a SUD to the Bluem API.
 *
 * @return void
 */
function bluem_mandates_instant_callback()
{
    if (strpos($_SERVER["REQUEST_URI"], "bluem-woocommerce/mandates_instant_callback") === false) {
        return;
    }

    $bluem_config = bluem_woocommerce_get_config();
    
    try {
        $bluem = new Bluem( $bluem_config );
    } catch ( Exception $e ) {
        // @todo: deal with incorrectly setup Bluem 
    }

    $mandateID = $_SESSION['bluem_mandateId'];

    $entranceCode = $_SESSION['bluem_entranceCode'];

    if (empty($mandateID)) {
        if (!empty($bluem_config->instantMandatesResponseURI)) {
            wp_redirect( $bluem_config->instantMandatesResponseURI . "?result=false&reason=error" );
            exit;
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
        if (!empty($request_from_db->payload)) {
            try {
                $newPayload = json_decode( $request_from_db->payload );
            } catch ( Throwable $th ) {
                $newPayload = new Stdclass;
            }
        } else {
            $newPayload = new Stdclass;
        }

        if ( isset( $response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport ) ) {
            $newPayload->purchaseID = $response->EMandateStatusUpdate->EMandateStatus->PurchaseID . "";
            $newPayload->report     = $response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport;

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
        
        // "De ondertekening is geslaagd";
        if (!empty($bluem_config->instantMandatesResponseURI)) {
            wp_redirect( $bluem_config->instantMandatesResponseURI . "?result=true" );
            exit;
        }
        $errormessage = "Fout: de ondertekening is geslaagd maar er is geen reponse URI opgegeven. Neem contact op met de website om dit technisch probleem aan te geven.";
        bluem_error_report_email(
            [
                'service'  => 'mandates',
                'function' => 'instant_callback',
                'message'  => $errormessage
            ]
        );
        bluem_dialogs_render_prompt( $errormessage );
        exit;
    }
    elseif ($statusCode === "Cancelled")
    {
        // "Je hebt de mandaat ondertekening geannuleerd";
        if (!empty($bluem_config->instantMandatesResponseURI)) {
            wp_redirect( $bluem_config->instantMandatesResponseURI . "?result=false&reason=cancelled" );
            exit;
        }
        $errormessage = "Fout: de transactie is geannuleerd. Probeer het opnieuw.";
        bluem_dialogs_render_prompt( $errormessage );
        exit;
    }
    elseif ($statusCode === "Open" || $statusCode == "Pending")
    {
        // "De mandaat ondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch."
        if (!empty($bluem_config->instantMandatesResponseURI)) {
            wp_redirect( $bluem_config->instantMandatesResponseURI . "?result=false&reason=open" );
            exit;
        }
        $errormessage = "Fout: de transactie staat nog open. Dit kan even duren. Vernieuw deze pagina regelmatig voor de status.";
        bluem_dialogs_render_prompt( $errormessage );
        exit;
    }
    elseif ($statusCode === "Expired")
    {
        // "Fout: De mandaat of het verzoek daartoe is verlopen";
        if (!empty($bluem_config->instantMandatesResponseURI)) {
            wp_redirect( $bluem_config->instantMandatesResponseURI . "?result=false&reason=expired" );
            exit;
        }
        $errormessage = "Fout: de transactie is verlopen. Probeer het opnieuw.";
        bluem_dialogs_render_prompt( $errormessage );
        exit;
    }
    else
    {
        bluem_error_report_email(
            [
                'service'  => 'mandates',
                'function' => 'shortcode_callback',
                'message'  => "Fout: Onbekende of foutieve status teruggekregen: {$statusCode}<br>Neem contact op met de webshop en vermeld deze status; gebruiker wel doorverwezen terug naar site"
            ]
        );
        wp_redirect( $bluem_config->instantMandatesResponseURI . "?result=false&reason=error" );
        exit;
    }
    exit;
}
