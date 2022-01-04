<?php

// @todo: add Woo Product update key if necessary, check https://docs.woocommerce.com/document/create-a-plugin/
// @todo: Localize all error messages to english primarily
// @todo: finish docblocking

if (!defined('ABSPATH')) {
    exit;
}

use Bluem\BluemPHP\Bluem;
use Carbon\Carbon;

add_action('parse_request', 'bluem_mandate_shortcode_execute');
/**
 * This function is called POST from the form rendered on a page or post
 *
 * @return void
 */
function bluem_mandate_shortcode_execute()
{
    if (substr($_SERVER["REQUEST_URI"], -43) !== "bluem-woocommerce/mandate_shortcode_execute") {
        // any other request
        return;
    }

    global $current_user;
    // if the submit button is clicked, send the email
    if (isset($_POST['bluem-submitted'])) {
        $debtorReference = "";
        if (isset($_POST["bluem_debtorReference"])) {
            $debtorReference = sanitize_text_field($_POST["bluem_debtorReference"]);
        } else {
            if (is_user_logged_in()) {
                $debtorReference = $current_user->user_nicename();
            } else {
                $debtorReference = "";
            }
        }
        $bluem_config = bluem_woocommerce_get_config();
        $bluem_config->merchantReturnURLBase = home_url(
            "bluem-woocommerce/mandate_shortcode_callback"
        );

        $preferences = get_option('bluem_woocommerce_options');


        $bluem_config->eMandateReason = "Incasso machtiging ".$debtorReference;


        $bluem = new Bluem($bluem_config);

        $mandate_id_counter = get_option('bluem_woocommerce_mandate_id_counter');

        if (!isset($mandate_id_counter)) {
            $mandate_id_counter = $preferences['mandate_id_counter'];
        }
        $mandate_id = $mandate_id_counter + 1;
        update_option('bluem_woocommerce_mandate_id_counter', $mandate_id);


        $request = $bluem->CreateMandateRequest(
            $debtorReference,
            $current_user->ID,
            $mandate_id
        );

        // Save the necessary data to later request more information and refer to this transaction
        $_SESSION['bluem_mandateId'] = $request->mandateID;
        $_SESSION['bluem_entranceCode'] = $request->entranceCode;

        update_user_meta(
            $current_user->ID,
            "bluem_latest_mandate_entrance_code",
            $request->entranceCode.""
        );

        // Actually perform the request.
        $response = $bluem->PerformRequest($request);


        if (!isset($response->EMandateTransactionResponse->TransactionURL)) {
            $msg = "Er ging iets mis bij het aanmaken van de transactie.<br>
            Vermeld onderstaande informatie aan het websitebeheer:";

            if (isset($response->EMandateTransactionResponse->Error->ErrorMessage)) {
                $msg.= "<br>" .
                $response->EMandateTransactionResponse->Error->ErrorMessage;
            } elseif (get_class($response)=="Bluem\BluemPHP\ErrorBluemResponse") {
                $msg.= "<br>" .
                $response->Error();
            } else {
                $msg .= "<br>Algemene fout";
            }
            bluem_error_report_email(
                [
                    'service'=>'mandates',
                    'function'=>'shortcode_execute',
                    'message'=>$msg
                ]
            );
            bluem_dialogs_render_prompt($msg);
            exit;
        }

        $mandate_id = $response->EMandateTransactionResponse->MandateID . "";
        $_SESSION['bluem_mandateId'] =$mandate_id;
        update_user_meta(
            $current_user->ID,
            "bluem_latest_mandate_id",
            $mandate_id
        );

        // redirect cast to string, necessary for AJAX response handling
        $transactionURL = ($response->EMandateTransactionResponse->TransactionURL . "");
        $_SESSION['bluem_recentTransactionURL'] = $transactionURL;

        $db_creation_result = bluem_db_create_request(
            [
            'entrance_code'=>$request->entranceCode,
            'transaction_id'=>$request->mandateID,
            'transaction_url'=>$transactionURL,
            'user_id'=> get_current_user_id(),
            'timestamp'=> date("Y-m-d H:i:s"),
            'description'=>"Mandate request",
            'debtor_reference'=>$debtorReference,
            'type'=>"mandates",
            'order_id'=>"",
            'payload'=>json_encode(
                [
                'created_via'=>'shortcode',
                'environment'=>$bluem->environment,
                'created_mandate_id'=>$mandate_id,
                ]
            )
            ]
        );
        

        if (ob_get_length()!==false && ob_get_length()>0) {
            ob_clean();
        }
        ob_start();
        wp_redirect($transactionURL);
        exit;
    }
    exit;
}

