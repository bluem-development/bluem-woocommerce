<?php

/**
 * Plugin Name: Bluem ePayments, iDIN and eMandates integration for shortcodes and WooCommerce checkout
 * Version: 1.2.8
 * Plugin URI: https://wordpress.org/plugins/bluem
 * Description: Bluem integration for WordPress and WooCommerce to facilitate Bluem services inside your site. Payments and eMandates payment gateway and iDIN identity verification
 * Author: Bluem Payment Services
 * Author URI: https://bluem.nl
 * Requires at least: 5.0
 * Tested up to: 5.7
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

global $bluem_db_version;
$bluem_db_version = 1.2;

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
}

// database functions
include_once __DIR__ . '/bluem-db.php';
include_once __DIR__ . '/bluem-interface.php';

// @todo: add login module later

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



// Update CSS within in Admin
function bluem_add_admin_style() {
    wp_enqueue_style('admin-styles', plugin_dir_url(__FILE__).'/css/admin.css');
  }
  add_action('admin_enqueue_scripts', 'bluem_add_admin_style');






// https://www.wpbeginner.com/wp-tutorials/how-to-add-admin-notices-in-wordpress/
function bluem_woocommerce_no_woocommerce_notice()
{
    if (is_admin()) {
        $bluem_options = get_option('bluem_woocommerce_options');
        if (!isset($bluem_options['suppress_woo']) || $bluem_options['suppress_woo']=="0") {
            echo '<div class="notice notice-warning is-dismissible">
            <p>De Bluem integratie is deels afhankelijk van WooCommerce - activeer deze plug-in ook.<br>
            Je kan deze melding en WooCommerce gerelateerde functionaliteiten ook uitzetten bij de <a href="'.admin_url('options-general.php?page=bluem').'">Instellingen</a>.</p>
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
        'bluem_settings_page'
    );
}
add_action('admin_menu', 'bluem_woocommerce_settings_handler');



/**
 * Register the necessary administrative pages in the WordPress back-end.
 *
 * @return void
 */
function bluem_register_menu()
{
    add_menu_page(
        "bluem_admin_requests_view",
        "Bluem",
        "manage_options",
        "bluem_admin_requests_view",
        "bluem_admin_requests_view",
        'dashicons-money'
    );

    // add_submenu_page
    //     "bluem",
    //     "Instellingen",
    //     "Instellingen",
    //     "manage_options",
    //     "bluem_settings_page",
    //     "bluem_settings_page"
    // );
    // add_submenu_page(
    //     "bluem",
    //     "Instellingen",
    //     "Instellingen",
    //     "manage_options",
    //     "bluem_settings_page",
    //     "bluem_settings_page"
    // );
}
add_action('admin_menu', 'bluem_register_menu', 9);




function bluem_admin_requests_view()
{
    if (isset($_GET['request_id']) && $_GET['request_id']!=="") {
        bluem_admin_requests_view_request();
    } else {
        bluem_admin_requests_view_all();
    }
}

function bluem_admin_requests_view_request()
{
    global $wpdb;
    date_default_timezone_set('Europe/Amsterdam');
    $wpdb->time_zone = 'Europe/Amsterdam';

    $id = $_GET['request_id'];

    $request_query = $wpdb->get_results("SELECT * FROM `bluem_requests` WHERE `id` = $id LIMIT 1");
    if (count($request_query)==0) {
        bluem_admin_requests_view_all();
        return;
    }

    $request = (object)$request_query[0];
    $request_author = get_user_by('id', $request->user_id);

    $logs = $wpdb->get_results("SELECT *  FROM  `bluem_requests_log` WHERE `request_id` = $id ORDER BY `timestamp` DESC");
    include_once 'views/request.php';
}
function bluem_admin_requests_view_all()
{
    global $wpdb;
    date_default_timezone_set('Europe/Amsterdam');
    $wpdb->time_zone = 'Europe/Amsterdam';

    $_requests = $wpdb->get_results("SELECT *  FROM `bluem_requests`");

    $requests['identity'] = [];
    $requests['payments'] = [];
    $requests['mandates'] = [];


    // if (isset($_GET['tab']) && $_GET['tab'] !== "") {
    //     $tab = $_GET['tab'];
    // } else {
    //     $tab = "index";
    // }

    foreach ($_requests as $_r) {
        $requests[$_r->type][] = $_r;
    }
    $users = get_users();
    $users_by_id = [];
    foreach ($users as $user) {
        $users_by_id[$user->ID] = $user;
    }

    include_once 'views/requests.php';
}

