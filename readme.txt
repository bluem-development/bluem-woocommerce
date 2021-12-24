=== Bluem ePayments, iDIN and eMandates integration for shortcodes and WooCommerce checkout ===
Contributors: bluempaymentservices
Donate link: https://daanrijpkema.github.io
Tags: Bluem,Payments,iDIN,iDEAL,Incassomachtigen,woocommerce, bluem, payment gateway, payments, ideal, paypal, mandates, identity, idin, age verification, iban-name check 
Requires at least: 5.0
Tested up to: 5.8.0
Requires PHP: 7.0
Stable tag: 1.3.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Connect your website to Bluem’s ePayments, eMandates, iDIN Identity services, Age 18+ verification and IBAN-Name check.

== Description ==

The Bluem WordPress and WooCommerce integration allows you to connect your website to Bluem’s ePayments (betalen en betalingen ontvangen), eMandates (digitaal incasso machtigen), iDIN Identity services & age 18+ verification (identificeren, 18+ check) and IBAN-Name check (IBAN naam check).
Concretely, the plug-in delivers:

- a payment gateway to accept eMandate transactions within a WooCommerce-activated website
- a payment gateway to accept ePayment transactions within a WooCommerce-activated website
- a shortcode, namely [bluem_machtigingsformulier] that allows (guest) users to perform an eMandate transaction request from any post or page (no WooCommerce plug-in necessary). The response is stored within the user profile metadata
- a shortcode [bluem_identificatieformulier] that allows (guest) users to perform an iDIN eID request and store this information within the user profile metadata for further usage in third-party plugins or functions within your theme.
- an extensive settings page that allows for enabling/disabling and configuration of specific services


== Installation ==

Installing this plugin can be done by using the following steps:

1. Download the plugin
1. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Activate the preferred modules from the 'Settings > Bluem' page.

== Usage ==
**Be sure to enable the desired modules from the Settings > Bluem page!**

Ensure that the required information is filled in within the settings page. The Access Token for testing, the SenderID and the brandID have to be set properly. These details are given by your Bluem account manager.

You can change the environment from `Testing` to `Live (production)` as soon as you have a production token. Please ensure you have tested your configuration extensively.

=== Mandates ===
eMandates is utilized as a Payment Gateway within WooCommerce. Enable it as a module in Settings > bluem and as the gateway in WooCommerce > Settings > Payments

From that moment onwards you can utilize the gateway during checkout.

==== Mandates shortcode ====
Activated if Mandates is activated as a module from the Settings > Bluem page.

Using this shortcode, it is possible to allow users to create a mandate separate from a WooCommerce context or usage. This can be used on the account information page to encourage people to already arrange this before proceeding on your site.
Shortcode: `[bluem_machtigingsformulier]`

It is possible to programmatically block display and functionality on your site based on the presence of a mandate for a user. Please contact us if you are interested in developing this in your site.

=== Payments ===
ePayments is utilized as a Payment Gateway within WooCommerce. Enable it as a module in Settings > bluem and as the gateway in WooCommerce > Settings > Payments

From that moment onwards you can utilize the gateway during checkout.

=== Identity ===

Available as automatic blocking for WooCommerce's checkout procedure and as shortcode (see next section).

If the user's identity and/or age is not yet verified, a prompt will be shown as soon as the user attempts to check out. In this prompt, a link to directly initiate the identification procedure will be present. As soon as the identification procedure has been completed successfully, the prompt will disappear and the checkout procedure can be followed. Any items in the cart will still be intact.

In the settings interface you can enable a check for Identity verification and additional Age verification based on a specific given age. This can only be enabled or disabled shop-wide at this moment. Do note: It is possible to disable the automatic check and implement your own blocking check based on specific conditions. Please contact us for instructions on how to do this.


==== Identity Shortcode ====
Activated if iDIN  is activated as a module from the Settings > Bluem page.

Using this shortcode, it is possible to allow users to identify separate from WooCommerce and a shop context. This can be used on the account information page to encourage people to verify their identities.
Shortcode: `[bluem_identificatieformulier]`

It is possible to programmatically block display and functionality on your site based on the verification status. Please contact us if you are interested in developing this in your site.

=== IBAN name check ===
Coming soon

== Frequently asked questions == 
Coming soon

== Upgrade Notice ==
Please deactivate and delete any previous versions of this plug-in prior to 1.1 to prevent naming conflicts to occur. Your settings will still be saved.


== Changelog ==
- 1.3.1: Added optional IP checking for country-filtering; improved stability
- 1.3.0: Stability fixes, guest identification checkout improved; added import and export function of configuration
- 1.2.19: Improved php library; added improved error handling and email reporting and prompts
- 1.2.15: Added customization options to the iDIN Dialogues; bug fixing
- 1.2.9: Fixed iDIN redirect problem; improved idin Message formatting; Added user profile Bluem transactions and added viewing of payload within transaction requests; stability fixes; layout fixes
- 1.2.8: Improved requests view page and general user interface
- 1.2.7: Added request logging in database with a clean new UI page; added redirect configuration for payments; moved iDIN check to the checkout page
- 1.2.6: Improved guest checkout identification blocking; improved cart url redirection in IDIN; Tested up to WordPress v5.7 and updated metadata to reflect this. Improved metadata management in user profile; woocommerce 5.1 compatibility
- 1.2.* Bugfixes
- 1.2: Updated layout references, added optional `bluem_checkout_check_idin_validated_filter`
- 1.1: Update and rename based on review for WordPress plugin approval (16th of Februari 2021)
- 1.0: Initial public release as plug-in: (3rd of February 2021): After several months of BETA, we are now ready to deliver this plug-in to the public through the WordPress plug-in archive.

== Screenshots ==

1. When configured, this is how the checkout procedure will look with either eMandates or Payments activated
2. This is the general settings page, showing the versatility of the plug-in.
3. This is the payment gateway settings page: the two possible gateways are automatically added and configurable from within WooCommerce.

== Support ==
If you have any questions, please reach out to me via email at d.rijpkema@bluem.nl. I aim to respond within five working days.