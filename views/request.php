<div class="wrap">
    <h1>
        <?php echo bluem_get_bluem_logo_html(48);?>
        Bluem &middot; Verzoekdetails
    </h1>

    <?php bluem_render_nav_header();?>

    <div class="bluem-request-card-body">
        <div class='bluem-column' style="width: 50%;">
            <h2>
                <?php echo ucfirst($request->type);?>
                Verzoek
            </h2>
            <p>
                <span class="bluem-request-label">
                    Omschrijving:
                </span>
                <?php echo $request->description;?>
            </p>
            <p>
                <span class="bluem-request-label">
                    Transactienummer:
                </span>
                <?php echo $request->transaction_id; ?>
                <?php if (isset($request->debtor_reference) && $request->debtor_reference !=="") { ?>
                <br>
                <span class="bluem-request-label">
                    Klantreferentie:
                </span><?php
                    echo $request->debtor_reference;
                } ?>
            </p>
            <p>
                <span class="bluem-request-label">
                    Gebruiker:
                </span><?php
                if (isset($request_author) && !is_null($request_author) && $request_author!==false && isset($request_author->user_nicename)) { ?>
                    <a href="<?php echo admin_url("user-edit.php?user_id=".$request->user_id); ?>" target="_blank"><?php
                        echo $request_author->user_nicename; ?>
                    </a><?php
                } else {
                    echo "Gast/onbekend";
                } ?>
            </p>
            <p>
                <span class="bluem-request-label">
                    Datum:
                </span>

                <?php echo \Carbon\Carbon::parse($request->timestamp)->timezone('Europe/Amsterdam')->format('d-m-Y H:i:s'); ?>
            </p>

            <p>
                <span class="bluem-request-label">
                    Status:
                </span>
                <?php bluem_render_request_status($request->status); ?>
            </p>

            <?php
            if (!is_null($request->order_id) && $request->order_id !="0") {
                try {
                    $order = new \WC_Order($request->order_id);
                } catch (Throwable $th) {
                    $order = false;
                }
                if ($order !==false) {
                    ?>
                <p>
                    <span class="bluem-request-label">
                        Bestelling:
                    </span>
                    <a href="<?php echo admin_url("post.php?post={$request->order_id}&action=edit"); ?>" target="_blank">
                        <?php echo $request->order_id ?> (<?php echo wc_price($order->get_total()); ?>)
                    </a>
                </p>
                <?php
                }
            } ?>
<?php if (count($links)>0) {
                ?>

<h4>Gekoppelde orders:
            </h4>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Datum</th>
                        <th>Ordernummer</th>
                        <th>Status</th>
                        <th>Totaalbedrag</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                foreach ($links as $link) {
                    if ($link->item_type=="order") {
                        $order = wc_get_order($link->item_id);
                        if ($order === false) {
                            continue;
                        }
                        $order_data = $order->get_data(); ?>
                        <tr>
                            <td>
                                <?php echo \Carbon\Carbon::parse($order->get_date_created())->timezone('Europe/Amsterdam')->format('d-m-Y H:i:s'); ?>
                            </td>
                            <td>
                                <a href='<?php echo admin_url("post.php?post={$link->item_id}&action=edit"); ?>' target='_blank'>Order #<?php echo $link->item_id; ?></a>
                            </td>
                            <td>
                                <?php echo ucfirst($order->get_status()); ?>
                            </td>
                            <td>
                                <?php echo $order_data['total'];
                                echo " ".$order->get_currency(); ?>
                            </td>
                        </tr><?php
                    }
                    // @todo: build other item types later
                    // @todo: build administrative functions to delete or edit links.
                } ?>
            </tbody>
        </table><?php
        }

        if (count($logs)>0) { ?>
            <h4>
                Gebeurtenissen:
            </h4>
            <ul>
                <?php

                foreach ($logs as $log) {
                    $d = str_replace(
                        ["<br><span style='font-family:monospace; font-size:9pt;'>", "</span>"],
                        "",
                        $log->description
                    );
                    $dparts = explode("New data: ", $d, 2); ?>
                    <li>
                        <span class="bluem-request-label">
                            <?php echo \Carbon\Carbon::parse($log->timestamp)->timezone('Europe/Amsterdam')->format('d-m-Y H:i:s'); ?>
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
                    Link naar transactie:
                </span>
                <br>
                <a href="<?php echo $request->transaction_url; ?>" target="_blank" rel="noopener noreferrer">
                    Transactie bekijken
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
                Extra details:
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
                Meer informatie:
                </span>
                <br>

                <a href="http://viamijnbank.net" target="_blank" rel="noopener noreferrer">
                    op het viamijnbank.net dashboard
                    <span class="dashicons dashicons-external" style="text-decoration: none;"></span>
                </a>
            </p>

        </div>


        <div style="clear:both; display:block; width:100%; border-top:1px solid #ddd; padding-top:5pt;  ">
            <span class="bluem-request-label">
                Administratie:
            </span>
            <br>
            <a href="<?php echo admin_url("admin.php?page=bluem_admin_requests_view&request_id=".$request->id."&admin_action=delete");?>"
                class="button bluem-button-danger" style="margin-top:5pt;">Verwijder dit verzoek direct</a>
                <br>

                Let op: data wordt dan onherroepelijk verwijderd!
            </div>
        <?php
if ($request->type == "identity") {
                ?>
        <div style="padding:20pt;">
            <h3>
                Extra opmerkingen aangaande programmatisch met iDIN resultaten werken:
            </h3>
            <p>

                Of de validatie is gelukt, kan je verkrijgen door in een plug-in of template de volgende PHP code te
                gebruiken:

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
                Deze resultaten zijn als object te verkrijgen door in een plug-in of template de volgende PHP code te
                gebruiken:
            </p>
            <p>
            <blockquote style="border: 1px solid #aaa; border-radius:5px;
margin:10pt 0 0 0; padding:5pt 15pt;">
                <pre>if (function_exists('bluem_idin_retrieve_results')) {
        $results = bluem_idin_retrieve_results();
        // print, show or save the results:
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