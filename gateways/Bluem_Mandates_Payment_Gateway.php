<?php

use Bluem\BluemPHP\Bluem;
use Bluem\BluemPHP\Responses\ErrorBluemResponse;

include_once __DIR__ . '/Bluem_Payment_Gateway.php';

class Bluem_Mandates_Payment_Gateway extends Bluem_Payment_Gateway
{
	protected $_show_fields = false;

	/**
	 * Class constructor
	 */
	public function __construct()
	{
        $methodDescription = 'eMandate Payment Gateway voor WordPress - WooCommerce.';

        parent::__construct(
            'bluem_mandates',
            'Bluem Digitaal Incassomachtiging (eMandate)',
            $methodDescription,
			home_url( 'wc-api/bluem_mandates_callback' )
        );

		if ( isset( $this->bluem_config->localInstrumentCode ) && $this->bluem_config->localInstrumentCode == "B2B" ) {
			$this->method_title = 'Bluem Zakelijke Incassomachtiging (eMandate)';
		} else {
			$this->method_title = 'Bluem Particuliere Incassomachtiging (eMandate)';
		}

		$this->has_fields = true;

		$options = get_option( 'bluem_woocommerce_options' );

		if ( !empty( $options['mandatesUseDebtorWallet'] ) && $options['mandatesUseDebtorWallet'] == '1' ) {
            $this->_show_fields = true;
        }

		// This action hook saves the settings
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);

		// ********** CREATING plugin URLs for specific functions **********
		add_action(
			'woocommerce_api_bluem_mandates_webhook',
			array( $this, 'bluem_mandates_webhook' ),
			5
		);
		add_action(
			'woocommerce_api_bluem_mandates_callback',
			array( $this, 'bluem_mandates_callback' )
		);

