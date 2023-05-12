<?php
/**
 * Plugin Name: Bluem ePayments, iDIN and eMandates integration for shortcodes and WooCommerce checkout
 * Version: 1.3.17.5
 * Plugin URI: https://wordpress.org/plugins/bluem
 * Description: Bluem integration for WordPress and WooCommerce to facilitate Bluem services inside your site. Payments and eMandates payment gateway and iDIN identity verification
 * Author: Bluem Payment Services
 * Author URI: https://bluem.nl
 * Requires at least: 5.0
 * Tested up to: 6.2
 *
 * WC requires at least: 5.0.0
 * WC tested up to: 7.7.0
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
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $bluem_db_version;
$bluem_db_version = 1.3;

const BLUEM_WOOCOMMERCE_MANUAL_URL = "https://codexology.notion.site/Bluem-voor-WordPress-en-WooCommerce-Handleiding-9e2df5c5254a4b8f9cbd272fae641f5e";

// @todo require certain minimum php version before installing - force this before installing or updating the plugin
require_once __DIR__ . '/bluem-compatibility.php';

// get composer dependencies
require __DIR__ . '/vendor/autoload.php';

if ( ! defined( "BLUEM_LOCAL_DATE_FORMAT" ) ) {
    define( "BLUEM_LOCAL_DATE_FORMAT", "Y-m-d\TH:i:s" );
}

// get specific gateways and helpers
if ( bluem_module_enabled( 'mandates' ) ) {
    include_once __DIR__ . '/bluem-mandates.php';
    include_once __DIR__ . '/bluem-mandates-instant.php';
    include_once __DIR__ . '/bluem-mandates-shortcode.php';
}
if ( bluem_module_enabled( 'payments' ) ) {
    include_once __DIR__ . '/bluem-payments.php';
}

if ( bluem_module_enabled( 'idin' ) ) {
    include_once __DIR__ . '/bluem-idin.php';
}

// database functions
require_once __DIR__ . '/bluem-db.php';

// interface and display functions
require_once __DIR__ . '/bluem-interface.php';

// integrations with external plugins
require_once __DIR__ . '/bluem-integrations.php';

/**
 * Check if WooCommerce is activated
 */
if ( ! function_exists( 'is_woocommerce_activated' ) ) {
    function is_woocommerce_activated() {
        $active_plugins = get_option( 'active_plugins' );

        if ( in_array('woocommerce/woocommerce.php', $active_plugins) ) {
            return true;
        }
        return false;
    }
}

/**
 * Check if Contact Form 7 is activated
 */
if ( ! function_exists( 'is_contactform7_activated' ) ) {
    function is_contactform7_activated() {
        $active_plugins = get_option( 'active_plugins' );

        if ( in_array('contact-form-7/wp-contact-form-7.php', $active_plugins) ) {
            return true;
        }
        return false;
    }
}

/**
 * Check if Gravity Forms is activated
 */
if ( ! function_exists( 'is_gravityforms_activated' ) ) {
    function is_gravityforms_activated() {
        $active_plugins = get_option( 'active_plugins' );

        if ( in_array('gravityforms', $active_plugins) ) {
            return true;
        }
        return false;
    }
}

/**
 * Check if Permalinks is enabled
 */
if ( ! function_exists( 'is_permalinks_enabled' ) ) {
    function is_permalinks_enabled() {
        $structure = get_option('permalink_structure');

        if (!empty($structure)) {
            return true;
        }
        return false;
    }
}

/**
 * Check if WooCommerce is active
 **/
if ( ! is_woocommerce_activated() ) {
    // No WooCommerce module found!
    add_action( 'admin_notices', 'bluem_woocommerce_no_woocommerce_notice' );
}

/**
 * Check if Permalinks is enabled
 **/
if ( ! is_permalinks_enabled() ) {
    // No WooCommerce module found!
    add_action( 'admin_notices', 'bluem_woocommerce_no_permalinks_notice' );
}

// Update CSS within in Admin
function bluem_add_admin_style() {
    wp_enqueue_style( 'admin-styles', plugin_dir_url( __FILE__ ) . '/css/admin.css' );
}

add_action( 'admin_enqueue_scripts', 'bluem_add_admin_style' );

// Update CSS within frontend
function bluem_add_front_style() {
    wp_register_style(
        'bluem_woo_front_styles',
        plugin_dir_url( __FILE__ ) . '/css/front.css'
    );
    wp_enqueue_style( 'bluem_woo_front_styles' );
}

add_action( 'wp_enqueue_scripts', 'bluem_add_front_style' );

// https://www.wpbeginner.com/wp-tutorials/how-to-add-admin-notices-in-wordpress/
function bluem_woocommerce_no_woocommerce_notice() {
    if ( is_admin() ) {
        $bluem_options = get_option( 'bluem_woocommerce_options' );
        if ( ! isset( $bluem_options['suppress_woo'] ) || $bluem_options['suppress_woo'] == "0" ) {
            echo '<div class="notice notice-warning is-dismissible">
            <p><span class="dashicons dashicons-warning"></span> De Bluem integratie is grotendeels afhankelijk van WooCommerce - installeer en/of activeer deze plug-in.<br>
            Je kan deze melding en WooCommerce gerelateerde functionaliteiten ook uitzetten bij de <a href="' . admin_url( 'admin.php?page=bluem-settings' ) . '">instellingen</a>.</p>
            </div>';
        }
    }
}

function bluem_woocommerce_no_permalinks_notice() {
    if ( is_admin() ) {
        echo '<div class="notice notice-warning is-dismissible">
        <p><span class="dashicons dashicons-warning"></span> De Bluem integratie is vanwege de routing afhankelijk van de WordPress Permalink instelling.<br>
        Selecteer een optie BEHALVE \'Eenvoudig\' bij de Permalink <a href="' . admin_url( 'options-permalink.php' ) . '">instellingen</a>.</p>
        </div>';
    }
}

