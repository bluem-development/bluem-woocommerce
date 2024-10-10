<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Bluem\BluemPHP\Bluem;

add_action( 'parse_request', 'bluem_mandates_instant_request' );

function bluem_mandates_instant_request(): void {
	if ( empty( $_SERVER['REQUEST_URI'] ) || ! str_contains( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'bluem-woocommerce/mandate_instant_request' ) ) {
		return;
	}

	$bluem_config = bluem_woocommerce_get_config();

	$debtorReference = ! empty( $_GET['debtorreference'] ) ? sanitize_text_field( wp_unslash( $_GET['debtorreference'] ) ) : '';

	if ( ! empty( $debtorReference ) ) {
		$debtorReference = sanitize_text_field( $debtorReference );

		$db_results = bluem_db_get_requests_by_keyvalues(
			array(
				'debtor_reference' => $debtorReference,
				'status'           => 'Success',
			)
		);

		// Check the sequence type or previous success results
		if ( $bluem_config->sequenceType === 'OOFF' || sizeof( $db_results ) === 0 ) {
			$bluem_config->merchantReturnURLBase = home_url(
				'bluem-woocommerce/mandates_instant_callback'
			);

			$preferences = get_option( 'bluem_woocommerce_options' );

			// Convert UTF-8 to ISO
			if ( ! empty( $bluem_config->eMandateReason ) ) {
				$bluem_config->eMandateReason = mb_convert_encoding( $bluem_config->eMandateReason, 'ISO-8859-1', 'UTF-8' );
			} else {
				$bluem_config->eMandateReason = esc_html__( 'Incasso machtiging ', 'bluem' ) . $debtorReference;
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

			// Actually perform the request.
			try {
				$response = $bluem->PerformRequest( $request );

				if ( ! isset( $response->EMandateTransactionResponse->TransactionURL ) ) {
					$msg = esc_html__(
						'Er ging iets mis bij het aanmaken van de transactie.<br>
                    Vermeld onderstaande informatie aan het websitebeheer:',
						'bluem'
					);

					if ( isset( $response->EMandateTransactionResponse->Error->ErrorMessage ) ) {
						$msg .= '<br>' .
							$response->EMandateTransactionResponse->Error->ErrorMessage;
					} elseif ( $response instanceof \Bluem\BluemPHP\ErrorBluemResponse ) {
						$msg .= '<br>' .
							$response->Error();
					} else {
						$msg .= '<br>Algemene fout';
					}
					bluem_error_report_email(
						array(
							'service'  => 'mandates',
							'function' => 'shortcode_execute',
							'message'  => $msg,
						)
					);
					bluem_dialogs_render_prompt( $msg );
					exit;
				}

				$mandate_id = $response->EMandateTransactionResponse->MandateID . '';

				// redirect cast to string, necessary for AJAX response handling
				$transactionURL = ( $response->EMandateTransactionResponse->TransactionURL . '' );

				bluem_db_insert_storage(
					array(
						'bluem_mandate_transaction_id'  => $mandate_id,
						'bluem_mandate_transaction_url' => $transactionURL,
						'bluem_mandate_entrance_code'   => $request->entranceCode,
					)
				);

				$db_creation_result = bluem_db_create_request(
					array(
						'entrance_code'    => $request->entranceCode,
						'transaction_id'   => $request->mandateID,
						'transaction_url'  => $transactionURL,
						'user_id'          => 0,
						'timestamp'        => gmdate( 'Y-m-d H:i:s' ),
						'description'      => 'Mandate request',
						'debtor_reference' => $debtorReference,
						'type'             => 'mandates',
						'order_id'         => '',
						'payload'          => wp_json_encode(
							array(
								'created_via'        => 'instant_request',
								'environment'        => $bluem->getConfig( 'environment' ),
								'created_mandate_id' => $mandate_id,
							)
						),
					)
				);

				if ( ob_get_length() !== false && ob_get_length() > 0 ) {
					ob_clean();
				}

				ob_start();
				wp_redirect( $transactionURL );
				exit;
			} catch ( \Exception $e ) {

			}
		} else {
			wp_redirect( $bluem_config->instantMandatesResponseURI . '?result=true' );
			exit;
		}
	}
	exit;
}

add_action( 'parse_request', 'bluem_mandates_instant_callback' );

/**
 * This function is executed at a callback GET request with a given mandateId. This is then, together with the entranceCode in Cookie, sent for a SUD to the Bluem API.
 *
 * @return void
 */
function bluem_mandates_instant_callback() {
	if ( empty( $_SERVER['REQUEST_URI'] ) || ( strpos( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'bluem-woocommerce/mandates_instant_callback' ) === false ) ) {
		return;
	}

	$bluem_config = bluem_woocommerce_get_config();

	try {
		$bluem = new Bluem( $bluem_config );
	} catch ( Exception $e ) {
		// @todo: deal with incorrectly setup Bluem
	}

	$storage = bluem_db_get_storage();

	$mandateID = $storage['bluem_mandate_transaction_id'] ?? 0;

	$entranceCode = $storage['bluem_mandate_entrance_code'] ?? '';

	if ( empty( $mandateID ) ) {
		if ( ! empty( $bluem_config->instantMandatesResponseURI ) ) {
			wp_redirect( $bluem_config->instantMandatesResponseURI . '?result=false&reason=error' );
			exit;
		}
		$errormessage = esc_html__( 'Fout: geen juist mandaat id teruggekregen bij callback. Neem contact op met de webshop en vermeld je contactgegevens.', 'bluem' );
		bluem_error_report_email(
			array(
				'service'  => 'mandates',
				'function' => 'shortcode_callback',
				'message'  => $errormessage,
			)
		);
		bluem_dialogs_render_prompt( $errormessage );
		exit;
	}

	if ( empty( $entranceCode ) ) {
		$errormessage = esc_html__( 'Fout: Entrancecode is niet set; kan dus geen mandaat opvragen', 'bluem' );
		bluem_error_report_email(
			array(
				'service'  => 'mandates',
				'function' => 'shortcode_callback',
				'message'  => $errormessage,
			)
		);
		bluem_dialogs_render_prompt( $errormessage );
		exit;
	}

	$response = $bluem->MandateStatus( $mandateID, $entranceCode );

	if ( ! $response->Status() ) {
		$errormessage = sprintf(
		/* translators: %s: status code */
			esc_html__( 'Fout bij opvragen status: %s. Neem contact op met de webshop en vermeld deze status', 'bluem' ),
			$response->Error()
		);
		bluem_error_report_email(
			array(
				'service'  => 'mandates',
				'function' => 'shortcode_callback',
				'message'  => $errormessage,
			)
		);
		bluem_dialogs_render_prompt( $errormessage );
		exit;
	}
	$statusUpdateObject = $response->EMandateStatusUpdate;
	$statusCode         = $statusUpdateObject->EMandateStatus->Status . '';

	$request_from_db = bluem_db_get_request_by_transaction_id_and_type(
		$mandateID,
		'mandates'
	);

	if ( $statusCode !== $request_from_db->status ) {
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
	if ( $statusCode === 'Success' ) {
		if ( ! empty( $request_from_db->payload ) ) {
			try {
				$newPayload = json_decode( $request_from_db->payload );
			} catch ( Throwable $th ) {
				$newPayload = new Stdclass();
			}
		} else {
			$newPayload = new Stdclass();
		}

		if ( isset( $response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport ) ) {
			$newPayload->purchaseID = $response->EMandateStatusUpdate->EMandateStatus->PurchaseID . '';
			$newPayload->report     = $response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport;

			bluem_db_update_request(
				$request_from_db->id,
				array(
					'payload' => wp_json_encode( $newPayload ),
				)
			);
		}

		$request_from_db = bluem_db_get_request_by_transaction_id_and_type(
			$mandateID,
			'mandates'
		);

		// "De ondertekening is geslaagd";
		if ( ! empty( $bluem_config->instantMandatesResponseURI ) ) {
			wp_redirect( $bluem_config->instantMandatesResponseURI . '?result=true' );
			exit;
		}
		$errormessage = esc_html__( 'Fout: de ondertekening is geslaagd maar er is geen response URI opgegeven. Neem contact op met de website om dit technisch probleem aan te geven.', 'bluem' );
		bluem_error_report_email(
			array(
				'service'  => 'mandates',
				'function' => 'instant_callback',
				'message'  => $errormessage,
			)
		);
		bluem_dialogs_render_prompt( $errormessage );
		exit;
	}

	if ( $statusCode === 'Cancelled' ) {
		// "Je hebt de mandaat ondertekening geannuleerd";
		if ( ! empty( $bluem_config->instantMandatesResponseURI ) ) {
			wp_redirect( $bluem_config->instantMandatesResponseURI . '?result=false&reason=cancelled' );
			exit;
		}
		$errormessage = esc_html__( 'Fout: de transactie is geannuleerd. Probeer het opnieuw.', 'bluem' );
		bluem_dialogs_render_prompt( $errormessage );
		exit;
	}

	if ( $statusCode === 'Open' || $statusCode === 'Pending' ) {
		// "De mandaat ondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch."
		if ( ! empty( $bluem_config->instantMandatesResponseURI ) ) {
			wp_redirect( $bluem_config->instantMandatesResponseURI . '?result=false&reason=open' );
			exit;
		}
		$errormessage = esc_html__( 'Fout: de transactie staat nog open. Dit kan even duren. Vernieuw deze pagina regelmatig voor de status.', 'bluem' );
		bluem_dialogs_render_prompt( $errormessage );
		exit;
	}

	if ( $statusCode === 'Expired' ) {
		// "Fout: De mandaat of het verzoek daartoe is verlopen";
		if ( ! empty( $bluem_config->instantMandatesResponseURI ) ) {
			wp_redirect( $bluem_config->instantMandatesResponseURI . '?result=false&reason=expired' );
			exit;
		}
		$errormessage = esc_html__( 'Fout: de transactie is verlopen. Probeer het opnieuw.', 'bluem' );
		bluem_dialogs_render_prompt( $errormessage );
		exit;
	}

	bluem_error_report_email(
		array(
			'service'  => 'mandates',
			'function' => 'shortcode_callback',
			'message'  => sprintf(
			/* translators: %s: status code */
				esc_html__( 'Fout: Onbekende of foutieve status teruggekregen: %s<br>Neem contact op met de webshop en vermeld deze status; gebruiker wel doorverwezen terug naar site', 'bluem' ),
				$statusCode
			),
		)
	);
	wp_redirect( $bluem_config->instantMandatesResponseURI . '?result=false&reason=error' );
	exit;
}
