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
        <?php echo bluem_get_bluem_logo_html(48); ?>
        <?php echo __('Import / export'); ?>
    </h1>

    <?php bluem_render_nav_header('importexport'); ?>

    <?php if (isset($messages) && count($messages) > 0) {
        ?>
        <div>
            <?php foreach ($messages as $m) {
                ?>
                <div class="notice notice-info inline" style="padding:10pt;">
                    <?php
                    echo $m;

                    ?>
                </div>
                <?php
            } ?>
        </div>
        <?php
    } ?>


    <div style="width: 45%; float:left;">


        <h2><span class="dashicons dashicons-database-export"></span> <?php echo __('Exporteren', 'bluem'); ?></h2>
        <p>
            <?php echo __('Kopieer en plak de onderstaande informatie om je instellingen te exporteren.', 'bluem'); ?>
        </p>
        <blockquote>
        <pre class="bluem_port_input"><?php echo $options_json ?? ''; ?>
            </pre>
        </blockquote>
    </div>


    <div style="width: 45%; float:left;">
        <h2><span class="dashicons dashicons-database-import"></span> <?php echo __('Importeren', 'bluem'); ?></h2>

        <p>
            <?php echo __('Upload je instellingen hier.', 'bluem'); ?>

        <form method="post" action="<?php echo admin_url('admin.php?page=bluem_admin_importexport&action=import'); ?>">
            <input type="hidden" name="action" value="import">
            <textarea class="bluem_port_input" name="import" id="import" rows="20"></textarea>
            <p><strong><?php echo __('Let op! Bestaande instellingen zullen worden overschreven.', 'bluem'); ?></strong>
            </p>
            <button type="submit"><?php echo __('Importeren', 'bluem'); ?></button>
        </form>
        </p>
    </div>

    <div style="clear: both;"></div>

    <?php bluem_render_footer(); ?>
</div>
