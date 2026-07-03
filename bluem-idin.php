<?php

if (!defined('ABSPATH')) {
    exit;
}

use Bluem\BluemPHP\Bluem;
use Bluem\BluemPHP\Exceptions\InvalidBluemConfigurationException;
use Bluem\BluemPHP\Helpers\BluemIdentityCategoryList;

/**
 * Check if WooCommerce is activated
 */
if (!function_exists('bluem_is_woocommerce_activated')) {
    function bluem_is_woocommerce_activated(): bool
    {
        $active_plugins = function_exists('get_option') ? get_option('active_plugins') : [];

        return in_array('woocommerce/woocommerce.php', $active_plugins, true);
    }
}

function bluem_woocommerce_get_idin_option($key)
{
    $options = bluem_woocommerce_get_idin_options();
    if (array_key_exists($key, $options)) {
        return $options[$key];
    }

    return false;
}

function bluem_woocommerce_get_idin_options(): array
{
    $idinDescriptionTags = (
        function_exists('bluem_get_IDINDescription_tags')
        ? bluem_get_IDINDescription_tags() : []
    );
    $idinDescriptionReplaces = (
        function_exists('bluem_get_IDINDescription_replaces')
        ? bluem_get_IDINDescription_replaces() : []
    );
    $idinDescriptionTable = '<table><thead><tr><th>Input field</th><th>Example value</th></tr></thead><tbody>';
    foreach ($idinDescriptionTags as $ti => $tag) {
        if (!isset($idinDescriptionReplaces[$ti])) {
            continue;
        }
        $idinDescriptionTable .= "<tr><td><code>$tag</code></td><td>" . $idinDescriptionReplaces[$ti] . '</td></tr>';
    }

    $idinDescriptionTable .= '</tbody></table>';
    $options = get_option('bluem_woocommerce_options');

    if ($options !== false
        && isset($options['IDINDescription'])
    ) {
        $idinDescriptionCurrentValue = bluem_parse_IDINDescription(
            $options['IDINDescription']
        );
    } else {
        $idinDescriptionCurrentValue = bluem_parse_IDINDescription(
            'Identification {gebruikersnaam}'
        );
    }

    return [
        'IDINBrandID' => [
            'key' => 'IDINBrandID',
            'title' => 'bluem_IDINBrandID',
            'name' => 'IDIN BrandId',
            'description' => '',
            'default' => '',
        ],
        'idin_scenario_active' => [
            'key' => 'idin_scenario_active',
            'title' => 'bluem_idin_scenario_active',
            'name' => esc_html__('IDIN Scenario', 'bluem'),
            'description' => esc_html__('Do you want to perform an age check or full address check during checkout?', 'bluem'),
            'type' => 'select',
            'default' => '0',
            'options' => [
                '0' => esc_html__('Do not perform an identity check during checkout', 'bluem'),
                '1' => esc_html__('Check the minimum age using an AgeCheckRequest', 'bluem'),
                '2' => esc_html__('Perform a full identity check and save it, but do NOT block checkout if the minimum age has not been reached', 'bluem'),
                '3' => esc_html__('Perform a full identity check, save it AND block checkout if the minimum age has not been reached', 'bluem'),

            ],
        ],
        'idin_woocommerce_age_verification' => [
            'key' => 'idin_woocommerce_age_verification',
            'title' => 'bluem_idin_woocommerce_age_verification',
            'name' => esc_html__('Age verification per product', 'bluem'),
            'description' => esc_html__('Do you want to enable age verification per product? (WooCommerce required)', 'bluem'),
            'type' => 'select',
            'default' => '0',
            'options' => [
                '0' => esc_html__('Do NOT perform age checks per product', 'bluem'),
                '1' => esc_html__('Perform age checks per product', 'bluem'),
            ],
        ],
        'idin_check_age_minimum_age' => [
            'key' => 'idin_check_age_minimum_age',
            'title' => 'bluem_idin_check_age_minimum_age',
            'name' => esc_html__('Minimum age', 'bluem'),
            'description' => esc_html__('What is the minimum age, in years? If the plugin checks age, this value is used to perform the check.', 'bluem'),
            'type' => 'number',
            'default' => '18',
        ],
        'idin_request_name' => [
            'key' => 'idin_request_name',
            'title' => 'bluem_idin_request_name',
            'name' => esc_html__('Request name?', 'bluem'),
            'description' => esc_html__(
                'If you perform a full identity check,
                do you want to request the name?',
                'bluem'
            ),
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_request_address' => [
            'key' => 'idin_request_address',
            'title' => 'bluem_idin_request_address',
            'name' => esc_html__('Request address?', 'bluem'),
            'description' => esc_html__(
                'If you perform a full identity check,
                do you want to request the residential address?',
                'bluem'
            ),
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_request_birthdate' => [
            'key' => 'idin_request_birthdate',
            'title' => 'bluem_idin_request_birthdate',
            'name' => esc_html__('Request date of birth?', 'bluem'),
            'description' => esc_html__(
                'If you perform a full identity check,
                do you want to request the date of birth? This value is ALWAYS requested
                if you also check the minimum age',
                'bluem'
            ),
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_request_gender' => [
            'key' => 'idin_request_gender',
            'title' => 'bluem_idin_request_gender',
            'name' => esc_html__('Request gender?', 'bluem'),
            'description' => esc_html__(
                'If you perform a full identity check,
                do you want to request the gender?',
                'bluem'
            ),
            'type' => 'bool',
            'default' => '0',
        ],
        'idin_request_telephone' => [
            'key' => 'idin_request_telephone',
            'title' => 'bluem_idin_request_telephone',
            'name' => esc_html__('Request phone number?', 'bluem'),
            'description' => esc_html__(
                'If you perform a full identity check,
                do you want to request the phone number?',
                'bluem'
            ),
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_request_email' => [
            'key' => 'idin_request_email',
            'title' => 'bluem_idin_request_email',
            'name' => esc_html__('Request email address?', 'bluem'),
            'description' => esc_html__(
                'If you perform a full identity check,
                do you want to request the email address?',
                'bluem'
            ),
            'type' => 'bool',
            'default' => '1',
        ],
        'IDINSuccessMessage' => [
            'key' => 'IDINSuccessMessage',
            'title' => 'bluem_IDINSuccessMessage',
            'name' => esc_html__('Message for successful identification via shortcode', 'bluem'),
            'description' => esc_html__('A concise description is sufficient.', 'bluem'),
            'default' => esc_html__('Your identification has been received successfully. Thank you.', 'bluem'),
        ],
        'IDINErrorMessage' => [
            'key' => 'IDINErrorMessage',
            'title' => 'bluem_IDINErrorMessage',
            'name' => esc_html__('Message for failed identification via shortcode', 'bluem'),
            'description' => esc_html__('A concise description is sufficient.', 'bluem'),
            'default' => esc_html__('An error occurred. The identification was canceled.', 'bluem'),
        ],

        'IDINPageURL' => [
            'key' => 'IDINPageURL',
            'title' => 'bluem_IDINPageURL',
            'name' => esc_html__('URL from which identification starts', 'bluem'),
            'description' => esc_html__('of the page where the identification process is displayed, for example an account page. The user returns to this page after the process.', 'bluem'),
            'default' => 'my-account',
        ],
        // 'IDINCategories' => [
        // 'key' => 'IDINCategories',
        // 'title' => 'bluem_IDINCategories',
        // 'name' => 'Comma separated categories in iDIN shortcode requests',
        // 'description' => 'Opties: CustomerIDRequest, NameRequest, AddressRequest, BirthDateRequest, AgeCheckRequest, GenderRequest, TelephoneRequest, EmailRequest',
        // 'default' => 'AddressRequest,BirthDateRequest'
        // ],
        'IDINShortcodeOnlyAfterLogin' => [
            'key' => 'IDINShortcodeOnlyAfterLogin',
            'title' => 'bluem_IDINShortcodeOnlyAfterLogin',
            'name' => esc_html__('Restrict shortcode to logged-in users', 'bluem'),
            'description' => esc_html__('Should the iDIN form via shortcode be visible to everyone or only logged-in users?', 'bluem'),
            'type' => 'select',
            'default' => '0',
            'options' => [
                '0' => esc_html__('For everyone', 'bluem'),
                '1' => esc_html__('Only for logged-in visitors', 'bluem'),
            ],
        ],
        'IDINDescription' => [
            'key' => 'IDINDescription',
            'title' => 'bluem_IDINDescription',
            'name' => esc_html__('Request description format', 'bluem'),
            'description' => '

        <div style="width:400px; float:right; margin:10px; font-size: 9pt;
        border: 1px solid #ddd;
        padding: 10pt;
        border-radius: 5pt;">
        ' . esc_html__('Possible input fields: ', 'bluem')
                . $idinDescriptionTable
                . '<br>'
                . esc_html__('Note: maximum 128 characters. Allowed characters:', 'bluem')
                . '<code>-0-9a-zA-ZéëïôóöüúÉËÏÔÓÖÜÚ€ ()+,.@&amp;=%&quot;&apos;/:;?$</code>'
                . esc_html__(
                    'Enter the format that the description of
            an identification request must follow, with automatically filled fields. This value is also shown in the Bluem portal as the \'Subject\' text.',
                    'bluem'
                )
                . '<br>' . esc_html__('Example current value', 'bluem') . ': <code style=\'display:inline-block;\'>'
                . $idinDescriptionCurrentValue . '</code><br>',
            'default'
            /* translators: %s: username tag template */
                => sprintf(esc_html__('Identification %s', 'bluem'), '{gebruikersnaam}'),
        ],
        'idin_add_field_in_order_emails' => [
            'key' => 'idin_add_field_in_order_emails',
            'title' => 'bluem_idin_add_field_in_order_emails',
            'name' => esc_html__('Identification status in emails', 'bluem'),
            'description' => esc_html__(
                'Should the identification status be shown
            in the order notification email to the customer and to yourself? <strong>Note: this currently only works for logged-in customers</strong>',
                'bluem'
            ),
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_add_address_in_order_emails' => [
            'key' => 'idin_add_address_in_order_emails',
            'title' => 'bluem_idin_add_address_in_order_emails',
            'name' => esc_html__('Identification address in emails', 'bluem'),
            'description' => esc_html__(
                'Should the identification address be shown
            in the order notification email to the customer and to yourself? <strong>Note: this currently only works for logged-in customers</strong>',
                'bluem'
            ),
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_add_name_in_order_emails' => [
            'key' => 'idin_add_name_in_order_emails',
            'title' => 'bluem_idin_add_name_in_order_emails',
            'name' => esc_html__('Identification name in emails', 'bluem'),
            'description' => esc_html__(
                'Should the identification name be shown
            in the order notification email to the customer and to yourself? <strong>Note: this currently only works for logged-in customers</strong>',
                'bluem'
            ),
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_add_birthdate_in_order_emails' => [
            'key' => 'idin_add_birthdate_in_order_emails',
            'title' => 'bluem_idin_add_birthdate_in_order_emails',
            'name' => esc_html__('Identification date of birth in emails', 'bluem'),
            'description' => esc_html__(
                'Should the identification date of birth be shown
            in the order notification email to the customer and to yourself? <strong>Note: this currently only works for logged-in customers</strong>',
                'bluem'
            ),
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_identify_button_inner' => [
            'key' => 'idin_identify_button_inner',
            'title' => 'bluem_idin_identify_button_inner',
            'name' => esc_html__('Text on identification button', 'bluem'),
            'description' => esc_html__('What should appear on the button in frames where identification is required?', 'bluem'),
            'default' => esc_html__('Click here to identify yourself', 'bluem'),
        ],
        'idin_identity_dialog_no_verification_text' => [
            'key' => 'idin_identity_dialog_no_verification_text',
            'title' => 'bluem_idin_identity_dialog_no_verification_text',
            'name' => esc_html__('Text in Identify frame (below checkout) when no valid identification is known yet but is required', 'bluem'),
            'description' => esc_html__('What should appear on the button in frames where identification is required?', 'bluem'),
            'default' => esc_html__('Your age is unknown or insufficient. You cannot complete this order. Please contact webshop support if you have questions.', 'bluem'),
        ],
        'idin_identity_topbar_no_verification_text' => [
            'key' => 'idin_identity_topbar_no_verification_text',
            'title' => 'bluem_idin_identity_topbar_no_verification_text',
            'name' => esc_html__('Text in popup above checkout when no valid identification is known yet but is required', 'bluem'),
            'description' => esc_html__('What should appear on the button in frames where identification is required?', 'bluem'),
            'default' => esc_html__('We have not been able to retrieve your age yet. Complete the identification procedure first.', 'bluem'),
        ],
        'idin_identity_topbar_invalid_verification_text' => [
            'key' => 'idin_identity_topbar_invalid_verification_text',
            'title' => 'bluem_idin_identity_topbar_invalid_verification_text',
            'name' => esc_html__('Text in popup above checkout when an invalid identification is returned after requesting it', 'bluem'),
            'description' => esc_html__('What should appear on the button in frames where identification is required?', 'bluem'),
            'default' => esc_html__('Your age is insufficient. You cannot complete this order.', 'bluem'),
        ],
        'idin_identity_dialog_thank_you_message' => [
            'key' => 'idin_identity_dialog_thank_you_message',
            'title' => 'bluem_idin_identity_dialog_thank_you_message',
            'name' => esc_html__('Text in frame below checkout once a valid identification procedure has been completed', 'bluem'),
            'description' => esc_html__('What should appear on the button in frames where identification is required?', 'bluem'),
            'default' => esc_html__('Your age has been verified, thank you.', 'bluem'),
        ],
        'idin_identity_popup_thank_you_message' => [
            'key' => 'idin_identity_popup_thank_you_message',
            'title' => 'bluem_idin_identity_popup_thank_you_message',
            'name' => esc_html__('Text in popup above checkout once a valid identification procedure has been completed', 'bluem'),
            'description' => esc_html__('What should appear on the button in frames where identification is required?', 'bluem'),
            'default' => esc_html__('Your age has been verified.', 'bluem'),
        ],
        'idin_identity_more_information_popup' => [
            'key' => 'idin_identity_more_information_popup',
            'title' => 'bluem_idin_identity_more_information_popup',
            'name' => esc_html__('Explanation frame about identification', 'bluem'),
            'type' => 'textarea',
            'description' => esc_html__('Write an explanation here, with optional links, to tell customers/users about iDIN and its importance.', 'bluem'),
            'default' => esc_html__(
                '**Identification has been mandatory since July 1, 2021 in stores where products are sold that require customer identity verification.**
            
The method used here is safe, fast and easy, just like iDEAL. It takes at most two minutes and the result is saved for future transactions if you are logged in as a returning customer.',
                'bluem'
            ),
        ],
        'idin_enable_ip_country_filtering' => [
            'key' => 'idin_enable_ip_country_filtering',
            'title' => 'bluem_idin_enable_ip_country_filtering',
            'name' => esc_html__('Filter identification to take place only in the Netherlands', 'bluem'),
            'description' => esc_html__("If this value is set to yes, checkout checks the user's location (based on IP) and only checks for iDIN data for Dutch IPs.", 'bluem'),
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_show_notice_in_checkout' => [
            'key' => 'idin_show_notice_in_checkout',
            'title' => 'bluem_idin_show_notice_in_checkout',
            'name' => esc_html__('Do you want to show the identification notice at the top of checkout?', 'bluem'),
            'description' => esc_html__('Do you also want to show the identification required message as a notice at the top of checkout?', 'bluem'),
            'type' => 'bool',
            'default' => '1',
        ],
    ];
}

