<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'bluem_add_gateway_class_mandates', 11);
function bluem_add_gateway_class_mandates($gateways)
{
    $gateways[] = Bluem_Mandates_Payment_Gateway::class;

    return $gateways;
}


function bluem_woocommerce_get_mandates_option($key)
{
    if (function_exists('bluem_woocommerce_get_mandates_options')) {
        $options = bluem_woocommerce_get_mandates_options();
        if (array_key_exists($key, $options)) {
            return $options[$key];
        }
    }

    return false;
}

function bluem_woocommerce_get_mandates_options()
{
    return [
        'brandID' => [
            'key' => 'brandID',
            'title' => 'bluem_brandID',
            'name' => 'Bluem Brand ID',
            'description' => 'What is your Bluem eMandates BrandID? You received this from Bluem.',
            'default' => '',
        ],
        'merchantID' => [
            'key' => 'merchantID',
            'title' => 'bluem_merchantID',
            'name' => 'Creditor merchantID (required for mandates in Production)',
            'description' => 'The merchantID, found on the contract you have with the bank for receiving direct debit mandates. <strong>This is essential: without this value, a customer will not be able to complete a mandate in production</strong>.',
            'default' => '',
        ],
        'merchantSubId' => [
            'key' => 'merchantSubId',
            'title' => 'bluem_merchantSubId',
            'name' => 'Bluem Merchant Sub ID',
            'default' => '0',
            'description' => 'You probably do not need to change this.',
            'type' => 'select',
            'options' => ['0' => '0'],
        ],

        'thanksPage' => [
            'key' => 'thanksPage',
            'title' => 'bluem_thanksPage',
            'name' => 'Where is the user ultimately redirected?',
            'type' => 'select',
            'options' => [
                'order_page' => 'Detail page of the newly placed order (default)',
            ],
        ],
        'eMandateReason' => [
            'key' => 'eMandateReason',
            'title' => 'bluem_eMandateReason',
            'name' => 'Mandate reason',
            'description' => 'A concise description of the direct debit shown during issuance.',
            'default' => 'Direct debit mandate',
        ],
        'localInstrumentCode' => [
            'key' => 'localInstrumentCode',
            'title' => 'bluem_localInstrumentCode',
            'name' => 'Direct debit mandate issuance type',
            'description' => 'Choose the direct debit mandate type. Contact Bluem if you have questions about this.',
            'type' => 'select',
            'default' => 'CORE',
            'options' => [
                'CORE' => 'CORE mandate',
                'B2B' => 'B2B mandate (business)',
            ],
        ],

        // RequestType = Issuing (altijd)
        'requestType' => [
            'key' => 'requestType',
            'title' => 'bluem_requestType',
            'name' => 'Bluem Request Type',
            'description' => '',
            'type' => 'select',
            'default' => 'Issuing',
            'options' => ['Issuing' => 'Issuing (default)'],
        ],

        'sequenceType' => [
            'key' => 'sequenceType',
            'title' => 'bluem_sequenceType',
            'name' => 'Direct debit sequence type',
            'description' => '',
            'type' => 'select',
            'default' => 'RCUR',
            'options' => [
                'RCUR' => 'Recurring mandate',
                'OOFF' => 'One-time mandate',
            ],
        ],

        'mandatesUseDebtorWallet' => [
            'key' => 'mandatesUseDebtorWallet',
            'title' => 'bluem_mandatesUseDebtorWallet',
            'name' => 'Select bank method',
            'description' => "Do you want a bank to be selected on this website during checkout instead of in the Bluem Portal? If you select 'Use own checkout', a field will be added to the WooCommerce checkout page where you can select one of the available banks.",
            'type' => 'select',
            'default' => '0',
            'options' => [
                '0' => 'Use Bluem Portal (default)',
                '1' => 'Use own checkout',
            ],
        ],

        'successMessage' => [
            'key' => 'successMessage',
            'title' => 'bluem_successMessage',
            'name' => 'Message for successful mandate via shortcode form',
            'description' => 'A concise description is sufficient.',
            'default' => 'Your mandate has been received successfully. Thank you.',
        ],
        'errorMessage' => [
            'key' => 'errorMessage',
            'title' => 'bluem_errorMessage',
            'name' => 'Message for failed mandate via shortcode form',
            'description' => 'A concise description is sufficient.',
            'default' => 'An error occurred. The direct debit mandate has been canceled.',
        ],

        'purchaseIDPrefix' => [
            'key' => 'purchaseIDPrefix',
            'title' => 'bluem_purchaseIDPrefix',
            'name' => 'Automatic prefix for customer reference',
            'description' => 'Which short text should be shown before the debtorReference for a transaction in the Bluem direct debit mandate portal. This can be useful for easily identifying Bluem transactions.',
            'type' => 'text',
            'default' => '',
        ],
        'debtorReferenceFieldName' => [
            'key' => 'debtorReferenceFieldName',
            'title' => 'bluem_debtorReferenceFieldName',
            'name' => 'Customer reference label for shortcode input form',
            'description' => "If you use the Mandates shortcode: which label should be shown for the input field in the form? This could be 'full name' or 'customer number', for example. <strong>Leave this field empty to show only a button</strong>.",
            'type' => 'text',
            'default' => '',
        ],
        'thanksPageURL' => [
            'key' => 'thanksPageURL',
            'title' => 'bluem_thanksPageURL',
            'name' => 'Result page slug',
            'description' => 'If you use the Mandates shortcode: on which page is the shortcode placed? This is a slug, so if you enter <code>thanks</code>, the full URL becomes: ' . site_url('thanks') . '. We include the query strings <code>result</code> and, where applicable, <code>reason</code> so you can capture the status.',
            'type' => 'text',
            'default' => '',
        ],
        'instantMandatesResponseURI' => [
            'key' => 'instantMandatesResponseURI',
            'title' => 'bluem_instantMandatesResponseURI',
            'name' => 'URI for InstantMandates',
            'description' => 'If you use InstantMandates: the <code>response</code> URI after a request. This can be an external URL or a deep link. We include the query strings <code>result</code> and, where applicable, <code>reason</code> so you can capture the status.',
            'type' => 'text',
            'default' => '',
        ],
        'mandate_id_counter' => [
            'key' => 'mandate_id_counter',
            'title' => 'bluem_mandate_id_counter',
            'name' => 'Starting number for mandate IDs',
            'description' => 'At which number do you want to number mandates at this moment? This number is then automatically incremented.',
            'type' => 'text',
            'default' => '1',
        ],
        'maxAmountEnabled' => [
            'key' => 'maxAmountEnabled',
            'title' => 'bluem_maxAmountEnabled',
            'name' => 'Check maximum order value for direct debit mandates',
            'description' => "Do you want business direct debit mandates to be checked against the maximum direct debit amount when a limited-amount mandate has been issued? Set this to 'check'. An error message will then be shown if a customer places an order with an allowed amount lower than the order amount (multiplied by the next setting, the factor). If the mandate is unlimited or otherwise higher than the order amount, the mandate is accepted.",
            'type' => 'select',
            'default' => '1',
            'options' => [
                '1' => 'Check MaxAmount',
                '0' => 'Do not check MaxAmount',
            ],
        ],

        // For B2B, we receive whether the user has issued a maximum mandate amount.
        // This mandate amount is compared with the order value. The order value plus
        // the percentage below must be lower than the maximum mandate amount.
        // Enter the percentage here.
        'maxAmountFactor' => [
            'key' => 'maxAmountFactor',
            'title' => 'bluem_maxAmountFactor',
            'name' => 'Which order factor may the maximum order amount be?',
            'description' => 'If a max amount is sent along, what is the maximum allowed amount? Based on the order size.',
            'type' => 'number',
            'attrs' => [
                'step' => '0.01',
                'min' => '0.00',
                'max' => '999.00',
                'placeholder' => '1.00',
            ],
            'default' => '1.00',
        ],
    ];
}


