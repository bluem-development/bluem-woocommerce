<div class="wrap">
    <h1>
        <?php echo bluem_get_bluem_logo_html(48);?>
        Bluem &middot; Verzoeken</h1>
    
    <?php bluem_render_nav_header('requests');?>


    <p>
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