function bluem_woocommerce_idin_settings_section(): void
{
    $options = function_exists('get_option') ? get_option('bluem_woocommerce_options') : []; ?>
    <p><a id="tab_idin"></a>
        <?php esc_html_e('Here you can configure all important details for iDIN (Identification).', 'bluem'); ?>
    </p>
    <h3>
        <span class="dashicons dashicons-saved"></span>
        <?php esc_html_e('Automatic check:', 'bluem'); ?>
    </h3>
    <p>
        <strong>
            <?php
            switch ($options['idin_scenario_active']) {

                case 0:
                    {
                        esc_html_e('No automatic check is performed', 'bluem');
                        break;
                    }
                case 1:
                    {
                        esc_html_e('A minimum age check is performed during checkout', 'bluem');
                        break;
                    }
                case 2:
                    {
                        esc_html_e(
                            'A full identity check is performed before checkout becomes available
                        ',
                            'bluem'
                        );
                        break;
                    }
                case 3:
                    {
                        esc_html_e('A full identity check and age check are performed before checkout becomes available', 'bluem');
                        break;
                    }
            }
    ?>
        </strong>

    </p>

    <?php
    if ($options['idin_scenario_active'] >= 1) {
        ?>
        <p>
            <?php esc_html_e('These details are currently requested during the full identity check before checkout:', 'bluem'); ?>
            <br/>
            <code style="display:inline-block;">
                <?php
                foreach (bluem_idin_get_categories() as $cat) {
                    echo esc_attr(str_replace('Request', '', $cat)) . '<br>';
                }
        ?>
            </code>
        </p>
        <?php
    }
    ?>

    <h3>
        <span class="dashicons dashicons-welcome-write-blog"></span>
        <?php esc_html_e('Initiate an iDIN request yourself on a page', 'bluem'); ?>
    </h3>
    <p>
        <?php esc_html_e('The iDIN form also works as a shortcode that you can place on a page, post or in a template. The shortcode is as follows:', 'bluem'); ?>
        <code>[bluem_identificatieformulier]</code>.
    </p>
    <p>
        <?php esc_html_e('Once you have placed it, a block becomes visible on this page showing the status of the identification procedure. If no identification has been performed, a button will appear to start it.', 'bluem'); ?>
    </p>
    <p>
        <?php
        esc_html_e(
            'After successfully completing identification via Bluem, the user returns to the page marked below
        as iDINPageURL',
            'bluem'
        );
    ?>
        <?php esc_html_e('current value:', 'bluem'); ?>
        <code>
            <?php
        if (isset($options['IDINPageURL'])) {
            echo esc_url($options['IDINPageURL']);
        }
    ?>
        </code>).
    </p>
    <h3>
        <span class="dashicons dashicons-editor-help"></span>
        <?php esc_html_e('Where can I find the details?', 'bluem'); ?>
    </h3>
    <p>
        <?php
        esc_html_e(
            'Details are saved as metadata in the user profile after identification. You can see these fields when
        viewing a user.',
            'bluem'
        );
    ?>
        <?php esc_html_e('For example, look at', 'bluem'); ?>
        <a href="<?php echo esc_url(admin_url('profile.php')); ?>" target="_blank">
            <?php esc_html_e('your own profile', 'bluem'); ?>
        </a>.
    </p>
    <h3>
        <span class="dashicons dashicons-admin-settings"></span>
        <?php esc_html_e('Identity settings and preferences', 'bluem'); ?>
    </h3>
    <?php
}

function bluem_woocommerce_settings_render_IDINSuccessMessage(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINSuccessMessage')
    );
}

function bluem_woocommerce_settings_render_IDINErrorMessage(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINErrorMessage')
    );
}

function bluem_woocommerce_settings_render_IDINPageURL(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINPageURL')
    );
}

function bluem_woocommerce_settings_render_IDINCategories(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINCategories')
    );
}

function bluem_woocommerce_settings_render_IDINBrandID(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINBrandID')
    );
}


function bluem_woocommerce_settings_render_IDINShortcodeOnlyAfterLogin(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINShortcodeOnlyAfterLogin')
    );
}

function bluem_woocommerce_settings_render_IDINDescription(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINDescription')
    );
}

function bluem_woocommerce_settings_render_idin_scenario_active(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_scenario_active')
    );
}

function bluem_woocommerce_settings_render_idin_woocommerce_age_verification(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_woocommerce_age_verification')
    );
}

function bluem_woocommerce_settings_render_idin_check_age_minimum_age(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_check_age_minimum_age')
    );
}


function bluem_woocommerce_settings_render_idin_request_address(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_address')
    );
}

function bluem_woocommerce_settings_render_idin_request_birthdate(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_birthdate')
    );
}

function bluem_woocommerce_settings_render_idin_request_gender(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_gender')
    );
}

function bluem_woocommerce_settings_render_idin_request_telephone(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_telephone')
    );
}

function bluem_woocommerce_settings_render_idin_request_email(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_email')
    );
}

function bluem_woocommerce_settings_render_idin_request_name(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_name')
    );
}