function bluem_woocommerce_tab()
{
    $default_tab = null;
    return isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : $default_tab;
}
/**
 * Settings page display
 *
 * @return void
 */
function bluem_settings_page()
{

    //Get the active tab from the GET param
    $tab = bluem_woocommerce_tab(); ?>

<div class="wrap bluem-settings-container">
    <div class="bluem-settings-header">

    <h1>
        <?php echo bluem_get_bluem_logo_html(24); ?>
        <?php echo esc_html(get_admin_page_title()); ?>
    </h1>
    <nav class="nav-tab-wrapper">
        <a href="<?php echo admin_url('options-general.php?page=bluem'); ?>" class="nav-tab
        <?php if ($tab===null) {
        echo "nav-tab-active";
    } ?>
        ">
        <span class="dashicons dashicons-admin-settings"></span>
            Algemeen
        </a>

        <?php if (bluem_module_enabled('mandates')) { ?>

        <a href="<?php echo admin_url('options-general.php?page=bluem#tab_mandates');?>" class="nav-tab
            <?php if ($tab==='mandates') {
        echo "nav-tab-active";
    } ?>
            ">
            <span class="dashicons dashicons-money"></span>
                        <!-- Digitaal  -->
                        Incassomachtigen
                         <!-- (eMandates) -->
        </a>
        <?php } ?>

        <?php if (bluem_module_enabled('payments')) { ?>

        <a href="<?php echo admin_url('options-general.php?page=bluem#tab_payments');?>" class="nav-tab
            <?php if ($tab==='payments') {
        echo "nav-tab-active";
    } ?>
            ">

            <span class="dashicons dashicons-money-alt"></span>
            iDEAL
             <!-- (ePayments) -->
        </a>
        <?php } ?>

        <?php if (bluem_module_enabled('idin')) { ?>

        <a href="<?php echo admin_url('options-general.php?page=bluem#tab_idin');?>" class="nav-tab
            <?php if ($tab==='idin') {
        echo "nav-tab-active";
    } ?>
            ">
            <span class="dashicons dashicons-businessperson"></span>
            Identiteit
            <!-- iDIN (Identity) -->
        </a>
        <?php } ?>

        <a href="<?php echo admin_url('admin.php?page=bluem_admin_requests_view'); ?>" class="nav-tab">
        <span class="dashicons dashicons-database-view"></span>
            Verzoeken
             <!-- overzicht -->
        </a>
        <a href="mailto:d.rijpkema@bluem.nl?subject=Bluem+Wordpress+Plugin" class="nav-tab" target="_blank">
        <span class="dashicons dashicons-editor-help"></span>
        Ondersteuning via e-mail
        </a>
    </nav>


    </div>
    <div class="bluem-settings">

        <form action="options.php" method="post">

            <?php
        settings_fields('bluem_woocommerce_modules_options');
        do_settings_sections('bluem_woocommerce_modules'); ?>
            
            <?php

            settings_fields('bluem_woocommerce_options');
            do_settings_sections('bluem_woocommerce'); ?>


            <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>" />
        </form>
<?php
bluem_render_footer(false); ?>

    </div>
</div>
<?php
}