/*
 * The gateway class itself, please note that it is inside plugins_loaded action hook
 */
if (bluem_woocommerce_is_woocommerce_active()) {
    add_action('plugins_loaded', 'bluem_init_mandate_gateway_class');
}

function bluem_init_mandate_gateway_class()
{
    include_once __DIR__ . '/gateways/Bluem_Mandates_Payment_Gateway.php';
}

// Integrations in third party systems:
add_action('bluem_woocommerce_valid_mandate_callback', 'bluem_woocommerce_valid_mandate_callback_function', 10, 2);
function bluem_woocommerce_valid_mandate_callback_function($user_id, $response)
{
    // Implement this method in third-party extensions of this system
}

add_action('show_user_profile', 'bluem_woocommerce_mandates_show_extra_profile_fields', 2);
add_action('edit_user_profile', 'bluem_woocommerce_mandates_show_extra_profile_fields');

function bluem_woocommerce_mandates_show_extra_profile_fields($user)
{
    $bluem_requests = bluem_db_get_requests_by_user_id_and_type($user->ID, 'mandates'); ?>
    <table class="form-table">
        <a id="user_mandates"></a>

        <?php if (isset($bluem_requests) && count($bluem_requests) > 0) { ?>
            <tr>
                <th>
                    Digitale Incassomachtigingen
                </th>
                <td>
                    <?php
                    bluem_render_requests_list($bluem_requests);
            ?>
                </td>
            </tr>
            <?php
        } else {
            // legacy code
            ?>
            <tr>
                <th><label for="bluem_latest_mandate_id">Most recent MandateID</label></th>
                <td>
                    <input type="text" name="bluem_latest_mandate_id" id="bluem_latest_mandate_id"
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_latest_mandate_id', true)); ?>"
                           class="regular-text"/><br/>
                    <span class="description">The most recent mandate ID is placed here and used during the next checkout.</span>
                </td>
            </tr>
            <tr>
                <th><label for="bluem_latest_mandate_entrance_code">Most recent EntranceCode</label></th>

                <td>
                    <input type="text" name="bluem_latest_mandate_entrance_code"
                           id="bluem_latest_mandate_entrance_code"
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_latest_mandate_entrance_code', true)); ?>"
                           class="regular-text"/><br/>
                    <span class="description">The most recent entrance_code is placed here and used during the next checkout.</span>
                </td>
            </tr>
            <tr>
                <th><label for="bluem_latest_mandate_amount">Amount of last mandate</label></th>
                <td>
                    <input type="text" name="bluem_latest_mandate_amount" id="bluem_latest_mandate_amount"
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_latest_mandate_amount', true)); ?>"
                           class="regular-text"/><br/>
                    <span class="description">This is the amount of the last mandate</span>
                </td>
            </tr>

            <?php
        }
    ?>
        <tr>
            <th><label for="bluem_mandates_validated">Mandate via shortcode / InstantMandates valid?</label></th>
            <td>
                <?php
            $curValidatedVal = (int) esc_attr(
                get_user_meta(
                    $user->ID,
                    'bluem_mandates_validated',
                    true
                )
            );
    ?>
                <select name="bluem_mandates_validated" id="bluem_mandates_validated">
                    <option value="1"
                        <?php
            if ($curValidatedVal == 1) {
                echo 'selected';
            }
    ?>
                    >
                        Yes
                    </option>
                    <option value="0"
                        <?php
    if ($curValidatedVal == 0) {
        echo 'selected';
    }
    ?>
                    >
                        No
                    </option>
                </select><br/>
                <span class="description">Has a mandate via shortcode or InstantMandates been received? If applicable, you can override this here</span>
            </td>
        </tr>
    </table>
    <?php
}