		// ********** Allow filtering Orders based on MandateID **********
		add_filter(
			'woocommerce_order_data_store_cpt_get_orders_query',
			function ( $query, $query_vars ) {
				if ( ! empty( $query_vars['bluem_mandateid'] ) ) {
					$query['meta_query'][] = array(
						'key'   => 'bluem_mandateid',
						'value' => esc_attr( $query_vars['bluem_mandateid'] ),
					);
				}

				return $query;
			},
			10,
			2
		);
	}

	/**
	 * Generic thank you page that redirects to the specific order page.
	 *
	 * @param [type] $order_id
	 *
	 * @return void
	 */
	public function bluem_thankyou( $order_id ) {
		$order = wc_get_order( $order_id );

		$url = $order->get_checkout_order_received_url();

		if ( ! $order->has_status( 'failed' ) ) {
			wp_safe_redirect( $url );
			exit;
		}

		// @todo: add alternative route?
	}

	/**
	 * Create plugin options page in admin interface
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = apply_filters( 'wc_offline_form_fields', [
			'enabled'     => [
				'title'       => 'Enable/Disable',
				'label'       => 'Activeer de Bluem eMandate Gateway',
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			],
			'title'       => [
				'title'       => 'Titel van betaalmethode',
				'type'        => 'text',
				'description' => 'Dit bepaalt de titel die de gebruiker ziet tijdens het afrekenen.',
				'default'     => 'Incasso machtiging voor zakelijke Rabobank, ING of ABN AMRO rekeningen',
			],
			'description' => [
				'title'       => 'Description',
				'type'        => 'textarea',
				'description' => 'Dit bepaalt de beschrijving die de gebruiker ziet tijdens het afrekenen.					',
				'default'     => 'Geef een B2B eMandate af voor een incasso voor je bestelling.',
			]
		] );
	}

	/**
	 * Check if a valid mandate already exists for this user
	 *
	 * @param  $order Order object
	 */
	private function _checkExistingMandate( $order ) {
		global $current_user;

		$order_id = $order->get_id();

		$user_id = $current_user->ID;

		$retrieved_request_from_db = false;

		$reason = "";

		$ready = false;

		if ( ! empty( $user_id ) ) {
			$request = bluem_db_get_most_recent_request( $user_id, "mandates" );

			if ( $request !== false ) {
				$bluem_latest_mandate_entrance_code = $request->entrance_code;
				$bluem_latest_mandate_id            = $request->transaction_id;

				$retrieved_request_from_db = true;

				$ready = true;
			} else {
				// no latest request found, also trying in user metadata (legacy)
				$user_meta = get_user_meta( $user_id );

				$bluem_latest_mandate_id = null;
				if ( ! empty( $user_meta['bluem_latest_mandate_id'] ) ) {
					$bluem_latest_mandate_id = $user_meta['bluem_latest_mandate_id'][0];

					$ready = true;
				}

				$bluem_latest_mandate_entrance_code = null;
				if ( ! empty( $user_meta['bluem_latest_mandate_entrance_code'] ) ) {
					$bluem_latest_mandate_entrance_code = $user_meta['bluem_latest_mandate_entrance_code'][0];

					$ready = true;
				}
			}
		}

		if ( $ready
		     && ! is_null( $bluem_latest_mandate_id )
		     && $bluem_latest_mandate_id !== ""
		     && ! is_null( $bluem_latest_mandate_entrance_code )
		     && $bluem_latest_mandate_entrance_code !== ""
		) {
            try {
                $existing_mandate_response = $this->bluem->MandateStatus(
                    $bluem_latest_mandate_id,
                    $bluem_latest_mandate_entrance_code
                );
            } catch ( Exception $e ) {
                return array(
                    'exception' => $e->getMessage(),
                    'result' => 'failure'
                );
            }

            if ( ! $existing_mandate_response->Status() ) {
				$reason = "No / invalid bluem response for existing mandate";
				// existing mandate response is not at all valid,
				// continue with actual mandate process
			} else if (
                $existing_mandate_response->EMandateStatusUpdate->EMandateStatus->Status . "" === "Success"
            ) {
                if ( $this->validateMandate(
                    $existing_mandate_response,
                    $order,
                    false,
                    false,
                    false
                )
                ) {
                    // successfully used previous mandate in current order,
                    // lets annotate that order with the corresponding metadata
                    update_post_meta(
                        $order_id,
                        'bluem_entrancecode',
                        $bluem_latest_mandate_entrance_code
                    );
                    update_post_meta(
                        $order_id,
                        'bluem_mandateid',
                        $bluem_latest_mandate_id
                    );

                    if ( $retrieved_request_from_db ) {
                        bluem_db_request_log(
                            $request->id,
                            "Utilized this request for a
payment for another order {$order_id}"
                        );

                        bluem_db_create_link(
                            $request->id,
                            $order_id
                        );

                        $cur_payload = json_decode( $request->payload, false );
                        if ( ! isset( $cur_payload->linked_orders ) ) {
                            $cur_payload->linked_orders = [];
                        }
                        $cur_payload->linked_orders[] = $order_id;

                        bluem_db_update_request(
                            $request->id,
                            [
                                'payload' => json_encode( $cur_payload )
                            ]
                        );
                    }

                    return array(
                        'result'   => 'success',
                        'redirect' => $order->get_checkout_order_received_url()
                    );
                }

$reason = "Existing mandate found, but not valid";
} else {
                $reason = "Existing mandate is not a successful mandate";
            }
		} else {
			$reason = "Not ready, no metadata";
		}

		return array(
			'result'  => 'fail',
			'message' => $reason
		);
	}

	/**
     * Define payment fields
     */
    public function payment_fields()
    {
		$BICs = $this->bluem->retrieveBICsForContext( "Mandates" );

		$description = $this->get_description();

        $options = [];

		if ( $description ) {
			echo wpautop( wptexturize( $description ) ); // @codingStandardsIgnoreLine.
		}

        // Loop through BICS
        foreach ( $BICs as $BIC ) {
            $options[ $BIC->issuerID ] = $BIC->issuerName;
        }

		// Check for options
        if ( $this->_show_fields && !empty( $options ) )
        {
            woocommerce_form_field( 'bluem_mandates_bic', array(
                'type' => 'select',
                'required' => true,
                'label' => 'Selecteer een bank:',
                'options' => $options,
            ), '' );
        }
    }

    /**
     * Payment fields validation
	 * @TODO
     */
    public function validate_fields()
    {
        return true;
    }

	/**
	 * Process payment through Bluem portal
	 *
	 * @param String $order_id
	 *
	 * @return void
	 */
	public function process_payment( $order_id )
	{
		global $current_user;

		$verbose = false;

		// Convert UTF-8 to ISO
		if ( ! empty( $this->bluem_config->eMandateReason ) ) {
			$this->bluem_config->eMandateReason = utf8_decode( $this->bluem_config->eMandateReason );
		} else {
			$this->bluem_config->eMandateReason = "Incasso machtiging";
		}

        try {
            $this->bluem = new Bluem( $this->bluem_config );
        } catch ( Exception $e ) {
            return array(
                'exception' => $e->getMessage(),
                'result' => 'failure'
            );
        }

        $order = wc_get_order( $order_id );

		// $user_id = $order->get_user_id();
		// $user_id = get_post_meta($order_id, '_customer_user', true);
		// improved retrieval of user id:
		$user_id = $current_user->ID;

		$settings = get_option( 'bluem_woocommerce_options' );

		$check = $this->_checkExistingMandate( $order );

		if ( isset( $check['result'] ) && $check['result'] === "success" ) {
			return array(
				'result'   => 'success',
				'redirect' => $order->get_checkout_order_received_url()
			);
			// @todo Possibly allow different redirect after fast checkout with existing, valid, mandate.
		}

		$bluem_mandates_bic = isset($_POST['bluem_mandates_bic']) ? sanitize_text_field($_POST['bluem_mandates_bic']) : '';

		$order_id = $order->get_id();
		// update: added prefixed order ID for retries of mandate requests
		$prefixed_order_id = date( "His" ) . $order_id;
		$mandate_id = $this->bluem->CreateMandateId(
			$prefixed_order_id,
			$user_id
		);

        try {
            $request = $this->bluem->CreateMandateRequest(
                $user_id,
                $order_id,
                $mandate_id
            );
        } catch ( Exception $e ) {
            return array(
                'exception' => $e->getMessage(),
                'result' => 'failure'
            );
        }

		if ( !empty( $bluem_mandates_bic ) )
        {
            $request->selectDebtorWallet( $bluem_mandates_bic );
        }

        // allow third parties to add additional data to the request object through this additional action
		$request = apply_filters(
			'bluem_woocommerce_enhance_mandate_request',
			$request
		);

        try {
            $response = $this->bluem->PerformRequest( $request );
        } catch ( Exception $e ) {
            return array(
                'exception' => $e->getMessage(),
                'result' => 'failure'
            );
        }

        if ( self::VERBOSE ) {
			var_dump( $order_id );
			var_dump( $user_id );
			var_dump( $mandate_id );
			var_dump( $response );
			die();
		}

		if ( $response instanceof ErrorBluemResponse ) {
			throw new RuntimeException( "An error occurred in the payment method. Please contact the webshop owner with this message:  " . $response->error() );
		}

		$attrs = $response->EMandateTransactionResponse->attributes();

		if ( ! isset( $attrs['entranceCode'] ) ) {
			throw new RuntimeException( "An error occurred in reading the transaction response. Please contact the webshop owner" );
		}
		$entranceCode = $attrs['entranceCode'] . "";

		update_post_meta( $order_id, 'bluem_entrancecode', $entranceCode );
		update_post_meta( $order_id, 'bluem_mandateid', $mandate_id );

		// https://docs.woocommerce.com/document/managing-orders/
		// Possible statuses: 'pending', 'processing', 'on-hold', 'completed', 'refunded, 'failed', 'cancelled',

		// Remove cart
		global $woocommerce;
		$woocommerce->cart->empty_cart();
		$order->update_status( 'pending', __( 'Awaiting Bluem eMandate Signature', 'wc-gateway-bluem' ) );

		if ( isset( $response->EMandateTransactionResponse->TransactionURL ) ) {

			// redirect cast to string, for AJAX response handling
			$transactionURL = ( $response->EMandateTransactionResponse->TransactionURL . "" );

			// Logging transaction
			$raw_request_object = [
				'entrance_code'    => $entranceCode,
				'transaction_id'   => $mandate_id,
				'transaction_url'  => $transactionURL,
				'user_id'          => get_current_user_id(),
				'timestamp'        => date( "Y-m-d H:i:s" ),
				'description'      => "Mandate request {$order_id} {$user_id}",
				'debtor_reference' => "",
				'type'             => "mandates",
				'order_id'         => $order_id,
				'payload'          => json_encode(
					[
						'environment'           => $this->bluem_config->environment,
						'order_amount'          => $order->get_total(),
						'created_mandate_id'    => $mandate_id,
						'local_instrument_code' => $this->bluem_config->localInstrumentCode,
						'issuing_type'          => $this->bluem_config->requestType,
						'sequence_type'         => $this->bluem_config->sequenceType,
						'linked_orders'         => [ $order_id ]
					]
				)
			];

			bluem_db_create_request(
				$raw_request_object
			);

			return array(
				'result'   => 'success',
				'redirect' => $transactionURL
			);
		}

		return array(
			'result' => 'failure'
		);
	}

	/**
	 * mandates_Webhook action
	 *
	 * @return void
	 */
	public function bluem_mandates_webhook()
	{
		// @todo: update this

        try {
            $this->bluem->Webhook();
        } catch ( Exception $e ) {
            // @todo: handle exception
        }

        $entranceCode = $statusUpdateObject->entranceCode . "";
		$mandateID    = $statusUpdateObject->EMandateStatus->MandateID . "";

		$webhook_status = $statusUpdateObject->EMandateStatus->Status . "";

		$order = $this->getOrder( $mandateID );
		if ( is_null( $order ) ) {
			echo "Error: No order found";
			exit;
		}
		$order_status = $order->get_status();

		if ( self::VERBOSE ) {
			echo "order_status: {$order_status}" . PHP_EOL;
			echo "webhook_status: {$webhook_status}" . PHP_EOL;
		}

		$user_id   = $user_id = $order->get_user_id();
		$user_meta = get_user_meta( $user_id );

		// Todo: if maxamount comes back from webhook (it should) then it can be accessed here
		// if (isset($user_meta['bluem_latest_mandate_amount'][0])) {
		// 	$mandate_amount = $user_meta['bluem_latest_mandate_amount'][0];
		// } else {
		// }

		if ( isset( $statusUpdateObject->EMandateStatus->AcceptanceReport->MaxAmount ) ) {
			$mandate_amount = (float) ( $statusUpdateObject->EMandateStatus->AcceptanceReport->MaxAmount . "" );
		} else {
			$mandate_amount = 0.0;    // mandate amount is not set, so it is unlimited
		}
		if ( self::VERBOSE ) {
			var_dump( $mandate_amount );
			echo PHP_EOL;
		}

		$settings = get_option( 'bluem_woocommerce_options' );

		if ( $settings['localInstrumentCode'] !== "B2B" ) {
			$maxAmountEnabled = true;
		} else {
			$maxAmountEnabled = ( isset( $settings['maxAmountEnabled'] ) && $settings['maxAmountEnabled'] === "1" );
		}

		if ( self::VERBOSE ) {
			echo "mandate_amount: {$mandate_amount}" . PHP_EOL;
		}

		if ( $maxAmountEnabled ) {

            $maxAmountFactor =  isset( $settings['maxAmountFactor'] )
                ? (float) ( $settings['maxAmountFactor'] )
                : 1.0 ;

			$mandate_successful = false;

			if ( $mandate_amount !== 0.0 ) {
				$order_price      = $order->get_total();
				$max_order_amount =  $order_price * $maxAmountFactor;
				if ( self::VERBOSE ) {
					echo "max_order_amount: {$max_order_amount}" . PHP_EOL;
				}

				if ( $mandate_amount >= $max_order_amount ) {
					$mandate_successful = true;
					if ( self::VERBOSE ) {
						echo "mandate is enough" . PHP_EOL;
					}
				} else if ( self::VERBOSE ) {
                    echo "mandate is too small" . PHP_EOL;
                }
			}
		} else {
			$mandate_successful = true;
		}

		if ( $webhook_status === "Success" ) {
//                if ($order_status === "processing") {
//                    // order is already marked as processing, nothing more is necessary
//                } else
            // check if maximum of order does not exceed mandate size based on user metadata
            if ( ( $order_status === "pending" ) && $mandate_successful ) {
                $order->update_status(
                    'processing',
                    __(
                        "Machtiging (Mandaat ID $mandateID) is gelukt en goedgekeurd; via webhook",
                        'wc-gateway-bluem'
                    )
                );
            }
		} elseif ( $webhook_status === "Cancelled" ) {
			$order->update_status( 'cancelled', __( 'Machtiging is geannuleerd; via webhook', 'wc-gateway-bluem' ) );
		}
//            elseif ($webhook_status === "Open" || $webhook_status == "Pending") {
		// if the webhook is still open or pending, nothing has to be done yet
//            }
		elseif ( $webhook_status === "Expired" ) {
			$order->update_status( 'failed', __( 'Machtiging is verlopen; via webhook', 'wc-gateway-bluem' ) );
		} else {
			$order->update_status( 'failed', __( 'Machtiging is gefaald: fout of onbekende status; via webhook', 'wc-gateway-bluem' ) );
		}
		exit;
	}

    /**
     * Retrieve an order based on its mandate_id in metadata from the WooCommerce store
     *
     * @param String $mandateID
     *
     * @return mixed|null
     */
	private function getOrder( string $mandateID )
	{
		$orders = wc_get_orders( array(
			'orderby'         => 'date',
			'order'           => 'DESC',
			'bluem_mandateid' => $mandateID
		) );
		if ( count( $orders ) == 0 ) {
			return null;
		}

		return $orders[0];
	}

	/**
	 * mandates_Callback function after Mandate process has been completed by the user
	 * @return void
	 */
	public function bluem_mandates_callback()
	{
		// $this->bluem = new Bluem( $this->bluem_config );
        // dont recreate it here, it should already exist in the gateway!

		if ( ! isset( $_GET['mandateID'] ) ) {
			$errormessage = "Fout: geen juist mandaat id teruggekregen bij mandates_callback. Neem contact op met de webshop en vermeld je contactgegevens.";
			bluem_error_report_email(
				[
					'service'  => 'mandates',
					'function' => 'mandates_callback',
					'message'  => $errormessage
				]
			);
			bluem_dialogs_render_prompt( $errormessage );
			exit;
		}

		if ( $_GET['mandateID'] == "" ) {
			$errormessage = "Fout: geen juist mandaat id teruggekregen bij mandates_callback. Neem contact op met de webshop en vermeld je contactgegevens.";
			bluem_error_report_email(
				[
					'service'  => 'mandates',
					'function' => 'mandates_callback',
					'message'  => $errormessage
				]
			);
			bluem_dialogs_render_prompt( $errormessage );
			exit;
		}
		$mandateID = $_GET['mandateID'];

		$order = $this->getOrder( $mandateID );
		if ( is_null( $order ) ) {
			$errormessage = "Fout: mandaat niet gevonden in webshop. Neem contact op met de webshop en vermeld de code {$mandateID} bij je gegevens.";
			bluem_error_report_email(
				[
					'service'  => 'mandates',
					'function' => 'mandates_callback',
					'message'  => $errormessage
				]
			);
			bluem_dialogs_render_prompt( $errormessage );
			exit;
		}

		$request_from_db = bluem_db_get_request_by_transaction_id_and_type(
			$mandateID,
			"mandates"
		);

		if ( ! $request_from_db ) {
			// @todo: give an error, as this transaction has clearly not been saved

			$entranceCode = $order->get_meta( 'bluem_entrancecode' );
		}

		$entranceCode = $request_from_db->entrance_code;

        try {
            $response = $this->bluem->MandateStatus( $mandateID, $entranceCode );
        } catch (Exception $e ) {
            $errormessage = "Fout bij opvragen status: " . $e->getMessage() . "<br>Neem contact op met de webshop en vermeld deze status";
            bluem_error_report_email(
                [
                    'service'  => 'mandates',
                    'function' => 'mandates_callback',
                    'message'  => $errormessage
                ]
            );
            bluem_dialogs_render_prompt( $errormessage );
            exit;
        }

        if ( ! $response->Status() ) {
			$errormessage = "Fout bij opvragen status: " . $response->Error() . "<br>Neem contact op met de webshop en vermeld deze status";
			bluem_error_report_email(
				[
					'service'  => 'mandates',
					'function' => 'mandates_callback',
					'message'  => $errormessage
				]
			);
			bluem_dialogs_render_prompt( $errormessage );
			exit;
		}

		if ( self::VERBOSE ) {
			var_dump( "mandateid: " . $mandateID );
			var_dump( "entrancecode: " . $entranceCode );
			echo "<hr>";
			var_dump( $response );
			echo "<hr>";
		}

		$statusUpdateObject = $response->EMandateStatusUpdate;
		$statusCode         = $statusUpdateObject->EMandateStatus->Status . "";

		// $request_from_db = bluem_db_get_request_by_transaction_id($mandateID);
		if ( $statusCode !== $request_from_db->status ) {
			bluem_db_update_request(
				$request_from_db->id,
				[
					'status' => $statusCode
				]
			);
		}
		if ( $statusCode === "Success" ) {
			if ( $request_from_db->id !== "" ) {
				$new_data = [];
				if ( isset( $response->EMandateStatusUpdate->EMandateStatus->PurchaseID ) ) {
					$new_data['purchaseID'] = $response
						                          ->EMandateStatusUpdate->EMandateStatus->PurchaseID . "";
				}
				if ( isset( $response->EMandateStatusUpdate->EMandateStatus->AcceptanceReport ) ) {
					$new_data['report'] = $response
						->EMandateStatusUpdate->EMandateStatus->AcceptanceReport;
				}
				if ( count( $new_data ) > 0 ) {
					bluem_db_put_request_payload(
						$request_from_db->id,
						$new_data
					);
				}
			}
			$this->validateMandate(
				$response, $order, true, true,
				true, $mandateID, $entranceCode
			);
		} elseif ( $statusCode === "Pending" ) {
			bluem_dialogs_render_prompt(
				"<p>Uw machtiging wacht op goedkeuring van
                    een andere ondertekenaar namens uw organisatie.<br>
                    Deze persoon dient in te loggen op internet bankieren
                    en deze machtiging ook goed te keuren.
                    Hierna is de machtiging goedgekeurd en zal dit
                    reageren op deze site.</p>"
			);
			exit;
		} elseif ( $statusCode === "Cancelled" ) {
			$order->update_status(
				'cancelled',
				__( 'Machtiging is geannuleerd', 'wc-gateway-bluem' )
			);

			bluem_transaction_notification_email(
				$request_from_db->id
			);
			bluem_dialogs_render_prompt( "Je hebt de mandaat ondertekening geannuleerd" );
			// terug naar order pagina om het opnieuw te proberen?
			exit;
		} elseif ( $statusCode === "Open" || $statusCode == "Pending" ) {
			bluem_dialogs_render_prompt( "De mandaat ondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch." );
			// callback pagina beschikbaar houden om het opnieuw te proberen?
			// is simpelweg SITE/wc-api/bluem_callback?mandateID=$mandateID
			exit;
		} elseif ( $statusCode === "Expired" ) {
			$order->update_status(
				'failed',
				__(
					'Machtiging is verlopen',
					'wc-gateway-bluem'
				)
			);

			bluem_transaction_notification_email(
				$request_from_db->id
			);

			bluem_dialogs_render_prompt(
				"Fout: De mandaat of het verzoek daartoe is verlopen"
			);
			exit;
		} else {
			$order->update_status(
				'failed',
				__(
					'Machtiging is gefaald: fout of onbekende status',
					'wc-gateway-bluem'
				)
			);
			$errormessage = "Fout: Onbekende of foutieve status teruggekregen: {$statusCode}
                    <br>Neem contact op met de webshop en vermeld deze status";
			bluem_error_report_email(
				[
					'service'  => 'mandates',
					'function' => 'mandates_callback',
					'message'  => $errormessage
				]
			);

			bluem_dialogs_render_prompt(
				$errormessage
			);
			exit;
		}
        exit;
	}

	/**
	 * Validating a given mandate based on MaxAmount given in $response, compared to $order total pricing and some additional parameters
	 *
	 * @param [type] $response
	 * @param [type] $order
	 * @param boolean $block_processing
	 * @param boolean $update_metadata
	 * @param [type] $mandate_id
	 * @param [type] $entrance_code
	 *
	 * @return bool
     */
	private function validateMandate( $response, $order, $block_processing = false, $update_metadata = true, $redirect = true, $mandate_id = null, $entrance_code = null )
	{
		$maxAmountResponse = $this->bluem->GetMaximumAmountFromTransactionResponse( $response );
		$user_id           = $order->get_user_id();

        // @todo: remove mandate ID from parameters and get it here:
		$mandate_id = $response->EMandateStatusUpdate->EMandateStatus->MandateID . "";

		$settings         = get_option( 'bluem_woocommerce_options' );
		$maxAmountEnabled = ( isset( $settings['maxAmountEnabled'] ) && $settings['maxAmountEnabled'] === "1" );
		if ( $maxAmountEnabled ) {
			$maxAmountFactor = ( isset( $settings['maxAmountFactor'] ) ? (float) ( $settings['maxAmountFactor'] ) : false );
		} else {
			$maxAmountFactor = 1.0;
		}

		$successful_mandate = false;

		$request_id      = "";
		$request_from_db = false;
		if ( ! empty( $mandate_id ) ) {
			$request_from_db = bluem_db_get_request_by_transaction_id_and_type(
				$mandate_id,
				"mandates"
			);

			$request_id = $request_from_db->id;
		}

		if ( $maxAmountEnabled ) {

			// NextDeli specific: estimate 10% markup on order total:
			$order_total_plus = (float) $order->get_total() * $maxAmountFactor;

			if ( self::VERBOSE ) {
				if ( $maxAmountResponse->amount === 0.0 ) {
					echo "No max amount set";
				} else {
					echo "MAX AMOUNT SET AT {$maxAmountResponse->amount} {$maxAmountResponse->currency}";
				}
				echo "<hr>";
				echo "Totaalbedrag: ";
				var_dump( (float) $order->get_total() );
				echo " | totaalbedrag +10 procent: ";
				var_dump( $order_total_plus );
				echo "<hr>";
			}

			if ( isset( $maxAmountResponse->amount ) && $maxAmountResponse->amount !== 0.0 ) {
				if ( $update_metadata ) {
					if ( self::VERBOSE ) {
						echo "<br>updating user meta: bluem_latest_mandate_amount to value {$maxAmountResponse->amount} - result: ";
					}
					update_user_meta(
						$user_id,
						'bluem_latest_mandate_amount',
						$maxAmountResponse->amount
					);
				}
				$allowed_margin = ( $order_total_plus <= $maxAmountResponse->amount );
				if ( self::VERBOSE ) {
					echo "binnen machtiging marge?";
					var_dump( $allowed_margin );
				}

				if ( $allowed_margin ) {
					$successful_mandate = true;
				} else if ( $block_processing ) {
                    $order->update_status( 'pending', __( 'Machtiging moet opnieuw ondertekend worden, want mandaat bedrag is te laag', 'wc-gateway-bluem' ) );

                    $url                     = $order->get_checkout_payment_url();
                    $order_total_plus_string = str_replace( ".", ",", ( "" . round( $order_total_plus, 2 ) ) );
                    bluem_dialogs_render_prompt(
                        "<p>Het automatische incasso mandaat dat je hebt afgegeven is niet toereikend voor de incassering van het factuurbedrag van jouw bestelling.</p>
<p>De geschatte factuurwaarde van jouw bestelling is EUR {$order_total_plus_string}. Het mandaat voor de automatische incasso die je hebt ingesteld is EUR {$maxAmountResponse->amount}. Ons advies is om jouw mandaat voor automatische incasso te verhogen of voor 'onbeperkt' te kiezen.</p>" .
                        "<p><a href='{$url}' target='_self'>Klik hier om terug te gaan naar de betalingspagina en een nieuw mandaat af te geven</a></p>",
                        false
                    );

                    bluem_db_request_log(
                        $request_id,
                        "User tried to give use this mandate with maxamount
&euro; {$maxAmountResponse->amount}, but the Order <a href='" .
                        admin_url( "post.php?post=" . $order->get_id() . "&action=edit" ) .
                        "' target='_self'>ID " . $order->get_id() . "</a> grand
total including correction is &euro; {$order_total_plus_string}.
The user is prompted to create a new mandate to fulfill this order."
                    );


                    exit;
                }
			} else {
				if ( $update_metadata ) {
					if ( self::VERBOSE ) {
						echo "<br>updating user meta: bluem_latest_mandate_amount to value 0 - result: ";
					}
					update_user_meta( $user_id, 'bluem_latest_mandate_amount', 0 );
				}
				$successful_mandate = true;
			}
		} else {
			// no maxamount check, so just continue;
			$successful_mandate = true;
		}

		if ( $update_metadata ) {
			if ( self::VERBOSE ) {
				echo "<br>updating user meta: bluem_latest_mandate_validated to value {$successful_mandate} - result: ";
			}
			update_user_meta(
				$user_id,
				'bluem_latest_mandate_validated',
				$successful_mandate
			);
		}

		if ( $successful_mandate ) {
			if ( $update_metadata ) {
				if ( $mandate_id !== "" ) {
					if ( self::VERBOSE ) {
						echo "<br>updating user meta: bluem_latest_mandate_id to value {$mandate_id} - result: ";
					}
					update_user_meta(
						$user_id,
						'bluem_latest_mandate_id',
						$mandate_id
					);
				}
				if ( $entrance_code !== "" ) {
					if ( self::VERBOSE ) {
						echo "<br>updating user meta: entranceCode to value {$entrance_code} - result: ";
					}
					update_user_meta(
						$user_id,
						'bluem_latest_mandate_entrance_code',
						$entrance_code
					);
				}
			}

			if ( self::VERBOSE ) {
				echo "mandaat is succesvol, order kan worden aangepast naar machtiging_goedgekeurd";
			}

			$order->update_status(
				'processing',
				__(
					"Machtiging (mandaat ID {$mandate_id}, verzoek ID {$request_id}
                        is gelukt en goedgekeurd",
					'wc-gateway-bluem'
				)
			);

			bluem_transaction_notification_email(
				$request_id
			);

			do_action(
				'bluem_woocommerce_valid_mandate_callback',
				$user_id,
				$response
			);

			if ( $redirect ) {
				if ( self::VERBOSE ) {
					die();
				}
				$this->bluem_thankyou( $order->get_id() );
			}

            return true;
		}
        return false;
	}
}
