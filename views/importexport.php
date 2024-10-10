<?php if (!defined('ABSPATH')) exit;
?>

<style>
    .bluem_port_input {
        width: 90%;
        /* margin:0 auto; */
        border: 1px solid #aaa;
        padding: 5px;
        display: block;
        white-space: pre-wrap;
        white-space: -moz-pre-wrap;
        white-space: -o-pre-wrap;
        word-wrap: break-word;
    }
</style>

<div class="wrap">
    <h1>
        <?php wp_kses_post(
            sprintf(
            /* translators: %s: logo html */
                esc_html__('%s Import / export', 'bluem'),
                bluem_get_bluem_logo_html(48),
            )
        ); ?>
    </h1>

    <?php bluem_render_nav_header('importexport'); ?>

    <?php if (isset($messages) && count($messages) > 0) {
        ?>
        <div>
            <?php foreach ($messages as $m) {
                ?>
                <div class="notice notice-info inline" style="padding:10pt;">
                    <?php
                    echo esc_html($m);

                    ?>
                </div>
                <?php
            } ?>
        </div>
        <?php
    } ?>


    <div style="width: 45%; float:left;">


        <h2><span class="dashicons dashicons-database-export"></span> <?php esc_html_e('Exporteren', 'bluem'); ?></h2>
        <p>
            <?php esc_html_e('Kopieer en plak de onderstaande informatie om je instellingen te exporteren.', 'bluem'); ?>
        </p>
        <blockquote>
        <pre class="bluem_port_input"><?php echo esc_js($options_json ?? ''); ?>
            </pre>
        </blockquote>
    </div>


    <div style="width: 45%; float:left;">
        <h2><span class="dashicons dashicons-database-import"></span> <?php esc_html_e('Importeren', 'bluem'); ?></h2>

        <p>
            <?php esc_html_e('Upload je instellingen hier.', 'bluem'); ?>

        <form method="post"
              action="<?php echo esc_url(admin_url('admin.php?page=bluem_admin_importexport&action=import')); ?>">
            <input type="hidden" name="action" value="import">
            <label for="import">
                Input:
            </label>
            <textarea class="bluem_port_input" name="import" id="import" rows="20"></textarea>
            <p>
                <strong><?php esc_html_e('Let op! Bestaande instellingen zullen worden overschreven.', 'bluem'); ?></strong>
            </p>
            <button type="submit"><?php esc_html_e('Importeren', 'bluem'); ?></button>
        </form>
        </p>
    </div>

    <div style="clear: both;"></div>

    <?php bluem_render_footer(); ?>
</div>
