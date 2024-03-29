<style>
    .bluem_port_input {
        width: 90%;
        /* margin:0 auto; */
        border:1px solid #aaa;
        padding:5px;
        display:block;
        white-space: pre-wrap;
        white-space: -moz-pre-wrap;
        white-space: -pre-wrap;
        white-space: -o-pre-wrap;
        word-wrap: break-word;
    }
</style>

<div class="wrap">
    <h1>
        <?php echo bluem_get_bluem_logo_html(48);?>
        Import / export
    </h1>

    <?php bluem_render_nav_header('importexport');?>

    <?php if (count($messages)>0) {
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



    <h2><span class="dashicons dashicons-database-export"></span> Exporteren</h2>
    <p>
        Copy-paste de onderstaande informatie om je instellingen te exporteren.
    </p>
    <blockquote>
        <pre class="bluem_port_input"><?php echo $options_json;?>
            </pre>
        </blockquote>
        </div>


        <div style="width: 45%; float:left;">
    <h2><span class="dashicons dashicons-database-import"></span> Importeren</h2>

    <p>
        Upload je instellingen hier.

        <form method="post" action="<?php echo admin_url('admin.php?page=bluem_admin_importexport&action=import');?>">
<input type="hidden" name="action" value="import">
<textarea class="bluem_port_input" name="import" id="import" rows="20"></textarea>
<p><strong>Let op! Bestaande instellingen zullen worden overschreven.</strong></p>
<button type="submit">Importeren</button>
        </form>
    </p>
        </div>

        <div style="clear: both;"></div>

        <?php bluem_render_footer(); ?>
</div>
