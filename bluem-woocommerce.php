<?php
// TODO: add Woo Product update key if necessary, check https://docs.woocommerce.com/document/create-a-plugin/

/**
 * Plugin Name: WooCommerce BlueM eMandate Integration
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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// our own integration code
// require_once 'BlueMIntegration.php';
// require_once 'BlueMIntegrationCallback.php';
// require_once 'BlueMIntegrationWebhook.php';





/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	// bluem_woocommerce();
} else {
	throw new Exception("WooCommerce not activated, add this pluginf irst", 1);	
}


/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'bluem_add_gateway_class' );
function bluem_add_gateway_class( $gateways ) {
	$gateways[] = 'BlueM_Gateway'; // your class name is here
	return $gateways;
}


/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'bluem_init_gateway_class' );
function bluem_init_gateway_class() {
 
	class BlueM_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {
 
		
 		$this->id = 'bluem'; // payment gateway plugin ID
		$this->icon = ''; // URL of the icon that will be displayed on checkout page near your gateway name
		$this->has_fields = true; // in case you need a custom credit card form
		$this->method_title = 'BlueM Gateway';
		$this->method_description = 'BlueM eMandate gateway'; // will be displayed on the options page
	 
		$this->bluem_options = [
			'bluem_environment'=>[
				'key'=>'bluem_environment',
				'name'=>'environment',
				'description'=>'Vul hier prod, test of acc in voor productie, test of acceptance omgeving.',
				'default'=>'test'
			],
			'bluem_senderID'=>[
				'key'=>'bluem_senderID',
				'name'=>'senderID',
				'description'=>'Het sender ID, uitgegeven door BlueM. Begint met een S, gevolgd door een getal.',
				'default'=>"S1212"
			],
			'bluem_test_accessToken'=>[
				'key'=>'bluem_test_accessToken',
				'name'=>'test_accessToken',
				'description'=>'Het access token om met BlueM te kunnen communiceren, voor de test omgeving',
				'default'=>'ef552fd4012f008a6fe3000000690107003559eed42f0000'
			],
			'bluem_production_accessToken'=>[
				'key'=>'bluem_production_accessToken',
				'name'=>'production_accessToken',
				'description'=>'Het access token om met BlueM te kunnen communiceren, voor de productie omgeving',
				'default'=>'170033937f3000f170df000000000107f1b150019333d317'
			],
			'bluem_merchantID'=>[
				'key'=>'bluem_merchantID',
				'name'=>'merchantID',
				'description'=>'het merchantID, te vinden op het contract dat je hebt met de bank voor ontvangen van incasso machtigingen',
				'default'=>'0020009469'
			],
			'bluem_merchantReturnURLBase'=>[
				'key'=>'bluem_merchantReturnURLBase',
				'name'=>'merchantReturnURLBase',
				'description'=>'Link naar de pagina waar mensen naar worden teruggestuurd nadat de machtiging is afgegeven.',
				'default'=>'http://192.168.64.2/wp/index.php/sample-page/'
			]
		];
		

		// gateways can support subscriptions, refunds, saved payment methods,
		// but in this version we begin with simple payments
		$this->supports = array(
			'products'
		);
	 
		// Method with all the options fields
		$this->init_form_fields();
	 
		// Load the settings.
		$this->init_settings();
		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		
		
		foreach ($this->bluem_options as $key => $option) {
			// $key = "bluem_{$option}";
			$this->$key = $this->get_option($key);
		}

		$this->enabled = $this->get_option( 'enabled' );
		
		// $this->testmode = 'yes' === $this->get_option( 'testmode' );
		

		// $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
		// $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
	 
		// This action hook saves the settings
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	 
		// We need custom JavaScript to obtain a token
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
 
			// You can also register a webhook here
			// not needed yet
			// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
 		}
 
		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields(){
 
		$this->form_fields = array(
		'enabled' => array(
			'title'       => 'Enable/Disable',
			'label'       => 'Enable BlueM eMandate Gateway',
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
		),
		'title' => array(
			'title'       => 'Title',
			'type'        => 'text',
			'description' => 'This controls the title which the user sees during checkout.',
			'default'     => 'NextDeli eMandate',
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => 'Description',
			'type'        => 'textarea',
			'description' => 'This controls the description which the user sees during checkout.',
			'default'     => 'Geef een B2B eMandate af voor een incasso voor je bestelling.',
		),


		// 'bluem_environment' => array(
		// 	'title'       => 'BlueM integration Environment',
		// 	'label'       => 'Enable Test Mode',
		// 	'type'        => 'text',
		// 	'description' => 'Choose "prod", "test" or "acc" to distinguish the call environment.',
		// 	'default'     => 'test',
		// 	'desc_tip'    => true,
		// ),
		// 'bluem_sender_id' => array(
		// 	'title'       => 'BlueM senderID',
		// 	'label'       => 'SenderID',
		// 	'type'        => 'text',
		// 	'description' => 'Sender ID given by BlueM',
		// 	'default'     => 'S....',
		// 	'desc_tip'    => true,
		// ),

		// 'testmode' => array(
		// 	'title'       => 'Test mode',
		// 	'label'       => 'Enable Test Mode',
		// 	'type'        => 'checkbox',
		// 	'description' => 'Place the payment gateway in test mode using test API keys.',
		// 	'default'     => 'yes',
		// 	'desc_tip'    => true,
		// ),
		// 'test_publishable_key' => array(
		// 	'title'       => 'Test Publishable Key',
		// 	'type'        => 'text'
		// ),
		// 'test_private_key' => array(
		// 	'title'       => 'Test Private Key',
		// 	'type'        => 'password',
		// ),
		// 'publishable_key' => array(
		// 	'title'       => 'Live Publishable Key',
		// 	'type'        => 'text'
		// ),
		// 'private_key' => array(
		// 	'title'       => 'Live Private Key',
		// 	'type'        => 'password'
		// )
	);
 foreach ($this->bluem_options as $key => $option) {
			$key = "bluem_{$key}";
		$this->form_fields[$key] = array(
			'title'       => $option['name'],
			'label'       => $option['name'],
			'type'        => (isset($option['type'])?$option['type']:"text"),
			'description' => $option['description'],
			'default'     => (isset($option['default'])?$option['default']:""),
			'desc_tip'    => true,
			);
		}
	 	}
 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {
 
		// ...
 
		}
 
		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
	 	public function payment_scripts() {
 
		// ...
 
	 	}
 
		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {
 
		// ...
 
		}
 
		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {
 
		// ...
 
	 	}
 
		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {
 
		// ...
 
	 	}
 	}
}