function bluem_woocommerce_settings_render_idin_add_field_in_order_emails(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_add_field_in_order_emails')
    );
}


function bluem_woocommerce_settings_render_idin_add_address_in_order_emails(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_add_address_in_order_emails')
    );
}

function bluem_woocommerce_settings_render_idin_add_name_in_order_emails(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_add_name_in_order_emails')
    );
}

function bluem_woocommerce_settings_render_idin_add_birthdate_in_order_emails(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_add_birthdate_in_order_emails')
    );
}

function bluem_woocommerce_settings_render_idin_identify_button_inner(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identify_button_inner')
    );
}


function bluem_woocommerce_settings_render_idin_identity_dialog_no_verification_text(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identity_dialog_no_verification_text')
    );
}


function bluem_woocommerce_settings_render_idin_identity_topbar_no_verification_text(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identity_topbar_no_verification_text')
    );
}


function bluem_woocommerce_settings_render_idin_identity_topbar_invalid_verification_text(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identity_topbar_invalid_verification_text')
    );
}


function bluem_woocommerce_settings_render_idin_identity_dialog_thank_you_message(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identity_dialog_thank_you_message')
    );
}


function bluem_woocommerce_settings_render_idin_identity_popup_thank_you_message(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identity_popup_thank_you_message')
    );
}


function bluem_woocommerce_settings_render_idin_identity_more_information_popup(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identity_more_information_popup')
    );
}


function bluem_woocommerce_settings_render_idin_show_notice_in_checkout(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_show_notice_in_checkout')
    );
}


function bluem_woocommerce_settings_render_idin_enable_ip_country_filtering(): void
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_enable_ip_country_filtering')
    );
}

function bluem_idin_get_categories(?int $preset_scenario = null)
{
    $catListObject = new BluemIdentityCategoryList();
    $options = get_option('bluem_woocommerce_options');

    // if you want to infer the scenario from the settings and not override it.
    if (is_null($preset_scenario)) {
        if (isset($options['idin_scenario_active']) && $options['idin_scenario_active'] !== '') {
            $scenario = (int) $options['idin_scenario_active'];
        } else {
            $scenario = 0;
        }
    } else {
        $scenario = $preset_scenario;
    }

    /**
     * Check the scenario
     *
     * '0' => 'Do not perform an identity check during checkout', so we do not override categories here
     * '1' => 'Check op de minimumleeftijd door een AgeCheckRequest'
     * '2' => 'Perform a full identity check and save it, but do NOT block checkout if the minimum age has not been reached'
     * '3' => 'Perform a full identity check, save it AND block checkout if the minimum age has not been reached'
     */
    if ($scenario === 1) {
        if (method_exists($catListObject, 'Add')) {
            $catListObject->Add('AgeCheckRequest');
        } elseif (method_exists($catListObject, 'addCat')) {
            $catListObject->addCat('AgeCheckRequest');
        }

        if (method_exists($catListObject, 'getCategories')) {
            return $catListObject->getCategories();
        }

        if (method_exists($catListObject, 'getCats')) {
            return $catListObject->getCats();
        }

        return [];
    }

    if ($scenario === 2 || $scenario === 3) {
        if (method_exists($catListObject, 'Add')) {
            $catListObject->Add('CustomerIDRequest');
        } elseif (method_exists($catListObject, 'addCat')) {
            $catListObject->addCat('CustomerIDRequest');
        }

        if ($scenario === 3) {
            if (method_exists($catListObject, 'Add')) {
                $catListObject->Add('BirthDateRequest');
            } elseif (method_exists($catListObject, 'addCat')) {
                $catListObject->addCat('BirthDateRequest');
            }
        }
    }

    /**
     * Check which data to get.
     */
    if (isset($options['idin_request_name']) && $options['idin_request_name'] == '1') {
        if (method_exists($catListObject, 'Add')) {
            $catListObject->Add('NameRequest');
        } else {
            $catListObject->addCat('NameRequest');
        }
    }
    if (isset($options['idin_request_address']) && $options['idin_request_address'] == '1') {
        if (method_exists($catListObject, 'Add')) {
            $catListObject->Add('AddressRequest');
        } else {
            $catListObject->addCat('AddressRequest');
        }
    }
    if (isset($options['idin_request_birthdate']) && $options['idin_request_birthdate'] == '1') {
        if (method_exists($catListObject, 'Add')) {
            $catListObject->Add('BirthDateRequest');
        } else {
            $catListObject->addCat('BirthDateRequest');
        }
    }
    if (isset($options['idin_request_gender']) && $options['idin_request_gender'] == '1') {
        if (method_exists($catListObject, 'Add')) {
            $catListObject->Add('GenderRequest');
        } else {
            $catListObject->addCat('GenderRequest');
        }
    }
    if (isset($options['idin_request_telephone']) && $options['idin_request_telephone'] == '1') {
        if (method_exists($catListObject, 'Add')) {
            $catListObject->Add('TelephoneRequest');
        } else {
            $catListObject->addCat('TelephoneRequest');
        }
    }
    if (isset($options['idin_request_email']) && $options['idin_request_email'] == '1') {
        if (method_exists($catListObject, 'Add')) {
            $catListObject->Add('EmailRequest');
        } else {
            $catListObject->addCat('EmailRequest');
        }
    }
    return $catListObject->getCategories();
}

/* ********* RENDERING THE STATIC FORM *********** */
add_shortcode('bluem_identificatieformulier', 'bluem_idin_form');

/**
 * Shortcode: `[bluem_identificatieformulier]`
 *
 * @return string
 */
function bluem_idin_form(): string
{
    $bluem_config = bluem_woocommerce_get_config();

    if (isset($bluem_config->IDINShortcodeOnlyAfterLogin)
        && $bluem_config->IDINShortcodeOnlyAfterLogin == '1'
        && !is_user_logged_in()
    ) {
        return '';
    }

    $html = '';

    $validated = false;

    $storage = bluem_db_get_storage();

    if (is_user_logged_in()) {
        $validated = get_user_meta(get_current_user_id(), 'bluem_idin_validated', true) == '1';
    } else {
        if (isset($storage['bluem_idin_validated']) && $storage['bluem_idin_validated'] === true) {
            $validated = true;
        }
        // @todo: handle $storage['bluem_idin_report_agecheckresponse'] if necessary
    }

    if ($validated) {
        if (isset($bluem_config->IDINSuccessMessage)) {
            $html .= '<p>' . $bluem_config->IDINSuccessMessage . '</p>';
        } else {
            $html .= sprintf('<p>%s</p>', esc_html__('Your identification request has been received. Thank you.', 'bluem'));
        }

        // $html.= "You have completed the identification procedure before. Thank you<br>";
        // $results = bluem_idin_retrieve_results();
        // $html.= "<pre>";
        // foreach ($results as $k => $v) {
        // if (!is_object($v)) {
        // $html.= "$k: $v";
        // } else {
        // foreach ($v as $vk => $vv) {
        // $html.= "\t$vk: $vv";
        // $html.="<BR>";
        // }
        // }
        // $html.="<BR>";
        // }
        // // var_dump($results);
        // $html.= "</pre>";
        // return;
        return $html;
    }

    if (isset($_GET['result']) && sanitize_text_field(wp_unslash($_GET['result'])) === 'false') {
        $html .= '<div class="">';

        if (isset($bluem_config->IDINErrorMessage)) {
            $html .= '<p>' . $bluem_config->IDINErrorMessage . '</p>';
        } else {
            $html .= sprintf('<p>%s</p>', esc_html__('An error occurred. Your request has been canceled.', 'bluem'));
        }

        if (!empty($storage['bluem_idin_transaction_url'])) {
            $retryURL = $storage['bluem_idin_transaction_url'];
            $html .= sprintf(
                "<p><a href='$retryURL' target='_self' alt='try again' class='button'>
%s</a></p>",
                esc_html__('Try again', 'bluem')
            );
        }
        $html .= '</div>';
    } else {
        $html .= esc_html__('You have not completed the identification procedure yet.', 'bluem') . '<br>';
        $html .= '<form action="' . home_url('bluem-woocommerce/idin_execute') . '" method="post">';
        // @todo add custom fields
        $html .= '<p>';
        $html .= '<p><input type="submit" name="bluem_idin_submitted" class="bluem-woocommerce-button bluem-woocommerce-button-idin" value="' . esc_html__('Identify', 'bluem') . '.."></p>';
        $html .= '</form>';
    }
    return $html;
}

/**
 * This function is called POST from the form rendered on a page or post
 *
 * @return void
 */
function bluem_idin_shortcode_idin_execute(): void
{
    $goto = false;
    if (!empty($_GET['redirect_to_checkout'])
        && sanitize_text_field(wp_unslash($_GET['redirect_to_checkout'])) === 'true'
    ) {
        // v1.2.6: added cart url instead of static cart as this is front-end language dependent
        // $goto = wc_get_cart_url();
        // v1.2.8: added checkout url instead of cart url :)
        $goto = wc_get_checkout_url();
    }

    bluem_idin_execute(null, true, $goto);
}

/**
 * This function is executed at a callback GET request with a given mandateId. This is then, together with the entranceCode in user or Bluem session storage, sent for a SUD to the Bluem API.
 */