/* ******** SETTINGS *********** */
/**
 * Settings page initialisation
 *
 * @return void
 */
function bluem_woocommerce_settings_handler() {
    add_options_page(
        'Bluem',
        'Bluem',
        'manage_options',
        'bluem',
        'bluem_settings_page'
    );
}

//add_action( 'admin_menu', 'bluem_woocommerce_settings_handler' );

/**
 * Register the necessary administrative pages in the WordPress back-end.
 *
 * @return void
 */
function bluem_register_menu() {
    add_menu_page(
        "Bluem",
        "Bluem",
        "manage_options",
        "bluem-admin",
        "bluem_home",
        plugins_url('bluem/assets/bluem/icon.png') //'dashicons-money'
    );

    add_submenu_page(
        'bluem-admin',
        'Transacties',
        'Transacties',
        'manage_options',
        'bluem-transactions',
        'bluem_requests_view'
    );

    add_submenu_page(
        'bluem-admin',
        'Instellingen',
        'Instellingen',
        'manage_options',
        'bluem-settings',
        'bluem_settings_page'
    );

    add_submenu_page(
        'bluem-admin',
        'Import / export',
        'Import / export',
        'manage_options',
        'bluem-importexport',
        'bluem_admin_importexport'
    );
}

add_action( 'admin_menu', 'bluem_register_menu', 9 );

function bluem_home() {
    include_once 'views/home.php';
}

function bluem_requests_view() {
    if ( isset( $_GET['request_id'] ) && $_GET['request_id'] !== "" ) {
        if ( isset( $_GET['admin_action'] ) && $_GET['admin_action'] == "delete" ) {
            bluem_db_delete_request_by_id( $_GET['request_id'] );
            wp_redirect(
                admin_url( "admin.php?page=bluem-transactions" )
            );
        } else {
            bluem_requests_view_request();
        }
    } else {
        bluem_requests_view_all();
    }
}

function bluem_requests_view_request() {
    global $wpdb;
    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';

    $id = $_GET['request_id'];

    $request_query = $wpdb->get_results( "SELECT * FROM `bluem_requests` WHERE `id` = $id LIMIT 1" );
    if ( count( $request_query ) == 0 ) {
        bluem_requests_view_all();

        return;
    }

    $request = (object) $request_query[0];
    $request_author = get_user_by( 'id', $request->user_id );

    $links = bluem_db_get_links_for_request( $request->id );

    $logs = bluem_db_get_logs_for_request( $id );

    include_once 'views/request.php';
}

function bluem_requests_view_all() {
    global $wpdb;
    // date_default_timezone_set('Europe/Amsterdam');
    // $wpdb->time_zone = 'Europe/Amsterdam';

    $_requests = $wpdb->get_results(
        "SELECT *
        FROM `bluem_requests`
        ORDER BY `type` , `timestamp` DESC"
    );

    $requests['identity'] = [];
    $requests['ideal'] = [];
    $requests['creditcard'] = [];
    $requests['paypal'] = [];
    $requests['mandates'] = [];

    // @todo Allow filtering on only one type

    foreach ( $_requests as $_r ) {
        $requests[ ($_r->type == 'payments' ? 'ideal' : $_r->type) ][] = $_r;
    }

    $users_by_id = [];

    $users = get_users();

    foreach ( $users as $user ) {
        $users_by_id[ $user->ID ] = $user;
    }

    include_once 'views/requests.php';
}

// @todo Deprecate this
function bluem_woocommerce_tab() {
    $default_tab = null;

    return isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : $default_tab;
}

/**
 * Settings page display
 *
 * @return void
 */
function bluem_settings_page() {

    //Get the active tab from the GET param
    $tab = bluem_woocommerce_tab(); ?>

    <div class="wrap">
        <h1>
            <?php echo bluem_get_bluem_logo_html( 48 ); ?>
            <?php echo esc_html( get_admin_page_title() ); ?>
        </h1>

        <?php bluem_render_nav_header('settings');?>

        <nav class="nav-tab-wrapper">
            <a href="<?php echo $_SERVER['REQUEST_URI']; ?>#tab_general"
               class="nav-tab
    <?php if ( $tab === null ) {
                   echo "nav-tab-active";
               } ?>
    ">
                <span class="dashicons dashicons-admin-settings"></span>
                Algemeen
            </a>

            <?php if ( bluem_module_enabled( 'mandates' ) ) { ?>

                <a href="<?php echo $_SERVER['REQUEST_URI'] . '#tab_mandates'; ?>"
                   class="nav-tab
        <?php if ( $tab === 'mandates' ) {
                       echo "nav-tab-active";
                   } ?>
        ">
                    <span class="dashicons dashicons-money"></span>

                    Incassomachtigen

                </a>
            <?php } ?>

            <?php if ( bluem_module_enabled( 'payments' ) ) { ?>

                <a href="<?php echo $_SERVER['REQUEST_URI'] . '#tab_payments'; ?>"
                   class="nav-tab
        <?php if ( $tab === 'payments' ) {
                       echo "nav-tab-active";
                   } ?>
        ">

                    <span class="dashicons dashicons-money-alt"></span>
                    ePayments
                </a>
            <?php } ?>

            <?php if ( bluem_module_enabled( 'idin' ) ) { ?>

                <a href="<?php echo $_SERVER['REQUEST_URI'] . '#tab_idin'; ?>"
                   class="nav-tab
        <?php if ( $tab === 'idin' ) {
                       echo "nav-tab-active";
                   } ?>
        ">
                    <span class="dashicons dashicons-businessperson"></span>
                    Identiteit
                </a>
            <?php } ?>

            <a href="<?php echo $_SERVER['REQUEST_URI'] . '#tab_integrations'; ?>"
               class="nav-tab
    <?php if ( $tab === 'integrations' ) {
                   echo "nav-tab-active";
               } ?>
    ">
                <span class="dashicons dashicons-admin-plugins"></span>
                Integraties
            </a>
        </nav>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'bluem_woocommerce_modules_options' );
            do_settings_sections( 'bluem_woocommerce_modules' );
            settings_fields( 'bluem_woocommerce_options' );
            do_settings_sections( 'bluem_woocommerce' ); ?>

            <input name="submit"
                   class="button button-primary"
                   type="submit" value="<?php esc_attr_e( 'Save' ); ?>"
            />
        </form>
        <?php bluem_render_footer( false ); ?>
    </div>
    <?php
}