add_action(
    'personal_options_update',
    'bluem_woocommerce_mandates_save_extra_profile_fields'
);
add_action(
    'edit_user_profile_update',
    'bluem_woocommerce_mandates_save_extra_profile_fields'
);

function bluem_woocommerce_mandates_save_extra_profile_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['bluem_latest_mandate_id'])) {
        update_user_meta(
            $user_id,
            'bluem_latest_mandate_id',
            esc_attr(sanitize_text_field(wp_unslash($_POST['bluem_latest_mandate_id'])))
        );
    }

    if (isset($_POST['bluem_latest_mandate_entrance_code'])) {
        update_user_meta(
            $user_id,
            'bluem_latest_mandate_entrance_code',
            esc_attr(sanitize_text_field(wp_unslash($_POST['bluem_latest_mandate_entrance_code'])))
        );
    }
    if (isset($_POST['bluem_latest_mandate_amount'])) {
        update_user_meta(
            $user_id,
            'bluem_latest_mandate_amount',
            esc_attr(sanitize_text_field(wp_unslash($_POST['bluem_latest_mandate_amount'])))
        );
    }
    if (isset($_POST['bluem_mandates_validated'])) {
        update_user_meta(
            $user_id,
            'bluem_mandates_validated',
            esc_attr(sanitize_text_field(wp_unslash($_POST['bluem_mandates_validated'])))
        );
    }
}