function bluem_idin_shortcode_callback(): void
{
    $bluem_config = bluem_woocommerce_get_config();

    // fallback until this is corrected in bluem-php
    $bluem_config->brandID = $bluem_config->IDINBrandID ?? $bluem_config->brandID ?? '';

    try {
        $bluem = new Bluem($bluem_config);
    } catch (Exception $e) {
        return;
        // @todo: deal with incorrectly configured Bluem here
    }

    $request_by_debtor_ref = false;

    if (isset($_GET['debtorReference']) && $_GET['debtorReference'] !== '') {
        $debtorReference = sanitize_text_field(wp_unslash($_GET['debtorReference']));
        $request_by_debtor_ref = bluem_db_get_request_by_debtor_reference($debtorReference);
    }

    if (is_user_logged_in()) {
        $entranceCode = get_user_meta(get_current_user_id(), 'bluem_idin_entrance_code', true);
        $transactionID = get_user_meta(get_current_user_id(), 'bluem_idin_transaction_id', true);
        $transactionURL = get_user_meta(get_current_user_id(), 'bluem_idin_transaction_url', true);
    } else {
        $storage = bluem_db_get_storage();

        if (!empty($storage['bluem_idin_entrance_code'])) {
            $entranceCode = $storage['bluem_idin_entrance_code'];
        } elseif ($request_by_debtor_ref !== false
            && isset($request_by_debtor_ref->entrance_code)
            && $request_by_debtor_ref->entrance_code !== ''
        ) {

            $entranceCode = $request_by_debtor_ref->entrance_code;
        } else {
            $errormessage = esc_html__(
                'Error: bluem_idin_entrance_code from the user or Bluem session storage is missing.
                This is required before you can complete an identification.
                Go back to the store and try again.',
                'bluem'
            );

            bluem_error_report_email(
                [
                    'service' => 'idin',
                    'function' => 'shortcode_callback',
                    'message' => $errormessage,
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            exit;
        }

        if (!empty($storage['bluem_idin_transaction_id'])) {
            $transactionID = $storage['bluem_idin_transaction_id'];
        } elseif ($request_by_debtor_ref !== false
            && isset($request_by_debtor_ref->transaction_id)
            && $request_by_debtor_ref->transaction_id !== ''
        ) {

            $transactionID = $request_by_debtor_ref->transaction_id;
        } else {
            $errormessage = esc_html__('Error: bluem_idin_transaction_id from the user or Bluem session storage is missing. This is required before you can complete an identification. Go back to the store and try again.', 'bluem');
            bluem_error_report_email(
                [
                    'service' => 'idin',
                    'function' => 'shortcode_callback',
                    'message' => $errormessage,
                ]
            );
            bluem_dialogs_render_prompt($errormessage);
            exit;
        }
    }

    $statusResponse = null;

    try {
        $statusResponse = $bluem->IdentityStatus(
            $transactionID,
            $entranceCode
        );
    } catch (Exception $e) {
        // @todo: deal with Exception here
    }

    if (!$statusResponse || !$statusResponse->ReceivedResponse()) {
        $errormessage = sprintf(
            /* translators: %1$s: transaction ID %2$s: entranceCode */
            esc_html__('Error: could not find request with %1$s and entranceCode %2$s', 'bluem'),
            $transactionID,
            $entranceCode
        );
        bluem_error_report_email(
            [
                'service' => 'idin',
                'function' => 'shortcode_callback',
                'message' => esc_html($errormessage),
            ]
        );
        bluem_dialogs_render_prompt($errormessage);
        exit;
    }

    $statusCode = ($statusResponse->GetStatusCode());

    $request_from_db = bluem_db_get_request_by_transaction_id($transactionID);

    if ($request_from_db->status !== $statusCode) {
        bluem_db_update_request(
            $request_from_db->id,
            [
                'status' => $statusCode,
            ]
        );
    }

    if (is_user_logged_in()) {
        update_user_meta(
            get_current_user_id(),
            'bluem_idin_validated',
            false
        );
    } else {
        bluem_db_insert_storage(
            [
                'bluem_idin_validated' => false,
            ]
        );
    }

    /**
     * Determining the right callback.
     */
    $goto = bluem_get_idin_shortcode_callback_url($bluem_config);

    switch ($statusCode) {
        case 'Success': // in case of success...
            // ..retrieve a report that contains the information based on the request type:
            $identityReport = $statusResponse->GetIdentityReport();

            if (is_user_logged_in()) {
                update_user_meta(get_current_user_id(), 'bluem_idin_results', wp_json_encode($identityReport));
                update_user_meta(get_current_user_id(), 'bluem_idin_validated', true);
            } else {
                try {
                    bluem_db_insert_storage(
                        [
                            'bluem_idin_validated' => true,
                            'bluem_idin_results' => wp_json_encode($identityReport),
                        ]
                    );
                } catch (Exception $e) {
                    // not inserted.
                }
            }

            // update an age check response field if that sccenario is active.
            $verification_scenario = bluem_idin_get_verification_scenario();

            if (isset($identityReport->AgeCheckResponse) && $verification_scenario === 1
            ) {
                $agecheckresponse = $identityReport->AgeCheckResponse . '';
                if (is_user_logged_in()) {
                    update_user_meta(get_current_user_id(), 'bluem_idin_report_agecheckresponse', $agecheckresponse);
                } else {
                    bluem_db_insert_storage(
                        [
                            'bluem_idin_report_agecheckresponse' => $agecheckresponse,
                        ]
                    );
                }
            }
            if (isset($identityReport->CustomerIDResponse)) {
                $customeridresponse = $identityReport->CustomerIDResponse . '';
                if (is_user_logged_in()) {
                    update_user_meta(get_current_user_id(), 'bluem_idin_report_customeridresponse', $customeridresponse);
                } else {
                    bluem_db_insert_storage(
                        [
                            'bluem_idin_report_customeridresponse' => $customeridresponse,
                        ]
                    );
                }
            }
            if (isset($identityReport->DateTime)) {
                $datetime = $identityReport->DateTime . '';
                if (is_user_logged_in()) {
                    update_user_meta(get_current_user_id(), 'bluem_idin_report_last_verification_timestamp', $datetime);
                } else {
                    bluem_db_insert_storage(
                        [
                            'bluem_idin_report_last_verification_timestamp' => $datetime,
                        ]
                    );
                }
            }

            if (isset($identityReport->BirthdateResponse)) {
                $birthdate = $identityReport->BirthdateResponse . '';
                if (is_user_logged_in()) {
                    update_user_meta(
                        get_current_user_id(),
                        'bluem_idin_report_birthdate',
                        $birthdate
                    );
                } else {
                    bluem_db_insert_storage(
                        [
                            'bluem_idin_report_birthdate' => $birthdate,
                        ]
                    );
                }
            }
            if (isset($identityReport->TelephoneResponse)) {
                $telephone = $identityReport->TelephoneResponse . '';
                if (is_user_logged_in()) {
                    update_user_meta(
                        get_current_user_id(),
                        'bluem_idin_report_telephone',
                        $telephone
                    );
                }
            }
            if (isset($identityReport->EmailResponse)) {
                $email = $identityReport->EmailResponse . '';
                if (is_user_logged_in()) {
                    update_user_meta(
                        get_current_user_id(),
                        'bluem_idin_report_email',
                        $email
                    );
                }
            }

            $min_age = bluem_idin_get_min_age();

            if ($verification_scenario === 3
                && isset($identityReport->BirthDateResponse)
            ) {
                $user_age = bluem_idin_get_age_based_on_date(
                    $identityReport->BirthDateResponse
                );

                if ($user_age >= $min_age) {
                    if (is_user_logged_in()) {
                        update_user_meta(
                            get_current_user_id(),
                            'bluem_idin_report_agecheckresponse',
                            'true'
                        );
                    } else {
                        bluem_db_insert_storage(
                            [
                                'bluem_idin_report_agecheckresponse' => true,
                            ]
                        );
                    }
                }
            }

            if (isset($request_from_db) && $request_from_db !== false) {
                if ($request_from_db->payload !== '') {
                    try {
                        $oldPayload = json_decode($request_from_db->payload);
                    } catch (Throwable $th) {
                        $oldPayload = new Stdclass();
                    }
                } else {
                    $oldPayload = new Stdclass();
                }
                $oldPayload->report = $identityReport;

                bluem_db_update_request(
                    $request_from_db->id,
                    [
                        'status' => $statusCode,
                        'payload' => wp_json_encode($oldPayload),
                    ]
                );
            }

            bluem_transaction_notification_email(
                $request_from_db->id
            );

            wp_safe_redirect($goto);
            exit;
        case 'Processing':
        case 'Pending':
            // @todo: improve this flow

            // @todo: improve this flow
            // do something when the request is still processing (for example tell the user to come back later to this page)
            break;
        case 'Cancelled':
            // @todo: improve this flow
            // do something when the request has been canceled by the user
            break;
        case 'Open':
            // @todo: improve this flow
            // do something when the request has not yet been completed by the user, redirecting to the transactionURL again
            break;
        case 'Expired':
            // @todo: improve this flow
            // do something when the request has expired
            break;
        case 'New':
            // @todo: improve this flow
            // do something when the request is still new
            break;
        default:
            // unexpected status returned, show an error
            break;
    }

    bluem_transaction_notification_email(
        $request_from_db->id
    );

    wp_safe_redirect(location: sprintf('%s?result=false&status=%s', $goto, $statusCode));
    exit;
}

/**
 * Determine the Callback URL for the iDIN shortcode flow
 * @param Stdclass $bluem_config
 *
 * @return string
 */
function bluem_get_idin_shortcode_callback_url(Stdclass $bluem_config): string
{

    if (str_contains(sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])), 'bluem-woocommerce/idin_shortcode_callback/go_to_cart')) {
        return wc_get_checkout_url();
    } elseif (! empty($bluem_config->IDINPageURL)) {
        $callbackUrl = (string) $bluem_config->IDINPageURL;

        // if goto is relative URL, append it with the home URL of WordPress
        if (! str_starts_with($callbackUrl, 'http://') && ! str_starts_with($callbackUrl, 'https://')) {
            return home_url($bluem_config->IDINPageURL);
        }
        // else just use goto
        return $callbackUrl;
    }
    return home_url();
}

/**
 * Identity webhook action
 *
 * @return void
 */
function bluem_idin_webhook(): void
{
    http_response_code(200);
    exit;
}

add_action('show_user_profile', 'bluem_woocommerce_idin_show_extra_profile_fields', 2);
add_action('edit_user_profile', 'bluem_woocommerce_idin_show_extra_profile_fields');