function bluem_woocommerce_general_settings_section() {
    // Hier kan je alle belangrijke gegevens instellen rondom Bluem algemeen. <br>
    echo '<p><a id="tab_general"></a>
    <div class="notice notice-warning inline" style="padding:10px;">
    <span class="dashicons dashicons-unlock"></span>
    Let op:
    Je hebt een geactiveerde account nodig bij Bluem.
    De gegevens die je ontvangt via e-mail kan je hieronder
    en per specifiek onderdeel invullen.
    </div>
    </p>';

    echo '<p>
    <div class="notice notice-info inline" style="padding:10px;">
    Heb je de plugin al geinstalleerd op een andere website?<br />
    Gebruik dan de import / export functie om dezelfde instellingen
    en voorkeuren in te laden.<br />';
    echo 'Ga naar <a href="' . admin_url( 'admin.php?page=bluem-importexport' ) . '"
     class="">instellingen importeren of exporteren</a>.</div>';
    echo '</p>';
}

function bluem_woocommerce_register_settings() {
    $tab = bluem_woocommerce_tab();

    register_setting(
        'bluem_woocommerce_options',
        'bluem_woocommerce_options',
        'bluem_woocommerce_options_validate'
    );

    if ( is_null( $tab ) ) {
        register_setting(
            'bluem_woocommerce_options',
            'bluem_woocommerce_modules_options',
            'bluem_woocommerce_modules_options_validate'
        );
        add_settings_section(
            'bluem_woocommerce_modules_section',
            _( 'Beheer onderdelen van deze plug-in' ),
            'bluem_woocommerce_modules_settings_section',
            'bluem_woocommerce'
        );
        add_settings_field(
            "mandates_enabled",
            _( "Mandates actief" ),
            "bluem_woocommerce_modules_render_mandates_activation",
            "bluem_woocommerce",
            "bluem_woocommerce_modules_section"
        );
        add_settings_field(
            "payments_enabled",
            _( "ePayments actief" ),
            "bluem_woocommerce_modules_render_payments_activation",
            "bluem_woocommerce",
            "bluem_woocommerce_modules_section"
        );
        add_settings_field(
            "idin_enabled",
            _( "iDIN actief" ),
            "bluem_woocommerce_modules_render_idin_activation",
            "bluem_woocommerce",
            "bluem_woocommerce_modules_section"
        );
        add_settings_field(
            "suppress_warning",
            _( "In admin omgeving waarschuwen als plugin nog niet goed is ingesteld" ),
            "bluem_woocommerce_modules_render_suppress_warning",
            "bluem_woocommerce",
            "bluem_woocommerce_modules_section"
        );
        add_settings_section(
            'bluem_woocommerce_general_section',
            '<span class="dashicons dashicons-admin-settings"></span> Algemene instellingen',
            'bluem_woocommerce_general_settings_section',
            'bluem_woocommerce'
        );
        $general_settings = bluem_woocommerce_get_core_options();
        foreach ( $general_settings as $key => $ms ) {
            add_settings_field(
                $key,
                $ms['name'],
                "bluem_woocommerce_settings_render_" . $key,
                "bluem_woocommerce",
                "bluem_woocommerce_general_section"
            );
        }
    }

    if ( bluem_module_enabled( 'mandates' ) ) {
        add_settings_section(
            'bluem_woocommerce_mandates_section',
            '<span class="dashicons dashicons-money"></span> Digitale Incassomachtiging instellingen',
            'bluem_woocommerce_mandates_settings_section',
            'bluem_woocommerce'
        );

        $mandates_settings = bluem_woocommerce_get_mandates_options();
        if ( is_array( $mandates_settings ) && count( $mandates_settings ) > 0 ) {
            foreach ( $mandates_settings as $key => $ms ) {
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

    if ( bluem_module_enabled( 'payments' ) ) {
        add_settings_section(
            'bluem_woocommerce_payments_section',
            '<span class="dashicons dashicons-money-alt"></span> ePayments payments instellingen',
            'bluem_woocommerce_payments_settings_section',
            'bluem_woocommerce'
        );

        $payments_settings = bluem_woocommerce_get_payments_options();
        if ( is_array( $payments_settings ) && count( $payments_settings ) > 0 ) {
            foreach ( $payments_settings as $key => $ms ) {
                $key_name = "bluem_woocommerce_settings_render_" . $key;
                add_settings_field(
                    $key,
                    $ms['name'],
                    $key_name,
                    "bluem_woocommerce",
                    "bluem_woocommerce_payments_section"
                );
            }
        }
    }

    if ( bluem_module_enabled( 'idin' ) ) {
        add_settings_section(
            'bluem_woocommerce_idin_section',
            '<span class="dashicons dashicons-admin-users"></span> iDIN instellingen',
            'bluem_woocommerce_idin_settings_section',
            'bluem_woocommerce'
        );

        $idin_settings = bluem_woocommerce_get_idin_options();
        if ( is_array( $idin_settings ) && count( $idin_settings ) > 0 ) {
            foreach ( $idin_settings as $key => $ms ) {
                $key_name = "bluem_woocommerce_settings_render_" . $key;
                add_settings_field(
                    $key,
                    $ms['name'],
                    $key_name,
                    "bluem_woocommerce",
                    "bluem_woocommerce_idin_section"
                );
            }
        }
    }

    add_settings_section(
        'bluem_woocommerce_integrations_section',
        '<span class="dashicons dashicons-admin-plugins"></span> Integratie instellingen',
        'bluem_woocommerce_integrations_settings_section',
        'bluem_woocommerce'
    );

    $integrations_settings = bluem_woocommerce_get_integrations_options();
    if ( is_array( $integrations_settings ) && count( $integrations_settings ) > 0 ) {
        foreach ( $integrations_settings as $key => $ms ) {
            $key_name = "bluem_woocommerce_settings_render_" . $key;
            add_settings_field(
                $key,
                $ms['name'],
                $key_name,
                "bluem_woocommerce",
                "bluem_woocommerce_integrations_section"
            );
        }
    }
}

add_action( 'admin_init', 'bluem_woocommerce_register_settings' );

add_action( 'show_user_profile', 'bluem_woocommerce_show_general_profile_fields', 1 );

function bluem_woocommerce_show_general_profile_fields() {
    // @todo: create template
    ?>
    <h2>
        <?php echo bluem_get_bluem_logo_html( 48 ); ?>
        <!-- Identiteit verificatie via Bluem -->
        Bluem onderdelen
    </h2>
    <table class="form-table">

        <tr>
            <th>
                Configureren?
            </th>
            <td>
                Ga naar de
                <a href="<?php echo home_url( "wp-admin/admin.php?page=bluem-settings" ); ?>">
                    instellingen</a> om het gedrag van elk Bluem onderdeel te wijzigen.
            </td>
        </tr>
    </table>
    <?php
}

// Settings functions
function bluem_woocommerce_get_option( $key ) {
    $options = bluem_woocommerce_get_core_options();

    if ( array_key_exists( $key, $options ) ) {
        return $options[ $key ];
    }

    return false;
}

function bluem_woocommerce_settings_render_environment() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_option( 'environment' )
    );
}

function bluem_woocommerce_settings_render_senderID() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_option( 'senderID' )
    );
}

