<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty(session_id()) ) {
    session_start();
}

use Bluem\BluemPHP\Bluem;

function bluem_woocommerce_integrations_settings_section() {
    echo '<p><a id="tab_integrations"></a></p>';
}

function bluem_woocommerce_get_integration_option( $key ) {
    $options = bluem_woocommerce_get_integrations_options();
    if ( array_key_exists( $key, $options ) ) {
        return $options[ $key ];
    }

    return false;
}

function bluem_woocommerce_get_integrations_options() {
    return [
        'gformActive' => [
            'key'         => 'gformActive',
            'title'       => 'bluem_gformActive',
            'name'        => 'Gravity Forms',
            'description' => 'Activeer de Gravity Forms integratie',
            'type'        => 'select',
            'default'     => 'N',
            'options'     => [
                'N' => 'Niet actief',
                'Y' => "Actief",
            ]
        ],
        'gformResultpage' => [
            'key'         => 'gformResultpage',
            'title'       => 'bluem_gformResultpage',
            'name'        => 'Slug resultaatpagina',
            'description' => 'De slug van de resultaatpagina',
            'default'     => ''
        ],
        'wpcf7Active' => [
            'key'         => 'wpcf7Active',
            'title'       => 'bluem_wpcf7Active',
            'name'        => 'ContactForm 7',
            'description' => 'Activeer de ContactForm 7 integratie',
            'type'        => 'select',
            'default'     => 'N',
            'options'     => [
                'N' => 'Niet actief',
                'Y' => "Actief",
            ]
        ],
        'wpcf7Resultpage' => [
            'key'         => 'wpcf7Resultpage',
            'title'       => 'bluem_wpcf7Resultpage',
            'name'        => 'Slug resultaatpagina',
            'description' => 'De slug van de resultaatpagina',
            'default'     => ''
        ],
    ];
}

function bluem_woocommerce_settings_render_gformActive() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_integration_option( 'gformActive' )
    );
}

function bluem_woocommerce_settings_render_gformResultpage() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_integration_option( 'gformResultpage' )
    );
}

function bluem_woocommerce_settings_render_wpcf7Active() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_integration_option( 'wpcf7Active' )
    );
}

function bluem_woocommerce_settings_render_wpcf7Resultpage() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_integration_option( 'wpcf7Resultpage' )
    );
}

/**
 * ContactForm 7 integration.
 * Javascript code in footer.
 */
add_action('wp_footer', 'bluem_woocommerce_integration_wpcf7_javascript');

function bluem_woocommerce_integration_wpcf7_javascript() {
    $bluem_config = bluem_woocommerce_get_config();

    if ($bluem_config->wpcf7Active !== 'Y') {
        return;
    }

    echo '
    <script>

    //var wpcf7Elm = document.querySelector(".wpcf7");

    document.addEventListener( "wpcf7submit", function( event ) {
        //
    }, false );

    document.addEventListener( "wpcf7mailsent", function( event ) {
        console.log(event);

        const url = "' . home_url('bluem-woocommerce/bluem-integrations/wpcf7_mandate') . '"

        var contact_form_id = event.detail.contactFormId;
        var inputs = event.detail.inputs;

        var data = new FormData();
        data.append("contact_form_id", contact_form_id);

        for (var i = 0; i < inputs.length; i++) {
            data.append(inputs[i].name, inputs[i].value);
        }

        let xhr = new XMLHttpRequest()

        xhr.open("POST", url, true)
        //xhr.setRequestHeader("Content-Type", "application/x-www-form-data")
        xhr.send(data);

        xhr.onload = function () {
            if (xhr.status === 200) {
                var json = JSON.parse(xhr.response);

                if (json.success === true) {
                    window.location.href = json.redirect_uri;
                }
            }
        }
    }, false );

    </script>';
}

/**
 * ContactForm 7 integration.
 * AJAX Form submissions.
 */
add_action( 'parse_request', 'bluem_woocommerce_integration_wpcf7_ajax' );

