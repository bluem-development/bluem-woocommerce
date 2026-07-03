<?php

if (!defined('ABSPATH')) {
    exit;
}

use Bluem\BluemPHP\Bluem;

function bluem_mandates_instant_request(): void
{
    $bluem_config = bluem_woocommerce_get_config();

    $debtorReference = !empty($_GET['debtorreference']) ? sanitize_text_field(wp_unslash($_GET['debtorreference'])) : '';
    // get from either casing for the key
    if (empty($debtorReference)) {
        $debtorReference = !empty($_GET['debtorReference']) ? sanitize_text_field(wp_unslash($_GET['debtorReference'])) : '';
    }

    if (empty($debtorReference)) {
        $errormessage = esc_html__('Error: no debtorReference specified', 'bluem');
        bluem_error_report_email(
            [
                'service' => 'mandates',
                'function' => 'shortcode_execute',
                'message' => $errormessage,
            ]
        );
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    $debtorReference = sanitize_text_field($debtorReference);

    $db_results = bluem_db_get_requests_by_keyvalues(
        [
            'debtor_reference' => $debtorReference,
            'status' => 'Success',
        ]
    );

    // Check the sequence type or previous success results
    if ($bluem_config->sequenceType === 'OOFF' || sizeof($db_results) === 0) {
        $bluem_config->merchantReturnURLBase = home_url(
            'bluem-woocommerce/mandates_instant_callback'
        );

        $preferences = get_option('bluem_woocommerce_options');

        // Convert UTF-8 to ISO
        if (!empty($bluem_config->eMandateReason)) {
            $bluem_config->eMandateReason = mb_convert_encoding($bluem_config->eMandateReason, 'ISO-8859-1', 'UTF-8');
        } else {
            $bluem_config->eMandateReason = esc_html__('Direct debit mandate ', 'bluem') . $debtorReference;
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
                $msg = esc_html__(
                    'Something went wrong while creating the transaction.<br>
                Please provide the information below to the website administrator:',
                    'bluem'
                );

                if (isset($response->EMandateTransactionResponse->Error->ErrorMessage)) {
                    $msg .= '<br>'
                        . $response->EMandateTransactionResponse->Error->ErrorMessage;
                } elseif ($response instanceof \Bluem\BluemPHP\ErrorBluemResponse) {
                    $msg .= '<br>'
                        . $response->Error();
                } else {
                    $msg .= '<br>General error';
                }
                bluem_error_report_email(
                    [
                        'service' => 'mandates',
                        'function' => 'shortcode_execute',
                        'message' => $msg,
                    ]
                );
                bluem_dialogs_render_prompt($msg);
                exit;
            }

            $mandate_id = $response->EMandateTransactionResponse->MandateID . '';

            // redirect cast to string, necessary for AJAX response handling
            $transactionURL = ($response->EMandateTransactionResponse->TransactionURL . '');

            bluem_db_insert_storage(
                [
                    'bluem_mandate_transaction_id' => $mandate_id,
                    'bluem_mandate_transaction_url' => $transactionURL,
                    'bluem_mandate_entrance_code' => $request->entranceCode,
                ]
            );

            $db_creation_result = bluem_db_create_request(
                [
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
                        [
                            'created_via' => 'instant_request',
                            'environment' => $bluem->getConfig('environment'),
                            'created_mandate_id' => $mandate_id,
                        ]
                    ),
                ]
            );

            if (ob_get_length() !== false && ob_get_length() > 0) {
                ob_clean();
            }

            ob_start();
            wp_redirect($transactionURL);
            exit;
        } catch (\Exception $e) {
        }
    } else {
        wp_redirect($bluem_config->instantMandatesResponseURI . '?result=true');
        exit;
    }
}

/**
 * This function is executed at a callback GET request with a given mandateId. This is then, together with the entranceCode in Cookie, sent for a SUD to the Bluem API.
 *
 * @return void
 */
