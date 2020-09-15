<?php


// @todo: add Woo Product update key if necessary, check https://docs.woocommerce.com/document/create-a-plugin/
// @todo: Localize all error messages to english primarily
// @todo: finish docblocking

if (!defined('ABSPATH')) {
	exit;
}


// get composer dependencies
require __DIR__ . '/vendor/autoload.php';


// get specific gateways and helpers
require_once __DIR__ . '/bluem-helper.php';



// use Bluem\BluemIntegration;

use Bluem\BluemPHP\IdentityBluemRequest;
use Bluem\BluemPHP\Integration;
use Carbon\Carbon;

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
add_filter('woocommerce_payment_gateways', 'bluem_add_gateway_class_mandates', 11);
function bluem_add_gateway_class_mandates($gateways)
{
	$gateways[] = 'BlueM_Gateway_Mandates'; // your class name is here
	return $gateways;
}

function _bluem_get_mandates_option($key)
{
	$options = _bluem_get_mandates_options();
	if (array_key_exists($key, $options)) {
		return $options[$key];
	}
	return false;
}

function _bluem_get_mandates_options()
{
	return [

		'merchantID' => [
			'key' => 'merchantID',
			'title' => 'bluem_merchantID',
			'name' => 'Bluem Merchant ID',
			'description' => 'het merchantID, te vinden op het contract dat je hebt met de bank voor ontvangen van incasso machtigingen',
			'default' => ''
		],


		'merchantSubId' => [
			'key' => 'merchantSubId',
			'title' => 'bluem_merchantSubId',
			'name' => 'Bluem Merchant Sub ID',
			'default' => '0',
			'description' => 'Hier hoef je waarschijnlijk niks aan te veranderen',
			'type' => 'select',
			'options' => ['0' => '0']
		],

		'thanksPage' => [
			'key' => 'thanksPage',
			'title' => 'bluem_thanksPage',
			'name' => 'Waar wordt de gebruiker uiteindelijk naar verwezen?',
			'type' => 'select',
			'options' => ['order_page' => "Detailpagina van de zojuist geplaatste bestelling (standaard)"],
			// 'description' => 'De slug van de bedankt pagina waarnaar moet worden verwezen na voltooien proces. Als je ORDERID in de URL verwerkt wordt deze voor je ingevuld',
			// 'default' => ('my-account/orders/')
		],
		// 'merchantReturnURLBase'=>[
		// 	'title'=>'bluem_merchantReturnURLBase',
		// 	'name'=>'merchantReturnURLBase',
		// 	'description'=>'Link naar de pagina waar mensen naar worden teruggestuurd nadat de machtiging is afgegeven.',
		// 	'default'=>home_url('wc-api/bluem_callback')
		// 	//'http://192.168.64.2/wp/index.php/sample-page/'
		// ],
		'eMandateReason' => [
			'key' => 'eMandateReason',
			'title' => 'bluem_eMandateReason',
			'name' => 'Reden voor Machtiging',
			'description' => 'Een bondige beschrijving van incasso weergegeven bij afgifte',
			'default' => 'Incasso machtiging'
		],
		'localInstrumentCode' => [
			'key' => 'localInstrumentCode',
			'title' => 'bluem_localInstrumentCode',
			'name' => 'Type incasso machtiging afgifte',
			'description' => 'Kies type incassomachtiging. Neem bij vragen hierover contact op met Bluem.',
			'type' => 'select',
			'default' => 'CORE',
			'options' => ['CORE' => 'CORE machtiging', 'B2B' => 'B2B machtiging (zakelijk)']
		],

		// RequestType = Issuing (altijd)
		'requestType' => [
			'key' => 'requestType',
			'title' => 'bluem_requestType',
			'name' => 'Bluem Request Type',
			'description' => '',
			'type' => 'select',
			'default' => 'Issuing',
			'options' => ['Issuing' => 'Issuing (standaard)']
		],

		// SequenceType = RCUR (altijd)
		'sequenceType' => [
			'key' => 'sequenceType',
			'title' => 'bluem_sequenceType',
			'name' => 'Type incasso sequentie',
			'description' => '',
			'type' => 'select',
			'default' => 'RCUR',
			'options' => ['RCUR' => 'Recurring machtiging']
		],

		'successMessage' => [
			'key' => 'successMessage',
			'title' => 'bluem_successMessage',
			'name' => 'Melding bij succesvolle machtiging via shortcode formulier',
			'description' => 'Een bondige beschrijving volstaat.',
			'default' => 'Uw machtiging is succesvol ontvangen. Hartelijk dank.'
		],
		'errorMessage' => [
			'key' => 'errorMessage',
			'title' => 'bluem_errorMessage',
			'name' => 'Melding bij gefaalde machtiging via shortcode formulier',
			'description' => 'Een bondige beschrijving volstaat.',
			'default' => 'Er is een fout opgetreden. De incassomachtiging is geannuleerd.'
		],

		'purchaseIDPrefix' => [
			'key' => 'purchaseIDPrefix',
			'title' => 'bluem_purchaseIDPrefix',
			'name' => 'purchaseIDPrefix',
			'description' => "Welke korte tekst moet voor de debtorReference weergegeven worden bij een transactie in de Bluem incassomachtiging portaal. Dit kan handig zijn om Bluem transacties makkelijk te kunnen identificeren.",
			'type' => 'text',
			'default' => ''
		],
		'debtorReferenceFieldName' => [
			'key' => 'debtorReferenceFieldName',
			'title' => 'bluem_debtorReferenceFieldName',
			'name' => 'debtorReferenceFieldName',
			'description' => "Welk label moet bij het invulveld in het formulier komen te staan? Dit kan bijvoorbeeld 'volledige naam' of 'klantnummer' zijn.",
			'type' => 'text',
			'default' => ''
		],
		// 'thanksPageURL'=> [ 
		// 	'key'=>'thanksPageURL',
		// 	'title'=>'bluem_thanksPageURL',
		// 	'name'=>'thanksPageURL',
		// 	'description'=>"Op welke pagina wordt de shortcode geplaatst?",
		// 	'type'=>'text',
		// 	'default'=>''
		// ],
		'mandate_id_counter' => [
			'key' => 'mandate_id_counter',
			'title' => 'bluem_mandate_id_counter',
			'name' => 'mandate_id_counter',
			'description' => "Op welk getal wil je mandaat op idt moment nummeren? Dit getal verhoogt zichzelf.",
			'type' => 'text',
			'default' => '1'
		],
		'maxAmountEnabled' => [
			'key' => 'maxAmountEnabled',
			'title' => 'bluem_maxAmountEnabled',
			'name' => 'maxAmountEnabled',
			'description' => "Wil je dat er een check wordt uitgevoerd op de maximale waarde van de incasso, indien er een beperkte bedrag incasso machtiging is afgegeven? Zet dit gegeven dan op 'wel checken'. Er wordt dan een foutmelding gegeven als een klant een bestelling plaatst met een toegestaan bedrag dat lager is dan het orderbedrag (vermenigvuldigd met het volgende gegeven, de factor). Is de machtiging onbeperkt of anders groter dan het orderbedrag, dan wordt de machtiging geaccepteerd.",
			'type' => 'select',
			'default' => '0',
			'options' => ['0' => 'Niet checken op MaxAmount', '1' => 'Wel checken op MaxAmount'],
		],

		'maxAmountFactor' => [
			'key' => 'maxAmountFactor',
			'title' => 'bluem_maxAmountFactor',
			'name' => 'maxAmountFactor',
			'description' => "Als er een max amount wordt meegestuurd, wat is dan het maximale bedrag wat wordt toegestaan? Gebaseerd op de order grootte.",
			'type' => 'number',
			'attrs' => ['step' => '0.01', 'min' => '0.00', 'max' => '999.00', 'placeholder' => '1.00'],
			'default' => '1.00'
		]




		//Bij B2B krijgen wij terug of de gebruiker een maximaal mandaatbedrag heeft afgegeven.
		// Dit mandaat bedrag wordt vergeleken met de orderwaarde. De orderwaarde plus
		// onderstaand percentage moet lager zijn dan het maximale mandaatbedrag.
		// Geef hier het percentage aan.
	];
}