function bluem_woocommerce_general_settings_section()
{
    echo '<p>Hier kan je alle belangrijke gegevens instellen rondom Bluem algemeen. <br>
    <span class="dashicons dashicons-unlock"></span>
    Let op:
    Je hebt een geactiveerde account nodig bij Bluem.
    De gegevens die je ontvangt via e-mail kan je hieronder
    en per specifiek onderdeel invullen.
    </p>';
    // Lees de readme bij de plug-in voor meer informatie.
    bluem_render_footer(false);
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


        add_settings_field(
            "suppress_warning",
            _("In admin omgeving waarschuwen als plugin nog niet goed is ingesteld"),
            "bluem_woocommerce_modules_render_suppress_warning",
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


    if (bluem_module_enabled('mandates')) {
        add_settings_section('bluem_woocommerce_mandates_section', 'Digitale Incassomachtiging instellingen', 'bluem_woocommerce_mandates_settings_section', 'bluem_woocommerce');

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

    if (bluem_module_enabled('payments')) {
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

    if (bluem_module_enabled('idin')) {
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


<select class='form-control' id='bluem_woocommerce_settings_<?php echo $key; ?>'
    name='bluem_woocommerce_options[<?php echo $key; ?>]'>
    <?php
            foreach ($field['options'] as $option_value => $option_name) {
                ?>
    <option value="<?php echo $option_value; ?>" <?php if (isset($values[$key]) && $values[$key] !== "" && $option_value == $values[$key]) {
                    echo "selected='selected'";
                } ?>><?php echo $option_name; ?></option>
    <?php
            } ?>
</select>
<?php
    } elseif ($field['type'] == "bool") {
        // var_dump($values[$key]);?>
<div class="form-check form-check-inline">
    <label class="form-check-label" for="<?php echo $key; ?>_1">
        <input class="form-check-input" type="radio"
        name="bluem_woocommerce_options[<?php echo $key; ?>]"
        id="<?php echo $key; ?>_1" value="1"
        <?php if (isset($values[$key]) && $values[$key]=="1") {
            echo "checked";
        } elseif ($field['default'] =="1") {
            echo "checked";
        } ?>
        >
         Ja
    </label>
</div>
<div class="form-check form-check-inline">
    <label class="form-check-label" for="<?php echo $key; ?>_0">
        <input class="form-check-input" type="radio"
        name="bluem_woocommerce_options[<?php echo $key; ?>]"
        id="<?php echo $key; ?>_0" value="0"
        <?php if (isset($values[$key]) && $values[$key]=="0") {
            echo "checked";
        } elseif ($field['default'] =="0") {
            echo "checked";
        } ?>
        >
         Nee
    </label>
</div>
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
        } ?>
<input class='bluem-form-control' id='bluem_woocommerce_settings_<?php echo $key; ?>'
    name='bluem_woocommerce_options[<?php echo $key; ?>]'
    value='<?php echo(isset($values[$key]) ? esc_attr($values[$key]) : $field['default']); ?>'
    <?php foreach ($attrs as $akey => $aval) {
            echo "$akey='$aval' ";
        } ?> />
<?php
    } ?>

<?php if (isset($field['description']) && $field['description'] !== "") {
        ?>

<br><label style='color:ddd;'
    for='bluem_woocommerce_settings_<?php echo $key; ?>'><?php echo $field['description']; ?></label>
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
            [
                'test' => 'Test',
                'prod' => "Productie (live)",
            ]
            // acceptance eventueel later toevoegen
        ],
        'senderID' => [
            'key' => 'senderID',
            'title' => 'bluem_senderID',
            'name' => 'Bluem Sender ID',
            'description' => 'Het sender ID, uitgegeven door Bluem. Begint met een S, gevolgd door een getal.',
            'default' => ""
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
            [
                '0' => "WooCommerce wel gebruiken",
                '1' => 'WooCommerce NIET gebruiken'
            ]
        ]
    ];
}



function bluem_woocommerce_get_config()
{
    $bluem_options = bluem_woocommerce_get_core_options();


    if (function_exists('bluem_woocommerce_get_mandates_options')) {
        $bluem_options = array_merge(
            $bluem_options,
            bluem_woocommerce_get_mandates_options()
        );
    }
    if (function_exists('bluem_woocommerce_get_idin_options')) {
        $bluem_options = array_merge(
            $bluem_options,
            bluem_woocommerce_get_idin_options()
        );
    }
    if (function_exists('bluem_woocommerce_get_payments_options')) {
        $bluem_options = array_merge(
            $bluem_options,
            bluem_woocommerce_get_payments_options()
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
    echo '
    <p>
    Zet hier gemakkelijk    de de diensten aan of uit die jouw website wel of niet nodig heeft,
    zodat je efficiënt kan werken.</p>';
}

function bluem_woocommerce_modules_render_mandates_activation()
{
    bluem_woocommerce_modules_render_generic_activation("mandates");
}

function bluem_woocommerce_modules_render_payments_activation()
{
    bluem_woocommerce_modules_render_generic_activation("payments");
}

function bluem_woocommerce_modules_render_idin_activation()
{
    bluem_woocommerce_modules_render_generic_activation("idin");
}

function bluem_woocommerce_modules_render_suppress_warning()
{
    bluem_woocommerce_modules_render_generic_activation("suppress_warning");
}


function bluem_woocommerce_settings_render_suppress_woo()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_option('suppress_woo')
    );
}

function bluem_woocommerce_modules_render_generic_activation($module)
{
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
    if ($bluem_options!==false
        && !isset($bluem_options["{$module}_enabled"])
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
    background:#fff; max-width:500px;'>".bluem_get_bluem_logo_html(48);
}
/**
 * Retrieve footer HTML for error/message prompt. Can include a simple link back to the webshop home URL.
 *
 * @param Bool $include_link
 * @return String
 */
function bluem_woocommerce_simplefooter(Bool $include_link = true): String
{
    return (
        $include_link
        ? "<p><a href='" . home_url() . "' target='_self' style='text-decoration:none;'>Ga terug</a></p>"
        : ""
    ) . "</div></body></html>";
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
    echo bluem_woocommerce_simplefooter(
        $include_link
    );
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
            <tr><?php
                foreach ($value as $kkey => $vvalue) { ?>
                    <th>
                        <?php echo $kkey; ?>
                    </th><?php
                } ?>
            </tr><?php
        } ?>
        <tr>
            <?php
            foreach ($value as $kkey => $vvalue) { ?>
                <td>
                    <?php echo $vvalue; ?>
                </td><?php
            } ?>
        </tr><?php
        $i++;
    } ?>
</table><?php
}