function bluem_mandates_instant_callback()
{
    $bluem_config = bluem_woocommerce_get_config();

    try {
        $bluem = new Bluem($bluem_config);
    } catch (Exception $e) {
        // @todo: deal with incorrectly setup Bluem
    }

    $storage = bluem_db_get_storage();

    $mandateID = $storage['bluem_mandate_transaction_id'] ?? 0;

    $entranceCode = $storage['bluem_mandate_entrance_code'] ?? '';

    if (empty($mandateID)) {
        if (!empty($bluem_config->instantMandatesResponseURI)) {
            wp_redirect($bluem_config->instantMandatesResponseURI . '?result=false&reason=error');
            exit;
        }
        $errormessage = esc_html__('Error: no valid mandate ID was returned during callback. Please contact the webshop and mention your contact details.', 'bluem');
        bluem_error_report_email(
            [
                'service' => 'mandates',
                'function' => 'shortcode_callback',
                'message' => $errormessage,
            ]
        );
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    if (empty($entranceCode)) {
        $errormessage = esc_html__('Error: EntranceCode is not set, so the mandate cannot be retrieved.', 'bluem');
        bluem_error_report_email(
            [
                'service' => 'mandates',
                'function' => 'shortcode_callback',
                'message' => $errormessage,
            ]
        );
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    $response = $bluem->MandateStatus($mandateID, $entranceCode);

    if (!$response->Status()) {
        $errormessage = sprintf(
            /* translators: %s: error status */
            esc_html__('Error retrieving status: %s. Please contact the webshop and mention this status.', 'bluem'),
            $response->Error()
        );
        bluem_error_report_email(
            [
                'service' => 'mandates',
                'function' => 'shortcode_callback',
                'message' => $errormessage,
            ]
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
            [
                'status' => $statusCode,
            ]
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
                [
                    'payload' => wp_json_encode($newPayload),
                ]
            );
        }

        $request_from_db = bluem_db_get_request_by_transaction_id_and_type(
            $mandateID,
            'mandates'
        );

        // "The signing succeeded";
        if (!empty($bluem_config->instantMandatesResponseURI)) {
            wp_redirect($bluem_config->instantMandatesResponseURI . '?result=true');
            exit;
        }
        $errormessage = esc_html__('Error: the signing succeeded but no response URI was specified. Please contact the website to report this technical issue.', 'bluem');
        bluem_error_report_email(
            [
                'service' => 'mandates',
                'function' => 'instant_callback',
                'message' => $errormessage,
            ]
        );
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    if ($statusCode === 'Cancelled') {
        // "You canceled the mandate signing";
        if (!empty($bluem_config->instantMandatesResponseURI)) {
            wp_redirect($bluem_config->instantMandatesResponseURI . '?result=false&reason=cancelled');
            exit;
        }
        $errormessage = esc_html__('Error: the transaction was canceled. Please try again.', 'bluem');
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    if ($statusCode === 'Open' || $statusCode === 'Pending') {
        // "The mandate signing has not yet been confirmed. This may take a moment but happens automatically."
        if (!empty($bluem_config->instantMandatesResponseURI)) {
            wp_redirect($bluem_config->instantMandatesResponseURI . '?result=false&reason=open');
            exit;
        }
        $errormessage = esc_html__('Error: the transaction is still open. This may take a moment. Refresh this page regularly to check the status.', 'bluem');
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    if ($statusCode === 'Expired') {
        // "Error: the mandate or mandate request has expired";
        if (!empty($bluem_config->instantMandatesResponseURI)) {
            wp_redirect($bluem_config->instantMandatesResponseURI . '?result=false&reason=expired');
            exit;
        }
        $errormessage = esc_html__('Error: the transaction has expired. Please try again.', 'bluem');
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    bluem_error_report_email(
        [
            'service' => 'mandates',
            'function' => 'shortcode_callback',
            'message' => sprintf(
                /* translators: %s: status code */
                esc_html__('Error: unknown or invalid status received: %s<br>Please contact the webshop and mention this status; the user has been redirected back to the site.', 'bluem'),
                $statusCode
            ),
        ]
    );
    wp_redirect($bluem_config->instantMandatesResponseURI . '?result=false&reason=error');
    exit;
}
