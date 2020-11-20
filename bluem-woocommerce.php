<?php

/**
 * Plugin Name: Bluem integration for WooCommerce
 * Version: 1.0.0
 * Plugin URI: https://github.com/DaanRijpkema/bluem-woocommerce
 * Description: Bluem WooCommerce integration for many functions: Payments and eMandates payment gateway and iDIN identity verification
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

// @todo: add Woo Product update key if necessary, check https://docs.woocommerce.com/document/create-a-plugin/
// @todo: Localize all error messages to english primarily
// @todo: finish docblocking
if (!defined('ABSPATH')) {
	exit;
}

// our own integration code

// get composer dependencies
require __DIR__ . '/vendor/autoload.php';

// get specific gateways and helpers
require_once __DIR__ . '/bluem-woocommerce-mandates.php';

require_once __DIR__ . '/bluem-woocommerce-payments.php';

require_once __DIR__ . '/bluem-woocommerce-idin.php';
require_once __DIR__ . '/bluem-woocommerce-idin-shortcode.php';


/**
 * Check if WooCommerce is active
 **/


if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	// bluem_woocommerce();
} else {

	// NO WCFM found, notify the admin!
	add_action('admin_notices', 'bluem_woocommerce_no_woocommerce_notice');
	// return;
	// throw new Exception("WooCommerce not activated, add this plugin first", 1);
}


// https://www.wpbeginner.com/wp-tutorials/how-to-add-admin-notices-in-wordpress/
function bluem_woocommerce_no_woocommerce_notice()
{
	if (is_admin()) {
		echo '<div class="notice notice-error is-dismissible">
			<p>Bluem WooCommerce is afhankelijk van WooCommerce - activeer deze plug-in ook!.</p>
			</div>';
	}
}

// echo "YO HERE";

/* ******** SETTINGS *********** */
/**
 * Settings page initialisation
 *
 * @return void
 */
function bluem_woocommerce_settings_handler()
{
	add_options_page(
		'Bluem',
		'Bluem',
		'manage_options',
		'bluem-woocommerce',
		'bluem_woocommerce_settings_page'
	);
}
add_action('admin_menu', 'bluem_woocommerce_settings_handler');

/**
 * Settings page display
 *
 * @return void
 */
function bluem_woocommerce_settings_page()
{
?>
	<style>
		.bluem-form-control {
			width: 100%;
		}

		.bluem-settings {
			column-count: 2;
			column-gap: 40px;

		}
	</style>


	<div class="bluem-settings">

		<h3>
			Uitleg over functies</h3>

		<p>
			Deze plug-in bevat de volgende onderdelen:
			<!-- deze  -->
		</p>

		<ul>
			<li>
				<strong>
					WooCommerce payment gateway voor eMandates
				</strong>
				<br>

			</li>
			<li>
				<strong>
					WooCommerce payment gateway voor ePayments (iDeal)
				</strong>
				<br>

			</li>


			<!-- <li>
	<strong>
	Interface voor iDIN (identificatie) transacties 
		</strong>
	<br>
	
	</li>		 -->

		</ul>


		<h2>Bluem instellingen</h2>

		<form action="options.php" method="post">
			<?php

			// register_setting( 'myoption-group', 'new_option_name' );
			// 	$this->form_fields[$option_key] = $ff;
			// }


			settings_fields('bluem_woocommerce_options');
			do_settings_sections('bluem_woocommerce');
			?>
			<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>" />
		</form>


	</div>
	<?php
}


function bluem_woocommerce_general_settings_section()
{
	echo '<p>Hier kan je alle belangrijke gegevens instellen rondom Bluem algemeen. Lees de readme bij de plug-in voor meer informatie.</p>';
}


function bluem_woocommerce_register_settings()
{
	register_setting('bluem_woocommerce_options', 'bluem_woocommerce_options', 'bluem_woocommerce_options_validate');

	add_settings_section('bluem_woocommerce_general_section', 'Algemene instellingen', 'bluem_woocommerce_general_settings_section', 'bluem_woocommerce');
	add_settings_section('bluem_woocommerce_mandates_section', 'Machtiging instellingen', 'bluem_woocommerce_mandates_settings_section', 'bluem_woocommerce');
	add_settings_section('bluem_woocommerce_payments_section', 'iDeal payments instellingen', 'bluem_woocommerce_payments_settings_section', 'bluem_woocommerce');
	add_settings_section('bluem_woocommerce_idin_section', 'iDIN payments instellingen', 'bluem_woocommerce_idin_settings_section', 'bluem_woocommerce');

	$general_settings = bluem_woocommerce_get_core_options();
	foreach ($general_settings as $key => $ms) {
		add_settings_field(
			$key,
			$ms['name'],
			"bluem_woocommerce_settings_render_" . $key,
			"bluem_woocommerce",
			"bluem_woocommerce_general_section"
		);
	}

	$mandates_settings = _bluem_get_mandates_options();
	if (is_array($mandates_settings) && count($mandates_settings) > 0) {

		foreach ($mandates_settings as $key => $ms) {
			add_settings_field(
				$key,
				$ms['name'],
				"bluem_woocommerce_settings_render_" . $key,
				"bluem_woocommerce",
				"bluem_woocommerce_mandates_section"
			);
		}
	}

	$payments_settings = _bluem_get_payments_options();
	if (is_array($payments_settings) && count($payments_settings) > 0) {
		foreach ($payments_settings as $key => $ms) {
			$fname = "bluem_woocommerce_settings_render_" . $key;
			add_settings_field(
				$key,
				$ms['name'],
				"bluem_woocommerce_settings_render_" . $key,
				"bluem_woocommerce",
				"bluem_woocommerce_payments_section"
			);
		}
	}

	$idin_settings = _bluem_get_idin_options();
	if (is_array($idin_settings) && count($idin_settings) > 0) {
		foreach ($idin_settings as $key => $ms) {
			$fname = "bluem_woocommerce_settings_render_" . $key;
			add_settings_field(
				$key,
				$ms['name'],
				"bluem_woocommerce_settings_render_" . $key,
				"bluem_woocommerce",
				"bluem_woocommerce_idin_section"
			);
		}
	}
}
add_action('admin_init', 'bluem_woocommerce_register_settings');


