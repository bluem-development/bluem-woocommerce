<?php if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>
        <?php echo wp_kses_post(bluem_get_bluem_logo_html(48)); ?>
        <?php esc_html_e('Transaction details', 'bluem'); ?>
    </h1>

    <?php bluem_render_nav_header(); ?>
    <?php if (!empty($request)) {
        ?>

        <div class="bluem-request-card-body">
            <div class='bluem-column' style="width: 50%;">
                <h2>
                    <?php echo esc_html(ucfirst($request->type)); ?>
                    <?php esc_html_e('Transaction', 'bluem'); ?>
                </h2>

                <table width="100%">
                    <tbody>
                    <tr>
                        <td width="35%"><?php esc_html_e('Description', 'bluem'); ?>:</td>
                        <td><?php echo esc_html($request->description ?? ''); ?></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Transaction number', 'bluem'); ?>:</td>
                        <td><?php echo esc_html($request->transaction_id ?? ''); ?></td>
                    </tr>
                    <?php if (isset($request->debtor_reference) && $request->debtor_reference !== "") { ?>
                        <tr>
                            <td><?php esc_html_e('Customer reference', 'bluem'); ?>:</td>
                            <td><?php echo esc_html($request->debtor_reference); ?></td>
                        </tr>
                    <?php } ?>
                    <?php
                    if (!is_null($request->order_id) && $request->order_id != "0") {
                        try {
                            $order = new \WC_Order($request->order_id);
                        } catch (Throwable $th) {
                            $order = false;
                        }
                        if ($order !== false) {
                            ?>
                            <tr>
                                <td><?php esc_html_e('Order', 'bluem'); ?>:</td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url("post.php?post={$request->order_id}&action=edit")); ?>"
                                       title="<?php echo esc_attr__('View order', 'bluem'); ?>"
                                       target="_blank">#<?php echo esc_html($order->get_order_number()); ?>
                                        (<?php echo wp_kses_post(wc_price($order->get_total())); ?>)</a></td>
                            </tr>
                        <?php }
                        } ?>
                    <tr>
                        <td><?php esc_html_e('User', 'bluem'); ?>:</td>
                        <?php if (isset($request_author->user_nicename) && $request_author !== false) { ?>
                            <td>
                                <a href="<?php echo esc_url(admin_url("user-edit.php?user_id=" . $request->user_id)); ?>"
                                   target="_blank"><?php echo esc_html($request_author->user_nicename); ?></a></td>
                        <?php } else { ?>
                            <td><?php esc_html_e('Guest / unknown', 'bluem'); ?></td>
                        <?php } ?>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Date', 'bluem'); ?>:</td>
                        <td><?php echo esc_html(bluem_get_formattedDate($request->timestamp ?? ''));
        ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Status', 'bluem'); ?>:</td>
                        <td><?php bluem_render_request_status($request->status); ?>
                            (<a href="<?php echo esc_url(admin_url("admin.php?page=bluem-transactions&request_id=" . $request->id . "&admin_action=status-update")); ?>"
                                title="Update status">
                                <?php esc_html_e('Update status', 'bluem'); ?></a>
                            )
                        </td>
                    </tr>
                    </tbody>
                </table>

                <?php if (isset($links) && count($links) > 0) { ?>
                    <h4><?php esc_html_e('Linked orders', 'bluem'); ?>:</h4>
                    <table class="widefat">
                        <thead>
                        <tr>
                            <th><?php esc_html_e('Date', 'bluem'); ?></th>
                            <th><?php esc_html_e('Order number', 'bluem'); ?></th>
                            <th><?php esc_html_e('Status', 'bluem'); ?></th>
                            <th><?php esc_html_e('Total amount', 'bluem'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($links as $link) { ?>
                            <?php if ($link->item_type == "order") { ?>
                                <?php $order = wc_get_order($link->item_id); ?>
                                <?php if ($order === false) {
                                    continue;
                                } ?>
                                <?php $order_data = $order->get_data(); ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html(bluem_get_formattedDate($order->get_date_created(), 'd-m-Y H:i')); ?>
                                    </td>
                                    <td>
                                        <a href='<?php echo esc_url(admin_url("post.php?post={$link->item_id}&action=edit")); ?>'
                                           target='_blank'><?php esc_html_e('Order', 'bluem'); ?>
                                            #<?php echo esc_html($order->get_order_number()); ?></a>
                                    </td>
                                    <td>
                                        <?php echo esc_html(ucfirst($order->get_status())); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($order_data['total']);
                                echo esc_html(" " . $order->get_currency()); ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        </tbody>
                    </table>
                <?php } ?>

                <?php if (isset($logs) && count($logs) > 0) { ?>
                    <h4>
                        <?php esc_html_e('Events', 'bluem'); ?>:
                    </h4>
                    <ul>
                    <?php

                    foreach ($logs as $log) {
                        $d = str_replace(
                            ["<br><span style='font-family:monospace; font-size:9pt;'>", "</span>"],
                            "",
                            $log->description
                        );
                        $dparts = explode(esc_html__('New data:', 'bluem'), $d, 2); ?>
                        <li>
                        <span class="bluem-request-label">
                            <?php echo esc_html(bluem_get_formattedDate($log->timestamp)); ?>
                        </span>
                        <?php echo wp_kses_post($dparts[0]); ?><?php
                        if (isset($dparts[1])) {
                            ?>&nbsp;
                        <abbr title="<?php
                        echo wp_kses_post(str_replace('"', '', $dparts[1]));
                            ?>" style="cursor: help;"><span class="dashicons dashicons-info-outline"></span>
                            </abbr><?php
                        } ?>
                        </li><?php
                    } ?>
                    </ul><?php
                } ?>
            </div>
            <div class="bluem-column" style="width: 40%; margin-left:5%">
                <?php if (isset($request->transaction_url)) {
                    ?>
                    <p>

                <span class="bluem-request-label">
                    <?php esc_html_e('Transaction link', 'bluem'); ?>:
                </span>
                        <br>
                        <a href="<?php echo esc_url($request->transaction_url); ?>" target="_blank"
                           rel="noopener noreferrer">
                            <?php esc_html_e('View transaction', 'bluem'); ?>
                            <span class="dashicons dashicons-external" style="text-decoration: none;"></span>
                        </a>
                    </p>

                    <?php
                } ?>
                <?php

                try {
                    $pl = json_decode($request->payload, false, 512, JSON_THROW_ON_ERROR);
                } catch (Exception $e) {
                    $pl = null;
                }
        if (!is_null($pl)) {
            ?>
                    <h4>
                        <?php esc_html_e('Extra details', 'bluem'); ?>:
                    </h4>
                    <?php
            foreach ($pl as $plk => $plv) {
                bluem_render_obj_row_recursive($plk, $plv);
            }
        }
        ?>
                </p>
                <p>
                <hr>
                <span class="bluem-request-label">
                <?php esc_html_e('More information', 'bluem'); ?>:
                </span>
                <br>

                <a href="https://viamijnbank.net" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('on the viamijnbank.net dashboard', 'bluem'); ?>
                    <span class="dashicons dashicons-external" style="text-decoration: none;"></span>
                </a>
                </p>

            </div>


            <div style="clear:both; display:block; width:100%; border-top:1px solid #ddd; padding-top:5pt;  ">
                <p style="margin: 5px 0; padding: 0;"><span
                            class="bluem-request-label"><?php esc_html_e('Administration', 'bluem'); ?>:</span></p>
                <p style="margin: 5px 0; padding: 0;"><a
                            href="<?php echo esc_url(admin_url("admin.php?page=bluem-transactions&request_id=" . absint($request->id) . "&admin_action=delete")); ?>"
                            class="button bluem-button-danger"
                            onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this transaction?', 'bluem')); ?>');"
                            style="margin-top:5pt;"><?php esc_html_e('Delete this request immediately', 'bluem'); ?></a>
                </p>
                <p>
                    <i><strong>
                            <?php esc_html_e('Warning', 'bluem'); ?>
                        </strong>:
                        <?php esc_html_e('This data will be permanently deleted!', 'bluem'); ?></i>
                </p>
            </div>
            <?php
            if ($request->type === "identity") {
                ?>
                <div style="padding:10pt 0;">
                <h3>
                    <?php esc_html_e('Additional notes on working with iDIN results programmatically:', 'bluem'); ?>
                </h3>
                <p>

                    <?php esc_html_e('You can check whether validation was successful by using the following PHP code in a plugin or template:', 'bluem'); ?>
                <blockquote style="border: 1px solid #aaa;
border-radius:5px; margin:10pt 0 0 0; padding:5pt 15pt;">
                        <pre>if (function_exists('bluem_idin_user_validated')) {
    $validated = bluem_idin_user_validated();

    if ($validated) {
        // validated
    } else {
        // not validated
    }
}</pre>
                </blockquote>
                </p>
                <p>
                    <?php esc_html_e('You can retrieve these results as an object by using the following PHP code in a plugin or template:', 'bluem'); ?>
                </p>
                <p>
                <blockquote style="border: 1px solid #aaa; border-radius:5px;
    margin:10pt 0 0 0; padding:5pt 15pt;">
                    <pre>if (function_exists('bluem_idin_retrieve_results')) {
        $results = bluem_idin_retrieve_results();
        // <?php esc_html_e('Display the results or store them elsewhere:', 'bluem'); ?>
        $results->BirthDateResponse; // returns 1975-07-25
        $results->NameResponse->LegalLastName; // returns Vries
    }</pre>
                </blockquote>
                </p>
                </div><?php
            }
        ?>
        </div>
    <?php } else { ?>
        <p><?php esc_html_e('Request not found', 'bluem'); ?></p>
    <?php } ?>
    <?php bluem_render_footer(); ?>
</div>

