<?php

if (!defined('ABSPATH')) {
    exit;
}
use Bluem\BluemPHP\Integration;

/* ********* RENDERING THE STATIC FORM *********** */
add_shortcode('bluem_identificatieformulier', 'bluem_idin_form');

/**
 * Shortcode: `[bluem_identificatieformulier]`
 *
 * @return void
 */
function bluem_idin_form()
{
    $bluem_config = bluem_woocommerce_get_config();

    if (isset($bluem_config->IDINShortcodeOnlyAfterLogin)
        && $bluem_config->IDINShortcodeOnlyAfterLogin=="1"
        && !is_user_logged_in()
    ) {
        return "";
    }

    // ob_start();

    $r ='';
    $validated = get_user_meta(get_current_user_id(), "bluem_idin_validated", true) == "1";

    if ($validated) {
        if (isset($bluem_config->IDINSuccessMessage)) {
            $r.= "<p>" . $bluem_config->IDINSuccessMessage . "</p>";
        } else {
            $r.= "<p>Uw verzoek is succesvol ontvangen. Hartelijk dank.</p>";
        }

        $r.= "Je hebt de identificatieprocedure eerder voltooid. Bedankt<br>";
        // $results = bluem_idin_retrieve_results();
        // $r.= "<pre>";
        // foreach ($results as $k => $v) {
        //     if (!is_object($v)) {
        //         $r.= "$k: $v";
        //     } else {
        //         foreach ($v as $vk => $vv) {
        //             $r.= "\t$vk: $vv";
        //             $r.="<BR>";
        //         }
        //     }
        //     $r.="<BR>";
        // }
        // // var_dump($results);
        // $r.= "</pre>";
        // return;
        return $r;
    } else {
        if (isset($_GET['result']) && sanitize_text_field($_GET['result']) == "false") {
            $r.= '<div class="">';

            if (isset($bluem_config->IDINErrorMessage)) {
                $r.= "<p>" . $bluem_config->IDINErrorMessage . "</p>";
            } else {
                $r.= "<p>Er is een fout opgetreden. Uw verzoek is geannuleerd.</p>";
            }

            if (isset($_SESSION['BluemIDINTransactionURL']) && $_SESSION['BluemIDINTransactionURL'] !== "") {
                $retryURL = $_SESSION['BluemIDINTransactionURL'];
                $r.= "<p><a href='{$retryURL}' target='_self' alt='probeer opnieuw' class='button'>Probeer het opnieuw</a></p>";
            } else {
                // $retryURL = home_url($bluem_config->checkoutURL);
            }
            $r.= '</div>';
        } else {
            $r.= "Je hebt de identificatieprocedure nog niet voltooid.<br>";
            $r.= '<form action="' . home_url('bluem-woocommerce/idin_execute') . '" method="post">';
            // todo add custom fields
            $r.= '<p>';
            $r.= '<p><input type="submit" name="bluem_idin_submitted" class="bluem-woocommerce-button bluem-woocommerce-button-idin" value="Identificeren.."></p>';
            $r.= '</form>';
        }
    }


    return $r;
    //ob_get_clean();
}

add_action('parse_request', 'bluem_idin_shortcode_idin_execute');
/**
 * This function is called POST from the form rendered on a page or post
 *
 * @return void
 */
function bluem_idin_shortcode_idin_execute()
{
    $shortcode_execution_url = "bluem-woocommerce/idin_execute";

    if (substr($_SERVER["REQUEST_URI"], -strlen($shortcode_execution_url)) !== $shortcode_execution_url) {
        // any other request
        return;
    }

    bluem_idin_execute();

}
/* ******** CALLBACK ****** */
add_action('parse_request', 'bluem_idin_shortcode_callback');
/**
 * This function is executed at a callback GET request with a given mandateId. This is then, together with the entranceCode in Session, sent for a SUD to the Bluem API.
 *
 */
