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
require 'BlueMIntegration.php';


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
		$this->method_description = 'BlueM eMandate gateway<br>Test de integratie ook met <a href="'.get_site_url().'/wp-content/plugins/bluem-woocommerce/integration/index.php">de toolset op deze pagina</a>'; // will be displayed on the options page
	 
		$this->bluem_options = [
			'environment'=>[
				'title'=>'bluem_environment',
				'name'=>'environment',
				'description'=>'Vul hier prod, test of acc in voor productie, test of acceptance omgeving.',
				'default'=>'test'
			],
			'senderID'=>[
				'title'=>'bluem_senderID',
				'name'=>'senderID',
				'description'=>'Het sender ID, uitgegeven door BlueM. Begint met een S, gevolgd door een getal.',
				'default'=>"S1212"
			],
			'test_accessToken'=>[
				'title'=>'bluem_test_accessToken',
				'name'=>'test_accessToken',
				'description'=>'Het access token om met BlueM te kunnen communiceren, voor de test omgeving',
				'default'=>'ef552fd4012f008a6fe3000000690107003559eed42f0000'
			],
			'production_accessToken'=>[
				'title'=>'bluem_production_accessToken',
				'name'=>'production_accessToken',
				'description'=>'Het access token om met BlueM te kunnen communiceren, voor de productie omgeving',
				'default'=>'170033937f3000f170df000000000107f1b150019333d317'
			],
			'merchantID'=>[
				'title'=>'bluem_merchantID',
				'name'=>'merchantID',
				'description'=>'het merchantID, te vinden op het contract dat je hebt met de bank voor ontvangen van incasso machtigingen',
				'default'=>'0020009469'
			],
			'merchantReturnURLBase'=>[
				'title'=>'bluem_merchantReturnURLBase',
				'name'=>'merchantReturnURLBase',
				'description'=>'Link naar de pagina waar mensen naar worden teruggestuurd nadat de machtiging is afgegeven.',
				'default'=>home_url('wc-api/bluem_callback')
				//'http://192.168.64.2/wp/index.php/sample-page/'
			],
			'expectedReturnStatus'=>[
				'title'=>'bluem_expectedReturnStatus',
				'name'=>'expectedReturnStatus',
				'description'=>'Welke status wil je terug krijgen voor een TEST transaction of status request? Mogelijke waarden: none, success, cancelled, expired, failure, open, pending',
				'default'=>'success'
			],
			'brandID'=>[
				'title'=>'bluem_brandID',
				'name'=>'brandID',
				'description'=>'Wat is je BrandID? Ingesteld bij BlueM',
				'default'=>'NextDeliMandate'
			],
			'eMandateReason'=>[
				'title'=>'bluem_eMandateReason',
				'name'=>'eMandateReason',
				'description'=>'bondige beschrijving van incasso weergegeven bij afgifte',
				'default'=>'Incasso abonnement'
			],
			'localInstrumentCode'=>[
				'title'=>'bluem_localInstrumentCode',
				'name'=>'localInstrumentCode',
				'description'=>'Kies type incasso: CORE of B2B',
				'default'=>'B2B'
			]
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
	 


		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		

// 		$no_exists_value = get_option( 'bluem_merchantID' );
// var_dump( $no_exists_value ); /* outputs false */
 
// $no_exists_value = get_option( 'no_exists_value', 'default_value' );
// var_dump( $no_exists_value ); /* outputs 'default_value' */

// die();
		$this->bluem_config = new Stdclass();
		foreach ($this->bluem_options as $key => $option) {
			$option_key = "bluem_{$key}";
			$this->$option_key = $this->get_option($option_key);
			
			$this->bluem_config->$key = $this->get_option($option_key);
		}

		$this->bluem_config->merchantReturnURLBase = home_url('wc-api/bluem_callback');
		// var_dump($this->bluem_config);
		// die();

		$this->enabled = $this->get_option( 'enabled' );
		
		// $this->testmode = 'yes' === $this->get_option( 'testmode' );
		

		// $this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
		// $this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );
	 
		// This action hook saves the settings
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	 
		// We need custom JavaScript to obtain a token
		// add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
 
			// You can also register a webhook here
			// not needed yet
			add_action( 'woocommerce_api_bluem_webhook', array( $this, 'webhook' ) );
			add_action( 'woocommerce_api_bluem_callback', array( $this, 'callback' ) );

			// add callback just before creating order, to add more metadata to order
			// add_action('woocommerce_checkout_create_order', 'add_mandate_metadata', 20, 2);
// 

			// allow filtering on metadata
			// 
			// add_filter( 'woocommerce_get_wp_query_args', function( $wp_query_args, $query_vars ){
		 //    if ( isset( $query_vars['meta_query'] ) ) {
			//         $meta_query = isset( $wp_query_args['meta_query'] ) ? $wp_query_args['meta_query'] : [];
			//         $wp_query_args['meta_query'] = array_merge( $meta_query, $query_vars['meta_query'] );
			//     }
			//     return $wp_query_args;
			// }, 10, 2 );
			
add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', function ( $query, $query_vars ) {
	if ( ! empty( $query_vars['bluem_mandateid'] ) ) {
		$query['meta_query'][] = array(
			'key' => 'bluem_mandateid',
			'value' => esc_attr( $query_vars['bluem_mandateid'] ),
		);
	}

	return $query;
}, 10, 2 );
 		}
 

		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields()
 		{
	  
       
			$this->form_fields = apply_filters( 'wc_offline_form_fields', [
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
	 			// var_dump($option);
				$option_key = "bluem_{$key}";
				$this->form_fields[$option_key] = array(
					'title'       => $option['name'],
					'label'       => $option['name'],
					'type'        => (isset($option['type'])?$option['type']:"text"),
					'description' => $option['description'],
					'default'     => (isset($option['default'])?$option['default']:""),
					'desc_tip'    => true,
					);
				}
				// die();
		 	}
 
		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id )
		{
	// echo urldecode("https%3A%2F%2Ftest.viamijnbank.net%2Fm%2F003f0a000171855e07018f000b90dd07008571f0006c0000");
	// die();
	$order = wc_get_order( $order_id );


 	$order_id = $order->get_order_number();
	$customer_id = get_post_meta($order_id, '_customer_user', true);

	// var_dump($this->bluem_config);
	$bluemobj = new BlueMIntegration($this->bluem_config);
	// var_dump($bluemobj);

// echo $bluemobj->CreateEntranceCode($order);
	update_post_meta( $order_id, 'bluem_entrancecode', $bluemobj->CreateEntranceCode($order) );
    update_post_meta( $order_id, 'bluem_mandateid', $bluemobj->CreateMandateId($order_id,$customer_id) );


	$response = $bluemobj->CreateNewTransaction($customer_id,$order_id);
// var_dump($response);
// var_dump($response->EMandateTransactionResponse->TransactionURL."");
// die();
	return array(
	        'result' => 'success',
	        'redirect' => ($response->EMandateTransactionResponse->TransactionURL."")
	        //$response->EMandateTransactionResponse->TransactionURL
	    );


	// if($response->Status())
	// {
	    // Mark as on-hold (we're awaiting the cheque)
	    // $order->update_status('signing_mandate', __( 'Awaiting BlueM eMandate confirmation', 'woocommerce' ));
	    // // 'pending', 'processing', 'on-hold', 'completed', 'refunded, 'failed', 'cancelled', 

	    // Remove cart

	    // $woocommerce->cart->empty_cart();
// var_dump($response->EMandateTransactionResponse);
// die();
    	//  redirect
	    // return array(
	    //     'result' => 'failure',
	    //     'message' => 'testje'
	    //     // 'redirect' => $response->EMandateTransactionResponse->TransactionURL
	    // );
	    // return array(
	    //     'result' => 'success',
	    //     'redirect' => $response->EMandateTransactionResponse->TransactionURL
	    // );
	// } else {

	// 	echo "ERROR";
	// 	var_dump($response);
	// 	die();
	// 	wc_add_notice( __('Payment error:', 'woothemes') . $response->EMandateTransactionResponse->Error(), 'error' );
	// 	return;
	// }


//     die();
//  			// die();
// // die();
//          echo "ORDER ID: ".$order_id;
//          echo "\n<BR>CUSTOMER ID: ".$customer_id;
// echo "<hr>";
//          var_dump($order);   
// die();

//https://docs.woocommerce.com/wc-apidocs/class-WC_Order.html

         // die();
    // // Mark as on-hold (we're awaiting the payment)
    // https://docs.woocommerce.com/document/managing-orders/
    // $order->update_status( 'on-hold', __( 'Awaiting offline payment', 'wc-gateway-offline' ) );
            
    // // Reduce stock levels
    // $order->reduce_order_stock();
            
    // // Remove cart
    // WC()->cart->empty_cart();
            
    // // Return thankyou redirect
    // return array(
    //     'result'    => 'success',
    //     'redirect'  => $this->get_return_url( $order )
    // );


	 	}
 