function bluem_woocommerce_settings_render_test_accessToken() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_option( 'test_accessToken' )
    );
}

function bluem_woocommerce_settings_render_production_accessToken() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_option( 'production_accessToken' )
    );
}

function bluem_woocommerce_settings_render_expectedReturnStatus() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_option( 'expectedReturnStatus' )
    );
}

function bluem_woocommerce_settings_render_input( $field ) {
    if ( $field === false ) {
        return;
    }
    $values = get_option( 'bluem_woocommerce_options' );
    $key    = $field['key'];

    // fallback
    if ( ! isset( $field['type'] ) ) {
        $field['type'] = "text";
    }

    if ( $field['type'] == "select" ) {

        // @todo stop using inline html and use a template engine here, like latte
        ?>


        <select class='form-control' id='bluem_woocommerce_settings_<?php echo $key; ?>'
                name='bluem_woocommerce_options[<?php echo $key; ?>]'>
            <?php
            foreach ( $field['options'] as $option_value => $option_name ) {
                ?>
                <option
                    value="<?php echo $option_value; ?>" <?php if ( isset( $values[ $key ] ) && $values[ $key ] !== "" && $option_value == $values[ $key ] ) {
                    echo "selected='selected'";
                } ?>><?php echo $option_name; ?></option>
                <?php
            } ?>
        </select>
        <?php
    } elseif ( $field['type'] == "bool" ) {
        ?>
        <div class="form-check form-check-inline">
            <label class="form-check-label" for="<?php echo $key; ?>_1">
                <input class="form-check-input" type="radio"
                       name="bluem_woocommerce_options[<?php echo $key; ?>]"
                       id="<?php echo $key; ?>_1" value="1"
                    <?php if ( isset( $values[ $key ] ) && $values[ $key ] == "1" ) {
                        echo "checked";
                    } elseif ( $field['default'] == "1" ) {
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
                    <?php if ( isset( $values[ $key ] ) && $values[ $key ] == "0" ) {
                        echo "checked";
                    } elseif ( $field['default'] == "0" ) {
                        echo "checked";
                    } ?>
                >
                Nee
            </label>
        </div>
        <?php
    } elseif ( $field['type'] == "textarea" ) {
        $attrs = [
            'id'    => "bluem_woocommerce_settings_$key",
            'class' => "bluem-form-control",
            'name'  => "bluem_woocommerce_options[$key]",
        ];
        ?>
        <textarea
    <?php foreach ( $attrs as $akey => $aval ) {
        echo "$akey='$aval' ";
    } ?>><?php echo( isset( $values[ $key ] ) ? esc_attr( $values[ $key ] ) : $field['default'] ); ?></textarea>
        <?php
    } else {
        $attrs = [];
        if ( $field['type'] == "password" ) {
            $attrs['type'] = "password";
        } elseif ( $field['type'] == "number" ) {
            $attrs['type'] = "number";
            if ( isset( $field['attrs'] ) ) {
                $attrs = array_merge( $attrs, $field['attrs'] );
            }
        } else {
            $attrs['type'] = "text";
        } ?>
        <input class='bluem-form-control' id='bluem_woocommerce_settings_<?php echo $key; ?>'
               name='bluem_woocommerce_options[<?php echo $key; ?>]'
               value='<?php echo( isset( $values[ $key ] ) ? esc_attr( $values[ $key ] ) : $field['default'] ); ?>'
            <?php foreach ( $attrs as $akey => $aval ) {
                echo "$akey='$aval' ";
            } ?> />
        <?php
    } ?>

    <?php if ( isset( $field['description'] ) && $field['description'] !== "" ) {
        ?>

        <br><label style='color:#333;'
                   for='bluem_woocommerce_settings_<?php echo $key; ?>'><?php echo $field['description']; ?></label>
        <?php
    }
}

/**
 * @return array
 */
function bluem_woocommerce_get_core_options(): array {
    return [
        'environment' => [
            'key'         => 'environment',
            'title'       => 'bluem_environment',
            'name'        => 'Kies de actieve modus',
            'description' => 'Vul hier welke modus je wilt gebruiken: prod, test of acc in voor productie (live), test of acceptance omgeving.',
            'type'        => 'select',
            'default'     => 'test',
            'options'     =>
                [
                    'test' => 'Test',
                    'prod' => "Productie (live)",
                ]
            // acceptance eventueel later toevoegen
        ],
        'senderID'    => [
            'key'         => 'senderID',
            'title'       => 'bluem_senderID',
            'name'        => 'Bluem Sender ID',
            'description' => 'Het sender ID, uitgegeven door Bluem. Begint met een S, gevolgd door een getal.',
            'default'     => ""
        ],
        'test_accessToken'       => [
            'key'         => 'test_accessToken',
            'title'       => 'bluem_test_accessToken',
            'type'        => 'password',
            'name'        => 'Access Token voor Testen',
            'description' => 'Het access token om met Bluem te kunnen communiceren, voor de test omgeving',
            'default'     => ''
        ],
        'production_accessToken' => [
            'key'         => 'production_accessToken',
            'title'       => 'bluem_production_accessToken',
            'type'        => 'password',
            'name'        => 'Access Token voor Productie',
            'description' => 'Het access token om met Bluem te kunnen communiceren, voor de productie omgeving',
            'default'     => ''
        ],
        'expectedReturnStatus'   => [
            'key'         => 'expectedReturnStatus',
            'title'       => 'bluem_expectedReturnStatus',
            'name'        => 'Test modus return status',
            'description' => 'Welke status wil je terug krijgen voor een TEST transaction of status request? Mogelijke waarden: none, success, cancelled, expired, failure, open, pending',
            'default'     => 'success',
            'type'        => 'select',
            'options'     => [
                'success'   => 'success',
                'cancelled' => 'cancelled',
                'expired'   => 'expired',
                'failure'   => 'failure',
                'open'      => 'open',
                'pending'   => 'pending',
                'none'      => 'none'
            ]
        ],
        'suppress_woo'           => [
            'key'         => 'suppress_woo',
            'title'       => 'bluem_suppress_woo',
            'name'        => 'WooCommerce gebruiken?',
            'description' => 'Zet dit op "WooCommerce niet gebruiken" als je deze plug-in wilt gebruiken op deze site zonder WooCommerce functionaliteiten.',
            'type'        => 'select',
            'default'     => '0',
            'options'     =>
                [
                    '0' => "WooCommerce wel gebruiken",
                    '1' => 'WooCommerce NIET gebruiken'
                ]
        ],
        'error_reporting_email'          => [
            'key'         => 'error_reporting_email',
            'title'       => 'bluem_error_reporting_email',
            'name'        => 'Rapporteer errors bij de developers via een automatische email',
            'description' => "Help ons snel problemen oplossen en downtime minimaliseren. Geef hier aan of je als de developers van Bluem  automatisch een notificatie e-mail wil sturen met technische details (geen persoonlijke informatie). Dit staat standaard aan, behalve als je dit expliciet uitzet. ",
            'type'        => 'select',
            'default'     => '1',
            'options'     => [
                '1' => 'Ja, stuur errors door naar de developers',
                '0' => 'Geen error reportage via e-mail',
            ],
        ],
        'transaction_notification_email' => [
            'key'         => 'transaction_notification_email',
            'title'       => 'bluem_transaction_notification_email',
            'name'        => 'E-mail notificatie voor website
                eigenaar bij elke nieuwe transactie?',
            'description' => "Geef hier aan of je als
                website-eigenaar automatisch een notificatie e-mail wil ontvangen met transactiedetails",
            'type'        => 'select',
            'default'     => '0',
            'options'     => [
                '0' => 'Geen e-mail notificatie (standaard)',
                '1' => 'Stuur notificatie voor elke transactie naar ' . get_option( 'admin_email' )
            ],
        ],
    ];
}

/**
 * Register the age verification attribute.
 */
function bluem_woocommerce_register_age_verification_attribute() {
    $args = array(
      'name'         => 'Age verification',
      'slug'         => 'age_verification',
      'type'         => 'select',
      'order_by'     => 'menu_order',
      'has_archives' => true,
    );
    register_taxonomy( 'pa_age_verification', 'product', $args );
}
add_action( 'woocommerce_attribute_registered', 'bluem_woocommerce_register_age_verification_attribute' );

/**
 * Add age verification field to admin product page.
 */
function bluem_woocommerce_add_age_verification_field() {
    global $product_object;

    // Get the saved value of the custom attribute
    $age_verification_value = $product_object->get_meta('pa_age_verification');

    // Set default value if the attribute value is not already set
    if (empty($age_verification_value)) {
        $age_verification_value = 'disable';
    }

    echo '<div class="options_group">';
    
    // Custom Attribute Field
    woocommerce_wp_select( array(
        'id' => 'age_verification',
        'label' => __('Age verification', 'bluem-woocommerce'),
        'placeholder' => '',
        'options' => array(
            'enable' => __('Enable', 'bluem-woocommerce'),
            'disable' => __('Disable', 'bluem-woocommerce'),
        ),
        'value' => $age_verification_value,
    ));
    
    echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'bluem_woocommerce_add_age_verification_field' );

/**
 * Save the age verification attribute value.
 */
function bluem_woocommerce_save_age_verification_values( $post_id ) {
    if ( 'product' !== get_post_type( $post_id ) ) {
        return;
    }
  
    if ( isset( $_POST['age_verification'] ) ) {
        $attribute_value = isset($_POST['age_verification']) ? sanitize_text_field($_POST['age_verification']) : '';
        update_post_meta( $post_id, 'pa_age_verification', $attribute_value );
    }
}
add_action( 'save_post', 'bluem_woocommerce_save_age_verification_values' );

/**
 * Error reporting email functionality
 * @return bool
 */
function bluem_error_report_email( $data = [] ): bool {
    $debug = false;

    $error_report_id = date( "Ymdhis" ) . '_' . rand( 0, 512 );

    $data = (object) $data;
    $data->error_report_id = $error_report_id;

    $settings = get_option( 'bluem_woocommerce_options' );

    // $data = bluem_db_get_request_by_id($request_id);
    // $pl = json_decode($data->payload);

    if ( is_null( $data ) ) {
        return false;
    }

    if ( ! isset( $settings['error_reporting_email'] )
         || $settings['error_reporting_email'] == 1
    ) {
        if ( $debug ) {
            echo "Sending error reporting email; Data:";
            var_dump( $data );
        }

        $author_name  = "Administratie van " . get_bloginfo( 'name' );
        $author_email = esc_attr(
            get_option( "admin_email" )
        );

        $to = "pluginsupport@bluem.nl";

        $subject = "[" . get_bloginfo( 'name' ) . "] ";
        $subject .= "Notificatie Error in Bluem ";

        $message = "<p>Error in Bluem plugin. $author_name <$author_email>,</p>";
        $message .= "<p>Data: <br>" . json_encode( $data ) . "</p>";

        ob_start();
        foreach ( $data as $k => $v ) {
            if ( is_null( $v ) ) {
                continue;
            }

            bluem_render_obj_row_recursive(
                "<strong>" . ucfirst( $k ) . "</strong>",
                $v
            );
        }
        $message_p = ob_get_clean();

        $message .= $message_p;
        $message .= "</p>";
        $message .= bluem_email_footer();

        $message = nl2br( $message );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        if ( $debug ) {
            bluem_email_debug( $to, $subject, $message, $headers );
        }

        $mailing = wp_mail( $to, $subject, $message, $headers );

        if ( $mailing ) {
            bluem_db_request_log( $error_report_id, "Sent error report mail to " . $to );
        }

        // or no mail sent

        return $mailing;
    }

    return false;
}

function bluem_email_footer(): string {
    return "<p>Ga naar de site op " . home_url() . " om dit verzoek in detail te bekijken.</p>";
}

function bluem_email_debug( $to, $subject, $message, $headers ) {
    echo "<HR> " . PHP_EOL;
    var_dump( $to );
    var_dump( $subject );
    echo "<HR> " . PHP_EOL;
    echo( $message );
    echo "<HR> " . PHP_EOL;
    var_dump( $headers );
    die();
}

/**
 * This function executes a notification mail, if this is set as such in the settings.
 *
 * @param $request_id
 *
 * @return bool
 */
function bluem_transaction_notification_email(
    $request_id
): bool {
    $debug = false;

    $settings = get_option( 'bluem_woocommerce_options' );

    $data = bluem_db_get_request_by_id( $request_id );
    if ( $data === false ) {
        return false;
    }
    $data = (object) $data;

    $pl = json_decode( $data->payload );

    if ( isset( $pl->sent_notification ) && $pl->sent_notification == "true" ) {
        return false;
    }

    if ( is_null( $data ) ) {
        return false;
    }

    if ( ! isset( $settings['transaction_notification_email'] )
         || $settings['transaction_notification_email'] == 1
    ) {
        if ( $debug ) {
            echo "Sending notification email for request. Data:";
            var_dump( $data );
        }

        $author_name = "administratie van " . get_bloginfo( 'name' );
        //get_the_author_meta('user_nicename');

        $to = esc_attr(
            get_option( "admin_email" )
        );

        $subject = "[" . get_bloginfo( 'name' ) . "] ";
        $subject .= "Notificatie Bluem " . ucfirst( $data->type ) . " verzoek › ID " . $data->transaction_id;
        if ( isset( $data->status ) ) {
            $subject .= " › status: $data->status ";
        }

        $message = "<p>Beste $author_name,</p>";
        $message .= "<p>Er is een nieuw Bluem " . ucfirst( $data->type ) . " verzoek verwerkt met de volgende gegevens:</p><p>";
        // $data->payload = json_decode($data->payload);

        ob_start();
        foreach ( $data as $k => $v ) {
            if ( $k == "payload" ) {
                echo "<br><strong>Meer details</strong>:<br>  Zie admin interface<br>";
                continue;
            }

            if ( is_null( $v ) ) {
                continue;
            }

            bluem_render_obj_row_recursive(
                "<strong>" . ucfirst( $k ) . "</strong>",
                $v
            );
        }
        $message_p = ob_get_clean();

        $message .= $message_p . "</p>";
        $message .= bluem_email_footer();

        $message = nl2br( $message );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        if ( $debug ) {
            bluem_email_debug( $to, $subject, $message, $headers );
        }
        $mailing = wp_mail( $to, $subject, $message, $headers );

        if ( $mailing ) {
            bluem_db_put_request_payload(
                $request_id,
                [
                    'sent_notification' => true
                ]
            );

            bluem_db_request_log( $request_id, "Sent notification mail to " . $to );
        }

        return $mailing;
    }

    return false;
}

function bluem_woocommerce_get_config(): Stdclass {
    $bluem_options = bluem_woocommerce_get_core_options();

    if ( function_exists( 'bluem_woocommerce_get_mandates_options' ) ) {
        $bluem_options = array_merge(
            $bluem_options,
            bluem_woocommerce_get_mandates_options()
        );
    }
    if ( function_exists( 'bluem_woocommerce_get_idin_options' ) ) {
        $bluem_options = array_merge(
            $bluem_options,
            bluem_woocommerce_get_idin_options()
        );
    }
    if ( function_exists( 'bluem_woocommerce_get_payments_options' ) ) {
        $bluem_options = array_merge(
            $bluem_options,
            bluem_woocommerce_get_payments_options()
        );
    }

    $bluem_options = array_merge(
        $bluem_options,
        bluem_woocommerce_get_integrations_options()
    );

    $config = new Stdclass();

    $values = get_option( 'bluem_woocommerce_options' );
    foreach ( $bluem_options as $key => $option ) {
        $config->$key = $values[ $key ] ?? ( $option['default'] ?? "" );
    }

    return $config;
}

function bluem_woocommerce_modules_settings_section() {
    echo '<p>Tip: Verhoog de efficiëntie door alleen de diensten te activeren die voor jouw website van toepassing zijn.</p>';
}

function bluem_woocommerce_modules_render_mandates_activation() {
    bluem_woocommerce_modules_render_generic_activation( "mandates" );
}

function bluem_woocommerce_modules_render_payments_activation() {
    bluem_woocommerce_modules_render_generic_activation( "payments" );
}

function bluem_woocommerce_modules_render_idin_activation() {
    bluem_woocommerce_modules_render_generic_activation( "idin" );
}

function bluem_woocommerce_modules_render_suppress_warning() {
    bluem_woocommerce_modules_render_generic_activation( "suppress_warning" );
}


function bluem_woocommerce_settings_render_suppress_woo() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_option( 'suppress_woo' )
    );
}

