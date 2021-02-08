=== bluem-woocommerce ===
Contributors: daanrijpkema
Donate link: https://daanrijpkema.github.io
Tags: wordpress, plugin, woocommerce, bluem,payment,services,idin,mandates,ideal
Requires at least: 5.0
Tested up to: 5.6.1
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This is the official Bluem plug-in that offers an integration of its services for WordPress and WooCommerce 

== Description ==

BlueM WordPress and WooCommerce integration allows you to connect your website to Bluem's eMandate, ePayments and iDIN Identity services.
Concretely, the plug-in delivers:

- a payment gateway to accept eMandate transactions within a WooCommerce-activated website
- a payment gateway to accept ePayment transactions within a WooCommerce-activated website
- a shortcode, namely `[bluem_machtigingsformulier]` that allows (guest) users to perform an eMandate transaction request from any post or page (no WooCommerce plug-in necessary). The response is stored within the user profile metadata
- a shortcode `[bluem_identificatieformulier]` that allows (guest) users to perform an iDIN Identity request and store this information within the user profile metadata for further usage in third-party plugins or functions within your theme.
- an extensive settings page that allows for enabling/disabling and configuration of specific services

== Installation ==

Installing "bluem-woocommerce" can be done by using the following steps:

1. Download the plugin
1. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Activate the preferred modules from the 'Settings > Bluem' page.

== Usage ==
**Be sure to enable the desired modules from the Settings > Bluem page!**

Ensure that the required information is filled in within the settings page. The Access Token for testing, the SenderID and the brandID have to be set properly. These details are given by your Bluem account manager.

You can change the environment from Testing to Live production as soon as you have a production token and you have tested your configuration extensively.

=== Mandates ===
Connected to woocommerce as a payment gateway. Enable it as a module in Settings > bluem and as the gateway in WooCommerce > Settings > Payments

From that moment onwards you can utilize the gateway during checkout.

==== Mandates shortcode ====
Activated if Mandates is activated as a module from the Settings > Bluem page.
Shortcode: `[bluem_machtigingsformulier]`

=== Payments ===
Connected to woocommerce as a payment gateway. Enable it as a module in Settings > bluem and as the gateway in WooCommerce > Settings > Payments

From that moment onwards you can utilize the gateway during checkout.

=== Identity ===
Currently available as shortcode: 

==== Identity Shortcode ====
Activated if iDIN  is activated as a module from the Settings > Bluem page.
Shortcode: `[bluem_identificatieformulier]`

== Frequently asked questions == 
Coming soon

== Releases ==

=== 1.0: Initial public release as plug-in ===
3rd of February 2021: After several months of BETA, we are now ready to deliver this plug-in to the public through the WordPress plug-in archive.


== Support ==
If you have any questions, please reach out to me via email at d.rijpkema@bluem.nl. I aim to respond to requests within 5 working days.