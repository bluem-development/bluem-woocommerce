<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Bluem\BluemPHP\Bluem;

add_action( 'parse_request', 'bluem_mandate_shortcode_execute' );

/**
 * This function is called POST from the form rendered on a page or post
 *
 * @return void
 * @throws DOMException
 * @throws HTTP_Request2_LogicException
 * @throws \Bluem\BluemPHP\Exceptions\InvalidBluemConfigurationException
 */
function bluem_mandate_shortcode_execute(): void {
	if ( substr( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), -43 ) !== 'bluem-woocommerce/mandate_shortcode_execute' ) {
		return;
	}

	$nonce = $_REQUEST['_wpnonce'];
	if ( ! wp_verify_nonce( $nonce, 'bluem-nonce' ) ) {
		die( 'Did not pass security check' );
	}

	global $current_user;

	$storage = bluem_db_get_storage();

	if ( isset( $_POST['bluem-submitted'] ) ) {
		$debtorReference = '';

		$bluem_config = bluem_woocommerce_get_config();

		$bluem_config->merchantReturnURLBase = home_url(
			'bluem-woocommerce/mandate_shortcode_callback'
		);

		// Check for recurring mode
		if ( $bluem_config->sequenceType === 'RCUR' ) {
			if ( ! empty( $storage['bluem_mandate_debtorreference'] ) ) {
				$debtorReference = $storage['bluem_mandate_debtorreference'];

				$db_query = array(
					'debtor_reference' => $debtorReference,
					'user_id'          => get_current_user_id(),
					'status'           => 'Success',
				);

				// Check for a successful transaction
				$db_results = bluem_db_get_requests_by_keyvalues( $db_query );

				if ( $db_results !== false && is_array( $db_results ) && sizeof( $db_results ) > 0 ) {
					$mandateID = $db_results[0]->transaction_id;

					bluem_db_insert_storage(
						array(
							'bluem_mandate_transaction_id' => $mandateID,
						)
					);

					if ( ! empty( $current_user ) ) {
						if ( current_user_can( 'edit_user', $current_user->ID ) ) {
							update_user_meta( $current_user->ID, 'bluem_mandates_validated', true );
							update_user_meta( $current_user->ID, 'bluem_latest_mandate_id', $mandateID );
						}
					}

					wp_redirect( home_url( $bluem_config->thanksPageURL ) . '?result=true' );
					exit;
				}
			} elseif ( ! empty( $_POST['bluem_debtorReference'] ) ) {
				$debtorReference = sanitize_text_field( $_POST['bluem_debtorReference'] );

				bluem_db_insert_storage(
					array(
						'bluem_mandate_debtorreference' => $debtorReference,
					)
				);

				$db_query = array(
					'debtor_reference' => $debtorReference,
					'user_id'          => get_current_user_id(),
					'status'           => 'Success',
				);

				// Check for a successful transaction
				$db_results = bluem_db_get_requests_by_keyvalues( $db_query );

				if ( $db_results !== false && is_array( $db_results ) && sizeof( $db_results ) > 0 ) {
					$mandateID = $db_results[0]->transaction_id;

					bluem_db_insert_storage(
						array(
							'bluem_mandate_transaction_id' => $mandateID,
						)
					);

					if ( ! empty( $current_user ) ) {
						if ( current_user_can( 'edit_user', $current_user->ID ) ) {
							update_user_meta( $current_user->ID, 'bluem_mandates_validated', true );
							update_user_meta( $current_user->ID, 'bluem_latest_mandate_id', $mandateID );
						}
					}

					wp_redirect( home_url( $bluem_config->thanksPageURL ) . '?result=true' );

					exit;
				}
			} elseif ( is_user_logged_in() ) {
				$debtorReference = $current_user->user_nicename();

				bluem_db_insert_storage(
					array(
						'bluem_mandate_debtorreference' => $debtorReference,
					)
				);
			}
		} elseif ( $bluem_config->sequenceType === 'OOFF' ) {
			if ( ! empty( $_POST['bluem_debtorReference'] ) ) {
				$debtorReference = sanitize_text_field( $_POST['bluem_debtorReference'] );

				bluem_db_insert_storage(
					array(
						'bluem_mandate_debtorreference' => $debtorReference,
					)
				);
			} elseif ( is_user_logged_in() ) {
				$debtorReference = $current_user->user_nicename();

				bluem_db_insert_storage(
					array(
						'bluem_mandate_debtorreference' => $debtorReference,
					)
				);
			}
		}

		$preferences = get_option( 'bluem_woocommerce_options' );

		// Convert UTF-8 to ISO
		if ( ! empty( $bluem_config->eMandateReason ) ) {
			$bluem_config->eMandateReason = mb_convert_encoding( $bluem_config->eMandateReason, 'ISO-8859-1', 'UTF-8' );
		} else {
			$bluem_config->eMandateReason = 'Incasso machtiging ' . $debtorReference;
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
		bluem_db_insert_storage(
			array(
				'bluem_mandate_transaction_id' => $request->mandateID,
				'bluem_mandate_entrance_code'  => $request->entranceCode,
			)
		);

		if ( ! empty( $current_user ) ) {
			if ( current_user_can( 'edit_user', $current_user->ID ) ) {
				update_user_meta(
					$current_user->ID,
					'bluem_latest_mandate_entrance_code',
					$request->entranceCode
				);
			}
		}

		// Actually perform the request.
		$response = $bluem->PerformRequest( $request );

		if ( ! isset( $response->EMandateTransactionResponse->TransactionURL ) ) {
			$msg = esc_html__(
				'Er ging iets mis bij het aanmaken van de transactie.<br>
            Vermeld onderstaande informatie aan het websitebeheer:',
				'bluem'
			);

			if ( isset( $response->EMandateTransactionResponse->Error->ErrorMessage ) ) {
				$msg .= '<br>' .
					esc_html( $response->EMandateTransactionResponse->Error->ErrorMessage );
			} elseif ( get_class( $response ) == 'Bluem\BluemPHP\ErrorBluemResponse' ) {
				$msg .= '<br>' .
					esc_html( $response->Error() );
			} else {
				$msg .= '<br>' . esc_html( 'Algemene fout', 'bluem' );
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
			)
		);

		if ( ! empty( $current_user ) ) {
			if ( current_user_can( 'edit_user', $current_user->ID ) ) {
				update_user_meta(
					$current_user->ID,
					'bluem_latest_mandate_id',
					$mandate_id
				);
			}
		}

		bluem_db_create_request(
			array(
				'entrance_code'    => $request->entranceCode,
				'transaction_id'   => $request->mandateID,
				'transaction_url'  => $transactionURL,
				'user_id'          => get_current_user_id(),
				'timestamp'        => gmdate( 'Y-m-d H:i:s' ),
				'description'      => 'Mandate request',
				'debtor_reference' => $debtorReference,
				'type'             => 'mandates',
				'order_id'         => '',
				'payload'          => wp_json_encode(
					array(
						'created_via'        => 'shortcode',
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
	}
	exit;
}

add_action( 'parse_request', 'bluem_mandate_mandate_shortcode_callback' );
/**
 * This function is executed at a callback GET request with a given mandateId. This is then, together with the entranceCode in Cookie, sent for a SUD to the Bluem API.
 *
 * @return void
 */
function bluem_mandate_mandate_shortcode_callback(): void {
	if ( strpos( sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'bluem-woocommerce/mandate_shortcode_callback' ) === false ) {
		return;
	}

	global $current_user;

	$bluem_config = bluem_woocommerce_get_config();

	$bluem_config->merchantReturnURLBase = home_url( 'wc-api/bluem_mandates_callback' );

	$storage = bluem_db_get_storage();

	try {
		$bluem = new Bluem( $bluem_config );
	} catch ( Exception $e ) {
		// @todo: deal with incorrectly setup Bluem
		// $e->getMessage();
	}

	// @todo: .. then use request-based approach soon as first check, then fallback to user meta check.
	if ( ! empty( $current_user->ID ) ) {
		$mandateID    = get_user_meta( $current_user->ID, 'bluem_latest_mandate_id', true );
		$entranceCode = get_user_meta( $current_user->ID, 'bluem_latest_mandate_entrance_code', true );
	} else {
		$mandateID    = $storage['bluem_mandate_transaction_id'] ?? 0;
		$entranceCode = $storage['bluem_mandate_entrance_code'] ?? '';
	}

	if ( ! isset( $_GET['mandateID'] ) ) {
		if ( $bluem_config->thanksPageURL !== '' ) {
			wp_redirect( home_url( $bluem_config->thanksPageURL ) . '?result=false&reason=error' );
			// echo "<p>Er is een fout opgetreden. De incassomachtiging is geannuleerd.</p>";
			return;
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
		$errormessage =
			sprintf(
			/* translators: %s: Error message */
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
		// Define a cookie so that this will be recognised the next time
		bluem_db_insert_storage(
			array(
				'bluem_mandate_transaction_id' => $mandateID,
			)
		);

		if ( ! empty( $current_user ) ) {
			if ( current_user_can( 'edit_user', $current_user->ID ) ) {
				update_user_meta( $current_user->ID, 'bluem_mandates_validated', true );
			}
		}

		if ( $request_from_db->payload !== '' ) {
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
		wp_redirect( home_url( $bluem_config->thanksPageURL ) . '?result=true' );
		exit;
	} elseif ( $statusCode === 'Cancelled' ) {
		// "Je hebt de mandaat ondertekening geannuleerd";
		wp_redirect( home_url( $bluem_config->thanksPageURL ) . '?result=false&reason=cancelled' );
		exit;
	} elseif ( $statusCode === 'Open' || $statusCode == 'Pending' ) {
		// "De mandaat ondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch."
		wp_redirect( home_url( $bluem_config->thanksPageURL ) . '?result=false&reason=open' );
		exit;
	} elseif ( $statusCode === 'Expired' ) {
		// "Fout: De mandaat of het verzoek daartoe is verlopen";
		wp_redirect( home_url( $bluem_config->thanksPageURL ) . '?result=false&reason=expired' );
		exit;
	} else {
		// "Fout: Onbekende of foutieve status";
		bluem_error_report_email(
			array(
				'service'  => 'mandates',
				'function' => 'shortcode_callback',
				'message'  =>
					sprintf(
					/* translators: %s: error status */
						esc_html__( 'Fout: Onbekende of foutieve status teruggekregen: %s. Neem contact op met de webshop en vermeld deze status; gebruiker wel doorverwezen terug naar site', 'bluem' ),
						$statusCode
					),
			)
		);
		wp_redirect( home_url( $bluem_config->thanksPageURL ) . '?result=false&reason=error' );
		exit;
	}
}

add_shortcode( 'bluem_machtigingsformulier', 'bluem_mandateform' );

/**
 * Rendering the static form
 * Shortcode: `[bluem_machtigingsformulier]`
 *
 * @return string
 */
function bluem_mandateform(): string {
	global $current_user;

	$bluem_config = bluem_woocommerce_get_config();

	$storage = bluem_db_get_storage();

	$bluem_config->merchantReturnURLBase = home_url(
		'wc-api/bluem_mandates_callback'
	);

	$user_allowed = apply_filters(
		'bluem_woocommerce_mandate_shortcode_allow_user',
		true
	);

	if ( ! $user_allowed ) {
		return '';
	}

	$mandateID = 0;

	$validated = false;

	/**
	 * Check if user is logged in.
	 */
	if ( is_user_logged_in() ) {
		$mandateID = get_user_meta( $current_user->ID, 'bluem_latest_mandate_id', true );

		$validated_db = get_user_meta( $current_user->ID, 'bluem_mandates_validated', true );

		// While be zero (string) when disabled
		if ( ! empty( $mandateID ) && $validated_db !== '0' ) {
			// Check for recurring mode
			if ( $bluem_config->sequenceType === 'RCUR' ) {
				$db_query = array(
					'transaction_id' => $mandateID,
					'user_id'        => get_current_user_id(),
					'status'         => 'Success',
				);

				$db_results = bluem_db_get_requests_by_keyvalues( $db_query );

				if ( $db_results !== false && is_array( $db_results ) && sizeof( $db_results ) > 0 ) {
					$mandateID = $db_results[0]->transaction_id;

					$validated = true;
				}
			}
		}
	} else {
		/**
		 * Visitor not logged in. Check other storages.
		 */
		if ( ! empty( $storage['bluem_mandate_transaction_id'] ) ) {
			$mandateID = $storage['bluem_mandate_transaction_id'];

			// Check for recurring mode
			if ( $bluem_config->sequenceType === 'RCUR' ) {
				$db_query = array(
					'transaction_id' => $mandateID,
					'user_id'        => get_current_user_id(),
					'status'         => 'Success',
				);

				$db_results = bluem_db_get_requests_by_keyvalues( $db_query );

				if ( $db_results !== false && is_array( $db_results ) && sizeof( $db_results ) > 0 ) {
					$mandateID = $db_results[0]->transaction_id;

					$validated = true;
				}
			}
		} elseif ( ! empty( $storage['bluem_mandate_debtorreference'] ) ) {
			$debtorReference = $storage['bluem_mandate_debtorreference'];

			// Check for recurring mode
			if ( $bluem_config->sequenceType === 'RCUR' ) {
				$db_query = array(
					'debtor_reference' => $debtorReference,
					'user_id'          => get_current_user_id(),
					'status'           => 'Success',
				);

				$db_results = bluem_db_get_requests_by_keyvalues( $db_query );

				if ( $db_results !== false && is_array( $db_results ) && sizeof( $db_results ) > 0 ) {
					$mandateID = $db_results[0]->transaction_id;

					$validated = true;
				}
			}
		}
	}

	/**
	 * Check if eMandate is valid..
	 */
	if ( $validated !== false ) {
		return '<p>' . esc_html__( 'Bedankt voor je machtiging met machtiging ID:', 'bluem' ) . " <span class='bluem-mandate-id'>" . esc_attr( $mandateID ) . '</span></p>';
	} else {
		$nonce = wp_create_nonce( 'bluem-nonce' );
		$html  = '<form action="' . home_url( 'bluem-woocommerce/mandate_shortcode_execute' ) . '?_wpnonce=' . $nonce . '" method="post">';
		$html .= '<p>' . esc_html__( 'Je moet nog een automatische incasso machtiging afgeven.', 'bluem' ) . '</p>';

		if ( ! empty( $bluem_config->debtorReferenceFieldName ) ) {
			$html .= '<p>' . $bluem_config->debtorReferenceFieldName . ' (' . esc_html__( 'verplicht', 'bluem' ) . ')<br/>';
			$html .= '<input type="text" name="bluem_debtorReference" required /></p>';
		} else {
			$html .= '<input type="hidden" name="bluem_debtorReference" value="' . ( ! empty( $current_user->ID ) ? $current_user->ID : 'visitor-' . time() ) . '"  />';
		}

		$html .= '<p><input type="submit" name="bluem-submitted" class="bluem-woocommerce-button bluem-woocommerce-button-mandates" 
            value="' . esc_html__( 'Machtiging proces starten', 'bluem' ) . '.."></p>';
		$html .= '</form>';

		return $html;
	}
}

add_filter( 'bluem_woocommerce_mandate_shortcode_allow_user', 'bluem_woocommerce_mandate_shortcode_allow_user_function', 10, 1 );

function bluem_woocommerce_mandate_shortcode_allow_user_function( $valid = true ) {
	// do something with the response, use this in third-party extensions of this system
	return $valid;
}
