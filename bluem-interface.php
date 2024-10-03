<?php

// @todo create a language file and consistently localize everything

function bluem_get_idin_logo_html(): string
{
    return "<img src='" .
        plugin_dir_url(__FILE__) . "assets/bluem/idin.png' class='bluem-idin-logo'
        style='float:left; max-height:64px; margin:10pt 20pt 0 10pt;' alt='iDIN logo' />";
}

// @todo make a stylesheet and include it, move all inline styles there.

function bluem_get_bluem_logo_html($height = 64): string
{
    return '<img src="' .
        plugin_dir_url(__FILE__) . 'assets/bluem/logo.png' .
        '" class="bluem-bluem-logo" style="' .
        "max-height:{$height}px; margin:10pt;  margin-bottom:0; " .
        ' alt="Bluem logo"
        "/>';
}


function bluem_render_request_table($categoryName, $requests, $users_by_id = []): void
{
    if (count($requests) === 0) {
        echo "<p>";
        printf(
        /* translators: %s: Name of the category (Bluem service)   */
            __('<p>No transactions yet for %s</p>', 'bluem'),
            $categoryName
        );
        echo "</p>";

        return;
    } ?>

    <div class="bluem-requests-table-container">
        <table class="table widefat bluem-requests-table">
            <thead>
            <tr>
                <th style="width:20%;"><?php _e('Verzoek', 'bluem'); ?></th>
                <th style="width:20%;"><?php _e('Gebruiker', 'bluem'); ?></th>
                <th style="width:15%;"><?php _e('Datum', 'bluem'); ?></th>
                <th style="width:20%;"><?php _e('Extra informatie', 'bluem'); ?></th>
                <th style="width:20%;"><?php _e('Status', 'bluem'); ?></th>
                <th style="width:5%;"></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $r) {
                $prettyRequestDate = date_i18n("d-m-Y H:i:s", strtotime($r->timestamp));
                ?>
                <tr>
                    <td width="20%">
                        <a href="<?php echo admin_url("admin.php?page=bluem-transactions&request_id=" . $r->id); ?>"
                           target="_self">
                            <?php echo esc_html($r->description); ?>
                        </a>
                        <br>
                        <span style="color:#aaa; font-size:9pt;">
                            <?php echo esc_html($r->transaction_id); ?>
                        </span>
                    </td>
                    <td width="20%"><?php bluem_render_request_user($r, $users_by_id); ?></td>
                    <td width="15%" title="<?php echo esc_attr($prettyRequestDate); ?>">
                        <?php echo esc_html($prettyRequestDate); ?>
                    </td>
                    <td width="20%">
                        <?php
                        if (!is_null($r->order_id) && $r->order_id != "0") {
                            try {
                                $order = new WC_Order($r->order_id);
                            } catch (Throwable $th) {
                                $order = false;
                            }
                            if ($order !== false) {
                                ?>
                            <a href="<?php echo admin_url("post.php?post={$r->order_id}&action=edit"); ?>"
                               target="_blank">
                                <?php _e('Bestelling', 'bluem'); ?><?php echo esc_html($order->get_order_number()); ?>
                                (<?php echo esc_html(wc_price($order->get_total())); ?>)
                                </a><?php
                            } else {
                                echo "&nbsp;";
                            }
                        } ?>
                        <?php if (isset($r->debtor_reference) && $r->debtor_reference !== "") {
                            ?>

                            <span style="color:#aaa; font-size:9pt; display:block;"><?php _e('Klantreferentie', 'bluem'); ?>:
            <?php
            echo esc_html($r->debtor_reference); ?>
            </span>
                            <?php
                        } ?>
                    </td>
                    <td width="20%"><?php bluem_render_request_status($r->status); ?></td>
                    <td width="5%"></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
    <?php
}