function bluem_woocommerce_idin_show_extra_profile_fields($user): void
{
    $bluem_requests = bluem_db_get_requests_by_user_id_and_type($user->ID . '', 'identity');
    ?>

    <table class="form-table" style="max-height:800px; overflow-y:auto;">
        <a id="user_identity"></a>

        <?php if (isset($bluem_requests) && count($bluem_requests) > 0) { ?>
            <tr>
                <th>
                    <?php esc_html_e('Identity', 'bluem'); ?>
                </th>
                <td>
                    <?php
                    bluem_render_requests_list($bluem_requests);
            ?>
                </td>
            </tr>
            <?php
        } else {
            ?>

        <tr>
            <th>
                <?php esc_html_e('Identity', 'bluem'); ?>
            </th>
            <td>
                <?php esc_html_e('No requests performed yet.', 'bluem'); ?>
            </td>
        </tr>
        <tr>
            <th>
                <label for="bluem_idin_entrance_code">
                    <?php esc_html_e('Bluem iDIN transaction details', 'bluem'); ?>

                </label>
            </th>
            <td>
                <input type="text" name="bluem_idin_entrance_code" id="bluem_idin_entrance_code"
                       value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_idin_entrance_code', true)); ?>"
                       class="regular-text"/><br/>
                <label for="bluem_idin_entrance_code"><span class="description">
			<?php esc_html_e('Most recent Entrance code for Bluem iDIN requests', 'bluem'); ?>
					</span></label>

                <br>
                <input type="text" name="bluem_idin_transaction_id" id="bluem_idin_transaction_id"
                       value="
			<?php
                           echo esc_attr(
                               get_user_meta($user->ID, 'bluem_idin_transaction_id', true)
                           );
            ?>
								"
                       class="regular-text"><br/>
                <label for="bluem_idin_transaction_id">
					<span class="description">
			<?php esc_html_e('The most recent transaction ID: this is used when performing the next identification.', 'bluem'); ?></span>
                </label>

                <br>
                <input type="text" name="bluem_idin_transaction_url" id="bluem_idin_transaction_url"
                       value="
			<?php
            echo esc_attr(
                get_user_meta($user->ID, 'bluem_idin_transaction_url', true)
            );
            ?>
								"
                       class="regular-text"><br/>
                <label for="bluem_idin_transaction_url">
                    <span class="description"><?php esc_html_e('The most recent transaction URL', 'bluem'); ?></span>
                </label>
                <br>
                <input type="text" name="bluem_idin_report_last_verification_timestamp"
                       id="bluem_idin_report_last_verification_timestamp"
                       value="
			<?php
            echo esc_attr(
                get_user_meta($user->ID, 'bluem_idin_report_last_verification_timestamp', true)
            );
            ?>
								"
                       class="regular-text"/><br/>
                <label for="bluem_idin_report_last_verification_timestamp">
                    <span class="description"><?php esc_html_e('Last time verification was performed', 'bluem'); ?></span>
                </label>
            </td>
        </tr>

        <tr>
            <?php
        }
    ?>


            <th>
                <label for="bluem_idin_report_agecheckresponse">
                    <?php esc_html_e('Bank response for age check, if applicable', 'bluem'); ?>

                </label>
            </th>


            <td>
                <?php $ageCheckResponse = get_user_meta($user->ID, 'bluem_idin_report_agecheckresponse', true); ?>
                <select class="form-control" name="bluem_idin_report_agecheckresponse"
                        id="bluem_idin_report_agecheckresponse">
                    <option value=""
                        <?php
                if ($ageCheckResponse == '') {
                    echo "selected='selected'";
                }
    ?>
                    ><?php esc_html_e('Age check has not been performed yet', 'bluem'); ?>
                    </option>
                    <option value="false"
                        <?php
    if ($ageCheckResponse === 'false') {
        echo "selected='selected'";
    }
    ?>
                    ><?php esc_html_e('Age found to be insufficient', 'bluem'); ?>
                    </option>
                    <option value="true"
                        <?php
    if ($ageCheckResponse === 'true') {
        echo "selected='selected'";
    }
    ?>
                    >
                        <?php esc_html_e('Age found to be sufficient', 'bluem'); ?>
                    </option>
                </select>

                <br>
                <span class="description"></span>
            </td>

        </tr>
        <tr>
            <th>
                <label for="bluem_idin_report_customeridresponse">
                    <?php esc_html_e('CustomerID returned by the bank', 'bluem'); ?>
                </label>
            </th>

            <td>


                <input type="text" name="bluem_idin_report_customeridresponse"
                       id="bluem_idin_report_customeridresponse"
                       value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_idin_report_customeridresponse', true)); ?>"
                       class="regular-text"/><br/>
                <span class="description"></span>
            </td>

        </tr>

        <tr>
            <th>
                <label for="bluem_idin_validated"><?php esc_html_e('iDIN responses', 'bluem'); ?></label>
            </th>

            <td>

                <select class="form-control" name="bluem_idin_validated" id="bluem_idin_validated">
                    <option value="0"
                        <?php
    if (get_user_meta($user->ID, 'bluem_idin_validated', true) == '0') {
        echo "selected='selected'";
    }
    ?>
                    ><?php esc_html_e('Identification has not been performed yet', 'bluem'); ?>
                    </option>
                    <option value="1"
                        <?php
    if (get_user_meta($user->ID, 'bluem_idin_validated', true) == '1') {
        echo "selected='selected'";
    }
    ?>
                    ><?php esc_html_e('Identification performed successfully', 'bluem'); ?>
                    </option>
                </select>

                <span class="description" style="display:block;">
					<?php esc_html_e('Status and results of iDIN requests', 'bluem'); ?>
				</span>
            </td>
        </tr>
    </table>
    <?php
}

add_action('personal_options_update', 'bluem_woocommerce_idin_save_extra_profile_fields');
add_action('edit_user_profile_update', 'bluem_woocommerce_idin_save_extra_profile_fields');

function bluem_woocommerce_idin_save_extra_profile_fields($user_id): bool
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (!empty($_POST['bluem_idin_entrance_code'])) {
        update_user_meta(
            $user_id,
            'bluem_idin_entrance_code',
            sanitize_text_field(wp_unslash($_POST['bluem_idin_entrance_code']))
        );
    }
    if (!empty($_POST['bluem_idin_transaction_id'])) {
        update_user_meta(
            $user_id,
            'bluem_idin_transaction_id',
            sanitize_text_field(wp_unslash($_POST['bluem_idin_transaction_id']))
        );
    }
    if (!empty($_POST['bluem_idin_transaction_url'])) {
        update_user_meta(
            $user_id,
            'bluem_idin_transaction_url',
            sanitize_text_field(wp_unslash($_POST['bluem_idin_transaction_url']))
        );
    }

    if (!empty($_POST['bluem_idin_validated'])) {
        update_user_meta(
            $user_id,
            'bluem_idin_validated',
            sanitize_text_field(wp_unslash($_POST['bluem_idin_validated']))
        );
    }

    if (!empty($_POST['bluem_idin_report_last_verification_timestamp'])) {
        update_user_meta(
            $user_id,
            'bluem_idin_report_last_verification_timestamp',
            sanitize_text_field(wp_unslash($_POST['bluem_idin_report_last_verification_timestamp']))
        );
    }

    if (!empty($_POST['bluem_idin_report_customeridresponse'])) {
        update_user_meta(
            $user_id,
            'bluem_idin_report_customeridresponse',
            sanitize_text_field(wp_unslash($_POST['bluem_idin_report_customeridresponse']))
        );
    }

    if (!empty($_POST['bluem_idin_report_agecheckresponse'])) {
        update_user_meta(
            $user_id,
            'bluem_idin_report_agecheckresponse',
            sanitize_text_field(wp_unslash($_POST['bluem_idin_report_agecheckresponse']))
        );
    }

    return true;
}

function bluem_idin_retrieve_results()
{
    $storage = bluem_db_get_storage();

    if (is_user_logged_in()) {
        $raw = get_user_meta(get_current_user_id(), 'bluem_idin_results', true);
    } else {
        $raw = $storage['bluem_idin_results'] ?? '';
    }

    return json_decode($raw);
}

/**
 * Check if validation is needed
 */
function bluem_idin_validation_needed(): bool
{
    global $current_user;

    $age_verification_needed = bluem_checkout_age_verification_needed();

    $options = get_option('bluem_woocommerce_options');

    if (isset($options['idin_enable_ip_country_filtering'])
        && $options['idin_enable_ip_country_filtering'] !== ''
    ) {
        $idin_enable_ip_country_filtering = $options['idin_enable_ip_country_filtering'];
    } else {
        $idin_enable_ip_country_filtering = true;
    }

    $bluem_config = bluem_woocommerce_get_config();

    $bluem_config->brandID = $bluem_config->IDINBrandID ?? $bluem_config->brandID ?? '';

    if (empty($bluem_config->brandID)) {
        return false;
    }

    try {
        $bluem = new Bluem($bluem_config);
    } catch (Exception $e) {
        // @todo: deal with non-configured bluem brandID, or assert that is has been configured on a higher level
        return false;
    }

    // Check if IP filtering is enabled
    if ($idin_enable_ip_country_filtering) {
        // override international IP's - don't validate idin when not NL
        if (!$bluem->VerifyIPIsNetherlands()) {
            return false;
        }
    }

    /**
     * Check if age verification is needed.
     */
    if (isset($options['idin_woocommerce_age_verification']) && $options['idin_woocommerce_age_verification'] === '1') {
        if (bluem_is_woocommerce_activated() && !$age_verification_needed) {
            return false;
        }
    }

    return true;
}

/**
 * Retrieves the user validation status
 */
function bluem_idin_user_validated(): bool
{
    global $current_user;

    $storage = bluem_db_get_storage();

    if (is_user_logged_in()) {
        return get_user_meta(get_current_user_id(), 'bluem_idin_validated', true) == '1';
    }
    // or as a guest:
    if (isset($storage['bluem_idin_validated']) && $storage['bluem_idin_validated'] === true) {
        return true;
    }
    return false;
}

function bluem_get_IDINDescription_tags(): array
{
    return [
        '{gebruikersnaam}',
        '{email}',
        '{klantnummer}',
        '{datum}',
        '{datumtijd}',
    ];
}

function bluem_get_IDINDescription_replaces(): array
{
    global $current_user;

    // with fallbacks if user is not logged in

    return [
        $current_user->display_name ?? '',    // '{gebruikersnaam}',
        $current_user->user_email ?? '',      // '{email}',
        (string) ($current_user->ID ?? ''),    // {klantnummer}
        gmdate('d-m-Y'),                        // '{datum}',
        gmdate('d-m-Y H:i'),                     // '{datumtijd}',
    ];
}

function bluem_parse_IDINDescription($input): string
{
    // input description tags
    $tags = bluem_get_IDINDescription_tags();
    $replaces = bluem_get_IDINDescription_replaces();
    $result = str_replace($tags, $replaces, $input);

    // filter based on full list of invalid chars for description based on XSD
    // Wel toegestaan: -0-9a-zA-ZéëïôóöüúÉËÏÔÓÖÜÚ€ ()+,.@&=%"'/:;?$
    $result = preg_replace('/[^-0-9a-zA-ZéëïôóöüúÉËÏÔÓÖÜÚ€ ()+,.@&=%\"\'\/:;?$]/u', '', $result);

    // also adhere to char limit
    return substr($result, 0, 128);
}