add_action('parse_request', 'bluem_mandate_mandate_shortcode_callback');
/**
 * This function is executed at a callback GET request with a given mandateId. This is then, together with the entranceCode in Session, sent for a SUD to the Bluem API.
 *
 * @return void
 */
function bluem_mandate_mandate_shortcode_callback()
{
    global $current_user;

    if (strpos($_SERVER["REQUEST_URI"], "bluem-woocommerce/mandate_shortcode_callback") === false) {
        return;
    }

    $bluem_config = bluem_woocommerce_get_config();
    $bluem_config->merchantReturnURLBase = home_url('wc-api/bluem_mandates_callback');

    try {
        $bluem = new Bluem( $bluem_config );
    } catch ( Exception $e ) {
        // @todo: deal with incorrectly setup Bluem 
    }

    // this is leading for shortcode approaches
    $validated = get_user_meta($current_user->ID, "bluem_mandates_validated", true);
    
    // @todo: .. then use request-based approach soon as first check, then fallback to user meta check.
    if(is_user_logged_in()) {
        
        $mandateID = get_user_meta($current_user->ID, "bluem_latest_mandate_id", true);
        $entranceCode = get_user_meta($current_user->ID, "bluem_latest_mandate_entrance_code", true);
    } else {
        $mandateID = $_SESSION['bluem_mandateId'];
        $entranceCode = $_SESSION['bluem_entranceCode'];
    }

    if (!isset($_GET['mandateID'])) {
        if ($bluem_config->thanksPageURL !== "") {
            wp_redirect(home_url($bluem_config->thanksPageURL) . "?result=false&reason=error");
            // echo "<p>Er is een fout opgetreden. De incassomachtiging is geannuleerd.</p>";
            return;
        }
        $errormessage = "Fout: geen juist mandaat id teruggekregen bij callback. Neem contact op met de webshop en vermeld je contactgegevens.";
        bluem_error_report_email(
            [
                'service'=>'mandates',
                'function'=>'shortcode_callback',
                'message'=>$errormessage
            ]
        );
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

//     $entranceCode = $_SESSION['bluem_entranceCode'];
    if (!isset($entranceCode) || $entranceCode == "") {
        $errormessage= "Fout: Entrancecode is niet set; kan dus geen mandaat opvragen";
        bluem_error_report_email(
            [
                'service'=>'mandates',
                'function'=>'shortcode_callback',
                'message'=>$errormessage
            ]
        );
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }
//    var_dump($_SESSION);
//    var_dump($entranceCode);
//    var_dump($mandateID);
//    die();
    
    $response = $bluem->MandateStatus($mandateID, $entranceCode);

    if (!$response->Status()) {
        $errormessage = "Fout bij opvragen status: " . $response->Error() . "
        <br>Neem contact op met de webshop en vermeld deze status";
        bluem_error_report_email(
            [
                'service'=>'mandates',
                'function'=>'shortcode_callback',
                'message'=>$errormessage
            ]
        );
        bluem_dialogs_render_prompt($errormessage);
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
            'status'=>$statusCode
            ]
        );
        // also update locally for email notification
        $request_from_db->status = $statusCode;
    }

    bluem_transaction_notification_email(
        $request_from_db->id
    );

    // Handling the response.
    if ($statusCode === "Success") {
        update_user_meta($current_user->ID, "bluem_mandates_validated", true);

        if ($request_from_db->payload!=="") {
            try {
                $newPayload = json_decode($request_from_db->payload);
            } catch (Throwable $th) {
                $newPayload = new Stdclass;
            }
        } else {
            $newPayload = new Stdclass;
        }


        if (isset($response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport)) {
            $newPayload->purchaseID = $response->EMandateStatusUpdate->EMandateStatus->PurchaseID."";
            $newPayload->report = $response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport;
            
            bluem_db_update_request(
                $request_from_db->id,
                [
                    'payload'=>json_encode($newPayload)
                    ]
            );
        }

        $request_from_db = bluem_db_get_request_by_transaction_id_and_type(
            $mandateID,
            "mandates"
        );

        wp_redirect(home_url($bluem_config->thanksPageURL) . "?result=true");
        exit;
    } elseif ($statusCode === "Cancelled") {
        // "Je hebt de mandaat ondertekening geannuleerd";
        wp_redirect(home_url($bluem_config->thanksPageURL) . "?result=false&reason=cancelled");
        exit;
    } elseif ($statusCode === "Open" || $statusCode == "Pending") {
        // "De mandaat ondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch."
        wp_redirect(home_url($bluem_config->thanksPageURL) . "?result=false&reason=open");
        exit;
    } elseif ($statusCode === "Expired") {
        // "Fout: De mandaat of het verzoek daartoe is verlopen";
        wp_redirect(home_url($bluem_config->thanksPageURL) . "?result=false&reason=expired");
        exit;
    } else {
        bluem_error_report_email(
            [
                'service'=>'mandates',
                'function'=>'shortcode_callback',
                'message'=> "Fout: Onbekende of foutieve status teruggekregen: {$statusCode}<br>Neem contact op met de webshop en vermeld deze status; gebruiker wel doorverwezen terug naar site"
            ]
        );
        wp_redirect(home_url($bluem_config->thanksPageURL) . "?result=false&reason=error");
        exit;
    }
}