function bluem_woocommerce_settings_render_error_reporting_email() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_option( 'error_reporting_email' )
    );
}

function bluem_woocommerce_settings_render_transaction_notification_email() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_option( 'transaction_notification_email' )
    );
}

function bluem_woocommerce_modules_render_generic_activation( $module ) {
    $field = [
        'key'         => "{$module}_enabled",
        'default'     => "",
        'description' => "",
        'options'     => [
            ''  => '(Maak een selectie)',
            '1' => 'Actief',
            '0' => 'Gedeactiveerd'
        ],
        'type'        => "select"
    ];

    bluem_woocommerce_settings_render_input( $field );
}

function bluem_module_enabled( $module ): bool {
    $bluem_options = get_option( 'bluem_woocommerce_options' );

    if ( $bluem_options === false ) {
        return false;
    }
    if ( $bluem_options !== false
         && ! isset( $bluem_options["{$module}_enabled"] )
         || $bluem_options["{$module}_enabled"] == "1"
    ) {
        return true;
    }

    return false;
}

/**
 * bluem_generic_tabler
 *
 * @return void
 */
function bluem_generic_tabler( $data ) {
    ?>
    <table class="table widefat">
    <?php
    $i = 0;
    foreach ( $data as $row ) {
        if ( $i == 0 ) {
            ?>
            <tr><?php
            foreach ( $row as $row_key => $row_value ) { ?>
                <th>
                <?php echo $row_key; ?>
                </th><?php
            } ?>
            </tr><?php
        } ?>
        <tr>
        <?php
        foreach ( $row as $row_key => $row_value ) { ?>
            <td>
            <?php echo $row_value; ?>
            </td><?php
        } ?>
        </tr><?php
        $i ++;
    } ?>
    </table><?php
}