function bluem_idin_execute($callback = null, $redirect = true, $redirect_page = false)
{
    global $current_user;

    $bluem_config = bluem_woocommerce_get_config();

    if (isset($bluem_config->IDINDescription)) {
        $description = bluem_parse_IDINDescription($bluem_config->IDINDescription);
    } else {
        $description = 'Identification ' . $current_user->display_name;
    }

    if (is_user_logged_in()) {
        $debtorReference = $current_user->ID;
    } else {
        $debtorReference = 'guest' . gmdate('Ymdhisu');
    }

    // fallback until this is corrected in bluem-php
    $bluem_config->brandID = $bluem_config->IDINBrandID;

    try {
        $bluem = new Bluem($bluem_config);
    } catch (InvalidBluemConfigurationException $e) {
        printf(
            /* translators: %s: Error message */
            esc_html__('Error: the Bluem plugin is not configured correctly. Please contact your system administrator. Error message: %s', 'bluem'),
            esc_html($e->getMessage())
        );

        exit;
    }

    $cats = bluem_idin_get_categories();

    if (count($cats) === 0) {
        $errormessage = esc_html__('No valid iDIN categories configured', 'bluem');
        // bluem_error_report_email(
        // [
        // 'service' => 'idin',
        // 'function' => 'idin_execute',
        // 'message' => $errormessage
        // ]
        // );
        bluem_dialogs_render_prompt($errormessage);
        exit();
    }

    if (is_null($callback)) {
        $callback = home_url('bluem-woocommerce/idin_shortcode_callback');
    }

    if ($redirect_page !== false) {
        $callback .= '/go_to_cart';
    }

    // To create AND perform a request:
    $request = $bluem->CreateIdentityRequest(
        $cats,
        $description,
        $debtorReference,
        '', // Entrance code
        $callback // Return URL
    );

    // allow the testing admin to alter the response status themselves.
    if ($bluem_config->environment === 'test') {
        $request->enableStatusGUI();
    }

    try {
        $response = $bluem->PerformRequest($request);

        if ($response->ReceivedResponse() && $response->Status() === true && !isset($response->IdentityTransactionResponse->Error)) {

            $entranceCode = $response->GetEntranceCode();
            $transactionID = $response->GetTransactionID();
            $transactionURL = $response->GetTransactionURL();

            bluem_db_create_request(
                [
                    'entrance_code' => $entranceCode,
                    'transaction_id' => $transactionID,
                    'transaction_url' => $transactionURL,
                    'user_id' => is_user_logged_in() ? $current_user->ID : 0,
                    'timestamp' => gmdate('Y-m-d H:i:s'),
                    'description' => $description,
                    'debtor_reference' => $debtorReference,
                    'type' => 'identity',
                    'order_id' => null,
                    'payload' => wp_json_encode(
                        [
                            'environment' => $bluem_config->environment,
                        ]
                    ),
                ]
            );

            // save this in our user metadata store
            if (is_user_logged_in()) {
                update_user_meta(
                    get_current_user_id(),
                    'bluem_idin_entrance_code',
                    $entranceCode
                );
                update_user_meta(
                    get_current_user_id(),
                    'bluem_idin_transaction_id',
                    $transactionID
                );
                update_user_meta(
                    get_current_user_id(),
                    'bluem_idin_transaction_url',
                    $transactionURL
                );
            } else {
                bluem_db_insert_storage(
                    [
                        'bluem_idin_entrance_code' => $entranceCode,
                        'bluem_idin_transaction_id' => $transactionID,
                        'bluem_idin_transaction_url' => $transactionURL,
                    ]
                );
            }

            if ($redirect) {
                if (ob_get_length() !== false && ob_get_length() > 0) {
                    ob_clean();
                }
                ob_start();
                wp_redirect($transactionURL);
                exit;
            } else {
                return [
                    'result' => true,
                    'url' => $transactionURL,
                ];
            }
        } else {
            $msg = esc_html__('Something went wrong while creating the transaction. Please provide the information below to the website administrator:', 'bluem');

            if ($response->Error() !== '') {
                $msg .= '<br>Response: '
                    . $response->Error();
            } elseif (!empty($response->IdentityTransactionResponse->Error->ErrorMessage)) {
                $msg .= '<br>' . sprintf(
                    /* translators: %s: Error message */
                    esc_html__('iDIN error: %s', 'bluem'),
                    $response->IdentityTransactionResponse->Error->ErrorMessage . ""
                );
            } else {
                $msg .= '<br>' . esc_html__('General error', 'bluem');
            }

            // bluem_error_report_email(
            // [
            // 'service' => 'idin',
            // 'function' => 'idin_execute',
            // 'message' => $msg
            // ]
            // );
            bluem_dialogs_render_prompt($msg);
            exit;
        }
    } catch (\Exception $e) {
        $msg = esc_html__('Something went wrong while creating the transaction. Please provide the information below to the website administrator:', 'bluem');

        if (!empty($e->getMessage())) {
            $msg .= '<br>Response: ' . esc_html($e->getMessage());
        } else {
            $msg .= '<br>' . esc_html__('General error', 'bluem');
        }
        //
        // bluem_error_report_email(
        // [
        // 'service' => 'idin',
        // 'function' => 'idin_execute',
        // 'message' => $e->getMessage()
        // ]
        // );
        bluem_dialogs_render_prompt($msg);
        exit;
    }
}

// https://www.businessbloomer.com/woocommerce-visual-hook-guide-checkout-page/
// add_action(
// 'woocommerce_review_order_before_payment',
// 'bluem_checkout_check_idin_validated'
// );

/**
 * Check if age verification is needed by checkout.
 */
function bluem_checkout_age_verification_needed(): bool
{
    $verification_needed = false;

    // Get the products in the cart
    $products = WC()->cart->get_cart();

    // Loop through the products
    foreach ($products as $cart_item_key => $cart_item) {
        $product_id = $cart_item['product_id'];
        $product = wc_get_product($product_id);

        // Retrieve custom attribute value
        $age_verification = $product->get_meta('pa_age_verification');

        // Check if age verification is enabled by user
        if (!empty($age_verification) && $age_verification === 'enable') {
            $verification_needed = true;
        }
    }
    return $verification_needed;
}

// CHECKOUT review message
add_action('woocommerce_review_order_before_payment', 'bluem_checkout_idin_notice');
function bluem_checkout_idin_notice(): void
{
    global $current_user;

    // if no woo
    if (!function_exists('is_checkout') || !function_exists('is_wc_endpoint_url')) {
        return;
    }

    // use a setting if this check has to be incurred
    if (function_exists('is_checkout') && !is_checkout()) {
        return;
    }

    if (!function_exists('bluem_idin_user_validated')) {
        return;
    }

    // don't show this notice on the my-account page (DrankStunter Request, 22-04-2021)
    if (is_page('my-account')
        || is_page('mijn-account')
    ) {
        return;
    }

    $validation_needed = bluem_idin_validation_needed();

    $options = get_option('bluem_woocommerce_options');

    if (isset($options['idin_scenario_active']) && $options['idin_scenario_active'] !== '') {
        $scenario = (int) $options['idin_scenario_active'];
    }

    if (isset($options['idin_identity_dialog_no_verification_text']) && $options['idin_identity_dialog_no_verification_text'] !== '') {
        $identity_dialog_no_verification_text = $options['idin_identity_dialog_no_verification_text'];
    } else {
        $identity_dialog_no_verification_text = esc_html__('Your age is unknown or insufficient. You cannot complete this order. Please contact webshop support if you have questions.', 'bluem');
    }

    if (isset($options['idin_identity_dialog_thank_you_message']) && $options['idin_identity_dialog_thank_you_message'] !== '') {
        $idin_identity_dialog_thank_you_message = $options['idin_identity_dialog_thank_you_message'];
    } else {
        $idin_identity_dialog_thank_you_message = esc_html__('Your age has been verified, thank you.', 'bluem');
    }

    if (isset($options['idin_identity_topbar_no_verification_text']) && $options['idin_identity_topbar_no_verification_text'] !== '') {
        $idin_identity_topbar_no_verification_text = $options['idin_identity_topbar_no_verification_text'];
    } else {
        $idin_identity_topbar_no_verification_text = esc_html__('We have not been able to retrieve your age yet. Complete the identification procedure first.', 'bluem');
    }

    if ($validation_needed && $scenario > 0) {
        echo '<h3>' . esc_html__('Identification', 'bluem') . '</h3>';

        $validated = bluem_idin_user_validated();

        $validation_message = $idin_identity_topbar_no_verification_text;

        if (!$validated) {
            bluem_idin_generate_notice_e($validation_message, true);

            return;
        }

        // get report from user metadata
        // $results = bluem_idin_retrieve_results();
        // identified? but is this person OK of age?
        if ($scenario === 1 || $scenario === 3) {
            // By default, we assume the age is NOT sufficient
            $age_valid = false;

            if (is_user_logged_in()) {
                $ageCheckResponse = get_user_meta(
                    $current_user->ID,
                    'bluem_idin_report_agecheckresponse',
                    true
                );
            } else {
                $storage = bluem_db_get_storage();

                $ageCheckResponse = $storage['bluem_idin_report_agecheckresponse'] ?? '';
            }

            // var_dump($ageCheckResponse);
            // check on age based on response of AgeCheckRequest in user meta
            // if ($scenario == 1)
            // {
            if (isset($ageCheckResponse)) {
                if ($ageCheckResponse === 'true') {

                    // TRUE returned by the bank
                    $age_valid = true;
                } else {
                    // ERROR KON BIRTHDAY NIET INLEZEN, WEL INGEVULD BIJ DE BANK? nIET VALIDE DUS
                    $validation_message = $identity_dialog_no_verification_text;
                    // /"Your age is unknown or insufficient. You cannot complete this order. Please contact webshop support if you have questions.";

                    $age_valid = false;
                }
            } else {
                // ERROR KON BIRTHDAY NIET INLEZEN, WEL INGEVULD BIJ DE BANK? nIET VALIDE DUS
                $validation_message = $identity_dialog_no_verification_text;
                // "We have not yet been able to retrieve your age during identification.<BR>
                // Neem contact op met de webshop support.";

                $age_valid = false;
            }
            // }

            // check on age based on response of BirthDateRequest in user meta
            // if ($scenario == 3)
            // {
            // $min_age = bluem_idin_get_min_age();

            // echo $results->BirthDateResponse; // prints 1975-07-25
            // if (isset($results->BirthDateResponse) && $results->BirthDateResponse!=="") {

            // $user_age = bluem_idin_get_age_based_on_date($results->BirthDateResponse);
            // if ($user_age < $min_age) {
            // $validation_message = "Your age, $user_age, is insufficient. The minimum age is {$min_age} years.
            // <br>Identify yourself again or contact us.";
            // $age_valid = false;
            // } else {
            // $age_valid = true;
            // }
            // } else {

            // ERROR KON BIRTHDAY NIET INLEZEN, WEL INGEVULD BIJ DE BANK? nIET VALIDE DUS
            // $validation_message = "We could not retrieve your age during identification.<BR>
            // Neem contact op met de webshop support.";
            // $age_valid =false;
            // }
            // }

            if (!$age_valid) {
                bluem_idin_generate_notice_e($validation_message, true);
            } else {
                bluem_idin_generate_notice_e($idin_identity_dialog_thank_you_message);
            }

            return;
        }
    }

    // <p>Identification is required before you can place this order</p>";

    if ($validation_needed && bluem_checkout_check_idin_validated_filter() == false) {
        bluem_idin_generate_notice_e('Verifieer eerst je identiteit.', true);
        // esc_html_e(
        // "Verifieer eerst je identiteit via de mijn account pagina",
        // "woocommerce"
        // );
        // return;
    }
}


