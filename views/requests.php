
<div class="wrap">
    <h1>
    <?php echo bluem_get_bluem_logo_html(48);?>
        Bluem &middot; Verzoeken overzicht</h1>
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
        Hieronder vind je een overzicht van alle Bluem verzoeken die gemaakt zijn sinds versie 1.2.7 van de Bluem plugin.
        Bekijk meer informatie over de transacties in je <a href='https://viamijnbank.net/' target='_blank'>viamijnbank.net dashboard</a>.
    </p>
    <p>
        Klik op een verzoek voor meer gedetailleerde informatie.
    </p>
    <?php
        foreach ($requests as $cat => $rs) {
            echo "<h4>".ucfirst($cat)."</h4>";
            bluem_render_request_table($rs, $users_by_id);
        }
    ?>
    <?php bluem_render_footer(); ?>
</div>