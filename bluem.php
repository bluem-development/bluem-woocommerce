<?php

/**
 * Plugin Name: Bluem
 * Version: 1.1.0
 * Plugin URI: https://github.com/DaanRijpkema/bluem
 * Description: Bluem integration for WordPress and WooCommerce to facilitate Bluem services inside your site. Payments and eMandates payment gateway and iDIN identity verification
 * Author: Daan Rijpkema
 * Author URI: https://github.com/DaanRijpkema/
 * Requires at least: 5.0
 * Tested up to: 5.6.1
 *
 * Text Domain: bluem
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



// get composer dependencies
require __DIR__ . '/vendor/autoload.php';

// get specific gateways and helpers
if (bluem_module_enabled('mandates')) {
    include_once __DIR__ . '/bluem-mandates.php';
    include_once __DIR__ . '/bluem-mandates-shortcode.php';
}
if (bluem_module_enabled('payments')) {
    include_once __DIR__ . '/bluem-payments.php';
}

if (bluem_module_enabled('idin')) {
    include_once __DIR__ . '/bluem-idin.php';
    include_once __DIR__ . '/bluem-idin-shortcode.php';
}

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

        $bluem_options = get_option('bluem_woocommerce_options');
        if (!isset($bluem_options['suppress_woo']) || $bluem_options['suppress_woo']=="0") {
            echo '<div class="notice notice-error is-dismissible">
            <p>Bluem WooCommerce is afhankelijk van WooCommerce - activeer deze plug-in ook. Je kan deze melding en WooCommerce gerelateerde functionaliteiten ook uitzetten bij de <a href="'.admin_url('options-general.php?page=bluem').'">Instellingen</a>.</p>
            </div>';
        }
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
        'bluem',
        'bluem_woocommerce_settings_page'
    );
}
add_action('admin_menu', 'bluem_woocommerce_settings_handler');



function bluem_woocommerce_tab() {
    $default_tab = null;
  return isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : $default_tab;

}
/**
 * Settings page display
 *
 * @return void
 */
function bluem_woocommerce_settings_page()
{

    //Get the active tab from the GET param
    $tab = bluem_woocommerce_tab(); ?>

    <style>
        .bluem-form-control {
            width: 100%;
        }

        .bluem-settings {
            /* column-count: 2;
            column-gap: 40px; */
            padding:20px;
        }
    </style>


<div class="wrap">
    <!-- Print the page title -->
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper">
      <a href="<?php echo admin_url('options-general.php?page=bluem');?>"
      class="nav-tab
        <?php if ($tab===null) {
            echo "nav-tab-active";
        } ?>
      ">
        Algemene instellingen
      </a>

      <?php if(bluem_module_enabled('mandates')) { ?>

        <a href="<?php echo admin_url('options-general.php?page=bluem&tab=mandates');?>"
            class="nav-tab
            <?php if($tab==='mandates') { echo "nav-tab-active"; } ?>
            ">
            Digitaal Incassomachtigen (eMandates)
        </a>
        <?php } ?>

    <?php if(bluem_module_enabled('payments')) { ?>

        <a href="<?php echo admin_url('options-general.php?page=bluem&tab=payments');?>"
            class="nav-tab
            <?php if($tab==='payments') { echo "nav-tab-active"; } ?>
            ">
            iDEAL (ePayments)
        </a>
        <?php } ?>

    <?php if(bluem_module_enabled('idin')) { ?>

        <a href="<?php echo admin_url('options-general.php?page=bluem&tab=idin');?>"
            class="nav-tab
            <?php if($tab==='idin') { echo "nav-tab-active"; } ?>
            ">
            iDIN (Identity)
        </a>
        <?php } ?>


        <a href="mailto:d.rijpkema@bluem.nl?subject=Bluem+Wordpress+Plugin" class="nav-tab" target="_blank">Problemen, vragen of suggesties? Neem contact op via e-mail</a>
    </nav>



    <div class="bluem-settings">

        <form action="options.php" method="post">

        <?php if(is_null($tab)) {
            ?>
            <?php
        settings_fields('bluem_woocommerce_modules_options');
        do_settings_sections('bluem_woocommerce_modules');
        ?>
        <?php
        } ?>
            <?php

            settings_fields('bluem_woocommerce_options');
            do_settings_sections('bluem_woocommerce');
            ?>
            <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>" />
        </form>


    </div>
</div>
    <?php
}