function bluem_render_request_status(string $status): void
{
    switch (strtolower($status)) {
        case 'created':
        {
            echo "<span style='color:#1a94c0;'>
                <span class='dashicons dashicons-plus-alt'></span>
                    " . __('Aangemaakt', 'bluem') . "
                </span>";

            break;
        }

        case 'success':
        {
            echo "<span style='color:#2e9801'>

                <span class='dashicons dashicons-yes-alt'></span>
                        " . __('Succesvol', 'bluem') . " afgerond
                    </span>";

            break;
        }

        case 'cancelled':
        {
            echo "<span style='color:#bd1818'>
                        <span class='dashicons dashicons-dismiss'></span>
                        " . __('Geannuleerd', 'bluem') . "</span>";
            break;
        }
        case 'expired':
        {
            echo "<span style='color:#bd1818'>
                        <span class='dashicons dashicons-dismiss'></span>
                        " . __('Verlopen', 'bluem') . "</span>";
            break;
        }
        case 'open':
        case 'new':
        {
            echo "<span style='color:#6a4285'>

                        <span class='dashicons dashicons-editor-help'></span>
                        " . __('Openstaand', 'bluem') . "</span>";
            break;
        }
        case 'pending':
        {
            echo "<span style='color:#6a4285' title='mogelijk moet dit verzoek nog worden ondertekend door een tweede ondertekenaar'>

                        <span class='dashicons dashicons-editor-help'></span>
                        " . __('in afwachting van verwerking', 'bluem') . "</span>";
            break;
        }
        case 'processing':
        {
            echo "<span style='color:#6a4285'>

                        <span class='dashicons dashicons-marker'></span>
                        " . __('In verwerking', 'bluem') . "</span>";
            break;
        }

        case 'insufficient':
        {
            echo "<span style='color:#ac1111'>

                            <span class='dashicons dashicons-dismiss'></span>
                            " . __('Ontoereikend', 'bluem') . "</span>";
            break;
        }
        case 'failure':
        {
            echo "<span style='color:#ac1111'>
                    <span class='dashicons dashicons-dismiss'></span>
                    " . __('Gefaald', 'bluem') . "</span>";
            break;
        }
        default:
        {
            echo "Status:". esc_html($status);
            break;
        }
    }
}

function bluem_render_request_user(object $r, array $users_by_id): void
{
    if (isset($users_by_id[(int)$r->user_id])) {
        ?>
        <a href="<?php echo admin_url("user-edit.php?user_id=" . $r->user_id . "#user_" . $r->type); ?>"
           target="_blank">
            <?php echo esc_html($users_by_id[(int)$r->user_id]->user_nicename); ?>
        </a>

        <?php
    } else {
        _e("Gastgebruiker/onbekend", 'bluem');
    }
}

function bluem_render_footer($align_right = true)
{
    ?>

    <p style="display:block;
    <?php
    if ($align_right) {
        echo 'text-align:right;';
    } ?>
            ">
        <?php _e('Hulp nodig?', 'bluem'); ?>
        <br>
        <a href="https://www.notion.so/codexology/Bluem-voor-WordPress-WooCommerce-Handleiding-9e2df5c5254a4b8f9cbd272fae641f5e"
           target="_blank" style="text-decoration:none;">
            <span class="dashicons dashicons-media-document"></span>
            <?php _e('Handleiding', 'bluem'); ?>
            <small>
            <span class="dashicons dashicons-external"></span>
            </small>
        </a>
        &middot;
        <a href="mailto:pluginsupport@bluem.nl?subject=Bluem+Wordpress+Plugin" target="_blank"
           style="text-decoration:none;">
            <span class="dashicons dashicons-editor-help"></span>
            <?php _e('E-mail support', 'bluem'); ?></a>

    </p>
    <?php
}

