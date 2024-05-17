<div class="wrap">
    <h1>
        <?php echo bluem_get_bluem_logo_html(48); ?>
        <?php echo __('Transactiedetails', 'bluem'); ?>
    </h1>

    <?php bluem_render_nav_header(); ?>

    <div class="bluem-request-card-body">
        <div class='bluem-column' style="width: 50%;">
            <h2>
                <?php echo ucfirst($request->type); ?>
                <?php echo __('Transactie', 'bluem'); ?>
            </h2>

            <table width="100%">
                <tbody>
                <tr>
                    <td width="35%"><?php echo __('Omschrijving', 'bluem'); ?>:</td>
                    <td><?php echo $request->description; ?></td>
                </tr>
                <tr>
                    <td><?php echo __('Transactienummer', 'bluem'); ?>:</td>
                    <td><?php echo $request->transaction_id; ?></td>
                </tr>
                <?php if (isset($request->debtor_reference) && $request->debtor_reference !== "") { ?>
                    <tr>
                        <td><?php echo __('Klantreferentie', 'bluem'); ?>:</td>
                        <td><?php echo $request->debtor_reference; ?></td>
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
                            <td><?php echo __('Bestelling', 'bluem'); ?>:</td>
                            <td><a href="<?php echo admin_url("post.php?post={$request->order_id}&action=edit"); ?>"
                                   title="Bestelling bekijken"
                                   target="_blank">#<?php echo $order->get_order_number(); ?>
                                    (<?php echo wc_price($order->get_total()); ?>)</a></td>
                        </tr>
                    <?php }
                } ?>
                <tr>
                    <td><?php echo __('Gebruiker', 'bluem'); ?>:</td>
                    <?php if (isset($request_author) && !is_null($request_author) && $request_author !== false && isset($request_author->user_nicename)) { ?>
                        <td><a href="<?php echo admin_url("user-edit.php?user_id=" . $request->user_id); ?>"
                               target="_blank"><?php echo $request_author->user_nicename; ?></a></td>
                    <?php } else { ?>
                        <td><?php echo __('Gastgebruiker/onbekend', 'bluem'); ?></td>
                    <?php } ?>
                </tr>
                <tr>
                    <td><?php echo __('Datum', 'bluem'); ?>:</td>
                    <td><?php echo bluem_get_formattedDate($request->timestamp ?? '');
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><?php echo __('Status', 'bluem'); ?>:</td>
                    <td><?php bluem_render_request_status($request->status); ?>
                        (<a href="<?php echo admin_url("admin.php?page=bluem-transactions&request_id=" . $request->id . "&admin_action=status-update"); ?>"
                            title="Update status">
                            <?php echo __('Update status', 'bluem'); ?></a>
                        )
                    </td>
                </tr>
                </tbody>
            </table>

            <?php if (isset($links) && count($links) > 0) { ?>
                <h4><?php echo __('Gekoppelde orders', 'bluem'); ?>:</h4>
                <table class="widefat">
                    <thead>
                    <tr>
                        <th><?php echo __('Datum', 'bluem'); ?></th>
                        <th><?php echo __('Ordernummer', 'bluem'); ?></th>
                        <th><?php echo __('Status', 'bluem'); ?></th>
                        <th><?php echo __('Totaalbedrag', 'bluem'); ?></th>
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
                                    <?php echo bluem_get_formattedDate($order->get_date_created(), 'd-m-Y H:i'); ?>
                                </td>
                                <td>
                                    <a href='<?php echo admin_url("post.php?post={$link->item_id}&action=edit"); ?>'
                                       target='_blank'><?php echo __('Order', 'bluem'); ?>
                                        #<?php echo $order->get_order_number(); ?></a>
                                </td>
                                <td>
                                    <?php echo ucfirst($order->get_status()); ?>
                                </td>
                                <td>
                                    <?php echo $order_data['total'];
                                    echo " " . $order->get_currency(); ?>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>

            <?php if (isset($logs) && count($logs) > 0) { ?>
                <h4>
                    <?php echo __('Gebeurtenissen', 'bluem'); ?>:
                </h4>
                <ul>
                <?php

                foreach ($logs as $log) {
                    $d = str_replace(
                        ["<br><span style='font-family:monospace; font-size:9pt;'>", "</span>"],
                        "",
                        $log->description
                    );
                    $dparts = explode(__('Nieuwe data:', 'bluem'), $d, 2); ?>
                    <li>
                    <span class="bluem-request-label">
                            <?php echo bluem_get_formattedDate($log->timestamp); ?>
                        </span>
                    <?php echo $dparts[0]; ?><?php
                    if (isset($dparts[1])) {
                        ?>&nbsp;
                    <abbr title="<?php
                    echo str_replace('"', '', $dparts[1]);
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
                    <?php echo __('Link naar transactie', 'bluem'); ?>:
                </span>
                    <br>
                    <a href="<?php echo $request->transaction_url; ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo __('Transactie bekijken', 'bluem'); ?>
                        <span class="dashicons dashicons-external" style="text-decoration: none;"></span>
                    </a>
                </p>

                <?php
            } ?>
            <?php

            $pl = json_decode($request->payload);
            if (!is_null($pl)) {
                ?>
                <h4>
                    <?php echo __('Extra details', 'bluem'); ?>:
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
                <?php echo __('Meer informatie', 'bluem'); ?>:
                </span>
            <br>

            <a href="http://viamijnbank.net" target="_blank" rel="noopener noreferrer">
                <?php echo __('op het viamijnbank.net dashboard', 'bluem'); ?>
                <span class="dashicons dashicons-external" style="text-decoration: none;"></span>
            </a>
            </p>

        </div>


        <div style="clear:both; display:block; width:100%; border-top:1px solid #ddd; padding-top:5pt;  ">
            <p style="margin: 5px 0; padding: 0;"><span
                        class="bluem-request-label"><?php echo __('Administratie', 'bluem'); ?>:</span></p>
            <p style="margin: 5px 0; padding: 0;"><a
                        href="<?php echo admin_url("admin.php?page=bluem-transactions&request_id=" . $request->id . "&admin_action=delete"); ?>"
                        class="button bluem-button-danger"
                        onclick="return confirm('<?php echo __('Weet je zeker dat je de transactie wilt verwijderen?', 'bluem'); ?>');"
                        style="margin-top:5pt;"><?php echo __('Verwijder dit verzoek direct', 'bluem'); ?></a></p>
            <p><?php echo __('<i><strong>Let op</strong>: data wordt dan onherroepelijk verwijderd!</i>', 'bluem'); ?></p>
        </div>
        <?php
        if ($request->type == "identity") {
            ?>
            <div style="padding:10pt 0;">
            <h3>
                <?php echo __('Extra opmerkingen aangaande programmatisch met iDIN resultaten werken:', 'bluem'); ?>
            </h3>
            <p>

                <?php echo __('Of de validatie is gelukt, kan je verkrijgen door in een plug-in of template de volgende PHP code te
                    gebruiken:', 'bluem'); ?>
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
                <?php echo __('Deze resultaten zijn als object te verkrijgen door in een plug-in of template de volgende PHP code te gebruiken:', 'bluem'); ?>
            </p>
            <p>
            <blockquote style="border: 1px solid #aaa; border-radius:5px;
    margin:10pt 0 0 0; padding:5pt 15pt;">
                    <pre>if (function_exists('bluem_idin_retrieve_results')) {
        $results = bluem_idin_retrieve_results();
        // <?php echo __('Geef de resultaten weer of sla ze elders op:', 'bluem'); ?>
        echo $results->BirthDateResponse; // prints 1975-07-25
        echo $results->NameResponse->LegalLastName; // prints Vries
    }</pre>
            </blockquote>
            </p>
            </div><?php
        }
        ?>
    </div>
    <?php bluem_render_footer(); ?>
</div>