// Reference: https://www.wpbeginner.com/wp-tutorials/how-to-add-admin-notices-in-wordpress/
add_action( 'admin_notices', 'bluem_setup_incomplete' );
function bluem_setup_incomplete() {
    if ( ! is_admin() ) {
        return;
    }

    $options = get_option( 'bluem_woocommerce_options' );

    if ( $options !== false && ! bluem_module_enabled( 'suppress_warning' ) ) {
        return;
    }

    if ( $options == false ) {
        $messages[] = "Account gegevens ontbreken";
    } else {
        $valid_setup = true;
        $messages    = [];
        if ( ! array_key_exists( 'senderID', $options )
             || $options['senderID'] === ""
        ) {
            $messages[]  = "SenderID ontbreekt";
            $valid_setup = false;
        }
        if ( ! array_key_exists( 'test_accessToken', $options )
             || $options['test_accessToken'] === ""
        ) {
            $messages[]  = "Test accessToken ontbreekt";
            $valid_setup = false;
        }

        if ( isset( $options['environment'] )
             && $options['environment'] == "prod"
             && (
                 ! array_key_exists( 'production_accessToken', $options )
                 || $options['production_accessToken'] === ""
             )
        ) {
            $messages[]  = "Production accessToken ontbreekt";
            $valid_setup = false;
        }

        if ( bluem_module_enabled( 'mandates' )
             && (
                 ! array_key_exists( 'brandID', $options )
                 || $options['brandID'] === ""
             )
        ) {
            $messages[]  = "eMandates brandID ontbreekt";
            $valid_setup = false;
        }

        if ( bluem_module_enabled( 'mandates' )
             && (
                 ! array_key_exists( 'merchantID', $options )
                 || $options['merchantID'] === ""
             )
        ) {
            $messages[]  = "eMandates merchantID ontbreekt";
            $valid_setup = false;
        }

        if ( bluem_module_enabled( 'idin' )
             && ( ! array_key_exists( 'IDINBrandID', $options )
                  || $options['IDINBrandID'] === "" )
        ) {
            $messages[]  = "iDIN BrandID ontbreekt";
            $valid_setup = false;
        }

        /**
         * Check if WooCommerce is active
         **/
        if ( is_woocommerce_activated() ) {
            // Get WooCommerce payment gateways
            $installed_payment_methods = WC()->payment_gateways->payment_gateways();

            foreach ( $installed_payment_methods as $method ) {
                switch ( $method->id ) {
                    case 'bluem_payments':
                        if ( $method->enabled === 'no' && bluem_module_enabled( 'payments' ) ) {
                            $msg = [
                                'Je hebt de Bluem iDEAL ingeschakeld maar de betaalmethode nog niet binnen WooCommerce geactiveerd.'
                            ];
                            bluem_display_module_notices( $msg, 'De Bluem integratie is nog niet volledig geactiveerd', ( $_GET['page'] !== 'wc-settings' ? admin_url( 'admin.php?page=wc-settings&tab=checkout' ) : '' ), 'Klik hier om naar de WooCommerce configuratie te gaan.' );
                        }
                        break;

                    case 'bluem_mandates':
                        if ( $method->enabled === 'no' && bluem_module_enabled( 'mandates' ) ) {
                            $msg = [
                                'Je hebt de Bluem mandates ingeschakeld maar de betaalmethode nog niet binnen WooCommerce geactiveerd.'
                            ];
                            bluem_display_module_notices( $msg, 'De Bluem integratie is nog niet volledig geactiveerd', ( $_GET['page'] !== 'wc-settings' ? admin_url( 'admin.php?page=wc-settings&tab=checkout' ) : '' ), 'Klik hier om naar de WooCommerce configuratie te gaan.' );
                        }
                        break;
                }
            }
        }

        if ( $valid_setup ) {
            return;
        }
    }
    bluem_display_module_notices( $messages, 'De Bluem integratie is nog niet volledig ingesteld', ( get_admin_page_title() !== "Bluem" ? admin_url( 'admin.php?page=bluem-settings' ) : '' ), 'Klik hier om de plugin verder in te stellen.' );
}

