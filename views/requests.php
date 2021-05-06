<div class="wrap">
    <h1>
        <?php echo bluem_get_bluem_logo_html(48);?>
        Bluem &middot; Verzoeken</h1>
    <nav class="nav-tab-wrapper">
        <a href="#" class="nav-tab nav-active tab-active active" style="background-color: #fff;">
            Alle verzoeken</a>
        <a href="<?php echo admin_url('options-general.php?page=bluem');?>" class="nav-tab">
            <span class="dashicons dashicons-admin-settings"></span>
            Instellingen
        </a>

        <a href="mailto:d.rijpkema@bluem.nl?subject=Bluem+Wordpress+Plugin" class="nav-tab" target="_blank">
            <span class="dashicons dashicons-editor-help"></span>
            Problemen,
            vragen of suggesties? Neem contact op via e-mail</a>
    </nav>
    <p>
        Hieronder staat een overzicht van alle Bluem verzoeken die gemaakt zijn sinds versie 1.2.7 van de Bluem
        plugin.
        Klik op een verzoek voor meer gedetailleerde informatie.
    <br>
        Bekijk nog meer informatie over alle transacties in het <a href='https://viamijnbank.net/'
            target='_blank'>viamijnbank.net dashboard</a>.
    </p>
    <?php
    foreach ($requests as $cat => $rs) {
        bluem_render_requests_table_title($cat);
        bluem_render_request_table($rs, $users_by_id);
    }
    ?>
    <?php bluem_render_footer(); ?>
</div>