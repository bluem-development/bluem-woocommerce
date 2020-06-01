<?php
// @todo: add Woo Product update key if necessary, check https://docs.woocommerce.com/document/create-a-plugin/
// @todo: Localize all error messages to english primarily
// @todo: finish docblocking
/**
 * Plugin Name: BlueM eMandate integratie for WooCommerce
 * Version: 1.0.0
 * Plugin URI: https://github.com/DaanRijpkema/bluem-woocommerce
 * Description: BlueM WooCommerce eMandate gateway integration
 * Author: Daan Rijpkema
 * Author URI: https://github.com/DaanRijpkema/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: bluem-woocommerce
 * Domain Path: /lang/
 *
 * @package WordPress
 * @author Hugh Lashbrooke
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
	exit;
}

// our own integration code
require 'BlueMIntegration.php';

/**
 * Check if WooCommerce is active
 **/
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	// bluem_woocommerce();
} else {
	throw new Exception("WooCommerce not activated, add this plugin first", 1);
}

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'bluem_add_gateway_class');
function bluem_add_gateway_class($gateways)
{
	$gateways[] = 'BlueM_Gateway'; // your class name is here
	return $gateways;
}

/*
 * The gateway class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'bluem_init_gateway_class');
function bluem_init_gateway_class()
{

	class BlueM_Gateway extends WC_Payment_Gateway
	{
		/**
		 * This boolean will cause more output to be generated for testing purposes. Keep it at false for the production environment or final testing
		 */
		private const VERBOSE = false;

		/**
		 * Class constructor
		 */
		public function __construct()
		{


			$this->id = 'bluem'; // payment gateway plugin ID
			$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
			$this->has_fields = true; // in case you need a custom credit card form
			$this->method_title = 'BlueM Integratie';
			$this->method_description = 'BlueM eMandate Payment Gateway voor WordPress - WooCommerce projecten, ontwikkeld door Daan Rijpkema voor NextDeli BV'; // will be displayed on the options page

			$this->bluem_options = [
				'environment' => [
					'title' => 'bluem_environment',
					'name' => 'environment',
					'description' => 'Vul hier prod, test of acc in voor productie, test of acceptance omgeving.',
					'default' => 'test'
				],
				'senderID' => [
					'title' => 'bluem_senderID',
					'name' => 'senderID',
					'description' => 'Het sender ID, uitgegeven door BlueM. Begint met een S, gevolgd door een getal.',
					'default' => "S1212"
				],
				'test_accessToken' => [
					'title' => 'bluem_test_accessToken',
					'name' => 'test_accessToken',
					'description' => 'Het access token om met BlueM te kunnen communiceren, voor de test omgeving',
					'default' => 'ef552fd4012f008a6fe3000000690107003559eed42f0000'
				],
				'production_accessToken' => [
					'title' => 'bluem_production_accessToken',
					'name' => 'production_accessToken',
					'description' => 'Het access token om met BlueM te kunnen communiceren, voor de productie omgeving',
					'default' => '170033937f3000f170df000000000107f1b150019333d317'
				],
				'merchantID' => [
					'title' => 'bluem_merchantID',
					'name' => 'merchantID',
					'description' => 'het merchantID, te vinden op het contract dat je hebt met de bank voor ontvangen van incasso machtigingen',
					'default' => '0020009469'
				],
				'thanksPage' => [
					'title' => 'bluem_thanksPage',
					'name' => 'thanksPage',
					'description' => 'De slug van de bedankt pagina waarnaar moet worden verwezen na voltooien proces. Als je ORDERID in de URL verwerkt wordt deze voor je ingevuld',
					'default' => ('mijn-account/view-order/ORDERID')
				],
				// 'merchantReturnURLBase'=>[
				// 	'title'=>'bluem_merchantReturnURLBase',
				// 	'name'=>'merchantReturnURLBase',
				// 	'description'=>'Link naar de pagina waar mensen naar worden teruggestuurd nadat de machtiging is afgegeven.',
				// 	'default'=>home_url('wc-api/bluem_callback')
				// 	//'http://192.168.64.2/wp/index.php/sample-page/'
				// ],
				'expectedReturnStatus' => [
					'title' => 'bluem_expectedReturnStatus',
					'name' => 'expectedReturnStatus',
					'description' => 'Welke status wil je terug krijgen voor een TEST transaction of status request? Mogelijke waarden: none, success, cancelled, expired, failure, open, pending',
					'default' => 'success'
				],
				'brandID' => [
					'title' => 'bluem_brandID',
					'name' => 'brandID',
					'description' => 'Wat is je BrandID? Ingesteld bij BlueM',
					'default' => 'NextDeliMandate'
				],
				'eMandateReason' => [
					'title' => 'bluem_eMandateReason',
					'name' => 'eMandateReason',
					'description' => 'bondige beschrijving van incasso weergegeven bij afgifte',
					'default' => 'Incasso abonnement'
				],
				'localInstrumentCode' => [
					'title' => 'bluem_localInstrumentCode',
					'name' => 'localInstrumentCode',
					'description' => 'Kies type incasso: CORE of B2B',
					'default' => 'B2B'
				]

				//Bij B2B krijgen wij terug of de gebruiker een maximaal mandaatbedrag heeft afgegeven. 
				// Dit mandaat bedrag wordt vergeleken met de orderwaarde. De orderwaarde plus 
				// onderstaand percentage moet lager zijn dan het maximale mandaatbedrag. 
				// Geef hier het percentage aan.
			];


			// gateways can support subscriptions, refunds, saved payment methods,
			// but in this version we begin with simple payments
			$this->supports = array(
				'products'
			);

			// Load the settings.
			$this->init_settings();
			// Method with all the options fields
			$this->init_form_fields();



			$this->title = $this->get_option('title');
			$this->description = $this->get_option('description');

			// ********** CREATING BlueM Configuration **********
			$this->bluem_config = new Stdclass();
			foreach ($this->bluem_options as $key => $option) {
				$option_key = "bluem_{$key}";
				$this->$option_key = $this->get_option($option_key);

				$this->bluem_config->$key = $this->get_option($option_key);
			}
			$this->bluem_config->merchantReturnURLBase = home_url('wc-api/bluem_callback');


			$this->bluem = new BlueMIntegration($this->bluem_config);

			$this->enabled = $this->get_option('enabled');

			// This action hook saves the settings
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

			// ********** CREATING plugin URLs for specific functions **********
			add_action('woocommerce_api_bluem_webhook', array($this, 'webhook'), 5);
			add_action('woocommerce_api_bluem_callback', array($this, 'callback'));


			// ********** Allow filtering Orders based on MandateID **********			
			add_filter('woocommerce_order_data_store_cpt_get_orders_query', function ($query, $query_vars) {
				if (!empty($query_vars['bluem_mandateid'])) {
					$query['meta_query'][] = array(
						'key' => 'bluem_mandateid',
						'value' => esc_attr($query_vars['bluem_mandateid']),
					);
				}

				return $query;
			}, 10, 2);
		}

		function bluem_thankyou($order_id)
		{
			$order = wc_get_order($order_id);
			$url = $order->get_view_order_url();

			if (!$order->has_status('failed')) {
				wp_safe_redirect($url);
				exit;
			}
		}
		/**
		 * Create plugin options page in admin interface
		 */
		public function init_form_fields()
		{
			$this->form_fields = apply_filters('wc_offline_form_fields', [
				'enabled' => [
					'title'       => 'Enable/Disable',
					'label'       => 'Enable BlueM eMandate Gateway',
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				],
				'title' => [
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default'     => 'Incasso machtiging voor zakelijke Rabobank, ING of ABN AMRO rekeningen',
				],
				'description' => [
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
					'default'     => 'Geef een B2B eMandate af voor een incasso voor je bestelling.',
				]
			]);

			foreach ($this->bluem_options as $key => $option) {
				$option_key = "bluem_{$key}";
				$this->form_fields[$option_key] = array(
					'title'       => $option['name'],
					'label'       => $option['name'],
					'type'        => (isset($option['type']) ? $option['type'] : "text"),
					'description' => $option['description'],
					'default'     => (isset($option['default']) ? $option['default'] : ""),
					'desc_tip'    => true,
				);
			}
		}
		/**
		 * Process payment through BlueM portal
		 *
		 * @param String $order_id
		 * @return void
		 */
		public function process_payment($order_id)
		{
			$order = wc_get_order($order_id);

			$user_id = $order->get_user_id();
			$user_meta = get_user_meta($user_id);

			if (isset($user_meta['bluem_latest_mandate_id']) && isset($user_meta['bluem_latest_mandate_amount'])) {
				if (
					count($user_meta['bluem_latest_mandate_id']) > 0 &&
					is_string($user_meta['bluem_latest_mandate_id'][0]) &&
					count($user_meta['bluem_latest_mandate_amount']) > 0 &&
					is_string($user_meta['bluem_latest_mandate_amount'][0])
				) {
					$existing_mandate_id = $user_meta['bluem_latest_mandate_id'][0];
					// $existing_mandate_amount = $user_meta['bluem_latest_mandate_amount'][0];
					$existing_entrance_code = $user_meta['bluem_latest_entrance_code'][0];

					$existing_mandate_response = $this->bluem->RequestTransactionStatus(
						$existing_mandate_id,
						$existing_entrance_code
					);
					if (!$existing_mandate_response->Status()) {
						// $this->renderPrompt("Fout: geen valide bestaand mandaat gevonden");
						// exit;
					} else {
						
						if ($existing_mandate_response->EMandateStatusUpdate->EMandateStatus->Status . "" === "Success") {
						
							if ($this->validateMandate(
								$existing_mandate_response,
								$order,
								false,
								false,
								false
							)) {
								return array(
									'result' => 'success', 
									'redirect' => $order->get_view_order_url()
								);
							}
						}
					}
				}
			}

			$order_id = $order->get_order_number();
			$customer_id = get_post_meta($order_id, '_customer_user', true);

			update_post_meta($order_id, 'bluem_entrancecode', $this->bluem->CreateEntranceCode());
			update_post_meta($order_id, 'bluem_mandateid', $this->bluem->CreateMandateId($order_id, $customer_id));

			$response = $this->bluem->CreateNewTransaction($customer_id, $order_id);

			// Mark as on-hold (we're awaiting the payment)
			// https://docs.woocommerce.com/document/managing-orders/
			// Possible statuses: 'pending', 'processing', 'on-hold', 'completed', 'refunded, 'failed', 'cancelled', 
			$order->update_status('pending', __('Awaiting BlueM Mandate Signature', 'wc-gateway-bluem'));


			// Remove cart
			global $woocommerce;
			$woocommerce->cart->empty_cart();

			if (isset($response->EMandateTransactionResponse->TransactionURL)) {

				// redirect cast to string, for AJAX response handling
				$transactionURL = ($response->EMandateTransactionResponse->TransactionURL . "");

				return array(
					'result' => 'success',
					'redirect' => $transactionURL
				);
			} else {
				return array(
					'result' => 'failure'
				);
			}
		}

		/**
		 * Webhook action
		 *
		 * @return void
		 */
		public function webhook()
		{
			$statusUpdateObject = $this->bluem->Webhook();
			
			$entranceCode = $statusUpdateObject->entranceCode . "";
			$mandateID = $statusUpdateObject->EMandateStatus->MandateID . "";

			$webhook_status = $statusUpdateObject->EMandateStatus->Status . "";

			$order = $this->getOrder($mandateID);
			if (is_null($order)) {
				echo "Error: No order found";
				exit;
			}
			$order_status = $order->get_status();

			if (self::VERBOSE) {

				echo "order_status: {$order_status}" . PHP_EOL;
				echo "webhook_status: {$webhook_status}" . PHP_EOL;
			}

			$user_id = $user_id = $order->get_user_id();
			$user_meta = get_user_meta($user_id);
			
			// Todo: if maxamount comes back from webhook (it should) then it can be accessed here
			// if (isset($user_meta['bluem_latest_mandate_amount'][0])) {
			// 	$mandate_amount = $user_meta['bluem_latest_mandate_amount'][0];
			// } else {
				
			// }
			if(isset($statusUpdateObject->EMandateStatus->AcceptanceReport->MaxAmount)) {
				$mandate_amount = (float) ($statusUpdateObject->EMandateStatus->AcceptanceReport->MaxAmount . "");
			} else {
				$mandate_amount = (float) 0.0;	// mandate amount is not set, so it is unlimited
			}
            if (self::VERBOSE) {
                var_dump($mandate_amount);
                echo PHP_EOL;
			}
			die();

			if (self::VERBOSE) echo "mandate_amount: {$mandate_amount}" . PHP_EOL;

			$mandate_successful = false;

			if ($mandate_amount !== 0.0) {

				$order_price = $order->get_total();
				$max_order_amount = (float) ($order_price * 1.1);
				if (self::VERBOSE) "max_order_amount: {$max_order_amount}" . PHP_EOL;

				if ($mandate_amount >= $max_order_amount) {
					$mandate_successful = true;
					if (self::VERBOSE) "mandate is enough" . PHP_EOL;
				} else {
					if (self::VERBOSE) "mandate is too small" . PHP_EOL;
				}
			}
			if ($webhook_status === "Success") {

				if ($order_status === "processing") {
					// order is already marked as processing, nothing more is necessary
				} elseif ($order_status === "pending") {
					// check if maximum of order does not exceed mandate size based on user metadata
					if ($mandate_successful) {

						$order->update_status('processing', __('Machtiging is gelukt en goedgekeurd; via webhook', 'wc-gateway-bluem'));
					}
					// iff order is within size, update to processing
				}
			} elseif ($webhook_status === "Cancelled") {
				$order->update_status('cancelled', __('Machtiging is geannuleerd; via webhook', 'wc-gateway-bluem'));
			} elseif ($webhook_status === "Open" || $webhook_status == "Pending") {
				// if the webhook is still open or pending, nothing has to be done as of yet
			} elseif ($webhook_status === "Expired") {
				$order->update_status('failed', __('Machtiging is verlopen; via webhook', 'wc-gateway-bluem'));
			} else {
				$order->update_status('failed', __('Machtiging is gefaald: fout of onbekende status; via webhook', 'wc-gateway-bluem'));
			}
			exit;
		}



		/**
		 * Retrieve an order based on its mandate_id in metadata from the WooCommerce store
		 *
		 * @param String $mandateID
		 * @return void
		 */
		private function getOrder(String $mandateID)
		{
			$orders = wc_get_orders(array(
				'orderby'   => 'date',
				'order'     => 'DESC',
				'bluem_mandateid' => $mandateID
			));
			if (count($orders) == 0) {
				return null;
			}
			return $orders[0];
		}


		/**
		 * Callback function after Mandate process has been completed by the user
		 * @return function [description]
		 */
		public function callback()
		{

			// $this->bluem = new BlueMIntegration($this->bluem_config);


			$mandateID = $_GET['mandateID'];
			if (!isset($_GET['mandateID'])) {
				$this->renderPrompt("Fout: geen juist mandaat id teruggekregen bij callback. Neem contact op met de webshop en vermeld je contactgegevens.");
				exit;
			}

			$order = $this->getOrder($mandateID);
			if (is_null($order)) {
				$this->renderPrompt("Fout: mandaat niet gevonden in webshop. Neem contact op met de webshop en vermeld de code {$mandateID} bij je gegevens.");
				exit;
			}
			$user_id = $order->get_user_id();

			// $order_meta = $order->get_meta_data();
			$entranceCode = $order->get_meta('bluem_entrancecode');

			$response = $this->bluem->RequestTransactionStatus($mandateID, $entranceCode);
			// var_dump($response);
			// die();
			// if(is_null($response) || is_string($response)) {
			// 	$this->renderPrompt("Fout bij opvragen status: " . $response->Error() . "<br>Neem contact op met de webshop en vermeld deze status");
			// 	exit;
			// }

			if (!$response->Status()) {
				$this->renderPrompt("Fout bij opvragen status: " . $response->Error() . "<br>Neem contact op met de webshop en vermeld deze status");
				exit;
			}

			if (self::VERBOSE) {
				var_dump("mandateid: " . $mandateID);
				var_dump("entrancecode: " . $entranceCode);
				echo "<hr>";
				var_dump($response);
				echo "<hr>";
			}

			$statusUpdateObject = $response->EMandateStatusUpdate;
			$statusCode = $statusUpdateObject->EMandateStatus->Status . "";
			// var_dump($statusCode);
			if ($statusCode === "Success") {

				$this->validateMandate($response, $order, true, true, true, $mandateID, $entranceCode);
			} elseif ($statusCode === "Cancelled") {
				$order->update_status('cancelled', __('Machtiging is geannuleerd', 'wc-gateway-bluem'));

				$this->renderPrompt("Je hebt de mandaat ondertekening geannuleerd");
				// terug naar order pagina om het opnieuw te proberen?
				exit;
			} elseif ($statusCode === "Open" || $statusCode == "Pending") {

				$this->renderPrompt("De mandaat ondertekening is nog niet bevestigd. Dit kan even duren maar gebeurt automatisch.");
				// callback pagina beschikbaar houden om het opnieuw te proberen?
				// is simpelweg SITE/wc-api/bluem_callback?mandateID=$mandateID
				exit;
			} elseif ($statusCode === "Expired") {
				$order->update_status('failed', __('Machtiging is verlopen', 'wc-gateway-bluem'));

				$this->renderPrompt("Fout: De mandaat of het verzoek daartoe is verlopen");
				exit;
			} else {
				$order->update_status('failed', __('Machtiging is gefaald: fout of onbekende status', 'wc-gateway-bluem'));
				//$statusCode == "Failure"
				$this->renderPrompt("Fout: Onbekende of foutieve status teruggekregen: {$statusCode}<br>Neem contact op met de webshop en vermeld deze status");
				exit;
			}
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
		 * @return void
		 */
		private function validateMandate($response, $order, $block_processing = false, $update_metadata = true, $redirect = true, $mandate_id = null, $entrance_code = null)
		{
			$maxAmountResponse = $this->bluem->GetMaximumAmountFromTransactionResponse($response);
			$user_id = $order->get_user_id();

			// NextDeli specific: estimate 10% markup on order total:
			$order_total_plus = (float) $order->get_total() * 1.1;

			if (self::VERBOSE) {

				if ($maxAmountResponse === 0.0) {
					echo "No max amount set";
				} else {
					echo "MAX AMOUNT SET AT {$maxAmountResponse->amount} {$maxAmountResponse->currency}";
				}
				echo "<hr>";
				echo "Totaalbedrag: ";
				var_dump((float) $order->get_total());
				echo " | totaalbedrag +10 procent: ";
				var_dump($order_total_plus);
				echo "<hr>";
			}

			$successful_mandate = false;

			if ($update_metadata) {
				if (self::VERBOSE) {
					echo "<br>updating user meta: bluem_latest_mandate_id and entranceCode to value {$mandate_id} and {$entrance_code} - result: ";
				}
				update_user_meta($user_id, 'bluem_latest_mandate_id', $mandate_id);
				update_user_meta($user_id, 'bluem_latest_entrance_code', $entrance_code);
			}



			if (isset($maxAmountResponse->amount) && $maxAmountResponse->amount !== 0.0) {
				if ($update_metadata) {
					if (self::VERBOSE) {
						echo "<br>updating user meta: bluem_latest_mandate_amount to value {$maxAmountResponse->amount} - result: ";
					}
					update_user_meta($user_id, 'bluem_latest_mandate_amount', $maxAmountResponse->amount);
				}
				$allowed_margin = ($order_total_plus <= $maxAmountResponse->amount);
				if (self::VERBOSE) {

					echo "binnen machtiging marge?";
					var_dump($allowed_margin);
				}

				if ($allowed_margin) {
					$successful_mandate = true;
				} else {

					if ($block_processing) {

						$order->update_status('pending', __('Machtiging moet opnieuw ondertekend worden, want mandaat bedrag is te laag', 'wc-gateway-bluem'));

						$url = $order->get_checkout_payment_url();
						$order_total_plus_string = str_replace(".",",",("".round($order_total_plus,2)));
						$this->renderPrompt(
							"<p>Het automatische incasso mandaat dat je hebt afgegeven is niet toereikend voor de incassering van het factuurbedrag van jouw bestelling.</p>
							<p>De geschatte factuurwaarde van jouw bestelling is EUR {$order_total_plus_string}. Het mandaat voor de automatische incasso die je hebt ingesteld is EUR {$maxAmountResponse->amount}. Ons advies is om jouw mandaat voor automatische incasso te verhogen of voor 'onbeperkt' te kiezen.</p>" .
								"<p><a href='{$url}' target='_self'>Klik hier om terug te gaan naar de betalingspagina en een nieuw mandaat af te geven</a></p>",
							false
						);

						exit;
					}
				}
			} else {
				if ($update_metadata) {
					if (self::VERBOSE) {
						echo "<br>updating user meta: bluem_latest_mandate_amount to value 0 - result: ";
					}
					update_user_meta($user_id, 'bluem_latest_mandate_amount', 0);
				}
				$successful_mandate = true;
			}

			if ($update_metadata) {
				if (self::VERBOSE) {
					echo "<br>updating user meta: bluem_latest_mandate_validated to value {$successful_mandate} - result: ";
				}
				update_user_meta($user_id, 'bluem_latest_mandate_validated', $successful_mandate);
			}

			if ($successful_mandate) {
				if(self::VERBOSE)
				{
					echo "mandaat is succesvol, order kan worden aangepast naar machtiging_goedgekeurd";
				}
				$order->update_status('processing', __('Machtiging is gelukt en goedgekeurd', 'wc-gateway-bluem'));
				if ($redirect) {
                    if (self::VERBOSE) {
                        die();
                    }
					$this->bluem_thankyou($order->get_id());
				} else {
					return true;
				}
			}
		}

		/**
		 * Retrieve header HTML for error/message prompts
		 *
		 * @return String
		 */
		private function getSimpleHeader(): String
		{
			return "<!DOCTYPE html><html><body><div 
			style='font-family:Arial,sans-serif;display:block; 
			margin:40pt auto; padding:10pt 20pt; border:1px solid #eee; 
			background:#fff; max-width:500px;'>";
		}
		/**
		 * Retrieve footer HTML for error/message prompt. Can include a simple link back to the webshop home URL.
		 *
		 * @param Bool $include_link
		 * @return String
		 */
		private function getSimpleFooter(Bool $include_link = true): String
		{
			return ($include_link ? "<p><a href='" . home_url() . "' target='_self' style='text-decoration:none;'>Ga terug naar de webshop</a></p>" : "") .
				"</div></body></html>";
		}

		/**
		 * Render a piece of HTML sandwiched beteween a simple header and footer, with an optionally included link back home
		 *
		 * @param String $html
		 * @param boolean $include_link
		 * @return void
		 */
		private function renderPrompt(String $html, $include_link = true)
		{
			echo $this->getSimpleHeader();
			echo $html;
			echo $this->getSimpleFooter($include_link);
		}
	}
}

// https://www.skyverge.com/blog/how-to-create-a-simple-woocommerce-payment-gateway/
