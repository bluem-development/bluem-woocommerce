<?php


// @todo: add Woo Product update key if necessary, check https://docs.woocommerce.com/document/create-a-plugin/
// @todo: Localize all error messages to english primarily
// @todo: finish docblocking

if (!defined('ABSPATH')) {
    exit;
}



use Bluem\BluemPHP\Integration;
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
        $debtorReference = sanitize_text_field($_POST["bluem_debtorReference"]);
        $bluem_config = _get_bluem_config();
        $bluem_config->merchantReturnURLBase = home_url(
            "bluem-woocommerce/mandate_shortcode_callback"
        );
        // var_dump($bluem_config);

        $preferences = get_option('bluem_woocommerce_options');
        $bluem = new Integration($bluem_config);
        // update_option('bluem_woocommerce_mandate_id_counter', 1114);
        // update_option('bluem_woocommerce_mandate_id_counter', 111112);

        $mandate_id_counter = get_option('bluem_woocommerce_mandate_id_counter');


        // var_dump($preferences);
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
        // var_dump($request);

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
            echo "Er ging iets mis bij het aanmaken van de transactie.<br>Vermeld onderstaande informatie aan het websitebeheer:<br><pre>";
            var_dump($response);
            echo "</pre>";
            if (isset($response->EMandateTransactionResponse->Error->ErrorMessage)) {
                echo "<br>Response: " . 
                $response->EMandateTransactionResponse->Error->ErrorMessage;
                // var_dump($response);
            }
            exit;
        }
        
        $_SESSION['bluem_mandateId'] =$mandate_id;
        $mandate_id = $response->EMandateTransactionResponse->MandateID . "";
        update_user_meta(
            $current_user->ID, "bluem_latest_mandate_id", $mandate_id
        );
    
        // redirect cast to string, necessary for AJAX response handling
        $transactionURL = ($response->EMandateTransactionResponse->TransactionURL . "");

        $_SESSION['bluem_recentTransactionURL'] = $transactionURL;
        ob_clean();
        ob_start();
        wp_redirect($transactionURL);
        exit;
    }
    exit;
    
}

/* ******** CALLBACK ****** */
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

    $bluem_config = _get_bluem_config();
    $bluem_config->merchantReturnURLBase = home_url('wc-api/bluem_mandates_callback');

    $bluem = new Integration($bluem_config);


    $validated = get_user_meta($current_user->ID, "bluem_mandates_validated", true);
    $mandateID = get_user_meta($current_user->ID, "bluem_latest_mandate_id", true);
    $entranceCode = get_user_meta($current_user->ID, "bluem_latest_mandate_entrance_code", true);



    if (!isset($_GET['mandateID'])) {
        if ($bluem_config->thanksPageURL !== "") {
            wp_redirect(home_url($bluem_config->thanksPageURL) . "?result=false&reason=error");
            // echo "<p>Er is een fout opgetreden. De incassomachtiging is geannuleerd.</p>";
            return;
        }
        echo "Fout: geen juist mandaat id teruggekregen bij callback. Neem contact op met de webshop en vermeld je contactgegevens.";
        exit;
    }

    // $entranceCode = $_SESSION['bluem_entranceCode'];
    if (!isset($entranceCode) || $entranceCode == "") {
        echo "Fout: Entrancecode is niet set; kan dus geen mandaat opvragen";
        die();
    }

    $response = $bluem->MandateStatus($mandateID, $entranceCode);

    if (!$response->Status()) {
        echo "Fout bij opvragen status: " . $response->Error() . "
        <br>Neem contact op met de webshop en vermeld deze status";
        exit;
    }
    $statusUpdateObject = $response->EMandateStatusUpdate;
    $statusCode = $statusUpdateObject->EMandateStatus->Status . "";

    // Handling the response.
    if ($statusCode === "Success") {
        update_user_meta($current_user->ID, "bluem_mandates_validated", true);
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
        // "Fout: Onbekende of foutieve status teruggekregen: {$statusCode}<br>Neem contact op met de webshop en vermeld deze status";
        wp_redirect(home_url($bluem_config->thanksPageURL) . "?result=false&reason=error");
        exit;
    }
}


/* ********* RENDERING THE STATIC FORM *********** */
add_shortcode('bluem_machtigingsformulier', 'bluem_mandateform');

/**
 * Shortcode: `[bluem_machtigingsformulier]`
 *
 * @return void
 */
function bluem_mandateform()
{
    global $current_user;


    $bluem_config = _get_bluem_config();
    $bluem_config->merchantReturnURLBase = home_url('wc-api/bluem_mandates_callback');
    $bluem = new Integration($bluem_config);



    $user_allowed = apply_filters(
        'bluem_woocommerce_mandate_shortcode_allow_user',true
    );
    
    if (!$user_allowed) {
        return '';
    }

    $validated = get_user_meta($current_user->ID, "bluem_mandates_validated", true);
    $mandateID = get_user_meta($current_user->ID, "bluem_latest_mandate_id", true);

    $validated = get_user_meta($current_user->ID, "bluem_mandates_validated", true);
        if ($validated!=="1") {
            echo '<form action="' . home_url('bluem-woocommerce/mandate_shortcode_execute') . '" method="post">';
            echo '<p>Je moet nog een automatische incasso machtiging afgeven.';
            // echo $bluem_config->debtorReferenceFieldName . ' (verplicht) <br/>';
            echo '<input type="hidden" name="bluem_debtorReference" value="' .$current_user->ID. '"  />';
            echo '</p>';
            echo '<p>';
            echo '<p><input type="submit" name="bluem-submitted" value="Machtiging proces starten.."></p>';
            echo '</form>';
        } else {
            echo "Bedankt voor je machtiging met machtiging ID: {$mandateID}";
        }

        return ob_get_clean();
}




add_filter('bluem_woocommerce_mandate_shortcode_allow_user', 'bluem_woocommerce_mandate_shortcode_allow_user_function', 10, 1);

function bluem_woocommerce_mandate_shortcode_allow_user_function($valid = true)
{
    return $valid;
    // do something with the response, use this in third-party extensions of this system
}

