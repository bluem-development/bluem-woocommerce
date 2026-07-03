<?php if (! defined('ABSPATH')) {
    exit;
}

?>
<div class="wrap">
    <h1>
        <?php echo wp_kses_post(bluem_get_bluem_logo_html(48)); ?>
        <?php esc_html_e('Transactions', 'bluem'); ?>
    </h1>

    <?php bluem_render_nav_header('transactions'); ?>

    <div class="wrap payment-methods">
        <h2 class="nav-tab-wrapper">
            <?php
            if (isset($requests)) {
                foreach ($requests as $cat => $rs) {
                    if ($cat === 'identity') {
                        $module_id = 'idin';
                    } else {
                        $module_id = $cat;
                    }

                    if (! bluem_module_enabled($module_id)) {
                        continue;
                    }
                    $active = in_array($cat, explode(',', $current_category), true);


                    ?>
                    <a class="nav-tab <?php echo $active ? 'active' : ''; ?>"
                       href="<?php echo admin_url('admin.php?page=bluem-transactions&request_type=' . $cat); ?>">
                        <!--                        data-tab="<?php echo esc_attr($cat); ?> "-->
                        <?php echo wp_kses_post(bluem_render_requests_type($cat));
                    ?>
                    </a>
                <?php }
                } ?>
        </h2>
        <?php if (isset($requests)) {
            foreach ($requests as $cat => $rs) {
                $active = in_array($cat, explode(',', $current_category), true);

                if (! $active) {
                    continue;
                }
                ?>
                <div id="<?php echo esc_attr($cat); ?>"
                     class="tab-content ">
                    <?php bluem_render_request_table($cat, $rs, $users_by_id ?? []); ?>
                    <?php if (count($rs) > 0) {
                        ?>
                        <p>
                            <?php echo count($rs); ?> <?php echo esc_html__('transaction(s) shown', 'bluem'); ?>
                            &middot;
                            <?php esc_html_e('Click a transaction for more detailed information.', 'bluem'); ?></p>
                        <p><?php echo wp_kses_post(__('View more information about all transactions in the <a href="https://viamijnbank.net/" target="_blank">viamijnbank.net dashboard</a>.', 'bluem')); ?></p>
                        <?php
                    } ?>
                </div>
            <?php }
            } ?>
    </div>


    <?php bluem_render_footer(); ?>
</div>

<style type="text/css">

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

    div.payment-methods .table.widefat {
        border: 1px solid #2b4e6c;
    }

</style>