function bluem_idin_shortcode_callback()
{
    // var_dump(strpos($_SERVER["REQUEST_URI"], "bluem-woocommerce/idin_shortcode_callback"));

    if (strpos($_SERVER["REQUEST_URI"], "bluem-woocommerce/idin_shortcode_callback") === false) {
        // return;
    } else {
        $bluem_config = bluem_woocommerce_get_config();
        // echo "YOO";
        // echo home_url($bluem_config->IDINPageURL);
        $bluem = new Integration($bluem_config);



        $entranceCode = get_user_meta(get_current_user_id(), "bluem_idin_entrance_code", true);
        $transactionID = get_user_meta(get_current_user_id(), "bluem_idin_transaction_id", true);
        $transactionURL = get_user_meta(get_current_user_id(), "bluem_idin_transaction_url", true);


        // this is purely for demonstrative purposes
        // $transactionID = $_SESSION['transactionID'];
        // $entranceCode = $_SESSION['entranceCode'];
        // var_dump($transactionID);
        // var_dump($entranceCode);
        $statusResponse = $bluem->IdentityStatus(
            $transactionID,
            $entranceCode
        );
        // var_dump($statusResponse);


        if ($statusResponse->ReceivedResponse()) {
            $statusCode = ($statusResponse->GetStatusCode());
            // var_dump($statusCode);
            // die();
            update_user_meta(get_current_user_id(), "bluem_idin_validated", false);

            switch ($statusCode) {
            case 'Success':
                // case 'New':
                // do what you need to do in case of success!

                // retrieve a report that contains the information based on the request type:
                $identityReport = $statusResponse->GetIdentityReport();
                update_user_meta(get_current_user_id(), "bluem_idin_results", json_encode($identityReport));

                update_user_meta(get_current_user_id(), "bluem_idin_validated", true);
                // var_dump($updresult);
                // die();

                // this contains an object with key-value pairs of relevant data from the bank:
                /*
                example contents:
                ["DateTime"]=>
                //  string(24) "2020-10-16T15:30:45.803Z"
                // ["CustomerIDResponse"]=>
                // string(21) "FANTASYBANK1234567890"
                // ["AddressResponse"]=>
                // object(Bluem\BluemPHP\IdentityStatusBluemResponse)#4 (5) {
                //     ["Street"]=>
                //     string(12) "Pascalstreet"
                //     ["HouseNumber"]=>
                //     string(2) "19"
                //     ["PostalCode"]=>
                //     string(6) "0000AA"
                //     ["City"]=>
                //     string(6) "Aachen"
                //     ["CountryCode"]=>
                //     string(2) "DE"
                // }
                // ["BirthDateResponse"]=>
                // string(10) "1975-07-25"
                */
                // store that information and process it.

                // You can for example use the BirthDateResponse to determine the age of the user and act accordingly
                wp_redirect(home_url($bluem_config->IDINPageURL) . "?result=true");
                exit;
            break;
            case 'Processing':
                echo "Request has status Processing";
                // no break
            case 'Pending':
                    echo "Request has status Pending";
                    // do something when the request is still processing (for example tell the user to come back later to this page)
                break;
            case 'Cancelled':
                    echo "Request has status Cancelled";
                    // do something when the request has been canceled by the user
                break;
            case 'Open':
                    echo "Request has status Open";
                    // do something when the request has not yet been completed by the user, redirecting to the transactionURL again
                break;
            case 'Expired':
                    echo "Request has status Expired";
                    // do something when the request has expired
                break;
            // case 'New':
                    //     echo "New request";
                    // break;
            default:
                    // unexpected status returned, show an error
                break;
            }
            wp_redirect(
                home_url($bluem_config->IDINPageURL) .
                "?result=false&status={$statusCode}"
            );
        } else {
            // no proper response received, tell the user
            wp_redirect(
                home_url($bluem_config->IDINPageURL) .
                "?result=false&status=no_response"
            );
        }
    }
}


add_action('show_user_profile', 'bluem_woocommerce_idin_show_extra_profile_fields');
add_action('edit_user_profile', 'bluem_woocommerce_idin_show_extra_profile_fields');