/*
 * The gateway class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'bluem_init_mandate_gateway_class');
function bluem_init_mandate_gateway_class()
{

	class BlueM_Gateway_Mandates extends WC_Payment_Gateway
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
			$this->core = new Bluem_Helper();


			$this->id = 'bluem_mandates'; // payment gateway plugin ID
			$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
			$this->has_fields = true; // in case you need a custom credit card form
			$this->method_title = 'BlueM Machtiging via eMandate';
			$this->method_description = 'BlueM eMandate Payment Gateway voor WordPress - WooCommerce. Alle instellingen zijn in te stellen onder <a href="' . home_url('wp-admin/options-general.php?page=bluem-woocommerce') . '">Instellingen &rarr; Bluem</a>'; // will be displayed on the options page


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

			// ********** CREATING Bluem Configuration **********
			$this->bluem_config = _get_bluem_config();
			$this->bluem = new Integration($this->bluem_config);

			$this->enabled = $this->get_option('enabled');

			// This action hook saves the settings
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

			// ********** CREATING plugin URLs for specific functions **********
			add_action('woocommerce_api_bluem_mandates_webhook', array($this, 'mandates_webhook'), 5);
			add_action('woocommerce_api_bluem_mandates_callback', array($this, 'mandates_callback'));

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

		/**
		 * Generic thank you page that redirects to the specific order page.
		 *
		 * @param [type] $order_id
		 * @return void
		 */
		public function bluem_thankyou($order_id)
		{
			$order = wc_get_order($order_id);
			$url = $order->get_view_order_url();

			if (!$order->has_status('failed')) {
				wp_safe_redirect($url);
				exit;
			}

			// todo: add alternative route?
		}

		/**
		 * Create plugin options page in admin interface
		 */
		public function init_form_fields()
		{
			$this->form_fields = apply_filters('wc_offline_form_fields', [
				'enabled' => [
					'title'       => 'Enable/Disable',
					'label'       => 'Activeer de Bluem eMandate Gateway',
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				],
				'title' => [
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
			]);
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

		// MANDATE SPECIFICS: 

		/**
		 * Process payment through Bluem portal
		 *
		 * @param String $order_id
		 * @return void
		 */
		public function process_payment($order_id)
		{
			$order = wc_get_order($order_id);

			$user_id = $order->get_user_id();
			$user_meta = get_user_meta($user_id);

			$settings = get_option('bluem_woocommerce_options');
			$maxAmountEnabled = (isset($settings['maxAmountEnabled']) ? ($settings['maxAmountEnabled'] == "1") : false);
			if ($maxAmountEnabled) {

				$maxAmountFactor = (isset($settings['maxAmountFactor']) ? (float)($settings['maxAmountFactor']) : false);
			} else {
				$maxAmountFactor = 1.0;
			}

			$bluem_latest_mandate_id = null;
			if (isset($user_meta['bluem_latest_mandate_id'])) {
				$bluem_latest_mandate_id = $user_meta['bluem_latest_mandate_id'][0];
			}
			$bluem_latest_mandate_amount = null;
			if (isset($user_meta['bluem_latest_mandate_amount'])) {
				$bluem_latest_mandate_amount = $user_meta['bluem_latest_mandate_amount'][0];
			}
			$bluem_latest_mandate_entrance_code = null;
			if (isset($user_meta['bluem_latest_mandate_entrance_code'])) {
				$bluem_latest_mandate_entrance_code = $user_meta['bluem_latest_mandate_entrance_code'][0];
			}
			
			if (
				!is_null($bluem_latest_mandate_id) && !is_null($bluem_latest_mandate_amount) &&
				($maxAmountEnabled == false || ($maxAmountEnabled &&
					($bluem_latest_mandate_amount == 0 ||
						($bluem_latest_mandate_amount > 0 && $bluem_latest_mandate_amount  >= (float)$order->get_total() * $maxAmountFactor))))
			) {

				
				$existing_mandate_response = $this->bluem->MandateStatus(
					$bluem_latest_mandate_id,
					$bluem_latest_mandate_entrance_code
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
						} else {
							// echo "mandaat gevonden maar niet valide";
						}
					}
				}
			}
			

			$order_id = $order->get_order_number();
			$customer_id = get_post_meta($order_id, '_customer_user', true);

			$mandate_id = $this->bluem->CreateMandateId($order_id, $customer_id);

			$response = $this->bluem->Mandate(
				$customer_id,
				$order_id,
				$mandate_id
			);
			

			if (is_a($response, "Bluem\BluemPHP\ErrorBluemResponse", false)) {
				throw new Exception("An error occured in the payment method. Please contact the webshop owner with this message:  " . $response->error());
			}

			$attrs = $response->EMandateTransactionResponse->attributes();
			
			// var_dump($attrs['entranceCode'] . "");
			if (!isset($attrs['entranceCode'])) {
				throw new Exception("An error occured in reading the transaction response. Please contact the webshop owner");
			}
			$entranceCode = $attrs['entranceCode'] . "";


			update_post_meta($order_id, 'bluem_entrancecode', $entranceCode);
			update_post_meta($order_id, 'bluem_mandateid', $mandate_id);

			// https://docs.woocommerce.com/document/managing-orders/
			// Possible statuses: 'pending', 'processing', 'on-hold', 'completed', 'refunded, 'failed', 'cancelled', 

			// Remove cart
			global $woocommerce;
			$woocommerce->cart->empty_cart();
			$order->update_status('pending', __('Awaiting Bluem Mandate Signature', 'wc-gateway-bluem'));

			if (isset($response->EMandateTransactionResponse->TransactionURL)) {

				// redirect cast to string, for AJAX response handling
				$transactionURL = ($response->EMandateTransactionResponse->TransactionURL . "");
				return array(
					'result' => 'success',
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
		public function mandates_webhook()
		{

			// todo: update this

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
			if (isset($statusUpdateObject->EMandateStatus->AcceptanceReport->MaxAmount)) {
				$mandate_amount = (float) ($statusUpdateObject->EMandateStatus->AcceptanceReport->MaxAmount . "");
			} else {
				$mandate_amount = (float) 0.0;	// mandate amount is not set, so it is unlimited
			}
			if (self::VERBOSE) {
				var_dump($mandate_amount);
				echo PHP_EOL;
			}
			// die();


			$settings = get_option('bluem_woocommerce_options');
			$maxAmountEnabled = (isset($settings['maxAmountEnabled']) ? ($settings['maxAmountEnabled'] == "1") : false);
			if ($maxAmountEnabled) {

				$maxAmountFactor = (isset($settings['maxAmountFactor']) ? (float)($settings['maxAmountFactor']) : false);
			} else {
				$maxAmountFactor = 1.0;
			}



			if (self::VERBOSE) echo "mandate_amount: {$mandate_amount}" . PHP_EOL;


			if ($maxAmountEnabled) {
				$mandate_successful = false;

				if ($mandate_amount !== 0.0) {
					$order_price = $order->get_total();
					$max_order_amount = (float) ($order_price * $maxAmountFactor);
					if (self::VERBOSE) {
						"max_order_amount: {$max_order_amount}" . PHP_EOL;
					}

					if ($mandate_amount >= $max_order_amount) {
						$mandate_successful = true;
						if (self::VERBOSE) {
							"mandate is enough" . PHP_EOL;
						}
					} else {
						if (self::VERBOSE) {
							"mandate is too small" . PHP_EOL;
						}
					}
				}
			} else {
				$mandate_successful = true;
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
		 * 
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
		 * mandates_Callback function after Mandate process has been completed by the user
		 * @return function [description]
		 */
		public function mandates_callback()
		{
			// echo "In mandate callback";

			// $this->bluem = new Integration($this->bluem_config);

			if (!isset($_GET['mandateID'])) {

				$this->renderPrompt("Fout: geen juist mandaat id teruggekregen bij mandates_callback. Neem contact op met de webshop en vermeld je contactgegevens.");
				exit;
			}
			$mandateID = $_GET['mandateID'];

			if (!isset($_GET['type']) || !in_array("" . $_GET['type'], ['default', 'simple'])) {
				$type = "default";
			} else {
				$type = $_GET['type'];
			}

			if ($type == "simple") {
			} else {
			}

			$order = $this->getOrder($mandateID);
			if (is_null($order)) {
				$this->renderPrompt("Fout: mandaat niet gevonden in webshop. Neem contact op met de webshop en vermeld de code {$mandateID} bij je gegevens.");
				exit;
			}
			$user_id = $order->get_user_id();

			// $order_meta = $order->get_meta_data();
			$entranceCode = $order->get_meta('bluem_entrancecode');

			// echo "Entrance: ";
			// var_dump($entranceCode);
			// die();
			// $mandateID = "415195c2dfc02ffa";
			$response = $this->bluem->MandateStatus($mandateID, $entranceCode);
			// var_dump($mandateID);
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


			$settings = get_option('bluem_woocommerce_options');
			$maxAmountEnabled = (isset($settings['maxAmountEnabled']) ? ($settings['maxAmountEnabled'] == "1") : false);
			if ($maxAmountEnabled) {
				$maxAmountFactor = (isset($settings['maxAmountFactor']) ? (float)($settings['maxAmountFactor']) : false);
			} else {
				$maxAmountFactor = 1.0;
			}

			$successful_mandate = false;

			if ($update_metadata) {
				if (self::VERBOSE) {
					echo "<br>updating user meta: bluem_latest_mandate_id and entranceCode to value {$mandate_id} and {$entrance_code} - result: ";
				}
				update_user_meta($user_id, 'bluem_latest_mandate_id', $mandate_id);
				update_user_meta($user_id, 'bluem_latest_mandate_entrance_code', $entrance_code);
			}


			if ($maxAmountEnabled) {

				// NextDeli specific: estimate 10% markup on order total:
				$order_total_plus = (float) $order->get_total() * $maxAmountFactor;

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
							$order_total_plus_string = str_replace(".", ",", ("" . round($order_total_plus, 2)));
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
			} else {
				// no maxamount check, so just continue;
				$successful_mandate = true;
			}

			if ($update_metadata) {
				if (self::VERBOSE) {
					echo "<br>updating user meta: bluem_latest_mandate_validated to value {$successful_mandate} - result: ";
				}
				update_user_meta($user_id, 'bluem_latest_mandate_validated', $successful_mandate);
			}

			if ($successful_mandate) {
				if (self::VERBOSE) {
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
	}





	function _get_bluem_config()
	{

		$core = new Bluem_Helper();

		$bluem_options = array_merge($core->GetBluemCoreOptions(), _bluem_get_mandates_options());
		$config = new Stdclass();

		$values = get_option('bluem_woocommerce_options');
		foreach ($bluem_options as $key => $option) {

			$config->$key = isset($values[$key]) ? $values[$key] : (isset($option['default']) ? $option['default'] : "");
		}
		$config->merchantReturnURLBase = home_url('wc-api/bluem_mandates_callback');
		return $config;
	}








	add_action('show_user_profile', 'bluem_woocommerce_mandates_show_extra_profile_fields');
	add_action('edit_user_profile', 'bluem_woocommerce_mandates_show_extra_profile_fields');

	function bluem_woocommerce_mandates_show_extra_profile_fields($user)
	{

?>
		<?php //var_dump($user->ID);
		?>
		<h2>Bluem eMandate Metadata</h2>
		<table class="form-table">
			<tr>
				<th><label for="bluem_latest_mandate_id">Meest recente MandateID</label></th>
				<td>
					<input type="text" name="bluem_latest_mandate_id" id="bluem_latest_mandate_id" value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_latest_mandate_id', true)); ?>" class="regular-text" /><br />
					<span class="description">Hier wordt het meest recente mandate ID geplaatst; en gebruikt bij het doen van een volgende checkout.</span>
				</td>
			</tr>
			<tr>
				<th><label for="bluem_latest_mandate_id">Meest recente EntranceCode</label></th>

				<td>
					<input type="text" name="bluem_latest_mandate_entrance_code" id="bluem_latest_mandate_entrance_code" value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_latest_mandate_entrance_code', true)); ?>" class="regular-text" /><br />
					<span class="description">Hier wordt het meest recente entrance_code geplaatst; en gebruikt bij het doen van een volgende checkout.</span>
				</td>
			</tr>
			<tr>
				<th><label for="bluem_latest_mandate_amount">Omvang laatste machtiging</label></th>
				<td>
					<input type="text" name="bluem_latest_mandate_amount" id="bluem_latest_mandate_amount" value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_latest_mandate_amount', true)); ?>" class="regular-text" /><br />
					<span class="description">Dit is de omvang van de laatste machtiging</span>
				</td>
			</tr>


		</table>
<?php
	}
	add_action('personal_options_update', 'bluem_woocommerce_mandates_save_extra_profile_fields');
	add_action('edit_user_profile_update', 'bluem_woocommerce_mandates_save_extra_profile_fields');

	function bluem_woocommerce_mandates_save_extra_profile_fields($user_id)
	{

		if (!current_user_can('edit_user', $user_id)) {
			return false;
		}

		update_user_meta($user_id, 'bluem_latest_mandate_id', esc_attr($_POST['bluem_latest_mandate_id']));
		update_user_meta($user_id, 'bluem_latest_mandate_entrance_code', esc_attr($_POST['bluem_latest_mandate_entrance_code']));
		update_user_meta($user_id, 'bluem_latest_mandate_amount', esc_attr($_POST['bluem_latest_mandate_amount']));

		// var_dump($_POST['bluem_latest_mandate_id']);
		// var_dump($_POST['bluem_latest_mandate_entrance_code']);

		// var_dump($_POST['bluem_latest_mandate_amount']);
		// die();
	}
}


/// SHORTCODE support!


/* ********* RENDERING THE STATIC FORM *********** */
add_shortcode('bluem_machtigingsformulier', 'bluem_mandateform');

/**
 * Shortcode: `[bluem_machtigingsformulier]`
 *
 * @return void
 */
function bluem_mandateform()
{
	$bluem_config = _get_bluem_config();
	// var_dump($bluem_config );
	ob_start();

	if (isset($_GET['result'])) {
		echo '<div class="">';
		if ($_GET['result'] == "true") {
			if (isset($bluem_config->successMessage)) {
				echo "<p>" . $bluem_config->successMessage . "</p>";
			} else {
				echo "<p>Uw machtiging is succesvol ontvangen. Hartelijk dank.</p>";
			}
		} else {
			if (isset($bluem_config->errorMessage)) {
				echo "<p>" . $bluem_config->errorMessage . "</p>";
			} else {
				echo "<p>Er is een fout opgetreden. De incassomachtiging is geannuleerd.</p>";
				// echo "<p>Uw machtiging is succesvol ontvangen. Hartelijk dank.</p>";
			}
			if (isset($_SESSION['bluem_recentTransactionURL']) && $_SESSION['bluem_recentTransactionURL'] !== "") {
				$retryURL = $_SESSION['bluem_recentTransactionURL'];
			} else {
				$retryURL = home_url($bluem_config->checkoutURL);
			}
			echo "<p><a href='{$retryURL}' target='_self' alt='probeer opnieuw te machtigen' class='button'>Probeer opnieuw</a></p>";
		}
		echo '</div>';
	} else {
		echo '<form action="' . home_url('bluem-woocommerce/execute') . '" method="post">';
		echo '<p>';
		echo $bluem_config->debtorReferenceFieldName . ' (verplicht) <br/>';
		echo '<input type="text" name="bluem_debtorReference" pattern="[a-zA-Z0-9 ]+" value="' . (isset($_POST["bluem_debtorReference"]) ? esc_attr($_POST["bluem_debtorReference"]) : '') . '" size="40" />';
		echo '</p>';
		echo '<p>';
		echo '<p><input type="submit" name="bluem-submitted" value="Machtiging aanmaken.."></p>';
		echo '</form>';
	}
	return ob_get_clean();
}

add_action('parse_request', 'bluem_mandate_shortcode_execute');
/**
 * This function is called POST from the form rendered on a page or post
 *
 * @return void
 */
function bluem_mandate_shortcode_execute()
{
	if (substr($_SERVER["REQUEST_URI"], -25) !== "bluem-woocommerce/execute") {
		// any other request
	} else {
		// if the submit button is clicked, send the email
		if (isset($_POST['bluem-submitted'])) {

			$debtorReference = sanitize_text_field($_POST["bluem_debtorReference"]);
			$bluem_config = _get_bluem_config();
			$bluem_config->merchantReturnURLBase = home_url("bluem-woocommerce/shortcode_callback");
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
			// var_dump($result);

			// var_dump($mandate_id);
			// die();

			$request = $bluem->CreateMandateRequest(
				$debtorReference,
				$mandate_id,
				$mandate_id
			);

			// die();

			// $request = new EMandateTransactionRequest(
			//     $bluem_config,
			//     $debtorReference,
			//     "",
			//     $bluem->CreateMandateID($debtorReference, "Machtiging"),
			//     (
			//         ($bluem_config->environment == BLUEM_ENVIRONMENT_TESTING &&
			//             isset($bluem_config->expected_return)) ?
			//         $bluem_config->expected_return : "")
			// );

			// Save the necessary data to later request more information and refer to this transaction
			// $_SESSION['bluem_mandateId'] = $request->mandateID;
			$_SESSION['bluem_entranceCode'] = $request->entranceCode;

			// var_dump($request);
			// $_SESSION['bluem_entranceCode'] = $request->entranceCode;

			// echo $request->xmlString();
			// die();
			// Actually perform the request.
			$response = $bluem->PerformRequest($request);

			// var_dump($response);

			// var_dump($response->EMandateTransactionResponse);
			$_SESSION['bluem_mandateId'] = $response->EMandateTransactionResponse->MandateID . "";
			// var_dump($_SESSION);
			// die();




			if (!isset($response->EMandateTransactionResponse->TransactionURL)) {
				echo "Er ging iets mis bij het aanmaken van de transactie.<br>Vermeld onderstaande informatie aan het websitebeheer:<br><pre>";
				var_dump($response);
				echo "</pre>";
				if (isset($response->EMandateTransactionResponse->Error->ErrorMessage)) {
					echo "<br>Response: " . $response->EMandateTransactionResponse->Error->ErrorMessage;
					// var_dump($response);
				}
				exit;
			}

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
}

/* ******** CALLBACK ****** */
add_action('parse_request', 'bluem_mandate_shortcode_callback');
/**
 * This function is executed at a callback GET request with a given mandateId. This is then, together with the entranceCode in Session, sent for a SUD to the Bluem API.
 *
 * @return void
 */
function bluem_mandate_shortcode_callback()
{
	if (strpos($_SERVER["REQUEST_URI"], "bluem-woocommerce/shortcode_callback") === false) {
		return;
	}

	$bluem_config = _get_bluem_config();
	$bluem = new Integration($bluem_config);

	// var_dump($_SESSION);

	// validation steps
	$mandateID = $_GET['mandateID'];
	if (!isset($_GET['mandateID'])) {
		if ($bluem_config->thanksPageURL !== "") {

			wp_redirect(home_url($bluem_config->thanksPageURL) . "?result=false&reason=error");
			// echo "<p>Er is een fout opgetreden. De incassomachtiging is geannuleerd.</p>";
			return;
		}
		echo "Fout: geen juist mandaat id teruggekregen bij callback. Neem contact op met de webshop en vermeld je contactgegevens.";
		exit;
	}

	$entranceCode = $_SESSION['bluem_entranceCode'];
	if (!isset($_SESSION['bluem_entranceCode']) || $_SESSION['bluem_entranceCode'] == "") {
		echo "Fout: Entrancecode is niet set; kan dus geen mandaat opvragen";
		die();
	}

	// Mandate SUD
	// var_dump($mandateID);

	// var_dump($entranceCode);
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



function bluem_woocommerce_mandates_settings_section()
{

	$mandate_id_counter = get_option('bluem_woocommerce_mandate_id_counter');
	// var_dump(home_url());
	if (home_url() == "https://www.h2opro.nl" && (int) ($mandate_id_counter . "") < 111100) {
		$mandate_id_counter += 111000;
		update_option('bluem_woocommerce_mandate_id_counter', $mandate_id_counter);
	}

	echo '<p>Hier kan je alle belangrijke gegevens instellen rondom Machtigingen. Lees de readme bij de plug-in voor meer informatie.</p>';

	echo "<p>Huidige mandaat ID counter: ";
	echo $mandate_id_counter;
	echo "</p>";

	echo "<p>Huidige Carbon tijd: " . Carbon::now()->timezone('Europe/Amsterdam')->toDateTimeString() . "</p>";
}

// https://www.skyverge.com/blog/how-to-create-a-simple-woocommerce-payment-gateway/