function bluem_woocommerce_general_settings_section()
{
    echo '<p>Hier kan je alle belangrijke gegevens instellen rondom Bluem algemeen. Lees de readme bij de plug-in voor meer informatie.</p>';
}


function bluem_woocommerce_register_settings()
{
    $tab = bluem_woocommerce_tab();

    register_setting('bluem_woocommerce_options', 'bluem_woocommerce_options', 'bluem_woocommerce_options_validate');


    if (is_null($tab)) {
        register_setting('bluem_woocommerce_options', 'bluem_woocommerce_modules_options', 'bluem_woocommerce_modules_options_validate');
        add_settings_section('bluem_woocommerce_modules_section', _('Beheer onderdelen van deze plug-in'), 'bluem_woocommerce_modules_settings_section', 'bluem_woocommerce');
        add_settings_field(
            "mandates_enabled",
            _("Mandates actief"),
            "bluem_woocommerce_modules_render_mandates_activation",
            "bluem_woocommerce",
            "bluem_woocommerce_modules_section"
        );
        add_settings_field(
            "payments_enabled",
            _("ePayments actief"),
            "bluem_woocommerce_modules_render_payments_activation",
            "bluem_woocommerce",
            "bluem_woocommerce_modules_section"
        );
        add_settings_field(
            "idin_enabled",
            _("iDIN actief"),
            "bluem_woocommerce_modules_render_idin_activation",
            "bluem_woocommerce",
            "bluem_woocommerce_modules_section"
        );

        add_settings_section('bluem_woocommerce_general_section', 'Algemene instellingen', 'bluem_woocommerce_general_settings_section', 'bluem_woocommerce');
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
    }

    if ($tab == "mandates" && bluem_module_enabled('mandates')) {
        add_settings_section('bluem_woocommerce_mandates_section', 'Machtiging instellingen', 'bluem_woocommerce_mandates_settings_section', 'bluem_woocommerce');

        $mandates_settings = bluem_woocommerce_get_mandates_options();
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
    }
    if ($tab == "payments" && bluem_module_enabled('payments')) {
        add_settings_section('bluem_woocommerce_payments_section', 'iDeal payments instellingen', 'bluem_woocommerce_payments_settings_section', 'bluem_woocommerce');


        $payments_settings = bluem_woocommerce_get_payments_options();
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
    }
    if ($tab == "idin" && bluem_module_enabled('idin')) {
        add_settings_section('bluem_woocommerce_idin_section', 'iDIN instellingen', 'bluem_woocommerce_idin_settings_section', 'bluem_woocommerce');


        $idin_settings = bluem_woocommerce_get_idin_options();
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



}
add_action('admin_init', 'bluem_woocommerce_register_settings');


function bluem_woocommerce_get_option($key)
{

    $options = bluem_woocommerce_get_core_options();

    if (array_key_exists($key, $options)) {
        return $options[$key];
    }
    return false;
}



function bluem_woocommerce_settings_render_environment()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_option('environment'));
}
function bluem_woocommerce_settings_render_senderID()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_option('senderID'));
}
function bluem_woocommerce_settings_render_brandID()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_option('brandID'));
}
function bluem_woocommerce_settings_render_test_accessToken()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_option('test_accessToken'));
}
function bluem_woocommerce_settings_render_production_accessToken()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_option('production_accessToken'));
}
function bluem_woocommerce_settings_render_expectedReturnStatus()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_option('expectedReturnStatus'));
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
        ],
        'suppress_woo' => [
            'key' => 'suppress_woo',
            'title' => 'bluem_suppress_woo',
            'name' => 'WooCommerce gebruiken?',
            'description' => 'Zet dit op "WooCommerce niet gebruiken" als je deze plug-in wilt gebruiken op deze site zonder WooCommerce functionaliteiten.',
            'type' => 'select',
            'default' => '0',
            'options' =>
            ['0' => "WooCommerce wel gebruiken", '1' => 'WooCommerce NIET gebruiken']
        ]
    ];
}



