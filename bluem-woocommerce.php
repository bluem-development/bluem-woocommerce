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
require __DIR__.'/vendor/autoload.php';

// get specific gateways and helpers
require_once __DIR__.'/bluem-woocommerce-mandates.php';
require_once __DIR__.'/bluem-woocommerce-payments.php';
require_once __DIR__. '/bluem-helper.php';


// use Bluem\BluemPHP\IdentityBluemRequest;
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

