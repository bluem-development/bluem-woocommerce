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

//    $log_file_path = WP_CONTENT_DIR . '/debug.log';
//    $log_lines = file($log_file_path);
//
//    $lines_to_display = array_slice($log_lines, -10);
//
//    var_dump($lines_to_display);

    function display_php_errors_in_plugin() {
        $php_error_log_path = ini_get('error_log');

        if (!empty($php_error_log_path) && file_exists($php_error_log_path)) {
            if ($log_contents = @file_get_contents($php_error_log_path)) {
                // Output the error log in a <pre> tag for formatting
                echo '<pre>' . $log_contents . '</pre>';
            } else {
                echo 'PHP error log not found or logging is disabled.';
            }
        } else {
            echo 'PHP error log not found or logging is disabled.';
        }
    }

    display_php_errors_in_plugin();

    ?>

    <?php bluem_render_footer(); ?>
</div>
