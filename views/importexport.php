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
        Bluem &middot; Import/export instellingen</h1>
    <nav class="nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=bluem_admin_requests_view');?>" class="nav-tab">
            Alle verzoeken</a>
        <a href="<?php echo admin_url('options-general.php?page=bluem');?>" class="nav-tab">
            <span class="dashicons dashicons-admin-settings"></span>
            Instellingen
        </a>
        <a href="https://www.notion.so/codexology/Bluem-voor-WordPress-WooCommerce-Handleiding-9e2df5c5254a4b8f9cbd272fae641f5e" target="_blank"
        class="nav-tab">
        <span class="dashicons dashicons-media-document"></span>
        Handleiding</a>
        <a href="mailto:d.rijpkema@bluem.nl?subject=Bluem+Wordpress+Plugin" class="nav-tab" target="_blank">
            <span class="dashicons dashicons-editor-help"></span>
            Neem contact op via e-mail</a>
    </nav>

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



    <h2>Exporteren</h2>
    <p>
        Copy-paste de onderstaande informatie om je instellingen te xporteren.
    </p>
    <blockquote>
        <pre class="bluem_port_input"><?php echo $options_json;?>
            </pre>
        </blockquote>
        </div>
    
    
        <div style="width: 45%; float:left;">
    <h2>Importeren</h2>

    <p>
        Upload je instellingen hier. <strong>Let op: bestaande instellingen zullen worden overschreven</strong>

        <form method="post" action="<?php echo admin_url('admin.php?page=bluem_admin_importexport&action=import');?>">
<input type="hidden" name="action" value="import">            
<textarea class="bluem_port_input" name="import" id=""  rows="20"></textarea>

<button type="submit">Importeren</button>
        </form>
    </p>
        </div>
    
        <?php bluem_render_footer(); ?>
</div>