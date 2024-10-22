<?php

if (!defined('ABSPATH')) {
    exit;
}

use Bluem\BluemPHP\Bluem;

function bluem_woocommerce_integrations_settings_section()
{
    echo '<p><a id="tab_integrations"></a></p>';
}

function bluem_woocommerce_get_integration_option($key)
{
    $options = bluem_woocommerce_get_integrations_options();
    if (array_key_exists($key, $options)) {
        return $options[$key];
    }

    return false;
}

function bluem_woocommerce_get_integrations_options()
{
    return array(
        'gformActive' => array(
            'key' => 'gformActive',
            'title' => 'bluem_gformActive',
            'name' => 'Gravity Forms',
            'description' => 'Activeer de Gravity Forms integratie',
            'type' => 'select',
            'default' => 'N',
            'options' => array(
                'N' => 'Niet actief',
                'Y' => 'Actief',
            ),
        ),
        'gformResultpage' => array(
            'key' => 'gformResultpage',
            'title' => 'bluem_gformResultpage',
            'name' => 'Slug resultaatpagina',
            'description' => 'De slug van de resultaatpagina',
            'default' => '',
        ),
        'wpcf7Active' => array(
            'key' => 'wpcf7Active',
            'title' => 'bluem_wpcf7Active',
            'name' => 'ContactForm 7',
            'description' => 'Activeer de ContactForm 7 integratie',
            'type' => 'select',
            'default' => 'N',
            'options' => array(
                'N' => 'Niet actief',
                'Y' => 'Actief',
            ),
        ),
        'wpcf7Resultpage' => array(
            'key' => 'wpcf7Resultpage',
            'title' => 'bluem_wpcf7Resultpage',
            'name' => 'Slug resultaatpagina',
            'description' => 'De slug van de resultaatpagina',
            'default' => '',
        ),
    );
}

function bluem_woocommerce_settings_render_gformActive()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_integration_option('gformActive')
    );
}

function bluem_woocommerce_settings_render_gformResultpage()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_integration_option('gformResultpage')
    );
}

function bluem_woocommerce_settings_render_wpcf7Active()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_integration_option('wpcf7Active')
    );
}

function bluem_woocommerce_settings_render_wpcf7Resultpage()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_integration_option('wpcf7Resultpage')
    );
}

/**
 * ContactForm 7 integration.
 *
 * Javascript code in footer.
 */
add_action('wp_footer', 'bluem_woocommerce_integration_wpcf7_javascript');