// add_action('woocommerce_check_cart_items', 'bluem_checkout_check_idin_validated'); // Cart and Checkout


add_action('woocommerce_after_checkout_validation', 'bluem_validate_idin_at_checkout', 10, 2);

/**
 * @param $fields
 * @param $errors
 *
 * @return void
 */
function bluem_validate_idin_at_checkout($fields, $errors): void
{
    bluem_checkout_check_idin_validated();
    // $errors->add( 'validation', 'Your first or last name contains a number. Really?' );
}


/**
 * Show notice!
 */

// @todo: this is partially redundant with bluem_checkout_idin_notice
add_action('template_redirect', 'bluem_checkout_check_idin_validated');
function bluem_checkout_check_idin_validated(): bool
{
    global $current_user;

    // ! is_user_logged_in() &&

    if (!function_exists('is_checkout') || !function_exists('is_wc_endpoint_url')) {
        return true;
    }

    // only run this check in Woo & checkout
    if (!is_checkout() || is_wc_endpoint_url()) {
        return true;
    }

    // don't show this notice on the my-account page (DrankStunter Request, 22-04-2021)
    if (is_page('my-account')
        || is_page('mijn-account')
    ) {
        return true;
    }

    if (!function_exists('bluem_idin_user_validated')) {
        return true;
    }

    $validation_needed = bluem_idin_validation_needed();

    $options = get_option('bluem_woocommerce_options');

    if (isset($options['idin_identify_button_inner']) && $options['idin_identify_button_inner'] !== '') {
        $identify_button_inner = $options['idin_identify_button_inner'];
    } else {
        $identify_button_inner = esc_html__('Click here to identify yourself', 'bluem');
    }

    if (isset($options['idin_identity_topbar_invalid_verification_text']) && $options['idin_identity_topbar_invalid_verification_text'] !== '') {
        $idin_identity_topbar_invalid_verification_text = $options['idin_identity_topbar_invalid_verification_text'];
    } else {
        $idin_identity_topbar_invalid_verification_text = esc_html__('Your age is insufficient. You cannot complete this order.', 'bluem');
    }
    if (isset($options['idin_identity_topbar_no_verification_text']) && $options['idin_identity_topbar_no_verification_text'] !== '') {
        $idin_identity_topbar_no_verification_text = $options['idin_identity_topbar_no_verification_text'];
    } else {
        $idin_identity_topbar_no_verification_text = esc_html__('We have not been able to retrieve your age yet. Complete the identification procedure first.', 'bluem');
    }

    if (isset($options['idin_identity_popup_thank_you_message']) && $options['idin_identity_popup_thank_you_message'] !== '') {
        $idin_identity_popup_thank_you_message = $options['idin_identity_popup_thank_you_message'];
    } else {
        $idin_identity_popup_thank_you_message = esc_html__('Your age has been verified.', 'bluem');
    }

    // @todo: implement this later to allow the notice to be hidden
    // but the checkout still be blocked. Right now this is
    // connected due to the design of WooCOmmerce.
    // Further research is needed to see if this is possible.
    $idin_show_notice_in_checkout = true;

    if (isset($options['idin_scenario_active']) && $options['idin_scenario_active'] !== '') {
        $scenario = (int) $options['idin_scenario_active'];
    }

    if ($validation_needed && $scenario > 0) {
        $validated = bluem_idin_user_validated();
        $idin_logo_html = bluem_get_idin_logo_html();
        $validation_message = $idin_identity_topbar_no_verification_text;

        // above 0: any form of verification is required
        if (!$validated) {
            if ($idin_show_notice_in_checkout) {
                wc_add_notice(
                    bluem_idin_generate_notice($validation_message, true, false, false),
                    'error'
                );
            }

            return false;
        } else {

            // get report from user metadata
            // $results = bluem_idin_retrieve_results();
            // identified? but is this person OK of age?
            if ($scenario == 1 || $scenario == 3) {
                // By default, we assume the age is NOT sufficient
                $age_valid = false;

                if (is_user_logged_in()) {
                    $ageCheckResponse = get_user_meta(
                        $current_user->ID,
                        'bluem_idin_report_agecheckresponse',
                        true
                    );
                } else {
                    $storage = bluem_db_get_storage();

                    $ageCheckResponse = $storage['bluem_idin_report_agecheckresponse'] ?? '';
                }

                // check on age based on response of AgeCheckRequest in user meta
                // if ($scenario == 1)
                // {
                $age_valid = false;
                if (isset($ageCheckResponse) && $ageCheckResponse != '') {
                    if ($ageCheckResponse === 'true') {

                        // TRUE returned by the bank
                        $age_valid = true;
                    } else {
                        // error: could not read birthday, filled in by the bank? therefore not valid
                        $validation_message = $idin_identity_topbar_invalid_verification_text;
                        $age_valid = false;
                    }
                } else {
                    // error: could not read birthday, filled in by the bank? therefore not valid.
                    $validation_message = $idin_identity_topbar_no_verification_text;

                    $age_valid = false;
                }

                if (!$age_valid) {
                    if ($idin_show_notice_in_checkout) {
                        wc_add_notice(
                            bluem_idin_generate_notice($validation_message, true, false, false),
                            'error'
                        );
                    }

                    return false;
                } else {
                    wc_add_notice(
                        $idin_identity_popup_thank_you_message,
                        'success'
                    );
                }
            }
        }
    }

    // custom user-based checks:
    if ($validation_needed && bluem_checkout_check_idin_validated_filter() == false) {
        if ($idin_show_notice_in_checkout) {
            wc_add_notice(
                bluem_idin_generate_notice($validation_message, true, false, false),
                'error'
            );
        }

        return false;
    }

    return true;
}

// @todo: simplify and merge above two functions bluem_checkout_check_idin_validated and bluem_checkout_idin_notice

add_filter(
    'bluem_checkout_check_idin_validated_filter',
    'bluem_checkout_check_idin_validated_filter_function',
    10,
    1
);
function bluem_checkout_check_idin_validated_filter(): bool
{
    // override this function if you want to add a filter to block the checkout procedure based on the iDIN validation procedure being completed.
    // if you return true, the checkout is enabled. If you return false, the checkout is blocked and a notice is shown.

    /*
    for example:
    if (!bluem_idin_user_validated_extra_function()) {
    return false;
    }
    */
    return true;
}

function bluem_idin_get_age_based_on_date($birthday_string): int
{
    $birthdate_seconds = strtotime($birthday_string);
    $now_seconds = time();

    return (int) floor(($now_seconds - $birthdate_seconds) / 60 / 60 / 24 / 365);
}

function bluem_idin_get_verification_scenario(): int
{
    $options = get_option('bluem_woocommerce_options');
    $scenario = 0;

    if (isset($options['idin_scenario_active'])
        && $options['idin_scenario_active'] !== ''
    ) {
        $scenario = (int) $options['idin_scenario_active'];
    }

    return $scenario;
}

function bluem_idin_get_min_age()
{
    $options = get_option('bluem_woocommerce_options');

    if (isset($options['idin_check_age_minimum_age'])
        && $options['idin_check_age_minimum_age'] !== ''
    ) {
        $min_age = $options['idin_check_age_minimum_age'];
    } else {
        $min_age = 18;
    }

    return $min_age;
}

// https://wordpress.stackexchange.com/questions/314955/add-custom-order-meta-to-order-completed-email
add_filter('woocommerce_email_order_meta_fields', 'bluem_order_email_identity_meta_data', 10, 3);

/**
 * Add identity-related metadata fields to an order confirmation email
 * Note: only works for logged-in users now.
 *
 * @param $fields
 * @param $sent_to_admin
 * @param $order
 */