function bluem_woocommerce_idin_show_extra_profile_fields($user)
{
?>
<?php //var_dump($user->ID);
?>
<h2>Bluem iDIN Metadata</h2>
<p>
Ga naar
<a href="<?php echo home_url("wp-admin/options-general.php?page=bluem"); ?>">
Bluem instellingen
</a>.</p>
<table class="form-table">
<tr>
<th><label for="bluem_idin_entrance_code">
    Bluem iDIN entrance code
</label></th>
<td>
<input type="text" name="bluem_idin_entrance_code" id="bluem_idin_entrance_code" value="<?php echo get_user_meta($user->ID, 'bluem_idin_entrance_code', true); ?>" class="regular-text" /><br />
<span class="description">Recentste Entrance code voor Bluem iDIN requests</span>
</td>
</tr>
<tr>
<th><label for="bluem_idin_transaction_id">
    Bluem iDIN transaction ID
</label></th>

<td>
<input type="text" name="bluem_idin_transaction_id" id="bluem_idin_transaction_id" value="<?php echo get_user_meta($user->ID, 'bluem_idin_transaction_id', true); ?>" class="regular-text" /><br />
<span class="description">Hier wordt het meest recente transaction ID geplaatst; en gebruikt bij het doen van een volgende identificatie.</span>
</td>
</tr>
<tr>
<th><label for="bluem_idin_transaction_url">
    Bluem iDIN transaction URL
</label></th>

<td>
<input type="text" name="bluem_idin_transaction_url" id="bluem_idin_transaction_url" value="<?php echo get_user_meta($user->ID, 'bluem_idin_transaction_url', true); ?>" class="regular-text" /><br />
<span class="description">Hier wordt het meest recente transactie URL geplaatst; .</span>
</td>
</tr>

<tr>
<th><label for="bluem_idin_transaction_url">iDIN responses</label></th>

<td>
<span class="description">
Status en Resultaten van IDIN requests
</span>

<select class="form-control" name="bluem_idin_validated" id="bluem_idin_validated">
<option value="0" <?php if (get_user_meta($user->ID,'bluem_idin_validated', true)== "0") {
    echo "selected='selected'";
} ?>>Identificatie nog niet uitgevoerd</option>
<option value="1" <?php if (get_user_meta($user->ID,'bluem_idin_validated', true)== "1") {
    echo "selected='selected'";
} ?>>Identificatie succesvol uitgevoerd</option>
</select>
</div>
</td>
</tr>
<table class="form-table">
<tr>
<td>
<pre><?php print_r(bluem_idin_retrieve_results()); ?></pre>
</td>
<td>

<h3>Verder met iDIN resultaten werken</h3>
<p>

Of de validatie is gelukt, kan je  verkrijgen door in een plug-in of template de volgende PHP code te gebruiken:

<blockquote style="border: 1px solid #aaa;
border-radius:5px; margin:10pt 0 0 0; padding:5pt 15pt;"><pre>if(function_exists('bluem_idin_user_validated')) {
    $validated = bluem_idin_user_validated();

    if($validated) {
        // validated
    } else {
        // not validated
    }
}</pre>
</blockquote>
</p>
<p>
Deze resultaten zijn als object te verkrijgen door in een plug-in of template de volgende PHP code te gebruiken:
</p>
<p>
<blockquote style="border: 1px solid #aaa; border-radius:5px;
margin:10pt 0 0 0; padding:5pt 15pt;">
<pre>if(function_exists('bluem_idin_retrieve_results')) {
        $results = bluem_idin_retrieve_results();
        // print, show or save the results:
        echo $results->BirthDateResponse; // prints 1975-07-25
        echo $results->NameResponse->LegalLastName; // prints Vries
    }</pre>
    </blockquote>
    </p>
    <p>
    <strong>
    Checkout blokkeren als iDIN niet is uitgevoerd:
    </strong>
    <br>
Voeg een filter toe voor id <code>bluem_checkout_check_idin_validated_fiter</code> als u een filter wilt toevoegen om de checkout procedure te blokkeren op basis van de IDIN validatie procedure die is voltooid.<br>
Als de geïnjecteerde functie true retourneert, wordt de kassa ingeschakeld. Als false wordt geretourneerd, wordt de kassa geblokkeerd en wordt een melding getoond.
</p>
    </td>
    </tr>
    </table>

    <?php
}
add_action('personal_options_update', 'bluem_woocommerce_idin_save_extra_profile_fields');
add_action('edit_user_profile_update', 'bluem_woocommerce_idin_save_extra_profile_fields');

function bluem_woocommerce_idin_save_extra_profile_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    update_user_meta(
        $user_id,
        'bluem_idin_entrance_code',
        sanitize_text_field($_POST['bluem_idin_entrance_code'])
    );
    update_user_meta(
        $user_id,
        'bluem_idin_transaction_id',
        sanitize_text_field($_POST['bluem_idin_transaction_id'])
    );
    update_user_meta(
        $user_id,
        'bluem_idin_transaction_url',
        sanitize_text_field($_POST['bluem_idin_transaction_url'])
    );

    update_user_meta(
        $user_id,
        'bluem_idin_validated',
        sanitize_text_field($_POST['bluem_idin_validated'])
    );
}

function bluem_idin_retrieve_results()
{
    $raw = get_user_meta(get_current_user_id(), "bluem_idin_results", true);

    $obj = json_decode($raw);
    return $obj;
}
function bluem_idin_user_validated()
{
    return get_user_meta(get_current_user_id(), "bluem_idin_validated", true) == "1";
}

