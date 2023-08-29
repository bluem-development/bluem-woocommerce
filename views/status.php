<style type="text/css">

    .bluem_logo {
        margin-top: 25px;
        background: linear-gradient(90deg, #7C2EC1 0%, #330B9B 100%);
        text-align: center;
        border-radius: 10px;
        line-height: 250px;
        font-weight: bold;
        font-size: 25px;
        color: #FFFFFF;
        height: 250px;
        width: 100%;
    }
    .bluem_logo:before {
        background-image: url('<?php echo plugin_dir_url( '' ); ?>/bluem/assets/bluem/logo-hero.png');
        background-size: contain;
        background-repeat: no-repeat;
        content: '';
    }

</style>

<?php

/**
 * Display PHP error log (if accessible)
 * @return string
 */
function display_php_errors() {
    $error_log_path = ini_get('error_log');

    if (!empty($error_log_path) && file_exists($error_log_path)) {
        if ($log_contents = @file_get_contents($error_log_path)) {
            $content = '<pre>' . esc_html($log_contents) . '</pre>';
        } else {
            $content = 'Unable to access the PHP error log. Either the log does not exist, logging has been disabled, or the necessary read permissions are lacking.';
        }
    } else {
        $content = 'Unable to access the PHP error log. Either the log does not exist, logging has been disabled, or the necessary read permissions are lacking.';
    }
    return $content;
}

/**
 * Display WordPress debug log (if exists)
 *
 * @return string
 */
function display_wordpress_debug_log() {
    $error_log_path = WP_CONTENT_DIR . '/debug.log';

    if (!empty($error_log_path) && file_exists($error_log_path)) {
        if ($log_contents = @file_get_contents($error_log_path)) {
            $content = '<pre>' . esc_html($log_contents) . '</pre>';
        } else {
            $content = 'Unable to access the WordPress debug log. Either the log does not exist, logging has been disabled, or the necessary read permissions are lacking.';
        }
    } else {
        $content = 'Unable to access the WordPress debug log. Either the log does not exist, logging has been disabled, or the necessary read permissions are lacking.';
    }
    return $content;
}

/**
 * Display WooCommerce logs (if exists)
 *
 * @return string
 */
function display_woocommerce_logs() {
    $woocommerce_logs = glob(WC_LOG_DIR . '*.log');

    $content = '';

    if (!empty($woocommerce_logs) && is_array($woocommerce_logs)) {
        foreach ($woocommerce_logs as $log) {
            $content .= '<h4>' . basename($log) . '</h4>';
            $content .= '<pre>' . esc_html(file_get_contents($log)) . '</pre>';
        }
    } else {
        $content = 'Unable to access the WooCommerce logs. Either the log does not exist, logging has been disabled, or the necessary read permissions are lacking.';
    }
    return $content;
}

?>

<div class="wrap">
    <h1>
        <?php echo bluem_get_bluem_logo_html(48);?>
        Status
    </h1>

    <?php bluem_render_nav_header('status');?>

    <div class="wrap payment-methods">
        <h2 class="nav-tab-wrapper">
            <a href="#" class="nav-tab" data-tab="general">Systeem</a>
            <a href="#" class="nav-tab" data-tab="logs">Logs</a>
        </h2>

        <div id="general" class="tab-content">
            <h1>Systeem</h1>

            <p>De volgende betaalmethoden zijn ingeschakeld:</p>
            <ul>
            <?php if ( bluem_module_enabled( 'mandates' ) ) { ?>
                <li>Incassomachtigen <span class="dashicons dashicons-yes" style="color: #4F800D;"></span></li>
            <?php } ?>
            <?php if ( bluem_module_enabled( 'payments' ) ) { ?>
                <li>Betalingen <span class="dashicons dashicons-yes" style="color: #4F800D;"></span></li>
            <?php } ?>
            <?php if ( bluem_module_enabled( 'idin' ) ) { ?>
                <li>Identiteit <span class="dashicons dashicons-yes" style="color: #4F800D;"></span></li>
            <?php } ?>
            </ul>
        </div>

        <div id="logs" class="tab-content">
            <h1>Logs</h1>

            <h3>PHP errors</h3>
            <?php echo display_php_errors(); ?>

            <h3>WordPress debug log</h3>
            <?php echo display_wordpress_debug_log(); ?>

            <h3>WooCommerce error logs</h3>
            <?php echo display_woocommerce_logs(); ?>
        </div>
    </div>

    <?php bluem_render_footer(); ?>
</div>

<script type="text/javascript">

    (function($) {
        $(document).ready(function() {
            // Handle tab click event
            $('div.payment-methods .nav-tab').on('click', function(e) {
                e.preventDefault();

                // Get the clicked tab's identifier
                var tabId = $(this).data('tab');

                // Show the corresponding tab
                $('div.payment-methods .nav-tab').removeClass('active');
                $(this).addClass('active');

                // Show the corresponding content container
                $('div.payment-methods .tab-content').removeClass('active');
                $('#' + tabId).addClass('active');
            });
            $('div.payment-methods .nav-tab:first-child').trigger('click');
        });
    })(jQuery);

</script>

<style type="text/css">

    div.payment-methods .nav-tab-wrapper {
        border-bottom: 1px solid #2b4e6c;
        margin-bottom: 0;
    }

    div.payment-methods .nav-tab {
        text-decoration: none;
        background-color: #99bed9;
        color: #2b4e6c;
    }

    div.payment-methods .nav-tab.active {
        background-color: #2b4e6c !important;
        color: #FFF;
    }

    div.payment-methods .tab-content {
        overflow: auto;
        max-height: 500px;
        display: none;
    }
    div.payment-methods .tab-content table {
        border: 1px solid #2b4e6c;
        margin-top: 0;
    }

    div.payment-methods .tab-content.active {
        display: block;
    }

    div.payment-methods .table.widefat {
        border: 1px solid #2b4e6c;
    }

</style>