function bluem_woocommerce_get_config()
{

    $bluem_options = bluem_woocommerce_get_core_options();


    if (function_exists('bluem_woocommerce_get_mandates_options')) {
        $bluem_options = array_merge(
            $bluem_options, bluem_woocommerce_get_mandates_options()
        );
    }
    if (function_exists('bluem_woocommerce_get_idin_options')) {
        $bluem_options = array_merge(
            $bluem_options, bluem_woocommerce_get_idin_options()
        );
    }
    if (function_exists('bluem_woocommerce_get_payments_options')) {
        $bluem_options = array_merge(
            $bluem_options, bluem_woocommerce_get_payments_options()
        );
    }

    $config = new Stdclass();

    $values = get_option('bluem_woocommerce_options');
    foreach ($bluem_options as $key => $option) {

        $config->$key = isset($values[$key]) ? $values[$key] : (isset($option['default']) ? $option['default'] : "");
    }
    return $config;
}




function bluem_woocommerce_modules_settings_section()
{

    
    echo '<p>
    <img src="'.
    home_url('/wp-content/plugins/bluem/assets/bluem/logo.png').
    '" style="float:left; max-height:64px; margin:10pt;"/>
    Je hebt een geactiveerde account nodig bij Bluem.
    De gegevens die je ontvangt via e-mail kan je hieronder
    en per specifiek onderdeel invullen.</p>';
    echo '<p>
    Schakel hier de onderdelen uit die jouw website wel of niet nodig heeft,
    zodat je efficiënt kan werken.</p>';
// var_dump(get_option('bluem_woocommerce_options'));
}

function bluem_woocommerce_modules_render_mandates_activation() {
    bluem_woocommerce_modules_render_generic_activation("mandates");
}

function bluem_woocommerce_modules_render_payments_activation() {
    bluem_woocommerce_modules_render_generic_activation("payments");
}

function bluem_woocommerce_modules_render_idin_activation() {
    bluem_woocommerce_modules_render_generic_activation("idin");
}


function bluem_woocommerce_settings_render_suppress_woo() {
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_option('suppress_woo'));
}

function bluem_woocommerce_modules_render_generic_activation($module) {

    $field = [
        'key'=> "{$module}_enabled",
        'default'=> "",
        'description'=> "",
        'options'=> [
            ''=>'(Maak een selectie)',
            '1'=>'Actief',
            '0'=>'Gedeactiveerd'
        ],
        'type'=> "select"
    ];

    bluem_woocommerce_settings_render_input($field);

}


function bluem_module_enabled($module)
{
    $bluem_options = get_option('bluem_woocommerce_options');
    if (!isset($bluem_options["{$module}_enabled"])
        || $bluem_options["{$module}_enabled"]=="1"
    ) {
        return true;
    }
    return false;
}




  /**
         * Retrieve header HTML for error/message prompts
         *
         * @return String
         */
        function bluem_woocommerce_simpleheader(): String
        {
            return "<!DOCTYPE html><html><body><div
            style='font-family:Arial,sans-serif;display:block;
            margin:40pt auto; padding:10pt 20pt; border:1px solid #eee;
            background:#fff; max-width:500px;'>";
        }
        /**
         * Retrieve footer HTML for error/message prompt. Can include a simple link back to the webshop home URL.
         *
         * @param Bool $include_link
         * @return String
         */
        function bluem_woocommerce_simplefooter(Bool $include_link = true): String
        {
            return ($include_link ? "<p><a href='" . home_url() . "' target='_self' style='text-decoration:none;'>Ga terug</a></p>" : "") .
                "</div></body></html>";
        }

        /**
         * Render a piece of HTML sandwiched beteween a simple header and footer, with an optionally included link back home
         *
         * @param String $html
         * @param boolean $include_link
         * @return void
         */
        function bluem_woocommerce_prompt(String $html, $include_link = true)
        {
            echo bluem_woocommerce_simpleheader();
            echo $html;
            echo bluem_woocommerce_simplefooter($include_link);
        }



/**
 * bluem_generic_tabler
 *
 * @return void
 */
function bluem_generic_tabler($data)
{
    ?>
    <table class="table widefat">
    <?php
    $i = 0;
    foreach ($data as $key => $value) {
        if ($i == 0) {
            ?>
                <tr>
                    <?php
                    foreach ($value as $kkey => $vvalue) {
                        ?>
                        <th>
                            <?php echo $kkey; ?>
                        </th><?php
                    } ?>
                </tr>

            <?php
        } ?>
        <tr>
            <?php
            foreach ($value as $kkey => $vvalue) {
                ?>
                <td>
                    <?php echo $vvalue; ?>
                </td><?php
            } ?>

        </tr><?php
        $i++;
    } ?>
    </table><?php
}