function bluem_get_IDINDescription_tags()
{
    return [
        '{gebruikersnaam}',
        '{email}',
        '{klantnummer}',
        '{datum}',
        '{datumtijd}'
    ];
}

function bluem_get_IDINDescription_replaces()
{
    global $current_user;

    // @todo: add fallbacks if user is not logged in

    return [
        $current_user->display_name,    //'{gebruikersnaam}',
        $current_user->user_email,      //'{email}',
        $current_user->ID,              // {klantnummer}
        date("d-m-Y"),                  //'{datum}',
        date("d-m-Y H:i")               //'{datumtijd}',
    ];
}
function bluem_parse_IDINDescription($input) {
    $tags = bluem_get_IDINDescription_tags();
    $replaces = bluem_get_IDINDescription_replaces();


    $result = str_replace($tags, $replaces, $input);
    $invalid_chars = ['[',']','{','}','!','#'];
    // @todo Add full list of invalid chars for description based on XSD
    $result = str_replace($invalid_chars,'',$result);

    $result = substr($result,0,128);
    return $result;
}

function bluem_idin_execute($callback=null, $redirect=true)
{
    global $current_user;
    $bluem_config = bluem_woocommerce_get_config();

    if (isset($bluem_config->IDINDescription)) {
        $description = bluem_parse_IDINDescription($bluem_config->IDINDescription);
    } else {
        $description =  "Identificatie " . $current_user->display_name ;
    }

    $debtorReference = $current_user->ID;

    $bluem = new Integration($bluem_config);

    $cats = explode(",", str_replace(" ", "", $bluem_config->IDINCategories));
    if (count($cats)==0) {
        echo "Geen juiste IDIN categories ingesteld";
        die();
    }

    if (is_null($callback)) {
        $callback = home_url("bluem-woocommerce/idin_shortcode_callback");
    }

    // To create AND perform a request:
    $request = $bluem->CreateIdentityRequest(
        $cats,
        $description,
        $debtorReference,
        $callback
    );
    $response = $bluem->PerformRequest($request);

    session_start();


    if ($response->ReceivedResponse()) {
        $entranceCode = $response->GetEntranceCode();
        $transactionID = $response->GetTransactionID();
        $transactionURL = $response->GetTransactionURL();

        // save this in our user meta data store
        update_user_meta(
            get_current_user_id(), "bluem_idin_entrance_code", $entranceCode
        );
        update_user_meta(
            get_current_user_id(), "bluem_idin_transaction_id", $transactionID
        );
        update_user_meta(
            get_current_user_id(), "bluem_idin_transaction_url", $transactionURL
        );

        if ($redirect) {
            if (ob_get_length()!==false && ob_get_length()>0) {
                ob_clean();
            }
            ob_start();
            wp_redirect($transactionURL);
            exit;
        } else {
            return ['result'=>true,'url'=>$transactionURL];
        }
    } else {

        $msg = "Er ging iets mis bij het aanmaken van de transactie.<br>
        Vermeld onderstaande informatie aan het websitebeheer:";
        //     <br><pre>";
        // bluem_generic_tabler($response);
        // echo "</pre>";
        if ($response->Error() !=="") {
            $msg.= "<br>Response: " .
            $response->Error();
        } else {
            $msg .= "algemene fout";
        }


        bluem_woocommerce_prompt($msg);
        exit;
    }
    exit;
}





add_action( 'woocommerce_check_cart_items', 'bluem_checkout_check_idin_validated' ); // Cart and Checkout
function bluem_checkout_check_idin_validated() 
{
    if (!function_exists('bluem_idin_user_validated')) {
        return;
    }

    if(bluem_checkout_check_idin_validated_fiter()==false) {
        wc_add_notice( __("Verifieer eerst je identiteit via de mijn account pagina", "woocommerce"), 'error' );
    }

    return;    
}

add_filter(
    'bluem_checkout_check_idin_validated_fiter', 
    'bluem_checkout_check_idin_validated_fiter_function', 
    10, 
    1
);
function bluem_checkout_check_idin_validated_fiter()
{
    // override this function if you want to add a filter to block the checkout procedure based on the IDIN validation procedure being completed.
    // if you return true, the checkout is enabled. If you return false, the checkout is blocked and a notice is shown.

    // example:
    // if (!bluem_idin_user_validated()) {
    //   return false;
    // }   
    
    return true;
}