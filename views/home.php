<?php if (!defined('ABSPATH')) exit;
?>

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
        background-image: url('<?php echo esc_url(plugin_dir_url( '' )); ?>/bluem/assets/bluem/logo-hero.png');
        background-size: contain;
        background-repeat: no-repeat;
        content: '';
    }

</style>

<div class="wrap">
    <h1>
        <?php echo wp_kses_post(bluem_get_bluem_logo_html(48)); ?>
    </h1>

    <?php bluem_render_nav_header('home'); ?>

    <div class="bluem_logo"><?php esc_html_e('Bluem Payments & Identity Services', 'bluem'); ?></div>

    <h2 style="margin-top: 25px;"><?php esc_html_e('Maak betalen gemakkelijk!', 'bluem'); ?></h2>

    <p><?php esc_html_e('Met de Bluem WordPress plug-in integreer je online betalingen, identificaties en leeftijdsverificaties gemakkelijk op je website.', 'bluem'); ?></p>

    <p><strong><?php esc_html_e('Bluem Dashboard', 'bluem'); ?></strong><br/>
        <?php wp_kses_post(__('Alle transacties zijn ook zichtbaar in het <a href="https://viamijnbank.net/" target="_blank">viamijnbank.net dashboard</a>.', 'bluem')); ?>
    </p>

    <p>
        <strong><?php esc_html_e('Plugin versie', 'bluem'); ?></strong><br/><?php esc_html_e('Versie', 'bluem'); ?> <?php $bluem = get_plugin_data(plugin_dir_path(__FILE__) . '../bluem.php');
        echo esc_html($bluem['Version']); ?> (<a href="<?php echo esc_url(admin_url($pluginDetailsLink)); ?>"
                                                 target="_blank"><?php esc_html_e('Details bekijken', 'bluem'); ?></a>)
    </p>

    <p>
        <strong><?php esc_html_e('Technische informatie', 'bluem'); ?></strong><br/><?php esc_html_e('WordPress versie:', 'bluem'); ?> <?php echo esc_html(get_bloginfo('version')); ?>
        <br/>
        <?php esc_html_e('WooCommerce versie:', 'bluem'); ?> <?php echo class_exists('WooCommerce') ? esc_attr(WC()->version) : esc_html__('WooCommerce not installed', 'bluem'); ?>
        <br/>
        <?php esc_html_e('Bluem PHP-library versie:', 'bluem'); ?> <?php echo esc_html($dependency_bluem_php_version ?? '-'); ?>
        <br/>
        <?php esc_html_e('PHP versie:', 'bluem'); ?> <?php echo esc_attr(PHP_VERSION); ?></p>

    <?php bluem_render_footer(); ?>
</div>
