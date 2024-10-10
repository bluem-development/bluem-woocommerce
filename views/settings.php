<?php if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>
        <?php echo wp_kses(bluem_get_bluem_logo_html(48), ['img']); ?>
        <?php echo esc_html__('Instellingen', 'bluem'); ?>
    </h1>

    <?php bluem_render_nav_header('settings'); ?>

    <div class="wrap payment-methods">
        <h2 class="nav-tab-wrapper">
            <a href="#" class="nav-tab" data-tab="general" title="<?php echo esc_html__('Algemeen', 'bluem'); ?>">
                <?php echo esc_html__('Algemeen', 'bluem'); ?>
            </a>
            <a href="#" class="nav-tab" data-tab="account" title="<?php echo esc_html__('Account', 'bluem'); ?>">
                <?php echo esc_html__('Account', 'bluem'); ?>
            </a>
            <?php if (bluem_module_enabled('mandates')) { ?>
                <a href="#" class="nav-tab" data-tab="mandates"
                   title="<?php echo esc_html__('Incassomachtigen', 'bluem'); ?>">
                    <?php echo esc_html__('Incassomachtigen', 'bluem'); ?>
                </a>
            <?php } ?>
            <?php if (bluem_module_enabled('payments')) { ?>
                <a href="#" class="nav-tab" data-tab="payments"
                   title="<?php echo esc_html__('Betalingen', 'bluem'); ?>">
                    <?php echo esc_html__('Betalingen', 'bluem'); ?>
                </a>
            <?php } ?>
            <?php if (bluem_module_enabled('idin')) { ?>
                <a href="#" class="nav-tab" data-tab="identity"
                   title="<?php echo esc_html__('Identiteit', 'bluem'); ?>">
                    <?php echo esc_html__('Identiteit', 'bluem'); ?>
                </a>
            <?php } ?>
            <a href="#" class="nav-tab" data-tab="integrations"
               title="<?php echo esc_html__('Integraties', 'bluem'); ?>">
                <?php echo esc_html__('Integraties', 'bluem'); ?>
            </a>
        </h2>

        <form action="options.php" method="post">
            <?php settings_fields('bluem_woocommerce_modules_options'); ?>
            <?php do_settings_sections('bluem_woocommerce_modules'); ?>
            <?php settings_fields('bluem_woocommerce_options'); ?>

            <div id="general" class="tab-content">
                <table class="form-table">
                    <tbody>
                    <?php do_settings_fields('bluem_woocommerce', 'bluem_woocommerce_modules_section'); ?>
                    </tbody>
                </table>
            </div>

            <div id="account" class="tab-content">
                <table class="form-table">
                    <tbody>
                    <?php do_settings_fields('bluem_woocommerce', 'bluem_woocommerce_general_section'); ?>
                    </tbody>
                </table>
            </div>

            <div id="mandates" class="tab-content">
                <table class="form-table">
                    <tbody>
                    <?php do_settings_fields('bluem_woocommerce', 'bluem_woocommerce_mandates_section'); ?>
                    </tbody>
                </table>
            </div>

            <div id="payments" class="tab-content">
                <table class="form-table">
                    <tbody>
                    <?php do_settings_fields('bluem_woocommerce', 'bluem_woocommerce_payments_section'); ?>
                    </tbody>
                </table>
            </div>

            <div id="identity" class="tab-content">
                <table class="form-table">
                    <tbody>
                    <?php do_settings_fields('bluem_woocommerce', 'bluem_woocommerce_idin_section'); ?>
                    </tbody>
                </table>
            </div>

            <div id="integrations" class="tab-content">
                <table class="form-table">
                    <tbody>
                    <?php do_settings_fields('bluem_woocommerce', 'bluem_woocommerce_integrations_section'); ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 0; padding-top: 25px; border-top: 1px solid #2b4e6c;">
                <input name="submit" class="button button-primary" type="submit"
                       value="<?php echo esc_html__('Veranderingen opslaan', 'bluem'); ?>"/>
            </div>
        </form>
    </div>

    <?php bluem_render_footer(); ?>
</div>

<script type="text/javascript">

    (function ($) {
        $(document).ready(function () {
            // Handle tab click event
            $('div.payment-methods .nav-tab').on('click', function (e) {
                e.preventDefault();

                // Get the clicked tab's identifier
                var tabId = $(this).data('tab');

                // Show the corresponding tab
                $('div.payment-methods .nav-tab').removeClass('active');
                $(this).addClass('active');

                // Show the corresponding content container
                $('div.payment-methods .tab-content').removeClass('active');
                $('#' + tabId).addClass('active');
            });
            $('div.payment-methods .nav-tab:first-child').trigger('click');
        });
    })(jQuery);

</script>

<style>

    .form-table th {
        padding: 20px 10px !important;
    }

    div.payment-methods .nav-tab-wrapper {
        border-bottom: 1px solid #2b4e6c;
        margin-bottom: 0;
    }

    div.payment-methods .nav-tab {
        text-decoration: none;
        background-color: #99bed9;
        color: #2b4e6c;
    }

    div.payment-methods .nav-tab.active {
        background-color: #2b4e6c !important;
        color: #FFF;
    }

    div.payment-methods .tab-content {
        overflow: auto;
        max-height: 500px;
        display: none;
    }

    div.payment-methods .tab-content table {
        border: 1px solid #2b4e6c;
        margin-top: 0;
    }

    div.payment-methods .tab-content.active {
        display: block;
    }

    div.payment-methods .table.widefat {
        border: 1px solid #2b4e6c;
    }

</style>