//  function add_mandate_metadata( $order, $data ) {
// 	var_dump($order);
// 	die();
// 	$order_id = $order->get_order_number();
// 	$customer_id = get_post_meta($order_id, '_customer_user', true);

// 	$bluemobj = new BlueMIntegration($this->bluem_config);
//     $order->update_meta_data( '_bluem_entrancecode', $bluemobj->getEntranceCode($order) );
//     $order->update_meta_data( '_bluem_mandateid', $bluemobj->createMandateId($order_id,$customer_id) );
// }


 /**
 * Output for the order received page.
 */
public function thankyou_page() {
echo "Thanks";
// $order->payment_complete();
    if ( $this->instructions ) {
        echo wpautop( wptexturize( $this->instructions ) );
    }
    die();
}
    
/**
 * Add content to the WC emails.
 *
 * @access public
 * @param WC_Order $order
 * @param bool $sent_to_admin
 * @param bool $plain_text
 */
public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
        
    // if ( $this->instructions && ! $sent_to_admin && 'offline' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
    //     echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
    // }
}
		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {
			echo "YO";
 // str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'BlueM_Gateway', home_url( '/bluem-callback' ) ) );

 // add_action( 'woocommerce_api_wc_gateway_paypal', array( $this, 'check_ipn_response' ) );
	// 	// ...
 echo "WEBHOOK CALLED";
 exit;
	 	}


	 	public function callback() {
	 		
	 		$bluemobj = new BlueMIntegration($this->bluem_config);
// echo home_url('wc-api/bluem_callback');

 // str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'BlueM_Gateway', home_url( '/bluem-callback' ) ) );

 // add_action( 'woocommerce_api_wc_gateway_paypal', array( $this, 'check_ipn_response' ) );
	// 	// ...
 

$mandateID = $_GET['mandateID'];
if(!isset($_GET['mandateID']))
{
	echo "geen juist mandaat id teruggekregen bij callback";
	exit;
}
// var_dump(expression)
// woocommerce_get_wp_query_args()
// var_dump($mandateID);
$orders = wc_get_orders( array(
    'orderby'   => 'date',
    'order'     => 'DESC',
    'bluem_mandateid'=>$mandateID
    // 'meta_query' => array(
        // array(
            // 'key' => 'bluem_mandateid',
            // 'compare' => 'EXISTS'
            // 'value'=> $mandateID
    //     )
    // )
));

if(count($orders)==0) {
	echo "mandaat niet gevonden in webshop";
	die();
}

$order = $orders[0];
// var_dump($order);

$order_meta = $order->get_meta_data();
// var_dump($order_meta);
// die();
	// $_mandateid = $order->get_meta('bluem_mandateid');
	var_dump("mandateid: ".$mandateID);
	$entranceCode = $order->get_meta('bluem_entrancecode');
	var_dump("entrancecode: ".$entranceCode);



// foreach ($order_meta as $metadata) {
	// var_dump($metadata);
	// if($metadata->current_data->key)
	// {

	// }
// }

// $entranceID = $order->

// die();
// $order = "";
$response = $bluemobj->RequestTransactionStatus($mandateID,$entranceCode);
if(!$response->Status()) {
	echo "Fout: ".$response->Error();
	exit;
}
// var_dump($response);
$statusUpdateObject = $response->EMandateStatusUpdate;

$statusCode = $statusUpdateObject->EMandateStatus->Status;
switch ($statusCode) {
	case 'Success':
	{

		echo "De machtiging is gelukt";
		$order->update_status('paid', __( 'Machtiging is gelukt', 'woocommerce' ));
		break;
	}
		case 'cancelled': 
		{
			$order->update_status('cancelled', __( 'Machtiging is geannuleerd', 'woocommerce' ));

			break;
		}
		case 'expired': 
		{
			$order->update_status('failed', __( 'Machtiging is verlopen', 'woocommerce' ));

			break;
		}
		case 'failure': 
		{
			$order->update_status('failed', __( 'Machtiging is gefaald', 'woocommerce' ));

			break;
		}
		case 'open': 
		{

			break;
		}
		case 'pending': 
		{

			break;
		}
	default:
		# code...
		break;
}

echo "<hr>";
echo "Status: ".$statusCode;
echo "<hr>";
echo "xml data";
var_dump($statusUpdateObject->EMandateStatus->OriginalReport);
// $xml_raw_report = "<".$statusUpdateObject->EMandateStatus->OriginalReport;
// $xml_raw_report = str_replace(['![CDATA[',']]'], '', $xml_raw_report);
// var_dump($xml_raw_report);
// $xmlReport = new SimpleXMLElement($xml_raw_report,LIBXML_NOCDATA);
// var_dump($xmlReport);
die();



// var_dump();
// var_dump($statusUpdateObject);
// die();
 // exit;
	 	}
 	}
}


// https://www.skyverge.com/blog/how-to-create-a-simple-woocommerce-payment-gateway/