function bluem_order_email_identity_meta_data($fields, $sent_to_admin, $order): bool|array
{
    global $current_user;

    $options = get_option('bluem_woocommerce_options');

    if (is_user_logged_in()) {
        $request = bluem_db_get_most_recent_request($current_user->ID, 'identity');
    } else {
        $request = false;

        // for now, don't add any information to an email if we can't find the request - for example if the guest session was not securely stored in an account

        // TODO DAAN
        // $order_id = $order->ID;
        // $requests_links = bluem_db_get_links_for_order($order_id);
        // $requests = [];
        // foreach ($requests_links as $rql) {
        // $requests[] = bluem_db_get_request_by_id($rql->request_id);
        // }

        // if (isset($requests) && count($requests)>0) {
        // $request = $requests[0];
        // } else {
        // echo "No requests yet";
        // $pl = false;
        // }
    }
    if ($request == false) {
        return false;
    }

    $pl = json_decode($request->payload);

    if (!array_key_exists('idin_add_field_in_order_emails', $options)
        || (array_key_exists('idin_add_field_in_order_emails', $options)
            && $options['idin_add_field_in_order_emails'] == '1')
    ) {
        if (is_user_logged_in()) {
            $validation_text = '';
            if (get_user_meta($current_user->ID, 'bluem_idin_validated', true)) {
                $validation_text = esc_html__('Yes', 'bluem');
                // $validation_text .= " (Transactie ". get_user_meta($current_user->ID, 'bluem_idin_transaction_id', true).")";
            } else {
                $validation_text = esc_html__('Yes, as guest user', 'bluem');
            }
        }

        $fields['bluem_idin_validated'] = [
            'label' => esc_html__('Identity verified', 'bluem'),
            'value' => $validation_text,
        ];
    }

    if (!array_key_exists('idin_add_address_in_order_emails', $options)
        || (array_key_exists('idin_add_address_in_order_emails', $options)
            && $options['idin_add_address_in_order_emails'] == '1')
    ) {
        if (is_user_logged_in()) {
            if ($request !== false) {
                $address_text = '';
                if (isset($pl->report->AddressResponse->Street)) {
                    $address_text .= $pl->report->AddressResponse->Street . ' ';
                }
                if (isset($pl->report->AddressResponse->HouseNumber)) {
                    $address_text .= $pl->report->AddressResponse->HouseNumber . ' ';
                }
                $address_text .= '<br>';
                if (isset($pl->report->AddressResponse->PostalCode)) {
                    $address_text .= $pl->report->AddressResponse->PostalCode . ' ';
                }
                if (isset($pl->report->AddressResponse->City)) {
                    $address_text .= $pl->report->AddressResponse->City . ' ';
                }
                if (isset($pl->report->AddressResponse->CountryCode)) {
                    $address_text .= $pl->report->AddressResponse->CountryCode . '';
                }

                $fields['bluem_idin_address'] = [
                    'label' => esc_html__('Address from verification', 'bluem'),
                    'value' => $address_text,
                ];
            } else {
                $fields['bluem_idin_address'] = [
                    'label' => esc_html__('Address from verification', 'bluem'),
                    'value' => esc_html__('Unknown', 'bluem'),
                ];
            }
        }
    }

    if (!array_key_exists('idin_add_name_in_order_emails', $options)
        || (array_key_exists('idin_add_name_in_order_emails', $options)
            && $options['idin_add_name_in_order_emails'] == '1')
    ) {
        if ($request !== false) {
            $name_text = '';
            if (isset($pl->report->NameResponse->Initials)
                && $pl->report->NameResponse->Initials !== ''
            ) {
                $name_text .= $pl->report->NameResponse->Initials . ' ';
            }
            if (isset($pl->report->NameResponse->LegalLastNamePrefix)
                && $pl->report->NameResponse->LegalLastNamePrefix !== ''
            ) {
                $name_text .= $pl->report->NameResponse->LegalLastNamePrefix . ' ';
            }
            if (isset($pl->report->NameResponse->LegalLastName)
                && $pl->report->NameResponse->LegalLastName !== ''
            ) {
                $name_text .= $pl->report->NameResponse->LegalLastName . ' ';
            }

            $fields['bluem_idin_name'] = [
                'label' => esc_html__('Name from verification', 'bluem'),
                'value' => $name_text,
            ];
        } else {
            $fields['bluem_idin_name'] = [
                'label' => esc_html__('Name from verification', 'bluem'),
                'value' => esc_html__('Unknown', 'bluem'),
            ];
        }
    }

    if (!array_key_exists('idin_add_birthdate_in_order_emails', $options)
        || (array_key_exists('idin_add_birthdate_in_order_emails', $options)
            && $options['idin_add_birthdate_in_order_emails'] == '1')
    ) {
        if ($request !== false) {
            $birthdate_text = '';
            if (isset($pl->report->BirthDateResponse)
                && $pl->report->BirthDateResponse !== ''
            ) {
                $birthdate_text .= $pl->report->BirthDateResponse . ' ';
            }

            $fields['bluem_idin_birthdate'] = [
                'label' => esc_html__('Date of birth from verification', 'bluem'),
                'value' => $birthdate_text,
            ];
        } else {
            $fields['bluem_idin_birthdate'] = [
                'label' => esc_html__('Date of birth from verification', 'bluem'),
                'value' => esc_html__('Unknown', 'bluem'),
            ];
        }
    }

    return $fields;
}


// add_action('woocommerce_review_order_before_payment', 'bluem_idin_before_payment_notice');
// function bluem_idin_before_payment_notice()
// {
// }

$allowedTags = [
    'a' => [
        'href' => [],
        'class' => [],
        'target' => [],
    ],
    'div' => [
        'class' => [],
        'id' => [],
        'style' => [],
    ],
    'img' => [
        'src' => [],
        'class' => [],
        'width' => [],
        'height' => [],
        'style' => [],
    ],
    'h4' => [],
    'hr' => [],
    'span' => [
        'class' => [],
    ],
];

/**
 * Generate the necessary HTML to show a notice concerning iDIN status
 *
 * @param String $message
 * @param boolean $button
 * @param bool $logo
 * @param bool $border
 *
 * @return String
 */
function bluem_idin_generate_notice(string $message = '', bool $button = false, bool $logo = true, bool $border = true): string
{
    global $allowedTags;

    $idin_logo_html = "<img src='"
        . esc_url(plugin_dir_url(__FILE__) . 'assets/bluem/idin.png') . "' class='bluem-idin-logo'
    style=' max-height:64px; '/>";

    $options = get_option('bluem_woocommerce_options');
    if (isset($options['idin_identify_button_inner']) && $options['idin_identify_button_inner'] !== '') {
        $identify_button_inner = $options['idin_identify_button_inner'];
    } else {
        $identify_button_inner = esc_html__('Click here to identify yourself', 'bluem');
    }
    if (isset($options['idin_identify_button_inner']) && $options['idin_identify_button_inner'] !== '') {
        $identify_button_inner = $options['idin_identify_button_inner'];
    } else {
        $identify_button_inner = esc_html__('Click here to identify yourself', 'bluem');
    }

    if (isset($options['idin_identity_more_information_popup']) && $options['idin_identity_more_information_popup'] !== '') {
        $more_information_popup = $options['idin_identity_more_information_popup'];
    } else {
        $more_information_popup = esc_html__('iDIN is an essential part of the shopping process for our webshop. If you see this frame, identification is essential for your purchase or interaction.', 'bluem');
    }

    $html = "<div style='position:relative;"
        . ($border ? 'border-radius:4px;
    width:auto;
    min-height:110px;
    display:block;
    padding:15pt;
    margin-top:10px;
    margin-bottom:10px;
    border:1px solid #50afed;' : '') . "'>";
    if ($logo) {
        $html .= $idin_logo_html;
    }

    $html .= "<div style='
    " . ($border ? 'margin-left:100px; display:block; width:auto; height:auto;' : '') . "'>
        $message";
    if ($button) {
        $html .= "<div style='' class='bluem-idin-button'>";
        $html
            .= sprintf(

                /* translators:
        %1$s: url  to more information
        %2$s: button text */
                __('<a href="%1$s" target="_self" class="button bluem-identify-button" style="display:inline-block">%2$s</a>', 'bluem'),
                esc_url(home_url('bluem-woocommerce/idin_execute?redirect_to_checkout=true')),
                $identify_button_inner
            );
        $html .= '</div>';
    }

    $checkout_url = esc_url(wc_get_checkout_url());
    $html .= '
    <div class="bluem-idin-box">
	<a class="bluem-idin-info-button" href="' . $checkout_url . '#idin_info_popup">
        <span class="dashicons dashicons-editor-help"></span>
        ' . esc_html__('What is this?', 'bluem') . '
    </a></div>';

    $html .= sprintf(
        wp_kses(
            /* translators: %1$s: checkout url %2$s: more information popup %3$s: checkout url */
            __(
                '<div id="idin_info_popup" class="bluem-idin-overlay">
                <div class="bluem-idin-popup">
                <h4>Explanation of required identification</h4>
                <a class="bluem-idin-popup-close bluem-idin-popup-close-icon" href="%1$s#">&times;</a>
                    <div class="bluem-idin-popup-content">
                        %2$s
                        <hr>
                        <a class="bluem-idin-popup-close" href="%3$s#">Click here to close this frame</a>
                    </div>
                </div>
            </div></div></div>',
                'bluem'
            ),
            $allowedTags
        ),
        esc_url($checkout_url),
        wp_kses_post($more_information_popup),
        esc_url($checkout_url)
    );

    return $html;
}

function bluem_idin_generate_notice_e(string $message = '', bool $button = false, bool $logo = true, bool $border = true): void
{
    global $allowedTags;
    echo wp_kses(
        bluem_idin_generate_notice($message, $button, $logo, $border),
        $allowedTags
    );
}


/*
 * Add identity request if it was already created but first decoupled form a user, based on sesh
**/
add_action('user_register', 'bluem_link_idin_request_to_sesh', 10, 1);
function bluem_link_idin_request_to_sesh($user_id): void
{
    $storage = bluem_db_get_storage();

    if (!isset($storage['bluem_idin_transaction_id'])) {
        return;
    }

    $tid = $storage['bluem_idin_transaction_id'];

    $req = bluem_db_get_request_by_transaction_id($tid);

    // only if the current response from the Bluem session storage
    // IS NOT YET linked to any user, i.e. user_id == 0
    if ($req->user_id == '0') {
        bluem_db_update_request(
            $req->id,
            ['user_id' => $user_id]
        );
        bluem_db_request_log($req->id, esc_html__('Identity linked to user by logging in', 'bluem'));

        $pl = json_decode($req->payload);

        // also update some user data if applicable
        if (isset($pl->report->AgeCheckResponse)) {
            update_user_meta(
                $user_id,
                'bluem_idin_report_agecheckresponse',
                $pl->report->AgeCheckResponse
            );
        }
        if (isset($pl->report->CustomerIDResponse)) {
            update_user_meta(
                $user_id,
                'bluem_idin_report_customeridresponse',
                $pl->report->CustomerIDResponse
            );
        }

        update_user_meta(
            $user_id,
            'bluem_idin_validated',
            ($req->status === 'Success' ? '1' : '0')
        );
    }
}