add_shortcode('bluem_machtigingsformulier', 'bluem_mandateform');

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

    $bluem = new Bluem($bluem_config);

    $user_allowed = apply_filters(
        'bluem_woocommerce_mandate_shortcode_allow_user',
        true
    );

    if (!$user_allowed) {
        return '';
    }

    $validated = 0;
    
    if(is_user_logged_in()) {
        $mandateID = get_user_meta($current_user->ID, "bluem_latest_mandate_id", true);
        $validated = get_user_meta($current_user->ID, "bluem_mandates_validated", true);
    } else {
        if(isset($_SESSION['bluem_mandateId']) && $_SESSION['bluem_mandateId']!=="") {
            $validated = 1;
            $mandateID = $_SESSION['bluem_mandateId'];
        }
    }

    if ($validated===1) {
        return "Bedankt voor je machtiging met machtiging ID: <span class='bluem-mandate-id'>$mandateID</span>";
    } else {
        $html = '<form action="' . home_url('bluem-woocommerce/mandate_shortcode_execute') . '" method="post">';
        $html .= '<p>Je moet nog een automatische incasso machtiging afgeven.';
        // $html= $bluem_config->debtorReferenceFieldName . ' (verplicht) <br/>';
        $html .= '<input type="hidden" name="bluem_debtorReference" value="' .$current_user->ID. '"  />';
        $html .= '</p><p>
            <input 
            type="submit" 
            name="bluem-submitted" 
             class="bluem-woocommerce-button bluem-woocommerce-button-mandates" 
             value="Machtiging proces starten..">
             </p>';
        $html .= '</form>';
    return $html;
    //ob_get_clean();
    }

}

add_filter('bluem_woocommerce_mandate_shortcode_allow_user', 'bluem_woocommerce_mandate_shortcode_allow_user_function', 10, 1);

function bluem_woocommerce_mandate_shortcode_allow_user_function($valid = true)
{
    return $valid;
    // do something with the response, use this in third-party extensions of this system
}