function bluem_woocommerce_integration_wpcf7_ajax()
{
    $bluem_config = bluem_woocommerce_get_config();

    if (strpos($_SERVER["REQUEST_URI"], 'bluem-woocommerce/bluem-integrations/wpcf7_mandate') === false) {
        return;
    }

    if ($bluem_config->wpcf7Active !== 'Y') {
        return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        $bluem_config = bluem_woocommerce_get_config();

        $debtorReference = bin2hex(random_bytes(15));

        if (!empty($debtorReference))
        {
            $debtorReference = sanitize_text_field( $debtorReference );

            $contact_form_id = !empty($_POST['contact_form_id']) ? $_POST['contact_form_id'] : '';

            $posted_data = [];

            foreach ($_POST as $key => $value) {
                if ($key !== 'contact_form_id') {
                    $posted_data[$key] = $value;
                }
            }

            $db_results = bluem_db_get_requests_by_keyvalues([
                'debtor_reference' => $debtorReference,
                'status' => 'Success',
            ]);

            // Check the sequence type or previous success results
            if ($bluem_config->sequenceType === 'OOFF' || sizeof($db_results) == 0)
            {
                $bluem_config->merchantReturnURLBase = home_url(
                    "bluem-woocommerce/bluem-integrations/wpcf7_callback"
                );

                $preferences = get_option( 'bluem_woocommerce_options' );

                // Convert UTF-8 to ISO
                if (!empty($bluem_mandate_reason)) {
                    $bluem_config->eMandateReason = $bluem_mandate_reason . ' (' . $debtorReference . ')';
                } elseif (!empty($bluem_config->eMandateReason)) {
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
                    $debtorReference,
                    $mandate_id
                );

                // Save the necessary data to later request more information and refer to this transaction
                $_SESSION['bluem_wpcf7_formId'] = $contact_form_id;
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

                        echo json_encode([
                            'success' => false,
                        ]);
                        die;
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
                                    'created_via' => 'contactform7',
                                    'environment' => $bluem->getConfig('environment'),
                                    'created_mandate_id' => $mandate_id,
                                    'contactform7' => json_encode(
                                        [
                                            'id' => $contact_form_id,
                                            'payload' => $posted_data,
                                        ]
                                    ),
                                ]
                            )
                        ]
                    );

                    echo json_encode([
                        'success' => true,
                        'redirect_uri' => $transactionURL,
                    ]);
                    die;
                } catch (\Exception $e) {
                    echo json_encode([
                        'success' => false,
                    ]);
                    die;
                }
            }
        }
    }

    echo json_encode([
        'success' => false,
    ]);
    die;
}

/**
 * ContactForm 7 integration.
 * Form submissions.
 */
add_action("wpcf7_submit", "bluem_woocommerce_integration_wpcf7_submit");

function bluem_woocommerce_integration_wpcf7_submit() {
    $bluem_config = bluem_woocommerce_get_config();

    if ($bluem_config->wpcf7Active !== 'Y') {
        return;
    }

    $submission = WPCF7_Submission::get_instance();

    $contact_form = $submission->get_contact_form();

    $contact_form_id = $contact_form->id();

    $is_bluem_mandate = $contact_form->is_true( 'bluem_mandate' );

    $bluem_mandate_approve = $contact_form->pref( 'bluem_mandate_approve' );
    
    $bluem_mandate_reason = $contact_form->pref( 'bluem_mandate_reason' );
    
    /**
     * TODO: Add to request.
     * Overwrite Bluem config variable.
     */
    $bluem_mandate_type = $contact_form->pref( 'bluem_mandate_type' );

    $is_bluem_ajax = $contact_form->is_true( 'bluem_is_ajax' );

    if ( $is_bluem_mandate && !$is_bluem_ajax && $submission ) {
        $posted_data = $submission->get_posted_data();

        $posted_data_hash = $submission->get_posted_data_hash();

        $debtorReference = bin2hex(random_bytes(15));

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
                    "bluem-woocommerce/bluem-integrations/wpcf7_callback"
                );

                $preferences = get_option( 'bluem_woocommerce_options' );

                // Convert UTF-8 to ISO
                if (!empty($bluem_mandate_reason)) {
                    $bluem_config->eMandateReason = $bluem_mandate_reason . ' (' . $debtorReference . ')';
                } elseif (!empty($bluem_config->eMandateReason)) {
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
                    $debtorReference,
                    $mandate_id
                );

                // Save the necessary data to later request more information and refer to this transaction
                $_SESSION['bluem_wpcf7_formId'] = $contact_form_id;
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
                                'function' => 'wpcf7_execute',
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
                                    'created_via' => 'contactform7',
                                    'environment' => $bluem->getConfig('environment'),
                                    'created_mandate_id' => $mandate_id,
                                    'contactform7' => json_encode(
                                        [
                                            'id' => $contact_form_id,
                                            'payload' => $posted_data,
                                        ]
                                    ),
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
    }
}

