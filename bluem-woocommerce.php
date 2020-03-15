<?php
/**
 * Plugin Name: bluem-woocommerce
 * Version: 1.0.0
 * Plugin URI: http://www.hughlashbrooke.com/
 * Description: This is your starter template for your next WordPress plugin.
 * Author: Hugh Lashbrooke
 * Author URI: http://www.hughlashbrooke.com/
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

// Load plugin class files.
require_once 'includes/class-bluem-woocommerce.php';
require_once 'includes/class-bluem-woocommerce-settings.php';

// Load plugin libraries.
require_once 'includes/lib/class-bluem-woocommerce-admin-api.php';
require_once 'includes/lib/class-bluem-woocommerce-post-type.php';
require_once 'includes/lib/class-bluem-woocommerce-taxonomy.php';

/**
 * Returns the main instance of bluem-woocommerce to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object bluem-woocommerce
 */
function bluem_woocommerce() {
	$instance = bluem-woocommerce::instance( __FILE__, '1.0.0' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = bluem-woocommerce_Settings::instance( $instance );
	}

	return $instance;
}


/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    
	bluem_woocommerce();
} else {
	throw new Exception("WooCommerce not activated", 1);
	
}
