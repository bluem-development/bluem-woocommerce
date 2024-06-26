<?php $pluginDetailsLink = 'plugin-install.php?tab=plugin-information&plugin=bluem&TB_iframe=true&width=600&height=550'; ?>

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
        <?php echo bluem_get_bluem_logo_html(48); ?>
    </h1>

    <?php bluem_render_nav_header('home'); ?>

    <div class="bluem_logo"><?php _e('Bluem Payments & Identity Services', 'bluem'); ?></div>

    <h2 style="margin-top: 25px;"><?php _e('Maak betalen gemakkelijk!', 'bluem'); ?></h2>

    <p><?php _e('Met de Bluem WordPress plug-in integreer je online betalingen, identificaties en leeftijdsverificaties gemakkelijk op je website.', 'bluem'); ?></p>

    <p><strong><?php _e('Bluem Dashboard', 'bluem'); ?></strong><br/>
        <?php _e('Alle transacties zijn ook zichtbaar in het <a href="https://viamijnbank.net/" target="_blank">viamijnbank.net dashboard</a>.', 'bluem'); ?>
    </p>

    <p>
        <strong><?php _e('Plugin versie', 'bluem'); ?></strong><br/><?php _e('Versie', 'bluem'); ?> <?php $bluem = get_plugin_data(plugin_dir_path(__FILE__) . '../bluem.php');
        echo $bluem['Version']; ?> (<a href="<?php echo admin_url($pluginDetailsLink); ?>"
                                       target="_blank"><?php _e('Details bekijken', 'bluem'); ?></a>)</p>

    <p>
        <strong><?php _e('Technische informatie', 'bluem'); ?></strong><br/><?php _e('WordPress versie:', 'bluem'); ?> <?php echo get_bloginfo('version'); ?>
        <br/>
        <?php _e('WooCommerce versie:', 'bluem'); ?> <?php echo class_exists('WooCommerce') ? WC()->version : __('WooCommerce not installed', 'bluem'); ?>
        <br/>
        <?php _e('Bluem PHP-library versie:', 'bluem'); ?> <?php echo $dependency_bluem_php_version ?? '-'; ?><br/>
        <?php _e('PHP versie:', 'bluem'); ?> <?php echo PHP_VERSION; ?></p>

    <?php bluem_render_footer(); ?>
</div>
