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

<div class="wrap">
    <h1>
        <?php echo bluem_get_bluem_logo_html(48);?>
    </h1>

    <?php bluem_render_nav_header('logs');?>

    <h2 style="margin-top: 25px;"><span class="dashicons dashicons-book"></span> Logs</h2>

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
                $content = 'PHP error log not found or logging is disabled.';
            }
        } else {
            $content = 'PHP error log not found or logging is disabled.';
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
                $content = 'PHP error log not found or logging is disabled.';
            }
        } else {
            $content = 'PHP error log not found or logging is disabled.';
        }
        return $content;
    }

    /**
     * Display WordPress debug log (if exists)
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
            $content = 'WooCommerce error logs not found or logging is disabled.';
        }
        return $content;
    }

    ?>

    <h3>PHP errors</h3>
    <?php display_php_errors(); ?>

    <h3>WordPress debug log</h3>
    <?php display_wordpress_debug_log(); ?>

    <h3>WooCommerce error logs</h3>
    <?php display_woocommerce_logs(); ?>

    <?php bluem_render_footer(); ?>
</div>