function bluem_woocommerce_mandates_settings_section()
{
    $mandate_id_counter = get_option('bluem_woocommerce_mandate_id_counter');

    // The below code is useful when you want the mandate_id to start counting at a fixed minimum.
    // This is what had to be implemented for H2OPro; one of the first clients.
    // @todo: convert to action so it can be overriden by third-party developers such as H2OPro.
    if (home_url() == 'https://www.h2opro.nl' && (int) ($mandate_id_counter . '') < 111100) {
        $mandate_id_counter += 111000;
        update_option('bluem_woocommerce_mandate_id_counter', $mandate_id_counter);
    }
    echo '<p><a id="tab_mandates"></a> Here you can configure all important details for Digital Direct Debit Mandates.</p>';
}

// ********************** Mandate specific

function bluem_woocommerce_settings_render_brandID()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('brandID'));
}

function bluem_woocommerce_settings_render_merchantID()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('merchantID'));
}

function bluem_woocommerce_settings_render_merchantSubId()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('merchantSubId'));
}

function bluem_woocommerce_settings_render_thanksPage()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('thanksPage'));
}

function bluem_woocommerce_settings_render_eMandateReason()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('eMandateReason'));
}

function bluem_woocommerce_settings_render_localInstrumentCode()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('localInstrumentCode'));
}

function bluem_woocommerce_settings_render_requestType()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('requestType'));
}

function bluem_woocommerce_settings_render_sequenceType()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('sequenceType'));
}

function bluem_woocommerce_settings_render_successMessage()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('successMessage'));
}

function bluem_woocommerce_settings_render_errorMessage()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('errorMessage'));
}

function bluem_woocommerce_settings_render_purchaseIDPrefix()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('purchaseIDPrefix'));
}

function bluem_woocommerce_settings_render_debtorReferenceFieldName()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('debtorReferenceFieldName'));
}

function bluem_woocommerce_settings_render_thanksPageURL()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('thanksPageURL'));
}

function bluem_woocommerce_settings_render_instantMandatesResponseURI()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('instantMandatesResponseURI'));
}

function bluem_woocommerce_settings_render_mandate_id_counter()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('mandate_id_counter'));
}

function bluem_woocommerce_settings_render_maxAmountEnabled()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('maxAmountEnabled'));
}

function bluem_woocommerce_settings_render_maxAmountFactor()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('maxAmountFactor'));
}

function bluem_woocommerce_settings_render_useMandatesDebtorWallet()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('useMandatesDebtorWallet'));
}

function bluem_woocommerce_settings_render_mandatesUseDebtorWallet()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('mandatesUseDebtorWallet'));
}

add_filter('bluem_woocommerce_enhance_mandate_request', 'bluem_woocommerce_enhance_mandate_request_function', 10, 1);

/**
 * allow third parties to add additional data to the request object through this additional action
 *
 * @param [type] $request
 *
 * @return void
 */
function bluem_woocommerce_enhance_mandate_request_function($request)
{
    // do something with the Bluem Mandate request, use this in third-party extensions of this system
    return $request;
}
