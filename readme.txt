=== Bluem ePayments, iDIN, eMandates services and integration for WooCommerce ===
Contributors: bluempaymentservices
Tags: Bluem,Payments,iDIN,iDEAL,eMandates
Requires at least: 5.0
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 1.3.22
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Connect your website to Bluem’s ePayments, eMandates, iDIN Identity services, Age 18+ verification and IBAN-Name check.

== Description ==

The Bluem WordPress and WooCommerce integration allows you to connect your website to Bluem’s ePayments (betalen en betalingen ontvangen), eMandates (digitaal incasso machtigen), iDIN Identity services & age 18+ verification (identificeren, 18+ check) and IBAN-Name check (IBAN naam check).
Concretely, the plug-in delivers:

- a payment gateway to accept eMandate transactions within a WooCommerce-activated website
- a payment gateway to accept ePayment transactions within a WooCommerce-activated website
- a shortcode, namely [bluem_machtigingsformulier] that allows (guest) users to perform an eMandate transaction request from any post or page (no WooCommerce plug-in necessary). The response is stored within the user profile metadata
- a shortcode [bluem_identificatieformulier] that allows (guest) users to perform an iDIN eID request and store this information within the user profile metadata for further usage in third-party plugins or functions within your theme.
- an extensive settings page that allows for enabling/disabling and configuration of specific services

To use these features, you need to register as a Bluem customer. For more information, please visit https://www.bluem.nl

== Installation ==

Installing this plugin can be done by using the following steps:

1. Download the plugin
1. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Activate the preferred modules from the 'Bluem > Settings' page.

== Usage ==
**Be sure to enable the desired modules from the Bluem > Settings page!**

Before you can start, you need to register as a Bluem customer. For more information, please visit https://www.bluem.nl

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

== Changelog ==
- 1.3.22: Further optimizations and security improvements
- 1.3.21: Further optimizations
- 1.3.20: General stability and code improvements to adhere to the standards
- 1.3.19: General stability and code improvements to adhere to the standards
- 1.3.18.0: Improved observability, removed unnecessary dependencies
- 1.3.17.15: Webhook certificate update (yearly refresh)
- 1.3.17.14: Stability fixes.
- 1.3.17.13: Stability fixes.
- 1.3.17.12: Stability fixes and improvements.
- 1.3.17.11: Stability fixes.
- 1.3.17.10: Updated eMandates BIC list in PHP library and webhook improvements.
- 1.3.17.9: Updated BIC list in PHP library. Some improvements.
- 1.3.17.8: Updated BIC list PHP library. Some improvements.
- 1.3.17.7: Updated PHP library for certificates.
- 1.3.17.6: Extra functionalities and improvements.
- 1.3.17.5: Added attribute to WooCommerce product to enable age verification per product.
- 1.3.17.4: Stability fixes.
- 1.3.17.3: Update Bluem PHP library.
- 1.3.17.2: Gravity Forms integration improvements.
- 1.3.17.1: Added extra fields for Gravity Forms eMandates integration.
- 1.3.17: Gravity Forms eMandates integration.
- 1.3.16.2: Stability fixes and improvements.
- 1.3.16.1: Stability fixes and improvements.
- 1.3.16: Added Sofort and Carte Bancaire payment methods.
- 1.3.15: Stability fixes and improvements.
- 1.3.14: Bugfix IPAPI.
- 1.3.13: Dependency PHP problem fixed.
- 1.3.12: Stability fixes.
- 1.3.11: Stability fixes.
- 1.3.10: Contact Form 7 eMandates integration.
- 1.3.9: Stability fixes and improvements.
- 1.3.8: Stability fixes and improvements.
- 1.3.7: Stability fixes. Instant eMandates for specific use-cases.
- 1.3.6: Improved redirects after transaction, eMandates for guests and requests view page in admin
- 1.3.5: Improved stability of core services, improved speed and layout
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

Note: Please deactivate and delete any previous versions of this plug-in prior to 1.1 to prevent naming conflicts to occur. Your settings will remain stored.
== Screenshots ==

1. When configured, this is how the checkout procedure will look with either eMandates or Payments activated
2. This is the general settings page, showing the versatility of the plug-in.
3. This is the payment gateway settings page: the two possible gateways are automatically added and configurable from within WooCommerce.

== Support ==
If you have any questions, please reach out via email at pluginsupport@bluem.nl. We aim to respond within five working days.

Find out more information in the [User Manual](https://codexology.notion.site/Bluem-voor-WordPress-en-WooCommerce-Handleiding-9e2df5c5254a4b8f9cbd272fae641f5e) or on [bluem.nl](https://bluem.nl)
