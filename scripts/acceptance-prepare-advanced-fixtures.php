<?php

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

$options = get_option('bluem_woocommerce_options', []);
$options = array_merge($options, [
    'environment' => 'test',
    'senderID' => 'S0001',
    'test_accessToken' => 'ci-acceptance-token',
    'production_accessToken' => 'ci-acceptance-production-token',
    'expectedReturnStatus' => 'success',
    'suppress_woo' => '0',
    'payments_enabled' => '1',
    'mandates_enabled' => '1',
    'idin_enabled' => '1',
    'brandID' => 'AcceptanceMandateBrand',
    'merchantID' => 'AcceptanceMerchant',
    'merchantSubId' => '0',
    'eMandateReason' => 'Bluem acceptance mandate',
    'localInstrumentCode' => 'CORE',
    'requestType' => 'Issuing',
    'sequenceType' => 'RCUR',
    'maxAmountEnabled' => '0',
    'maxAmountFactor' => '1',
    'IDINBrandID' => 'AcceptanceIdentityBrand',
    'IDINPageURL' => 'my-account',
    'IDINShortcodeOnlyAfterLogin' => '0',
    'IDINDescription' => 'Identity acceptance {gebruikersnaam}',
    'idin_request_name' => '1',
    'idin_request_address' => '1',
    'idin_request_birthdate' => '1',
    'idin_request_gender' => '0',
    'idin_request_telephone' => '1',
    'idin_request_email' => '1',
    'paymentsIDEALBrandID' => 'Payment',
]);

update_option('bluem_woocommerce_options', $options, false);

update_option('woocommerce_bluem_mandates_settings', [
    'enabled' => 'yes',
    'title' => 'Bluem eMandate Acceptance',
    'description' => 'Isolated acceptance mandate method',
], false);

WP_CLI::success('Mandate and iDIN acceptance modules are configured with isolated fixture values.');