/**
 * ContactForm 7 integration.
 * Callback for requests.
 */
add_action( 'parse_request', 'bluem_woocommerce_integration_wpcf7_callback' );

function bluem_woocommerce_integration_wpcf7_callback()
{
    $bluem_config = bluem_woocommerce_get_config();

    if (strpos($_SERVER["REQUEST_URI"], 'bluem-woocommerce/bluem-integrations/wpcf7_callback') === false) {
        return;
    }

    if ($bluem_config->wpcf7Active !== 'Y') {
        return;
    }

    try {
        $bluem = new Bluem( $bluem_config );
    } catch ( Exception $e ) {
        // @todo: deal with incorrectly setup Bluem
    }

    $formID = $_SESSION['bluem_wpcf7_formId'];

    $mandateID = $_SESSION['bluem_mandateId'];

    $entranceCode = $_SESSION['bluem_entranceCode'];

    if (empty($mandateID)) {
        if (!empty($bluem_config->wpcf7Resultpage)) {
            wp_redirect( home_url($bluem_config->wpcf7Resultpage) . "?form=$formID&result=false&reason=error" );
            exit;
        }
        $errormessage = "Fout: geen juist mandaat id teruggekregen bij callback. Neem contact op met de webshop en vermeld je contactgegevens.";
        bluem_error_report_email(
            [
                'service'  => 'mandates',
                'function' => 'wpcf7_callback',
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
                'function' => 'wpcf7_callback',
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
                'function' => 'wpcf7_callback',
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

        // "De ondertekening is geslaagd";
        if (!empty($bluem_config->wpcf7Resultpage)) {
            wp_redirect( home_url($bluem_config->wpcf7Resultpage) . "?form=$formID&result=true" );
            exit;
        }
        $errormessage = "Fout: de ondertekening is geslaagd maar er is geen response URI opgegeven. Neem contact op met de website om dit technisch probleem aan te geven.";
        bluem_error_report_email(
            [
                'service'  => 'mandates',
                'function' => 'wpcf7_callback',
                'message'  => $errormessage
            ]
        );
        bluem_dialogs_render_prompt( $errormessage );
        return;
    }
    elseif ($statusCode === "Cancelled")
    {
        // "Je hebt de mandaat ondertekening geannuleerd";
        if (!empty($bluem_config->wpcf7Resultpage)) {
            wp_redirect( home_url($bluem_config->wpcf7Resultpage) . "?form=$formID&result=false&reason=cancelled" );
            exit;
        }
        $errormessage = "Fout: de transactie is geannuleerd. Probeer het opnieuw.";
        bluem_dialogs_render_prompt( $errormessage );
        exit;
    }
    elseif ($statusCode === "Open" || $statusCode == "Pending")
    {
        // "De mandaat ondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch."
        if (!empty($bluem_config->wpcf7Resultpage)) {
            wp_redirect( home_url($bluem_config->wpcf7Resultpage) . "?form=$formID&result=false&reason=open" );
            exit;
        }
        $errormessage = "Fout: de transactie staat nog open. Dit kan even duren. Vernieuw deze pagina regelmatig voor de status.";
        bluem_dialogs_render_prompt( $errormessage );
        exit;
    }
    elseif ($statusCode === "Expired")
    {
        // "Fout: De mandaat of het verzoek daartoe is verlopen";
        if (!empty($bluem_config->wpcf7Resultpage)) {
            wp_redirect( home_url($bluem_config->wpcf7Resultpage) . "?form=$formID&result=false&reason=expired" );
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
                'function' => 'wpcf7_callback',
                'message'  => "Fout: Onbekende of foutieve status teruggekregen: {$statusCode}<br>Neem contact op met de webshop en vermeld deze status; gebruiker wel doorverwezen terug naar site"
            ]
        );
        if (!empty($bluem_config->wpcf7Resultpage)) {
            wp_redirect( home_url($bluem_config->wpcf7Resultpage) . "?form=$formID&result=false&reason=error" );
            exit;
        }
        $errormessage = "Fout: er is een onbekende fout opgetreden. Probeer het opnieuw.";
        bluem_dialogs_render_prompt( $errormessage );
        exit;
    }
    exit;
}

/**
 * ContactForm 7 integration.
 * Shortcode to display results.
 */
add_shortcode( 'bluem_resultaatpagina', 'bluem_woocommerce_integration_wpcf7_results_shortcode' );
add_shortcode( 'bluem_wpcf7_results', 'bluem_woocommerce_integration_wpcf7_results_shortcode' );

function bluem_woocommerce_integration_wpcf7_results_shortcode()
{
    $bluem_config = bluem_woocommerce_get_config();

    if ($bluem_config->wpcf7Active !== 'Y') {
        return;
    }

    if (empty($_GET['form']) || empty($_GET['result'])) {
        return 'Er is een fout opgetreden. Ga terug en probeer het opnieuw.';
    }

    $contact_form = WPCF7_ContactForm::get_instance($_GET['form']);

    if (!empty($contact_form)) {
        if (!empty($_GET['result']) && $_GET['result'] == 'true') {
            return '<p>' . $contact_form->pref( 'bluem_mandate_success' ) . '</p>';
        }
    }
    return '<p>' . $contact_form->pref( 'bluem_mandate_failure' ) . '</p>';
}

/**
 * Gravity Forms integration.
 * Hook for submissions.
 */
add_action( 'gform_after_submission', 'bluem_woocommerce_integration_gform_submit', 10, 2 );

function bluem_woocommerce_integration_gform_submit( $entry, $form ) {
    $bluem_config = bluem_woocommerce_get_config();

    if ($bluem_config->gformActive !== 'Y') {
        return;
    }

    /**
     * Define form data.
     */
    $form_data = [];

    // Loop through fields
    foreach ( $form['fields'] as $field ) {
        $inputs = $field->get_entry_inputs();
        if ( is_array( $inputs ) ) {
            foreach ( $inputs as $input ) {
                $value = rgar( $entry, (string) $input['id'] );
            }
        } else {
            $value = rgar( $entry, (string) $field->id );

            if (empty($value)) {
                $value = $field->defaultValue;
            }
        }

        if (!empty($field->inputName) && !empty($value)) {
            $form_data[$field->inputName] = $value;
        } elseif (!empty($field->label) && !empty($value)) {
            $form_data[$field->label] = $value;
        }
    }
    
    // Get custom parameters for this form
    $bluem_mandate = $form_data['bluem_mandate'];
    $bluem_mandate_approve = $form_data['bluem_mandate_approve'];
    $bluem_mandate_success = $form_data['bluem_mandate_success'];
    $bluem_mandate_failure = $form_data['bluem_mandate_failure'];
    $bluem_mandate_reason = $form_data['bluem_mandate_reason'];
    $bluem_mandate_type = $form_data['bluem_mandate_type'];
    
    $bluem_is_ajax = $form_data['bluem_is_ajax'];

    /**
     * Define payload for Bluem.
     */
    $payload = [
        'source_url' => $entry['source_url'],
        'form_id' => $entry['form_id'],
        'entry_id' => $entry['id'],
        'ip' => $entry['ip'],
    ];

    /**
     * Do mandate request.
     */
    if ($bluem_mandate === 'true' && $bluem_mandate_approve === 'true')
    {
        $debtorReference = bin2hex(random_bytes(15));

        if (!empty($debtorReference))
        {
            // Define debtor reference
            $debtorReference = sanitize_text_field( $debtorReference );

            // Get previous requests by debtor reference
            $db_results = bluem_db_get_requests_by_keyvalues([
                'debtor_reference' => $debtorReference,
                'status' => 'Success',
            ]);

            // Check for mandate type
            if (!empty($bluem_mandate_type)) {
                $bluem_config->sequenceType = $bluem_mandate_type;
            }

            // Check the sequence type or previous success results
            if ($bluem_config->sequenceType === 'OOFF' || sizeof($db_results) == 0)
            {
                $bluem_config->merchantReturnURLBase = home_url(
                    "bluem-woocommerce/bluem-integrations/gform_callback"
                );

                $preferences = get_option( 'bluem_woocommerce_options' );

                // Convert UTF-8 to ISO
                if (!empty($bluem_mandate_reason)) {
                    $bluem_config->eMandateReason = $bluem_mandate_reason . ' (' . $debtorReference . ')';
                } elseif (!empty($bluem_config->eMandateReason)) {
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
                    $debtorReference,
                    $mandate_id
                );

                // Save the necessary data to later request more information and refer to this transaction
                $_SESSION['bluem_gform_entryId'] = $payload['entry_id'];
                $_SESSION['bluem_gform_formId'] = $payload['form_id'];
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
                                'function' => 'gform_execute',
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
                                    'created_via' => 'gform',
                                    'environment' => $bluem->getConfig('environment'),
                                    'created_mandate_id' => $mandate_id,
                                    'details' => json_encode(
                                        [
                                            'id' => $payload['form_id'],
                                            'payload' => json_encode($payload),
                                        ]
                                    ),
                                ]
                            )
                        ]
                    );

                    /**
                     * Get Gravity Form entry.
                     */
                    $entry = GFAPI::get_entry( $payload['entry_id'] );
                    if ( is_wp_error( $entry ) ) {
                        // Handle error
                    }

                    /**
                     * Update Gravity Forms details.
                     */
                    $entry['bluem_payload'] = [
                        'mandate_id' => $request->mandateID,
                        'mandate_entrance_code' => $request->entranceCode,
                        'bluem_record_id' => $db_creation_result,
                    ];

                    // Update the entry
                    $result = GFAPI::update_entry( $entry );

                    if ( is_wp_error( $result ) ) {
                        // Handle error
                    } else {
                        // Entry updated successfully
                    }

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
        }
    }
}

