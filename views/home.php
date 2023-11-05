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

    <?php bluem_render_nav_header('home');?>

    <div class="bluem_logo">Bluem Payments & Identity Services</div>

    <h2 style="margin-top: 25px;">Maak betalen gemakkelijk!</h2>

    <p>Met de Bluem WordPress plug-in integreer je online betalingen, identificaties en leeftijdsverificaties gemakkelijk op je website.</p>

    <p><strong>Bluem Dashboard</strong><br />Alle transacties zijn ook zichtbaar in het <a href='https://viamijnbank.net/' target='_blank'>viamijnbank.net dashboard</a>.</p>

    <p><strong>Plugin versie</strong><br />Versie <?php $bluem = get_plugin_data( WP_PLUGIN_DIR . '/bluem/bluem.php' ); echo $bluem['Version']; ?> (<a href="<?php echo admin_url('plugin-install.php?tab=plugin-information&plugin=bluem&TB_iframe=true&width=600&height=550'); ?>" target="_blank">Details bekijken</a>)</p>

    <p><strong>Technische informatie</strong><br />WordPress versie: <?php echo get_bloginfo('version'); ?><br />WooCommerce versie: <?php echo class_exists('WooCommerce') ? WC()->version : __('WooCommerce not installed', 'bluem'); ?><br />Bluem PHP-library versie: <?php echo $dependency_bluem_php_version; ?><br />PHP versie: <?php echo phpversion(); ?></p>

    <?php bluem_render_footer(); ?>
</div>