function bluem_render_requests_list($requests)
{
    ?>
    <div class="bluem-request-list">
        <?php foreach ($requests as $r) {
            $pl = json_decode($r->payload); ?>
            <div class="bluem-request-list-item">


                <?php
                if ($r->type === "payments" || $r->type === "mandates") {
                    if (!is_null($pl)) {
                        ?>
                        <div class="bluem-request-list-item-floater">
                        <?php
                        foreach ($pl as $k => $v) {
                            bluem_render_obj_row_recursive($k, $v);
                        } ?>
                        </div><?php
                    }
                } elseif ($r->type === "identity") {
                    ?>
                    <div class="bluem-request-list-item-floater">
                        <?php
                        if (!is_null($pl)) {
                            ?>

                            <div>
                                <?php if (isset($pl->report->CustomerIDResponse)
                                    && $pl->report->CustomerIDResponse . "" != ""
                                ) { ?>
                                    <span class="bluem-request-label">
                    <?php _e('CustomerID', 'bluem'); ?>:
                </span>
                                    <?php echo esc_html($pl->report->CustomerIDResponse); ?>
                                    <?php
                                } ?>
                            </div>

                            <?php if (isset($pl->report->AddressResponse)) { ?>
                                <div>
                <span class="bluem-request-label">
                <?php _e('Adres', 'bluem'); ?>:
                </span>
                                    <?php foreach ($pl->report->AddressResponse as $k => $v) {
                                        echo esc_html($v . " ");
                                    } ?>
                                </div>
                                <?php
                            } ?>

                            <?php if (isset($pl->report->BirthDateResponse)) { ?>
                                <div>
                <span class="bluem-request-label">
                    <?php _e('Geb.datum', 'bluem'); ?>:
                </span>
                                    <?php echo esc_html($pl->report->BirthDateResponse); ?>


                                </div>
                            <?php } ?>
                            <?php if (isset($pl->report->EmailResponse)) { ?>
                                <div>
                <span class="bluem-request-label">
                    <?php _e('E-mail', 'bluem'); ?>:
                </span>
                                    <?php echo esc_html($pl->report->EmailResponse); ?>

                                </div>
                            <?php } ?>
                            <?php if (isset($pl->report->TelephoneResponse1)) { ?>
                                <div>
                <span class="bluem-request-label">
                    <?php _e('Telefoonnr.', 'bluem'); ?>:
                </span>
                                    <?php echo esc_html($pl->report->TelephoneResponse1); ?>

                                </div>

                            <?php } ?>
                            <?php
                            if (isset($pl->environment)) { ?>
                                <div>
                <span class="bluem-request-label">
                <?php _e('Bluem modus', 'bluem'); ?>:
                </span>
                                    <?php echo ucfirst($pl->environment); ?>
                                </div>
                                <?php
                            } ?>
                            <?php
                        } ?>
                    </div>
                    <?php
                } ?>

                <div class="bluem-request-list-item-row bluem-request-list-item-row-title">
                    <a href="<?php echo admin_url("admin.php?page=bluem-transactions&request_id=" . $r->id); ?>"
                       target="_self">
                        <?php echo esc_html($r->description); ?>
                    </a>
                </div>
                <div class="bluem-request-list-item-row">

            <span class="bluem-request-label">
                <?php _e('Transactienummer', 'bluem'); ?>:

            </span>
                    <?php echo esc_html($r->transaction_id); ?>

                </div>
                <?php if (isset($r->debtor_reference) && $r->debtor_reference !== "") {
                    ?>
                    <div class="bluem-request-list-item-row">
            <span class="bluem-request-label">
                <?php _e('Klantreferentie', 'bluem'); ?>:
            </span>
                        <?php echo esc_html($r->debtor_reference); ?>
                    </div>
                    <?php
                } ?>
                <div class="bluem-request-list-item-row">

            <span class="bluem-request-label">
                <?php _e('Tijdstip', 'bluem'); ?>
            </span>
                    <?php $rdate = new DateTimeImmutable($r->timestamp, new DateTimeZone("Europe/Amsterdam")); ?>
                    <?php echo esc_html($rdate->format("d-m-Y H:i:s")); ?>
                </div>


                <div class="bluem-request-list-item-row">

            <span class="bluem-request-label">
                <?php _e('Status', 'bluem'); ?>:
            </span>
                    <?php bluem_render_request_status($r->status); ?>
                </div>
            </div>
            <?php
        } ?>
    </div>
    <?php
}


function bluem_render_obj_row_recursive($key, $value, $level = 0): void
{
    if ($key === "linked_orders") {
        return;
    }

    if (is_numeric($key)) {
        $key = "";
        $prettyKey = "";
    } else {
        $prettyKey = ucfirst(str_replace(['_', 'Response1', 'Response', 'id'], [' ', '', '', 'ID'], $key));
        if ($level > 1) {
            $prettyKey = str_repeat("&nbsp;&nbsp;", $level - 1) . $prettyKey;
        }
    }

    if ($prettyKey !== "") {
        echo "<span class='bluem-request-label' title='$prettyKey'>
            ".esc_html($prettyKey).":
        </span> ";
    }

    if (is_string($value)) {
        if ($prettyKey === 'Contactform7') {
            bluem_woocommerce_render_contactform7_table($value);
        } elseif ($prettyKey === 'Details') {
            bluem_woocommerce_render_details_table($value);
        } else {
            echo wp_kses_post($value);
        }
    } elseif (is_iterable($value) || is_object($value)) {
        echo "<br>";
        foreach ($value as $valuekey => $valuevalue) {
            if ($valuekey === "linked_orders") {
                continue;
            }

            bluem_render_obj_row_recursive($valuekey, $valuevalue, $level + 1);
        }
    } elseif (is_bool($value)) {
        echo " " . ($value ? __("Ja",'bluem') : __("Nee",'bluem'));
    }

    echo "<br>";
}