// Reference: https://www.wpbeginner.com/wp-tutorials/how-to-add-admin-notices-in-wordpress/
add_action('admin_notices', 'bluem_setup_incomplete');
function bluem_setup_incomplete()
{
    if (!is_admin()) {
        return;
    }

    $options = get_option('bluem_woocommerce_options');

    if ($options !== false && !bluem_module_enabled('suppress_warning')) {
        return;
    }

    if ($options == false) {
        $messages[] = "Account gegevens missen";
    } else {
        $valid_setup = true;
        $messages = [];
        if (!array_key_exists('senderID', $options)
        || (array_key_exists('senderID', $options)
        && $options['senderID'] === "")
        ) {
            $messages[] = "SenderID mist";
            $valid_setup = false;
        }
        if (!array_key_exists('test_accessToken', $options)
        || (array_key_exists('test_accessToken', $options)
        && $options['test_accessToken'] === "")
        ) {
            $messages[] = "Test accessToken mist";
            $valid_setup = false;
        }

        if (isset($options['environment'])
        && $options['environment'] == "prod"
        && (
            !array_key_exists('production_accessToken', $options)
        || (array_key_exists('production_accessToken', $options)
        && $options['production_accessToken'] === "")
        )
        ) {
            $messages[] = "Production accessToken mist";
            $valid_setup = false;
        }


        if (bluem_module_enabled('mandates')
        && (
            !array_key_exists('brandID', $options)
            || (
                array_key_exists('brandID', $options)
                && $options['brandID'] === ""
            )
        )
                ) {
            $messages[] = "eMandates brandID mist";
            $valid_setup = false;
        }

        if (bluem_module_enabled('idin')
            && (!array_key_exists('IDINBrandID', $options)
            || (array_key_exists('IDINBrandID', $options)
            && $options['IDINBrandID'] === ""))
        ) {
            $messages[] = "iDIN BrandID  mist";
            $valid_setup = false;
        }

        // @todo add more checks

        if ($valid_setup) {
            return;
        }
    }

    echo '<div class="notice notice-warning is-dismissible">
        <p><strong>De Bluem integratie is nog niet volledig ingesteld:</strong><br>
        ';
    foreach ($messages as $m) {
        echo "$m<br>";
    }
    echo '
        </p>';

    if (get_admin_page_title() !== "Bluem") {
        echo '
            <p><a href="
            '.admin_url('options-general.php?page=bluem').'
            ">
            Klik hier om de plugin verder in te stellen.
            </a>
            </p>';
    }

    echo '</div>';

    // @todo: add warning when Payments Bluem module is activated but the Payments WooCommerce payment gateway is not activated yet - with a link to activate it
    // wp-admin/admin.php?page=wc-settings&tab=checkout
    // 127.0.0.1/wp-admin/admin.php?page=wc-settings&tab=checkout&section=bluem_payments
    // enable button on payment gateway settings is superfluous

    // @todo: add warning when Mandates Bluem module is activated but the Mandates WooCommerce payment gateway is not activated yet - with a link to activate it
    // wp-admin/admin.php?page=wc-settings&tab=checkout
}
