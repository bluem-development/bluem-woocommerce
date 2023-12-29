<div class="wrap">
    <h1>
        <?php echo bluem_get_bluem_logo_html(48);?>
        Transacties
    </h1>

    <?php bluem_render_nav_header('transactions');?>

    <div class="wrap payment-methods">
        <h2 class="nav-tab-wrapper">
            <?php foreach ($requests as $cat => $rs) { ?>
                <a href="#" class="nav-tab" data-tab="<?php echo $cat; ?> ">
                    <?php echo bluem_render_requests_type($cat);

                    if(!empty($rs)) {
                        $count = count($rs);
                        if ($count > 99) {
                            $count = '99+';
                        }
                        echo sprintf("&nbsp;(%s)", count($rs));
                    }
                    ?>
                </a>
            <?php } ?>
        </h2>

        <?php foreach ($requests as $cat => $rs) { ?>
            <div id="<?php echo $cat; ?>" class="tab-content">
                <?php bluem_render_request_table($rs, $users_by_id); ?>
            </div>
        <?php } ?>
    </div>

    <p>Klik op een transactie voor meer gedetailleerde informatie.</p>
    <p>Bekijk nog meer informatie over alle transacties in het <a href='https://viamijnbank.net/' target='_blank'>viamijnbank.net dashboard</a>.</p>

    <?php bluem_render_footer(); ?>
</div>

<script type="text/javascript">

    (function($) {
        $(document).ready(function() {
            // Handle tab click event
            $('div.payment-methods .nav-tab').on('click', function(e) {
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

    div.payment-methods .tab-content {
        display: none;
    }

    div.payment-methods .tab-content.active {
        display: block;
    }

    div.payment-methods .table.widefat {
        border: 1px solid #2b4e6c;
    }

</style>