function bluem_woocommerce_render_details_table(string $value): void {
    $additional_details = json_decode($value);

    if (!empty($additional_details)) {
        $formHTML = '';

        if (!empty($additional_details->id) || !empty($additional_details->payload)) {
            $formHTML = '<table style="padding:5pt; border:1px solid #ddd; margin:10px 0; display: inline-block; vertical-align: inherit;">
<thead>
    <tr>
        <th style="text-align: left;">'.__('Naam','bluem').'</th>
        <th style="text-align: left;">'.__('Waarde','bluem').'</th>
    </tr>
</thead>
<tbody>';
        }

        if (!empty($additional_details->payload)) {
            try {

                $additional_details_payload = json_decode($additional_details->payload, false, 512, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                $additional_details_payload = [];
            }

            foreach ($additional_details_payload as $dKey => $dValue) {
                if ($dKey === 'source_url') {
                    $dValue = '<a href="' . $dValue . '" target="_blank">' . $dValue . '</a>';
                }

                $formHTML .= sprintf("
<tr>
<td><span class='bluem-request-label'>%s</span></td>
<td>%s</td></tr>", ucfirst(str_replace('_', ' ', $dKey)), $dValue);
            }
            $formLink = admin_url("admin.php?page=gf_entries&view=entry&id={$additional_details_payload->form_id}&lid={$additional_details_payload->entry_id}&order=ASC&filter&paged=1&pos=0&field_id&operator");
            $formHTML .= "
<tr>

<td><span class='bluem-request-label'>" . __('Formulier invulling', 'bluem') . "</span></td>
                        
                        <td>
                            <a href=\"$formLink\" target='_blank'>
                            " . __('Bekijk', 'bluem') . "</a>
                        </td>
                    </tr>";
        }
        $formHTML .= '</tbody></table>';


        if (!empty($formHTML)) {
            echo wp_kses_post($formHTML);
        } else {
            echo wp_kses_post($value);
        }
    }
}
function bluem_woocommerce_render_contactform7_table(string $value): void
{
    try {
        $contactFormData = json_decode($value, false, 512, JSON_THROW_ON_ERROR);
    } catch (Exception $e) {
        $contactFormData = null;
    }

    if (!empty($contactFormData)) {
        $formHTML = '';

        if (!empty($contactFormData->id)) {
            $formHTML = '<table style="display: inline-block; vertical-align: inherit;">
<thead>
    <tr>
        <th style="text-align: left;">' . __('Naam', 'bluem') . '</th>
        <th style="text-align: left;">' . __('Waarde', 'bluem') . '</th>
    </tr>
</thead>
<tbody>
    <tr>
        <td>' . __('Formulier ID', 'bluem') . ':</td>
        <td>' . $contactFormData->id . '</td>
    </tr>';
        }

        if (!empty($contactFormData->payload)) {
            foreach ($contactFormData->payload as $payloadKey => $payloadValue) {
                $formHTML .= '<tr>
                            <td>' . wp_kses_post($payloadKey) . '</td>
                            <td>' . wp_kses_post($payloadValue) . '</td>
                        </tr>';
            }
        }
        $formHTML .= '</tbody></table>';
        if (!empty($formHTML)) {
            echo wp_kses_post($formHTML);
        } else {
            echo wp_kses_post($value);
        }
    }
}

function bluem_render_requests_type($cat): string
{
    if ($cat === "mandates") {
        return __("Incassomachtigen", 'bluem');
    }

    if ($cat === "ideal") {
        return __("iDEAL", 'bluem');
    }

    if ($cat === "creditcard") {
        return __("Creditcard", 'bluem');
    }

    if ($cat === "paypal") {
        return __("PayPal", 'bluem');
    }

    if ($cat === "cartebancaire") {
        return __("Carte Bancaire", 'bluem');
    }

    if ($cat === "sofort") {
        return __("SOFORT", 'bluem');
    }

    if ($cat === "identity") {
        return __("Identiteit", 'bluem');
    }

    if ($cat === "integrations") {
        return __("Integraties", 'bluem');
    }
    return __('Onbekend type', 'bluem') . ": " . esc_html($cat);
}

function bluem_render_requests_table_title($cat): void
{
    $result = "";
    if ($cat === "mandates") {
        $result .= '<span class="dashicons dashicons-money"></span>&nbsp; ';
        $result .= __("Digitaal Incassomachtigen", 'bluem');
    } elseif ($cat === "ideal") {
        $result .= '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        $result .= __("iDEAL betalingen", 'bluem');
    } elseif ($cat === "creditcard") {
        $result .= '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        $result .= __("Creditcard betalingen", 'bluem');
    } elseif ($cat === "paypal") {
        $result .= '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        $result .= __("PayPal betalingen", 'bluem');
    } elseif ($cat === "cartebancaire") {
        $result .= '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        $result .= __("Carte Bancaire betalingen", 'bluem');
    } elseif ($cat === "sofort") {
        $result .= '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        $result .= __("SOFORT betalingen", 'bluem');
    } elseif ($cat === "identity") {
        $result .= '<span class="dashicons dashicons-businessperson"></span>&nbsp; ';
        $result .= __("Identiteit", 'bluem');
    } elseif ($cat === "integrations") {
        $result .= '<span class="dashicons dashicons-businessperson"></span>&nbsp; ';
        $result .= __("Integraties", 'bluem');
    }

    echo "<h2>" . wp_kses_post($result) . "</h2>";
}


function bluem_render_nav_header($active_page = '')
{
    ?>
    <nav class="nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=bluem-admin'); ?>"
            <?php if ($active_page === "home") {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>>
            <span class="dashicons dashicons-admin-home"></span>
            <?php _e('Home', 'bluem'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=bluem-activate'); ?>"
            <?php if ($active_page === "activate") {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>>
            <span class="dashicons dashicons-yes-alt"></span>
            <?php _e('Activatie', 'bluem'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=bluem-transactions'); ?>"
            <?php if ($active_page === "transactions") {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>>
            <span class="dashicons dashicons-money"></span>
            <?php _e('Transacties', 'bluem'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=bluem-settings'); ?>"
            <?php if ($active_page === "settings") {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>>
            <span class="dashicons dashicons-admin-settings"></span>
            <?php _e('Instellingen', 'bluem'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=bluem-importexport'); ?>"
            <?php if ($active_page === "importexport") {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>>
            <span class="dashicons dashicons-database"></span>
            <?php _e('Import / export', 'bluem'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=bluem-status'); ?>"
            <?php if ($active_page === "status") {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>>
            <span class="dashicons dashicons-info"></span>
            <?php _e('Status', 'bluem'); ?>
        </a>
        <a href="https://www.notion.so/codexology/Bluem-voor-WordPress-WooCommerce-Handleiding-9e2df5c5254a4b8f9cbd272fae641f5e"
           target="_blank"
           class="nav-tab">
            <span class="dashicons dashicons-media-document"></span>
            <?php _e('Handleiding', 'bluem'); ?>
            <small>
                <span class="dashicons dashicons-external"></span>
            </small>
        </a>
        <a href="mailto:pluginsupport@bluem.nl?subject=Bluem+Wordpress+Plugin" class="nav-tab" target="_blank">
            <span class="dashicons dashicons-editor-help"></span>
            <?php _e('E-mail support', 'bluem'); ?>
        </a>
    </nav>

    <?php
}


// Helper class for layout

/**
 * @param string $requestTimestamp
 * @param string $format
 * @return string
 */
function bluem_get_formattedDate(string $requestTimestamp, string $format = 'd-m-Y H:i:s'): string
{
    try {
        $dateTime = new DateTime($requestTimestamp);
    } catch (Exception $e) {
        return '';
    }

    $dateTime->setTimezone(new DateTimeZone('Europe/Amsterdam'));
    return $dateTime->format($format);
}