function bluem_woocommerce_integration_wpcf7_javascript()
{
    $bluem_config = bluem_woocommerce_get_config();

    if ($bluem_config->wpcf7Active !== 'Y') {
        return;
    }

    echo '
    <script>

    document.addEventListener( "wpcf7submit", function ( event ) {
        //
    }, false );

    document.addEventListener( "wpcf7mailsent", function ( event ) {
        const url = "' . esc_url(home_url('bluem-woocommerce/bluem-integrations/wpcf7_mandate')) . '"

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
 * Gravity Forms integration.
 *
 * Javascript code in footer.
 */
add_action('wp_footer', 'bluem_woocommerce_integration_gform_javascript');

function bluem_woocommerce_integration_gform_javascript()
{
    $bluem_config = bluem_woocommerce_get_config();

    if ($bluem_config->gformActive !== 'Y') {
        return;
    }

    echo '
    <script>

    document.addEventListener( "gform_confirmation_loaded", function ( event ) {
        var formId = event.detail.apiResponse.form_id;
        var iframe = document.getElementById("gform_ajax_frame_" + formId);
        var responseText = iframe.contentDocument.body.innerText;
        var responseObj = JSON.parse(responseText);
        if (responseObj.redirect_url) {
            window.location.href = responseObj.redirect_url;
        }
    });

    </script>';
}

/**
 * ContactForm 7 integration.
 * AJAX Form submissions.
 */
add_action('parse_request', 'bluem_woocommerce_integration_wpcf7_ajax');

function bluem_woocommerce_integration_wpcf7_ajax()
{
    $bluem_config = bluem_woocommerce_get_config();

    if (!isset($_SERVER['REQUEST_URI']) || strpos(sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])), 'bluem-woocommerce/bluem-integrations/wpcf7_mandate') === false) {
        return;
    }

    $bluem_mandate_approve = !empty($_POST['bluem_mandate_approve']) ? sanitize_text_field(wp_unslash($_POST['bluem_mandate_approve'])) : '';

    if ($bluem_config->wpcf7Active !== 'Y' || empty($bluem_mandate_approve)) {
        return;
    }

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $bluem_config = bluem_woocommerce_get_config();

        $debtorReference = bin2hex(random_bytes(15));

        if (!empty($debtorReference)) {
            $debtorReference = sanitize_text_field($debtorReference);

            $db_results = bluem_db_get_requests_by_keyvalues(
                array(
                    'debtor_reference' => $debtorReference,
                    'status' => 'Success',
                )
            );

            $contact_form_id = !empty($_POST['contact_form_id']) ? sanitize_text_field(wp_unslash($_POST['contact_form_id'])) : '';

            $posted_data = array();
            // @todo: change this to only retrieve the fields relevant for the form
            // $form = get_post_meta($contact_form_id, '_form', true);
            // var_dump($form);
            foreach ($_POST as $key => $value) {
                if ($key !== 'contact_form_id') {
                    $posted_data[sanitize_text_field($key)] = sanitize_text_field(wp_unslash($value));
                }
            }

            // Check the sequence type or previous success results
            if ($bluem_config->sequenceType === 'OOFF' || sizeof($db_results) == 0) {
                $bluem_config->merchantReturnURLBase = home_url(
                    'bluem-woocommerce/bluem-integrations/wpcf7_callback'
                );

                $preferences = get_option('bluem_woocommerce_options');

                // Convert UTF-8 to ISO
                // if (!empty($bluem_mandate_reason)) {
                // $bluem_config->eMandateReason = $bluem_mandate_reason . ' (' . $debtorReference . ')';
                // } else
                if (!empty($bluem_config->eMandateReason)) {
                    $bluem_config->eMandateReason = mb_convert_encoding($bluem_config->eMandateReason, 'ISO-8859-1', 'UTF-8');
                } else {
                    $bluem_config->eMandateReason = 'Incasso machtiging ' . $debtorReference;
                }

                $bluem = new Bluem($bluem_config);

                $mandate_id_counter = get_option('bluem_woocommerce_mandate_id_counter');

                if (!isset($mandate_id_counter)) {
                    $mandate_id_counter = $preferences['mandate_id_counter'];
                }

                $mandate_id = $mandate_id_counter + 1;

                update_option('bluem_woocommerce_mandate_id_counter', $mandate_id);

                $request = $bluem->CreateMandateRequest(
                    $debtorReference,
                    $debtorReference,
                    $mandate_id
                );

                // Actually perform the request.
                try {
                    $response = $bluem->PerformRequest($request);

                    if (!isset($response->EMandateTransactionResponse->TransactionURL)) {
                        $msg = 'Er ging iets mis bij het aanmaken van de transactie.<br>
                        Vermeld onderstaande informatie aan het websitebeheer:';

                        if (isset($response->EMandateTransactionResponse->Error->ErrorMessage)) {
                            $msg .= '<br>' .
                                $response->EMandateTransactionResponse->Error->ErrorMessage;
                        } elseif ($response instanceof \Bluem\BluemPHP\ErrorBluemResponse) {
                            $msg .= '<br>' .
                                $response->Error();
                        } else {
                            $msg .= '<br>Algemene fout';
                        }
                        bluem_error_report_email(
                            array(
                                'service' => 'mandates',
                                'function' => 'shortcode_execute',
                                'message' => $msg,
                            )
                        );

                        echo wp_json_encode(
                            array(
                                'success' => false,
                            )
                        );
                        die;
                    }

                    $mandate_id = $response->EMandateTransactionResponse->MandateID . '';

                    // redirect cast to string, necessary for AJAX response handling
                    $transactionURL = ($response->EMandateTransactionResponse->TransactionURL . '');

                    bluem_db_insert_storage(
                        array(
                            'bluem_mandate_transaction_id' => $mandate_id,
                            'bluem_mandate_transaction_url' => $transactionURL,
                            'bluem_integration_wpcf7_form_id' => $contact_form_id,
                            'bluem_mandate_entrance_code' => $request->entranceCode,
                        )
                    );

                    $db_creation_result = bluem_db_create_request(
                        array(
                            'entrance_code' => $request->entranceCode,
                            'transaction_id' => $request->mandateID,
                            'transaction_url' => $transactionURL,
                            'user_id' => 0,
                            'timestamp' => gmdate('Y-m-d H:i:s'),
                            'description' => 'Mandate request',
                            'debtor_reference' => $debtorReference,
                            'type' => 'mandates',
                            'order_id' => '',
                            'payload' => wp_json_encode(
                                array(
                                    'created_via' => 'contactform7',
                                    'environment' => $bluem->getConfig('environment'),
                                    'created_mandate_id' => $mandate_id,
                                    'contactform7' => wp_json_encode(
                                        array(
                                            'id' => $contact_form_id,
                                            'payload' => $posted_data,
                                        )
                                    ),
                                )
                            ),
                        )
                    );

                    echo wp_json_encode(
                        array(
                            'success' => true,
                            'redirect_uri' => esc_url($transactionURL),
                        )
                    );
                    die;
                } catch (Exception $e) {
                    echo wp_json_encode(
                        array(
                            'success' => false,
                        )
                    );
                    die;
                }
            }
        }
    }

    echo wp_json_encode(
        array(
            'success' => false,
        )
    );
    die;
}

/**
 * ContactForm 7 integration.
 * Form submissions.
 */
add_action('wpcf7_submit', 'bluem_woocommerce_integration_wpcf7_submit');