function bluem_display_module_notices( $notices, $title = '', $btn_link = '', $btn_title = '' ) {
    echo '<div class="notice notice-warning is-dismissible">
        <p><span class="dashicons dashicons-warning"></span> <strong>' . $title . ':</strong><br>
        ';
    foreach ( $notices as $m ) {
        echo "* $m<br>";
    }
    echo '
        </p>';

    if ( ! empty( $btn_link ) ) {
        echo '<p><a href="' . $btn_link . '">' . $btn_title . '</a></p>';
    }

    echo '</div>';
}

/*
 *  Adding Meta container admin shop_order pages
 */
add_action( 'add_meta_boxes', 'bluem_order_requests_metabox' );

/**
 * bluem_order_requests_metabox
 *
 * @return void
 */
function bluem_order_requests_metabox() {
    add_meta_box(
        'bluem_order_requests_metabox_content',
        __(
            'Bluem request(s)',
            'bluem'
        ),
        'bluem_order_requests_metabox_content',
        'shop_order',
        'normal',
        'default'
    );
}

/**
 * Adding Meta field in the meta container admin shop_order pages
 *
 * @return void
 */
function bluem_order_requests_metabox_content() {
    global $post;
    $order_id = $post->ID;
//    $order = wc_get_order($order_id);

    // $requests1 = bluem_db_get_requests_by_keyvalue("order_id", $order_id);
    // $requests = array_merge($requests1,$requests2);
    // var_dump($order_id);

    //  requests from links:
    $requests_links = bluem_db_get_links_for_order( $order_id );
    $requests       = [];
    foreach ( $requests_links as $rql ) {
        $requests[] = bluem_db_get_request_by_id( $rql->request_id );
    }

    if ( isset( $requests ) && count( $requests ) > 0 ) {
        bluem_render_requests_list( $requests );
    } else {
        echo "No requests yet";
    }
}