/**
 * Gravity Forms integration.
 * Callback after request
 */
add_action( 'parse_request', 'bluem_woocommerce_integration_gform_callback' );

function bluem_woocommerce_integration_gform_callback()
{
    $bluem_config = bluem_woocommerce_get_config();

    if (strpos($_SERVER["REQUEST_URI"], 'bluem-woocommerce/bluem-integrations/gform_callback') === false) {
        return;
    }

    if ($bluem_config->gformActive !== 'Y') {
        return;
    }

    try {
        $bluem = new Bluem( $bluem_config );
    } catch ( Exception $e ) {
        // @todo: deal with incorrectly setup Bluem
    }

    $formID = $_SESSION['bluem_gform_formId'];

    $entryID = $_SESSION['bluem_gform_entryId'];

    $mandateID = $_SESSION['bluem_mandateId'];

    $entranceCode = $_SESSION['bluem_entranceCode'];

    if (empty($mandateID)) {
        if (!empty($bluem_config->gformResultpage)) {
            wp_redirect( home_url($bluem_config->gformResultpage) . "?form=$formID&entry=$entryID&result=false&reason=error" );
            exit;
        }
        $errormessage = "Fout: geen juist mandaat id teruggekregen bij callback. Neem contact op met de webshop en vermeld je contactgegevens.";
        bluem_error_report_email(
            [
                'service'  => 'mandates',
                'function' => 'gform_callback',
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
                'function' => 'gform_callback',
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
                'function' => 'gform_callback',
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

        if (!empty($entryID) && !empty($formID))
        {
            /**
             * Get the entry instance.
             */
            $entry = GFAPI::get_entry( $entryID );
            
            // Get the form instance
            $form = GFAPI::get_form( $formID );

            /**
             * Update Gravity Forms details.
             */
            $entry['bluem_payload'] = [
                'mandate_id' => !empty($mandateID) ? $mandateID : '',
                'mandate_entrance_code' => !empty($entranceCode) ? $entranceCode : '',
                'bluem_record_id' => !empty($request_from_db) ? $request_from_db->id : '',
                'status' => !empty($statusCode) ? $statusCode : '',
            ];

            // Update the entry
            $result = GFAPI::update_entry( $entry );

            /**
             * Define form data.
             */
            $edit_data = [];
            $form_data = [];

            // Get the fields
            $fields = $form['fields'];

            // Loop through fields
            foreach ( $fields as $field ) {
                $field_id = $field['id'];
                $field_name = $field['inputName'];
                $field_label = $field['label'];
                $field_value = rgar( $entry, $field_id );

                if (!empty($field_name))
                {
                    $edit_data[] = [
                        'field_id' => $field_id,
                        'field_name' => $field_name,
                        'field_label' => $field_label,
                        'field_value' => $field_value,
                    ];
                }

                if (!empty($field_label)) {
                    $form_data[$field_label] = $field_value;
                } elseif (!empty($field_id)) {
                    $form_data[$field_id] = $field_value;
                }
            }

            // Loop through data
            foreach ($edit_data as $key => $value) {
                $newValue = '';

                if ($value['field_name'] === 'bluem_mandate_accountname') {
                    $payload = json_decode(json_encode($newPayload));
                    $newValue = $payload->report->DebtorAccountName;
                } elseif ($value['field_name'] === 'bluem_mandate_iban') {
                    $payload = json_decode(json_encode($newPayload));
                    $newValue = $payload->report->DebtorIBAN;
                }

                /**
                 * Edit field.
                 */
                if (!empty($newValue)) {
                    $result = GFAPI::update_entry_field( $entryID, $value['field_id'], $newValue );
                }
            }
        }

        // "De ondertekening is geslaagd";
        if (!empty($bluem_config->gformResultpage)) {
            wp_redirect( home_url($bluem_config->gformResultpage) . "?form=$formID&entry=$entryID&mid=$mandateID&ec=$entranceCode&result=true" );
            exit;
        }
        $errormessage = "Fout: de ondertekening is geslaagd maar er is geen response URI opgegeven. Neem contact op met de website om dit technisch probleem aan te geven.";
        bluem_error_report_email(
            [
                'service'  => 'mandates',
                'function' => 'gform_callback',
                'message'  => $errormessage
            ]
        );
        bluem_dialogs_render_prompt( $errormessage );
        return;
    }
    elseif ($statusCode === "Cancelled")
    {
        // "Je hebt de mandaat ondertekening geannuleerd";
        if (!empty($bluem_config->gformResultpage)) {
            wp_redirect( home_url($bluem_config->gformResultpage) . "?form=$formID&entry=$entryID&mid=$mandateID&ec=$entranceCode&result=false&reason=cancelled" );
            exit;
        }
        $errormessage = "Fout: de transactie is geannuleerd. Probeer het opnieuw.";
        bluem_dialogs_render_prompt( $errormessage );
        exit;
    }
    elseif ($statusCode === "Open" || $statusCode == "Pending")
    {
        // "De mandaat ondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch."
        if (!empty($bluem_config->gformResultpage)) {
            wp_redirect( home_url($bluem_config->gformResultpage) . "?form=$formID&entry=$entryID&mid=$mandateID&ec=$entranceCode&result=false&reason=open" );
            exit;
        }
        $errormessage = "Fout: de transactie staat nog open. Dit kan even duren. Vernieuw deze pagina regelmatig voor de status.";
        bluem_dialogs_render_prompt( $errormessage );
        exit;
    }
    elseif ($statusCode === "Expired")
    {
        // "Fout: De mandaat of het verzoek daartoe is verlopen";
        if (!empty($bluem_config->gformResultpage)) {
            wp_redirect( home_url($bluem_config->gformResultpage) . "?form=$formID&entry=$entryID&mid=$mandateID&ec=$entranceCode&result=false&reason=expired" );
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
                'function' => 'gform_callback',
                'message'  => "Fout: Onbekende of foutieve status teruggekregen: {$statusCode}<br>Neem contact op met de webshop en vermeld deze status; gebruiker wel doorverwezen terug naar site"
            ]
        );
        if (!empty($bluem_config->gformResultpage)) {
            wp_redirect( home_url($bluem_config->gformResultpage) . "?form=$formID&entry=$entryID&mid=$mandateID&ec=$entranceCode&result=false&reason=error" );
            exit;
        }
        $errormessage = "Fout: er is een onbekende fout opgetreden. Probeer het opnieuw.";
        bluem_dialogs_render_prompt( $errormessage );
        exit;
    }
    exit;
}

/**
 * Gravity Forms integration.
 * Shortcode to display results.
 */
add_shortcode( 'bluem_gform_results', 'bluem_woocommerce_integration_gform_results_shortcode' );

function bluem_woocommerce_integration_gform_results_shortcode()
{
    $bluem_config = bluem_woocommerce_get_config();

    if ($bluem_config->gformActive !== 'Y') {
        return;
    }

    if (empty($_GET['form']) || empty($_GET['entry']) || empty($_GET['mid']) || empty($_GET['ec']) || empty($_GET['result'])) {
        return 'Er is een fout opgetreden. Ga terug en probeer het opnieuw.';
    }

    $request_from_db = bluem_db_get_request_by_transaction_id_and_entrance_code(
        $_GET['mid'],
        $_GET['ec'],
        "mandates"
    );

    if ($request_from_db !== false)
    {
        $request_id = $request_from_db->id;

        $entrance_code = $request_from_db->entrance_code;

        $transaction_id = $request_from_db->transaction_id;

        if (!empty($request_from_db->payload))
        {
            $payload = json_decode($request_from_db->payload);

            if (!empty($payload) && !empty($payload->details))
            {
                $details = json_decode($payload->details);

                if (!empty($details->payload))
                {
                    $details_payload = json_decode($details->payload);

                    $entry_id = $details_payload->entry_id;

                    $form_id = $details_payload->form_id;
                }
            }
        }

        if (!empty($request_from_db->status)) {
            $status = $request_from_db->status;
        }
    }

    if (!empty($entry_id))
    {
        /**
         * Get the entry instance.
         */
        $entry = GFAPI::get_entry( $entry_id );
        
        // Get the form instance
        $form = GFAPI::get_form( $entry['form_id'] );

        /**
         * Define form data.
         */
        $form_data = [];

        // Get the fields
        $fields = $form['fields'];

        // Loop through fields
        foreach ( $fields as $field ) {
            $field_id = $field['id'];
            $field_name = $field['inputName'];
            $field_label = $field['label'];
            $field_value = rgar( $entry, $field_id );

            if (!empty($field_label)) {
                $form_data[$field_label] = $field_value;
            } elseif (!empty($field_id)) {
                $form_data[$field_id] = $field_value;
            }
        }
    }

    if (!empty($status))
    {
        if ($status === 'Success') {
            return '<p>' . !empty($form_data) && !empty($form_data['bluem_mandate_success']) ? $form_data['bluem_mandate_success'] : 'De machtiging is gelukt.' . '</p>';
        } else {
            return '<p>' . !empty($form_data) && !empty($form_data['bluem_mandate_failure']) ? $form_data['bluem_mandate_failure'] : 'De machtiging is mislukt. Probeer het opnieuw.' . '</p>';
        }
    }
    else
    {
        return '<p>' . !empty($form_data) && !empty($form_data['bluem_mandate_failure']) ? $form_data['bluem_mandate_failure'] : 'De machtiging is mislukt. Probeer het opnieuw.' . '</p>';
    }
}