function _bluem_get_option($key)
{

	$options = bluem_woocommerce_get_core_options();

	if (array_key_exists($key, $options)) {
		return $options[$key];
	}
	return false;
}



function bluem_woocommerce_settings_render_environment()
{
	bluem_woocommerce_settings_render_input(_bluem_get_option('environment'));
}
function bluem_woocommerce_settings_render_senderID()
{
	bluem_woocommerce_settings_render_input(_bluem_get_option('senderID'));
}
function bluem_woocommerce_settings_render_brandID()
{
	bluem_woocommerce_settings_render_input(_bluem_get_option('brandID'));
}
function bluem_woocommerce_settings_render_test_accessToken()
{
	bluem_woocommerce_settings_render_input(_bluem_get_option('test_accessToken'));
}
function bluem_woocommerce_settings_render_production_accessToken()
{
	bluem_woocommerce_settings_render_input(_bluem_get_option('production_accessToken'));
}
function bluem_woocommerce_settings_render_expectedReturnStatus()
{
	bluem_woocommerce_settings_render_input(_bluem_get_option('expectedReturnStatus'));
}


function bluem_woocommerce_settings_render_input($field)
{
	if ($field === false) {
		return;
	}
	$values = get_option('bluem_woocommerce_options');
	$key = $field['key'];

	// fallback
	if (!isset($field['type'])) {
		$field['type'] = "text";
	}

	if ($field['type'] == "select") {
	?>


		<select class='form-control' id='bluem_woocommerce_settings_<?php echo $key; ?>' name='bluem_woocommerce_options[<?php echo $key; ?>]'>
			<?php
			foreach ($field['options'] as $option_value => $option_name) {
			?>
				<option value="<?php echo $option_value; ?>" <?php if (isset($values[$key]) && $values[$key] !== "" && $option_value == $values[$key]) {
																	echo "selected='selected'";
																} ?>><?php echo $option_name; ?></option>
			<?php
			}
			?>
		</select>
	<?php
	} else {
		$attrs = [];
		if ($field['type'] == "password") {
			$attrs['type'] = "password";
		} elseif ($field['type'] == "number") {
			$attrs['type'] = "number";
			if (isset($field['attrs'])) {

				$attrs = array_merge($attrs, $field['attrs']);
			}
		} else {
			$attrs['type'] = "text";
		}
	?>
		<input class='bluem-form-control' id='bluem_woocommerce_settings_<?php echo $key; ?>' name='bluem_woocommerce_options[<?php echo $key; ?>]' value='<?php echo (isset($values[$key]) ? esc_attr($values[$key]) : $field['default']); ?>' <?php foreach ($attrs as $akey => $aval) {
																																																													echo "$akey='$aval' ";
																																																												} ?> />
	<?php
	}
	?>

	<?php if (isset($field['description']) && $field['description'] !== "") {
	?>

		<br><label style='color:ddd;' for='bluem_woocommerce_settings_<?php echo $key; ?>'><?php echo $field['description']; ?></label>
	<?php
	} ?>


<?php
}


function bluem_woocommerce_get_core_options()
{
	return [
		'environment' => [
			'key' => 'environment',
			'title' => 'bluem_environment',
			'name' => 'Kies de actieve modus',
			'description' => 'Vul hier welke modus je wilt gebruiken: prod, test of acc in voor productie (live), test of acceptance omgeving.',
			'type' => 'select',
			'default' => 'test',
			'options' =>
			['prod' => "Productie (live)", 'test' => 'Test']
			// acceptance eventueel later toevoegen
		],
		'senderID' => [
			'key' => 'senderID',
			'title' => 'bluem_senderID',
			'name' => 'Bluem Sender ID',
			'description' => 'Het sender ID, uitgegeven door Bluem. Begint met een S, gevolgd door een getal.',
			'default' => ""
		],
		'brandID' => [
			'key' => 'brandID',
			'title' => 'bluem_brandID',
			'name' => 'Bluem Brand ID',
			'description' => 'Wat is je BrandID? Gegeven door Bluem',
			'default' => ''
		],
		'test_accessToken' => [
			'key' => 'test_accessToken',
			'title' => 'bluem_test_accessToken',
			'type' => 'password',
			'name' => 'Access Token voor Testen',
			'description' => 'Het access token om met Bluem te kunnen communiceren, voor de test omgeving',
			'default' => ''
		],
		'production_accessToken' => [
			'key' => 'production_accessToken',
			'title' => 'bluem_production_accessToken',
			'type' => 'password',
			'name' => 'Access Token voor Productie',
			'description' => 'Het access token om met Bluem te kunnen communiceren, voor de productie omgeving',
			'default' => ''
		],
		'expectedReturnStatus' => [
			'key' => 'expectedReturnStatus',
			'title' => 'bluem_expectedReturnStatus',
			'name' => 'Test modus verwachte return status',
			'description' => 'Welke status wil je terug krijgen voor een TEST transaction of status request? Mogelijke waarden: none, success, cancelled, expired, failure, open, pending',
			'default' => 'success',
			'type' => 'select',
			'options' => [
				'success' => 'success',
				'cancelled' => 'cancelled',
				'expired' => 'expired',
				'failure' => 'failure',
				'open' => 'open',
				'pending' => 'pending',
				'none' => 'none'
			]
		]
	];
}