/**
 * Retrieve header HTML for error/message prompts
 *
 * @return String
 */
function bluem_dialogs_get_simple_header(): string {
    return "<!DOCTYPE html><html lang='nl'><body><div
    style='font-family:Arial,sans-serif;display:block;
    margin:40pt auto; padding:10pt 20pt; border:1px solid #eee;
    background:#fff; max-width:500px;'>" .
           bluem_get_bluem_logo_html( 48 );
}

/**
 * Retrieve footer HTML for error/message prompt. Can include a simple link back to the webshop home URL.
 *
 * @param Bool $include_link
 *
 * @return String
 */
function bluem_dialogs_get_simple_footer( bool $include_link = true ): string {
    return (
           $include_link ?
               "<p><a href='" . home_url() . "' target='_self' style='text-decoration:none;'>Ga terug naar de webshop</a></p>" :
               ""
           ) . "</div></body></html>";
}

/**
 * Render a piece of HTML sandwiched beteween a simple header and footer, with an optionally included link back home
 *
 * @param String $html
 * @param boolean $include_link
 *
 * @return void
 */
function bluem_dialogs_render_prompt( string $html, bool $include_link = true ) {
    echo bluem_dialogs_get_simple_header();
    echo $html;
    echo bluem_dialogs_get_simple_footer( $include_link );
}

/**
 * Perform import action
 *
 * @param $data
 *
 * @return array
 */
function bluem_admin_import_execute( $data ): array {
    $cur_options = get_option( 'bluem_woocommerce_options' );

    $results = [];
    foreach ( $data as $k => $v ) {
        $cur_options[ $k ] = $v;
        $results[ $k ] = true;
    }
    update_option( "bluem_woocommerce_options", $cur_options );

    return $results;
}

/**
 * Render the admin Import / Export page
 * @return void
 */
function bluem_admin_importexport() {
    $import_data = null;
    $messages = [];

    if ( isset( $_POST['action'] ) && $_POST['action'] == "import" ) {
        $decoded = true;

        if ( isset( $_POST['import'] ) && $_POST['import'] !== "" ) {
            $import_data = json_decode(
                stripslashes(
                    $_POST['import']
                ),
                true
            );
            if ( is_null( $import_data ) ) {
                $messages[] = "Kon niet importeren: de input is niet geldige JSON";
                $decoded = false;
            }
        }

        if ( $decoded ) {
            $results    = bluem_admin_import_execute( $import_data );
            $sett_count = 0;
            foreach ( $results as $r ) {
                if ( $r ) {
                    $sett_count ++;
                }
            }
            $messages[] = "Importeren is uitgevoerd: $sett_count instellingen aangepast.";
        }
    }

    $options = get_option( 'bluem_woocommerce_options' );

    $options_json = "";
    if ( $options !== false ) {
        $options_json = json_encode( $options );
    }

    // @todo: improve this by creating a renderer function and passing the renderdata
    // @todo: then generalise this to other parts of the plugin
    include_once 'views/importexport.php';
}


function bluem_woocommerce_is_woocommerce_active(): bool {
    return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
}