function bluem_woocommerce_integration_wpcf7_submit()
{
    $bluem_config = bluem_woocommerce_get_config();

    if ($bluem_config->wpcf7Active !== 'Y') {
        return;
    }

    $submission = WPCF7_Submission::get_instance();

    $contact_form = $submission->get_contact_form();

    $contact_form_id = $contact_form->id();

    $is_bluem_mandate = $contact_form->is_true('bluem_mandate');

    $bluem_mandate_approve = $contact_form->pref('bluem_mandate_approve');

    $bluem_mandate_reason = $contact_form->pref('bluem_mandate_reason');

    /**
     * TODO: Add to request.
     * Overwrite Bluem config variable.
     */
    $bluem_mandate_type = $contact_form->pref('bluem_mandate_type');

    $is_bluem_ajax = $contact_form->is_true('bluem_is_ajax');

    if ($is_bluem_mandate && !$is_bluem_ajax && !empty($bluem_mandate_approve) && !empty($submission)) {
        $posted_data = $submission->get_posted_data();

        $posted_data_hash = $submission->get_posted_data_hash();

        $debtorReference = bin2hex(random_bytes(15));

        if (!empty($debtorReference)) {
            $debtorReference = sanitize_text_field($debtorReference);

            $db_results = bluem_db_get_requests_by_keyvalues(
                array(
                    'debtor_reference' => $debtorReference,
                    'status' => 'Success',
                )
            );

            // Check the sequence type or previous success results
            if ($bluem_config->sequenceType === 'OOFF' || sizeof($db_results) == 0) {
                $bluem_config->merchantReturnURLBase = home_url(
                    'bluem-woocommerce/bluem-integrations/wpcf7_callback'
                );

                $preferences = get_option('bluem_woocommerce_options');

                // Convert UTF-8 to ISO
                if (!empty($bluem_mandate_reason)) {
                    $bluem_config->eMandateReason = $bluem_mandate_reason . ' (' . $debtorReference . ')';
                } elseif (!empty($bluem_config->eMandateReason)) {
                    $bluem_config->eMandateReason = mb_convert_encoding($bluem_config->eMandateReason, 'ISO-8859-1', 'UTF-8');
                } else {
                    $bluem_config->eMandateReason = 'Incasso machtiging ' . $debtorReference;
                }

                $bluem = new Bluem($bluem_config);

                $mandate_id_counter = get_option('bluem_woocommerce_mandate_id_counter');

                if (!isset($mandate_id_counter)) {
                    $mandate_id_counter = $preferences['mandate_id_counter'];
                }

                $mandate_id = $mandate_id_counter + 1;

                update_option('bluem_woocommerce_mandate_id_counter', $mandate_id);

                $request = $bluem->CreateMandateRequest(
                    $debtorReference,
                    $debtorReference,
                    $mandate_id
                );

                // Actually perform the request.
                try {
                    $response = $bluem->PerformRequest($request);

                    if (!isset($response->EMandateTransactionResponse->TransactionURL)) {
                        $msg = 'Er ging iets mis bij het aanmaken van de transactie.<br>
                        Vermeld onderstaande informatie aan het websitebeheer:';

                        if (isset($response->EMandateTransactionResponse->Error->ErrorMessage)) {
                            $msg .= '<br>' .
                                $response->EMandateTransactionResponse->Error->ErrorMessage;
                        } elseif ($response instanceof \Bluem\BluemPHP\ErrorBluemResponse) {
                            $msg .= '<br>' .
                                $response->Error();
                        } else {
                            $msg .= '<br>Algemene fout';
                        }
                        bluem_error_report_email(
                            array(
                                'service' => 'mandates',
                                'function' => 'wpcf7_execute',
                                'message' => $msg,
                            )
                        );

                        bluem_dialogs_render_prompt($msg);
                        exit;
                    }

                    $mandate_id = $response->EMandateTransactionResponse->MandateID . '';

                    // redirect cast to string, necessary for AJAX response handling
                    $transactionURL = ($response->EMandateTransactionResponse->TransactionURL . '');

                    bluem_db_insert_storage(
                        array(
                            'bluem_mandate_transaction_id' => $mandate_id,
                            'bluem_mandate_transaction_url' => $transactionURL,
                            'bluem_integration_wpcf7_form_id' => $contact_form_id,
                            'bluem_mandate_entrance_code' => $request->entranceCode,
                        )
                    );

                    $db_creation_result = bluem_db_create_request(
                        array(
                            'entrance_code' => $request->entranceCode,
                            'transaction_id' => $request->mandateID,
                            'transaction_url' => $transactionURL,
                            'user_id' => 0,
                            'timestamp' => gmdate('Y-m-d H:i:s'),
                            'description' => 'Mandate request',
                            'debtor_reference' => $debtorReference,
                            'type' => 'mandates',
                            'order_id' => '',
                            'payload' => wp_json_encode(
                                array(
                                    'created_via' => 'contactform7',
                                    'environment' => $bluem->getConfig('environment'),
                                    'created_mandate_id' => $mandate_id,
                                    'contactform7' => wp_json_encode(
                                        array(
                                            'id' => $contact_form_id,
                                            'payload' => $posted_data,
                                        )
                                    ),
                                )
                            ),
                        )
                    );

                    if (ob_get_length() !== false && ob_get_length() > 0) {
                        ob_clean();
                    }

                    ob_start();
                    wp_redirect($transactionURL);
                    exit;
                } catch (\Exception $e) {
                    exit('Error');
                }
            } else {
                wp_redirect($bluem_config->instantMandatesResponseURI . '?result=true');
                exit;
            }
        }
    }
}

/**
 * ContactForm 7 integration.
 * Callback for requests.
 */
add_action('parse_request', 'bluem_woocommerce_integration_wpcf7_callback');

