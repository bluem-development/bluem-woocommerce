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

    <div class="bluem_logo"><?php __('Bluem Payments & Identity Services', 'bluem'); ?></div>

    <h2 style="margin-top: 25px;"><?php __('Maak betalen gemakkelijk!', 'bluem'); ?></h2>

    <p><?php __('Met de Bluem WordPress plug-in integreer je online betalingen, identificaties en leeftijdsverificaties gemakkelijk op je website.', 'bluem'); ?></p>

    <p><strong><?php __('Bluem Dashboard', 'bluem'); ?></strong><br/>
        <?php __('Alle transacties zijn ook zichtbaar in het <a href="https://viamijnbank.net/" target="_blank">viamijnbank.net dashboard</a>.', 'bluem'); ?>
    </p>

    <p>
        <strong><?php __('Plugin versie', 'bluem'); ?></strong><br/><?php __('Versie', 'bluem'); ?> <?php $bluem = get_plugin_data(plugin_dir_path(__FILE__) . '../bluem.php');
        echo $bluem['Version']; ?> (<a href="<?php echo admin_url($pluginDetailsLink); ?>"
                                       target="_blank"><?php __('Details bekijken', 'bluem'); ?></a>)</p>

    <p>
        <strong><?php __('Technische informatie', 'bluem'); ?></strong><br/><?php __('WordPress versie:', 'bluem'); ?> <?php echo get_bloginfo('version'); ?>
        <br/>
        <?php __('WooCommerce versie:', 'bluem'); ?> <?php echo class_exists('WooCommerce') ? WC()->version : __('WooCommerce not installed', 'bluem'); ?>
        <br/>
        <?php __('Bluem PHP-library versie:', 'bluem'); ?> <?php echo $dependency_bluem_php_version ?? '-'; ?><br/>
        <?php __('PHP versie:', 'bluem'); ?> <?php echo PHP_VERSION; ?></p>

    <?php bluem_render_footer(); ?>
</div>