function bluem_woocommerce_integration_wpcf7_callback()
{
    $bluem_config = bluem_woocommerce_get_config();

    $storage = bluem_db_get_storage();

    if (empty($_SERVER['REQUEST_URI']) || strpos(sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])), 'bluem-woocommerce/bluem-integrations/wpcf7_callback') === false) {
        return;
    }

    if ($bluem_config->wpcf7Active !== 'Y') {
        return;
    }

    try {
        $bluem = new Bluem($bluem_config);
    } catch (Exception $e) {
        // @todo: deal with incorrectly setup Bluem
    }

    $formID = $storage['bluem_integration_wpcf7_form_id'] ?? 0;

    $mandateID = $storage['bluem_mandate_transaction_id'] ?? 0;

    $entranceCode = $storage['bluem_mandate_entrance_code'] ?? '';

    if (empty($mandateID)) {
        if (!empty($bluem_config->wpcf7Resultpage)) {
            wp_redirect(home_url($bluem_config->wpcf7Resultpage) . "?form=$formID&result=false&reason=error");
            exit;
        }
        $errormessage = 'Fout: geen juist mandaat id teruggekregen bij callback. Neem contact op met de webshop en vermeld je contactgegevens.';
        bluem_error_report_email(
            array(
                'service' => 'mandates',
                'function' => 'wpcf7_callback',
                'message' => $errormessage,
            )
        );
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    if (empty($entranceCode)) {
        $errormessage = 'Fout: Entrancecode is niet set; kan dus geen mandaat opvragen';
        bluem_error_report_email(
            array(
                'service' => 'mandates',
                'function' => 'wpcf7_callback',
                'message' => $errormessage,
            )
        );
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    $response = $bluem->MandateStatus($mandateID, $entranceCode);

    if (!$response->Status()) {
        $errormessage = 'Fout bij opvragen status: ' . $response->Error() . '
        <br>Neem contact op met de webshop en vermeld deze status';
        bluem_error_report_email(
            array(
                'service' => 'mandates',
                'function' => 'wpcf7_callback',
                'message' => $errormessage,
            )
        );
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }
    $statusUpdateObject = $response->EMandateStatusUpdate;
    $statusCode = $statusUpdateObject->EMandateStatus->Status . '';

    $request_from_db = bluem_db_get_request_by_transaction_id_and_type(
        $mandateID,
        'mandates'
    );

    if ($statusCode !== $request_from_db->status) {
        bluem_db_update_request(
            $request_from_db->id,
            array(
                'status' => $statusCode,
            )
        );
        // also update locally for email notification
        $request_from_db->status = $statusCode;
    }

    bluem_transaction_notification_email(
        $request_from_db->id
    );

    // Handling the response.
    if ($statusCode === 'Success') {
        if (!empty($request_from_db->payload)) {
            try {
                $newPayload = json_decode($request_from_db->payload);
            } catch (Throwable $th) {
                $newPayload = new Stdclass();
            }
        } else {
            $newPayload = new Stdclass();
        }

        if (isset($response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport)) {
            $newPayload->purchaseID = $response->EMandateStatusUpdate->EMandateStatus->PurchaseID . '';
            $newPayload->report = $response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport;

            bluem_db_update_request(
                $request_from_db->id,
                array(
                    'payload' => wp_json_encode($newPayload),
                )
            );
        }

        $request_from_db = bluem_db_get_request_by_transaction_id_and_type(
            $mandateID,
            'mandates'
        );

        // "De ondertekening is geslaagd";
        if (!empty($bluem_config->wpcf7Resultpage)) {
            wp_redirect(home_url($bluem_config->wpcf7Resultpage) . "?form=$formID&result=true");
            exit;
        }
        $errormessage = 'Fout: de ondertekening is geslaagd maar er is geen response URI opgegeven. Neem contact op met de website om dit technisch probleem aan te geven.';
        bluem_error_report_email(
            array(
                'service' => 'mandates',
                'function' => 'wpcf7_callback',
                'message' => $errormessage,
            )
        );
        bluem_dialogs_render_prompt($errormessage);
        return;
    }

    if ($statusCode === 'Cancelled') {
        // "Je hebt de mandaat ondertekening geannuleerd";
        if (!empty($bluem_config->wpcf7Resultpage)) {
            wp_redirect(home_url($bluem_config->wpcf7Resultpage) . "?form=$formID&result=false&reason=cancelled");
            exit;
        }
        $errormessage = 'Fout: de transactie is geannuleerd. Probeer het opnieuw.';
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    if ($statusCode === 'Open' || $statusCode === 'Pending') {
        // "De mandaatondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch."
        if (!empty($bluem_config->wpcf7Resultpage)) {
            wp_redirect(home_url($bluem_config->wpcf7Resultpage) . "?form=$formID&result=false&reason=open");
            exit;
        }
        $errormessage = 'Fout: de transactie staat nog open. Dit kan even duren. Vernieuw deze pagina regelmatig voor de status.';
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    if ($statusCode === 'Expired') {
        // "Fout: De mandaat of het verzoek daartoe is verlopen";
        if (!empty($bluem_config->wpcf7Resultpage)) {
            wp_redirect(home_url($bluem_config->wpcf7Resultpage) . "?form=$formID&result=false&reason=expired");
            exit;
        }
        $errormessage = 'Fout: de transactie is verlopen. Probeer het opnieuw.';
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    bluem_error_report_email(
        array(
            'service' => 'mandates',
            'function' => 'wpcf7_callback',
            'message' => "Fout: Onbekende of foutieve status teruggekregen: $statusCode<br>Neem contact op met de webshop en vermeld deze status; gebruiker wel doorverwezen terug naar site",
        )
    );

    if (!empty($bluem_config->wpcf7Resultpage)) {
        wp_redirect(home_url($bluem_config->wpcf7Resultpage) . "?form=$formID&result=false&reason=error");
        exit;
    }
    $errormessage = 'Fout: er is een onbekende fout opgetreden. Probeer het opnieuw.';
    bluem_dialogs_render_prompt($errormessage);
    exit;
}

/**
 * ContactForm 7 integration.
 * Shortcode to display results.
 */
add_shortcode('bluem_resultaatpagina', 'bluem_woocommerce_integration_wpcf7_results_shortcode');
add_shortcode('bluem_wpcf7_results', 'bluem_woocommerce_integration_wpcf7_results_shortcode');

function bluem_woocommerce_integration_wpcf7_results_shortcode()
{
    $bluem_config = bluem_woocommerce_get_config();

    if ($bluem_config->wpcf7Active !== 'Y') {
        return '';
    }

    if (empty($_GET['form']) || empty($_GET['result'])) {
        return 'Er is een fout opgetreden. Ga terug en probeer het opnieuw.';
    }

    if (!empty($_GET['form'])) {

        $contact_form = WPCF7_ContactForm::get_instance(sanitize_text_field(wp_unslash($_GET['form'])));

        if (!empty($contact_form)) {
            if (!empty($_GET['result']) && wp_unslash($_GET['result']) === 'true') {
                return '<p>' . $contact_form->pref('bluem_mandate_success') . '</p>';
            }
        }
    }
    return '<p>' . $contact_form->pref('bluem_mandate_failure') . '</p>';
}

/**
 * Gravity Forms integration.
 * Hook for submissions.
 */
add_action('gform_after_submission', 'bluem_woocommerce_integration_gform_submit', 10, 2);

function bluem_woocommerce_integration_gform_submit($entry, $form)
{
    $bluem_config = bluem_woocommerce_get_config();

    if ($bluem_config->gformActive !== 'Y') {
        return;
    }

    /**
     * Define form data.
     */
    $form_data = array();

    // Loop through fields
    foreach ($form['fields'] as $field) {
        $inputs = $field->get_entry_inputs();
        if (is_array($inputs)) {
            foreach ($inputs as $input) {
                $value = rgar($entry, (string)$input['id']);
            }
        } else {
            $value = rgar($entry, (string)$field->id);

            if (empty($value)) {
                $value = $field->defaultValue;
            }
        }

        if (!empty($field->inputName) && !empty($value) && strpos($field->inputName, 'bluem_') === 0) {
            // echo " set $value for input {$field->inputName}";
            $form_data[$field->inputName] = $value;
        }

        if (!empty($field->label) && !empty($value) && strpos($field->label, 'bluem_') === 0) {
            // echo " set $value for label {$field->label}";
            $form_data[$field->label] = $value;
        }
    }

    // Get custom parameters for this form
    $bluem_mandate = $form_data['bluem_mandate'] ?? null;
    $bluem_mandate_approve = $form_data['bluem_mandate_approve'] ?? null;
    $bluem_mandate_success = $form_data['bluem_mandate_success'] ?? null;
    $bluem_mandate_failure = $form_data['bluem_mandate_failure'] ?? null;
    $bluem_mandate_reason = $form_data['bluem_mandate_reason'] ?? null;
    $bluem_mandate_type = $form_data['bluem_mandate_type'] ?? null;

    $bluem_is_ajax = $form_data['bluem_is_ajax'] ?? null;

    /**
     * Define payload for Bluem.
     */
    $payload = array(
        'source_url' => $entry['source_url'],
        'form_id' => $entry['form_id'],
        'entry_id' => $entry['id'],
        'ip' => $entry['ip'],
    );

    /**
     * Do mandate request.
     */
    if ($bluem_mandate === 'true' && $bluem_mandate_approve === 'true') {
        $debtorReference = bin2hex(random_bytes(15));

        if (empty($debtorReference)) {
            return;
        }

        // Define debtor reference
        $debtorReference = sanitize_text_field($debtorReference);

        // Get previous requests by debtor reference
        $db_results = bluem_db_get_requests_by_keyvalues(
            array(
                'debtor_reference' => $debtorReference,
                'status' => 'Success',
            )
        );

        // Check for mandate type
        if (!empty($bluem_mandate_type)) {
            $bluem_config->sequenceType = $bluem_mandate_type;
        }

        // Check the sequence type or previous success results
        if ($bluem_config->sequenceType === 'OOFF' || sizeof($db_results) == 0) {
            $bluem_config->merchantReturnURLBase = home_url(
                'bluem-woocommerce/bluem-integrations/gform_callback'
            );

            $preferences = get_option('bluem_woocommerce_options');

            // Convert UTF-8 to ISO
            if (!empty($bluem_mandate_reason)) {
                $bluem_config->eMandateReason = $bluem_mandate_reason . ' (' . $debtorReference . ')';
            } elseif (!empty($bluem_config->eMandateReason)) {
                $bluem_config->eMandateReason = mb_convert_encoding($bluem_config->eMandateReason, 'ISO-8859-1', 'UTF-8');
            } else {
                $bluem_config->eMandateReason = 'Incasso machtiging ' . $debtorReference;
            }

            try {
                $bluem = new Bluem($bluem_config);
            } catch (Exception $e) {
                echo('Kon gravity form niet goed uitvoeren; check je instellingen');
                die();
            }

            $mandate_id_counter = get_option('bluem_woocommerce_mandate_id_counter');

            if (!isset($mandate_id_counter)) {
                $mandate_id_counter = $preferences['mandate_id_counter'];
            }

            $mandate_id = $mandate_id_counter + 1;

            update_option('bluem_woocommerce_mandate_id_counter', $mandate_id);

            try {
                $request = $bluem->CreateMandateRequest(
                    $debtorReference,
                    $debtorReference,
                    $mandate_id
                );

                // Actually perform the request.

                $response = $bluem->PerformRequest($request);

                if (!isset($response->EMandateTransactionResponse->TransactionURL)) {
                    $msg = 'Er ging iets mis bij het aanmaken van de transactie.<br>
                    Vermeld onderstaande informatie aan het websitebeheer:';

                    if (isset($response->EMandateTransactionResponse->Error->ErrorMessage)) {
                        $msg .= '<br>' .
                            $response->EMandateTransactionResponse->Error->ErrorMessage;
                    } elseif (get_class($response) === 'Bluem\BluemPHP\ErrorBluemResponse') {
                        $msg .= '<br>' .
                            $response->Error();
                    } else {
                        $msg .= '<br>Algemene fout';
                    }

                    bluem_error_report_email(
                        array(
                            'service' => 'mandates',
                            'function' => 'gform_execute',
                            'message' => $msg,
                        )
                    );

                    bluem_dialogs_render_prompt($msg);
                    exit;
                }

                $mandate_id = $response->EMandateTransactionResponse->MandateID . '';

                // redirect cast to string, necessary for AJAX response handling
                $transactionURL = ($response->EMandateTransactionResponse->TransactionURL . '');

                bluem_db_insert_storage(
                    array(
                        'bluem_mandate_transaction_id' => $mandate_id,
                        'bluem_mandate_transaction_url' => $transactionURL,
                        'bluem_integration_gform_form_id' => $payload['form_id'],
                        'bluem_integration_gform_entry_id' => $payload['entry_id'],
                        'bluem_mandate_entrance_code' => $request->entranceCode,
                    )
                );

                $db_creation_result = bluem_db_create_request(
                    array(
                        'entrance_code' => $request->entranceCode,
                        'transaction_id' => $request->mandateID,
                        'transaction_url' => $transactionURL,
                        'user_id' => 0,
                        'timestamp' => gmdate('Y-m-d H:i:s'),
                        'description' => 'Mandate request',
                        'debtor_reference' => $debtorReference,
                        'type' => 'mandates',
                        'order_id' => '',
                        'payload' => wp_json_encode(
                            array(
                                'created_via' => 'gform',
                                'environment' => $bluem->getConfig('environment'),
                                'created_mandate_id' => $mandate_id,
                                'details' => wp_json_encode(
                                    array(
                                        'id' => $payload['form_id'],
                                        'payload' => wp_json_encode($payload),
                                    )
                                ),
                            )
                        ),
                    )
                );

                /**
                 * Get Gravity Form entry.
                 */
                $entry = GFAPI::get_entry($payload['entry_id']);
                if (is_wp_error($entry)) {
                    // Handle error
                }

                /**
                 * Update Gravity Forms details.
                 */
                $entry['bluem_payload'] = array(
                    'mandate_id' => $request->mandateID,
                    'mandate_entrance_code' => $request->entranceCode,
                    'bluem_record_id' => $db_creation_result,
                );

                // Update the entry
                $result = GFAPI::update_entry($entry);

                if (is_wp_error($result)) {
                    // Handle error
                    // var_dump( $result );
                    die();
                } else {
                    // Entry updated successfully
                }

                if (ob_get_length() !== false && ob_get_length() > 0) {
                    ob_clean();
                }

                if ($bluem_is_ajax === 'true') {
                    echo wp_json_encode(
                        array(
                            'success' => true,
                            'redirect_url' => esc_url($transactionURL),
                        )
                    );
                } else {
                    ob_start();
                    wp_redirect($transactionURL);
                }
                exit;
            } catch (Exception $e) {
                // var_dump($e->getMessage());
                die();
            }
        }
    }
}

/**
 * Gravity Forms integration.
 * Callback after request
 */
add_action('parse_request', 'bluem_woocommerce_integration_gform_callback');

function bluem_woocommerce_integration_gform_callback()
{
    $bluem_config = bluem_woocommerce_get_config();

    $storage = bluem_db_get_storage();

    if (strpos(sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])), 'bluem-woocommerce/bluem-integrations/gform_callback') === false) {
        return;
    }

    if ($bluem_config->gformActive !== 'Y') {
        return;
    }

    try {
        $bluem = new Bluem($bluem_config);
    } catch (Exception $e) {
        // @todo: deal with incorrectly setup Bluem
    }

    $formID = $storage['bluem_integration_gform_form_id'] ?? 0;

    $entryID = $storage['bluem_integration_gform_entry_id'] ?? 0;

    $mandateID = $storage['bluem_mandate_transaction_id'] ?? 0;

    $entranceCode = $storage['bluem_mandate_entrance_code'] ?? '';

    if (empty($mandateID)) {
        if (!empty($bluem_config->gformResultpage)) {
            wp_redirect(home_url($bluem_config->gformResultpage) . "?form=$formID&entry=$entryID&result=false&reason=error");
            exit;
        }
        $errormessage = 'Fout: geen juist mandaat id teruggekregen bij callback. Neem contact op met de webshop en vermeld je contactgegevens.';
        bluem_error_report_email(
            array(
                'service' => 'mandates',
                'function' => 'gform_callback',
                'message' => $errormessage,
            )
        );
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    if (empty($entranceCode)) {
        $errormessage = 'Fout: Entrancecode is niet set; kan dus geen mandaat opvragen';
        bluem_error_report_email(
            array(
                'service' => 'mandates',
                'function' => 'gform_callback',
                'message' => $errormessage,
            )
        );
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    $response = $bluem->MandateStatus($mandateID, $entranceCode);

    if (!$response->Status()) {
        $errormessage = 'Fout bij opvragen status: ' . $response->Error() . '
        <br>Neem contact op met de webshop en vermeld deze status';
        bluem_error_report_email(
            array(
                'service' => 'mandates',
                'function' => 'gform_callback',
                'message' => $errormessage,
            )
        );
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }
    $statusUpdateObject = $response->EMandateStatusUpdate;
    $statusCode = $statusUpdateObject->EMandateStatus->Status . '';

    $request_from_db = bluem_db_get_request_by_transaction_id_and_type(
        $mandateID,
        'mandates'
    );

    if ($statusCode !== $request_from_db->status) {
        bluem_db_update_request(
            $request_from_db->id,
            array(
                'status' => $statusCode,
            )
        );
        // also update locally for email notification
        $request_from_db->status = $statusCode;
    }

    bluem_transaction_notification_email(
        $request_from_db->id
    );

    // Handling the response.
    if ($statusCode === 'Success') {
        if (!empty($request_from_db->payload)) {
            try {
                $newPayload = json_decode($request_from_db->payload);
            } catch (Throwable $th) {
                $newPayload = new Stdclass();
            }
        } else {
            $newPayload = new Stdclass();
        }

        if (isset($response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport)) {
            $newPayload->purchaseID = $response->EMandateStatusUpdate->EMandateStatus->PurchaseID . '';
            $newPayload->report = $response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport;

            bluem_db_update_request(
                $request_from_db->id,
                array(
                    'payload' => wp_json_encode($newPayload),
                )
            );
        }

        $request_from_db = bluem_db_get_request_by_transaction_id_and_type(
            $mandateID,
            'mandates'
        );

        if (!empty($entryID) && !empty($formID)) {
            /**
             * Get the entry instance.
             */
            $entry = GFAPI::get_entry($entryID);

            // Get the form instance
            $form = GFAPI::get_form($formID);

            /**
             * Update Gravity Forms details.
             */
            $entry['bluem_payload'] = array(
                'mandate_id' => !empty($mandateID) ? $mandateID : '',
                'mandate_entrance_code' => !empty($entranceCode) ? $entranceCode : '',
                'bluem_record_id' => !empty($request_from_db) ? $request_from_db->id : '',
                'status' => !empty($statusCode) ? $statusCode : '',
            );

            // Update the entry
            $result = GFAPI::update_entry($entry);

            /**
             * Define form data.
             */
            $edit_data = array();
            $form_data = array();

            // Get the fields
            $fields = $form['fields'];

            // Loop through fields
            foreach ($fields as $field) {
                $field_id = $field['id'];
                $field_name = $field['inputName'];
                $field_label = $field['label'];
                $field_value = rgar($entry, $field_id);

                if (!empty($field_name)) {
                    $edit_data[] = array(
                        'field_id' => $field_id,
                        'field_name' => $field_name,
                        'field_label' => $field_label,
                        'field_value' => $field_value,
                    );
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

                $payload = json_decode(wp_json_encode($newPayload));

                $mandateDateTimeFormatted = '';
                try {
                    $mandateDateTime = new DateTimeImmutable($payload->report->DateTime);
                    $mandateDateTimeFormatted = $mandateDateTime->format('d-m-Y H:i');
                } catch (Exception $e) {
                }

                if ($value['field_name'] === 'bluem_mandate_accountname') {
                    $newValue = $payload->report->DebtorAccountName;
                } elseif ($value['field_name'] === 'bluem_mandate_request_id') {
                    $newValue = $payload->report->MandateRequestID;
                } elseif ($value['field_name'] === 'bluem_mandate_datetime') {
                    $newValue = $mandateDateTimeFormatted;
                } elseif ($value['field_name'] === 'bluem_mandate_iban') {
                    $newValue = $payload->report->DebtorIBAN;
                }

                /**
                 * Edit field.
                 */
                if (!empty($newValue)) {
                    $result = GFAPI::update_entry_field($entryID, $value['field_id'], $newValue);
                }
            }
        }

        // "De ondertekening is geslaagd";
        if (!empty($bluem_config->gformResultpage)) {
            wp_redirect(home_url($bluem_config->gformResultpage) . "?form=$formID&entry=$entryID&mid=$mandateID&ec=$entranceCode&result=true");
            exit;
        }
        $errormessage = 'Fout: de ondertekening is geslaagd maar er is geen response URI opgegeven. Neem contact op met de website om dit technisch probleem aan te geven.';
        bluem_error_report_email(
            array(
                'service' => 'mandates',
                'function' => 'gform_callback',
                'message' => $errormessage,
            )
        );
        bluem_dialogs_render_prompt($errormessage);
        return;
    } elseif ($statusCode === 'Cancelled') {
        // "Je hebt de mandaat ondertekening geannuleerd";
        if (!empty($bluem_config->gformResultpage)) {
            wp_redirect(home_url($bluem_config->gformResultpage) . "?form=$formID&entry=$entryID&mid=$mandateID&ec=$entranceCode&result=false&reason=cancelled");
            exit;
        }
        $errormessage = 'Fout: de transactie is geannuleerd. Probeer het opnieuw.';
        bluem_dialogs_render_prompt($errormessage);
        exit;
    } elseif ($statusCode === 'Open' || $statusCode == 'Pending') {
        // "De mandaat ondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch."
        if (!empty($bluem_config->gformResultpage)) {
            wp_redirect(home_url($bluem_config->gformResultpage) . "?form=$formID&entry=$entryID&mid=$mandateID&ec=$entranceCode&result=false&reason=open");
            exit;
        }
        $errormessage = 'Fout: de transactie staat nog open. Dit kan even duren. Vernieuw deze pagina regelmatig voor de status.';
        bluem_dialogs_render_prompt($errormessage);
        exit;
    } elseif ($statusCode === 'Expired') {
        // "Fout: De mandaat of het verzoek daartoe is verlopen";
        if (!empty($bluem_config->gformResultpage)) {
            wp_redirect(home_url($bluem_config->gformResultpage) . "?form=$formID&entry=$entryID&mid=$mandateID&ec=$entranceCode&result=false&reason=expired");
            exit;
        }
        $errormessage = 'Fout: de transactie is verlopen. Probeer het opnieuw.';
        bluem_dialogs_render_prompt($errormessage);
        exit;
    } else {
        bluem_error_report_email(
            array(
                'service' => 'mandates',
                'function' => 'gform_callback',
                'message' => "Fout: Onbekende of foutieve status teruggekregen: {$statusCode}<br>Neem contact op met de webshop en vermeld deze status; gebruiker wel doorverwezen terug naar site",
            )
        );
        if (!empty($bluem_config->gformResultpage)) {
            wp_redirect(home_url($bluem_config->gformResultpage) . "?form=$formID&entry=$entryID&mid=$mandateID&ec=$entranceCode&result=false&reason=error");
            exit;
        }
        $errormessage = 'Fout: er is een onbekende fout opgetreden. Probeer het opnieuw.';
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }
    exit;
}

/**
 * Gravity Forms integration.
 * Shortcode to display results.
 */
add_shortcode('bluem_gform_results', 'bluem_woocommerce_integration_gform_results_shortcode');

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
        sanitize_text_field(wp_unslash($_GET['mid'])),
        sanitize_text_field(wp_unslash($_GET['ec'])),
    );

    if ($request_from_db !== false) {
        $request_id = $request_from_db->id;

        $entrance_code = $request_from_db->entrance_code;

        $transaction_id = $request_from_db->transaction_id;

        if (!empty($request_from_db->payload)) {
            $payload = json_decode($request_from_db->payload);

            if (!empty($payload) && !empty($payload->details)) {
                $details = json_decode($payload->details);

                if (!empty($details->payload)) {
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

    if (!empty($entry_id)) {
        /**
         * Get the entry instance.
         */
        $entry = GFAPI::get_entry($entry_id);

        // Get the form instance
        $form = GFAPI::get_form($entry['form_id']);

        /**
         * Define form data.
         */
        $form_data = array();

        // Get the fields
        $fields = $form['fields'];

        // Loop through fields
        foreach ($fields as $field) {
            $field_id = $field['id'];
            $field_name = $field['inputName'];
            $field_label = $field['label'];
            $field_value = rgar($entry, $field_id);

            if (!empty($field_label)) {
                $form_data[$field_label] = $field_value;
            } elseif (!empty($field_id)) {
                $form_data[$field_id] = $field_value;
            }
        }
    }

    if (!empty($status)) {
        if ($status === 'Success') {
            return '<p>' . !empty($form_data) && !empty($form_data['bluem_mandate_success']) ? $form_data['bluem_mandate_success'] : 'De machtiging is gelukt.' . '</p>';
        } else {
            return '<p>' . !empty($form_data) && !empty($form_data['bluem_mandate_failure']) ? $form_data['bluem_mandate_failure'] : 'De machtiging is mislukt. Probeer het opnieuw.' . '</p>';
        }
    } else {
        return '<p>' . !empty($form_data) && !empty($form_data['bluem_mandate_failure']) ? $form_data['bluem_mandate_failure'] : 'De machtiging is mislukt. Probeer het opnieuw.' . '</p>';
    }
}
