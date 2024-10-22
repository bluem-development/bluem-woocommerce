<?php
if (!defined('ABSPATH')) {
    exit;
}
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
    return sprintf(
        '<img src="%sassets/bluem/logo.png" class="bluem-bluem-logo" height="' . esc_attr($height) . '" style="max-height:' . esc_attr($height) . 'px; margin:10pt;  margin-bottom:0;  alt="Bluem logo"
        "/>',
        plugin_dir_url(__FILE__)
    );
}


function bluem_render_request_table($categoryName, $requests, $users_by_id = array()): void
{
    if (count($requests) === 0) {
        echo '<p>';
        printf(
        /* translators: %s: Name of the category (Bluem service)   */
            esc_html__('No transactions yet for %s', 'bluem'),
            esc_attr($categoryName)
        );
        echo '</p>';

        return;
    } ?>

    <div class="bluem-requests-table-container">
        <table class="table widefat bluem-requests-table">
            <thead>
            <tr>
                <th style="width:20%;"><?php esc_html_e('Verzoek', 'bluem'); ?></th>
                <th style="width:20%;"><?php esc_html_e('Gebruiker', 'bluem'); ?></th>
                <th style="width:15%;"><?php esc_html_e('Datum', 'bluem'); ?></th>
                <th style="width:20%;"><?php esc_html_e('Extra informatie', 'bluem'); ?></th>
                <th style="width:20%;"><?php esc_html_e('Status', 'bluem'); ?></th>
                <th style="width:5%;"></th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($requests as $r) {
                $prettyRequestDate = date_i18n('d-m-Y H:i:s', strtotime($r->timestamp));
                ?>
                <tr>
                    <td width="20%">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=bluem-transactions&request_id=' . $r->id)); ?>"
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
                        if (!is_null($r->order_id) && $r->order_id != '0') {
                            try {
                                $order = new WC_Order($r->order_id);
                            } catch (Throwable $th) {
                                $order = false;
                            }
                            if ($order !== false) {
                                ?>
                                <a href="<?php echo esc_url(admin_url("post.php?post={$r->order_id}&action=edit")); ?>"
                                   target="_blank">
                                    <?php esc_html_e('Bestelling', 'bluem'); ?><?php echo esc_html($order->get_order_number()); ?>
                                    (<?php echo wp_kses_post(wc_price($order->get_total())); ?>)
                                </a>
                                <?php
                            } else {
                                echo '&nbsp;';
                            }
                        }
                        ?>
                        <?php
                        if (isset($r->debtor_reference) && $r->debtor_reference !== '') {
                            ?>

                            <span style="color:#aaa; font-size:9pt; display:block;"><?php esc_html_e('Klantreferentie', 'bluem'); ?>:
							<?php
                            echo esc_html($r->debtor_reference);
                            ?>
			</span>
                            <?php
                        }
                        ?>
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
                    " . esc_html__('Aangemaakt', 'bluem') . '
                </span>';

            break;
        }

        case 'success':
        {
            echo "<span style='color:#2e9801'>

                <span class='dashicons dashicons-yes-alt'></span>
                        " . esc_html__('Succesvol', 'bluem') . ' afgerond
                    </span>';

            break;
        }

        case 'cancelled':
        {
            echo "<span style='color:#bd1818'>
                        <span class='dashicons dashicons-dismiss'></span>
                        " . esc_html__('Geannuleerd', 'bluem') . '</span>';
            break;
        }
        case 'expired':
        {
            echo "<span style='color:#bd1818'>
                        <span class='dashicons dashicons-dismiss'></span>
                        " . esc_html__('Verlopen', 'bluem') . '</span>';
            break;
        }
        case 'open':
        case 'new':
        {
            echo "<span style='color:#6a4285'>

                        <span class='dashicons dashicons-editor-help'></span>
                        " . esc_html__('Openstaand', 'bluem') . '</span>';
            break;
        }
        case 'pending':
        {
            echo "<span style='color:#6a4285' title='mogelijk moet dit verzoek nog worden ondertekend door een tweede ondertekenaar'>

                        <span class='dashicons dashicons-editor-help'></span>
                        " . esc_html__('in afwachting van verwerking', 'bluem') . '</span>';
            break;
        }
        case 'processing':
        {
            echo "<span style='color:#6a4285'>

                        <span class='dashicons dashicons-marker'></span>
                        " . esc_html__('In verwerking', 'bluem') . '</span>';
            break;
        }

        case 'insufficient':
        {
            echo "<span style='color:#ac1111'>

                            <span class='dashicons dashicons-dismiss'></span>
                            " . esc_html__('Ontoereikend', 'bluem') . '</span>';
            break;
        }
        case 'failure':
        {
            echo "<span style='color:#ac1111'>
                    <span class='dashicons dashicons-dismiss'></span>
                    " . esc_html__('Gefaald', 'bluem') . '</span>';
            break;
        }
        default:
        {
            echo 'Status:' . esc_html($status);
            break;
        }
    }
}

function bluem_render_request_user(object $r, array $users_by_id): void
{
    if (isset($users_by_id[(int)$r->user_id])) {
        ?>
        <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $r->user_id . '#user_' . $r->type)); ?>"
           target="_blank">
            <?php echo esc_html($users_by_id[(int)$r->user_id]->user_nicename); ?>
        </a>

        <?php
    } else {
        esc_html_e('Gastgebruiker/onbekend', 'bluem');
    }
}

function bluem_render_footer($align_right = true): void
{
    ?>

    <p style="display:block;
    <?php
    if ($align_right) {
        echo 'text-align:right;';
    }
    ?>
            ">
        <?php esc_html_e('Hulp nodig?', 'bluem'); ?>
        <br>
        <a href="https://bluem-development.github.io/bluem-docs/wordpress-woo-handleiding.html"
           target="_blank" style="text-decoration:none;">
            <span class="dashicons dashicons-media-document"></span>
            <?php esc_html_e('Handleiding', 'bluem'); ?>
            <small>
                <span class="dashicons dashicons-external"></span>
            </small>
        </a>
        &middot;
        <a href="mailto:pluginsupport@bluem.nl?subject=Bluem+WordPress+Plugin" target="_blank"
           style="text-decoration:none;">
            <span class="dashicons dashicons-editor-help"></span>
            <?php esc_html_e('E-mail support', 'bluem'); ?></a>

    </p>
    <?php
}

function bluem_render_requests_list($requests)
{
    ?>
    <div class="bluem-request-list">
        <?php
        foreach ($requests as $r) {
            $pl = json_decode($r->payload);
            ?>
            <div class="bluem-request-list-item">


                <?php
                if ($r->type === 'payments' || $r->type === 'mandates') {
                    if (!is_null($pl)) {
                        ?>
                        <div class="bluem-request-list-item-floater">
                            <?php
                            foreach ($pl as $k => $v) {
                                bluem_render_obj_row_recursive($k, $v);
                            }
                            ?>
                        </div>
                        <?php
                    }
                } elseif ($r->type === 'identity') {
                    ?>
                    <div class="bluem-request-list-item-floater">
                        <?php
                        if (!is_null($pl)) {
                            ?>

                            <div>
                                <?php
                                if (isset($pl->report->CustomerIDResponse)
                                    && $pl->report->CustomerIDResponse . '' != ''
                                ) {
                                    ?>
                                    <span class="bluem-request-label">
									<?php esc_html_e('CustomerID', 'bluem'); ?>:
				</span>
                                    <?php echo esc_html($pl->report->CustomerIDResponse); ?>
                                    <?php
                                }
                                ?>
                            </div>

                            <?php if (isset($pl->report->AddressResponse)) { ?>
                                <div>
				<span class="bluem-request-label">
								<?php esc_html_e('Adres', 'bluem'); ?>:
				</span>
                                    <?php
                                    foreach ($pl->report->AddressResponse as $k => $v) {
                                        echo esc_html($v . ' ');
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                            ?>

                            <?php if (isset($pl->report->BirthDateResponse)) { ?>
                                <div>
				<span class="bluem-request-label">
								<?php esc_html_e('Geb.datum', 'bluem'); ?>:
				</span>
                                    <?php echo esc_html($pl->report->BirthDateResponse); ?>


                                </div>
                            <?php } ?>
                            <?php if (isset($pl->report->EmailResponse)) { ?>
                                <div>
				<span class="bluem-request-label">
								<?php esc_html_e('E-mail', 'bluem'); ?>:
				</span>
                                    <?php echo esc_html($pl->report->EmailResponse); ?>

                                </div>
                            <?php } ?>
                            <?php if (isset($pl->report->TelephoneResponse1)) { ?>
                                <div>
				<span class="bluem-request-label">
								<?php esc_html_e('Telefoonnr.', 'bluem'); ?>:
				</span>
                                    <?php echo esc_html($pl->report->TelephoneResponse1); ?>

                                </div>

                            <?php } ?>
                            <?php
                            if (isset($pl->environment)) {
                                ?>
                                <div>
				<span class="bluem-request-label">
								<?php esc_html_e('Bluem modus', 'bluem'); ?>:
				</span>
                                    <?php echo esc_attr(ucfirst($pl->environment)); ?>
                                </div>
                                <?php
                            }
                            ?>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                }
                ?>

                <div class="bluem-request-list-item-row bluem-request-list-item-row-title">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bluem-transactions&request_id=' . $r->id)); ?>"
                       target="_self">
                        <?php echo esc_html($r->description); ?>
                    </a>
                </div>
                <div class="bluem-request-list-item-row">

			<span class="bluem-request-label">
				<?php esc_html_e('Transactienummer', 'bluem'); ?>:

			</span>
                    <?php echo esc_html($r->transaction_id); ?>

                </div>
                <?php
                if (isset($r->debtor_reference) && $r->debtor_reference !== '') {
                    ?>
                    <div class="bluem-request-list-item-row">
			<span class="bluem-request-label">
					<?php esc_html_e('Klantreferentie', 'bluem'); ?>:
			</span>
                        <?php echo esc_html($r->debtor_reference); ?>
                    </div>
                    <?php
                }
                ?>
                <div class="bluem-request-list-item-row">

			<span class="bluem-request-label">
				<?php esc_html_e('Tijdstip', 'bluem'); ?>
			</span>
                    <?php $rdate = new DateTimeImmutable($r->timestamp, new DateTimeZone('Europe/Amsterdam')); ?>
                    <?php echo esc_html($rdate->format('d-m-Y H:i:s')); ?>
                </div>


                <div class="bluem-request-list-item-row">

			<span class="bluem-request-label">
				<?php esc_html_e('Status', 'bluem'); ?>:
			</span>
                    <?php bluem_render_request_status($r->status); ?>
                </div>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}


function bluem_render_obj_row_recursive($key, $value, $level = 0): void
{
    if ($key === 'linked_orders') {
        return;
    }

    if (is_numeric($key)) {
        $key = '';
        $prettyKey = '';
    } else {
        $prettyKey = ucfirst(str_replace(array('_', 'Response1', 'Response', 'id'), array(' ', '', '', 'ID'), $key));
        if ($level > 1) {
            $prettyKey = str_repeat('&nbsp;&nbsp;', $level - 1) . $prettyKey;
        }
    }

    if ($prettyKey !== '') {
        echo wp_kses_post(
            "<span class='bluem-request-label' title='" . esc_attr($prettyKey) . "'>
            " . esc_html($prettyKey) . ':
        </span>&nbsp;'
        );
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
        echo '<br>';
        foreach ($value as $valuekey => $valuevalue) {
            if ($valuekey === 'linked_orders') {
                continue;
            }

            bluem_render_obj_row_recursive($valuekey, $valuevalue, $level + 1);
        }
    } elseif (is_bool($value)) {
        echo ' ' . ($value ? esc_html__('Ja', 'bluem') : esc_html__('Nee', 'bluem'));
    }

    echo '<br>';
}

function bluem_woocommerce_render_details_table(string $value): void
{
    $additional_details = json_decode($value);

    if (!empty($additional_details)) {
        $formHTML = '';

        if (!empty($additional_details->id) || !empty($additional_details->payload)) {
            $formHTML = '<table style="padding:5pt; border:1px solid #ddd; margin:10px 0; display: inline-block; vertical-align: inherit;">
<thead>
    <tr>
        <th style="text-align: left;">' . esc_html__('Naam', 'bluem') . '</th>
        <th style="text-align: left;">' . esc_html__('Waarde', 'bluem') . '</th>
    </tr>
</thead>
<tbody>';
        }

        if (!empty($additional_details->payload)) {
            try {

                $additional_details_payload = json_decode($additional_details->payload, false, 512, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                $additional_details_payload = array();
            }

            foreach ($additional_details_payload as $dKey => $dValue) {
                if ($dKey === 'source_url') {
                    $dValue = '<a href="' . $dValue . '" target="_blank">' . $dValue . '</a>';
                }

                $formHTML .= sprintf(
                    "
<tr>
<td><span class='bluem-request-label'>%s</span></td>
<td>%s</td></tr>",
                    ucfirst(str_replace('_', ' ', $dKey)),
                    $dValue
                );
            }
            $formLink = admin_url("admin.php?page=gf_entries&view=entry&id={$additional_details_payload->form_id}&lid={$additional_details_payload->entry_id}&order=ASC&filter&paged=1&pos=0&field_id&operator");
            $formHTML .= "
<tr>

<td><span class='bluem-request-label'>" . esc_html__('Formulier invulling', 'bluem') . "</span></td>
                        
                        <td>
                            <a href=\"$formLink\" target='_blank'>
                            " . esc_html__('Bekijk', 'bluem') . '</a>
                        </td>
                    </tr>';
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
        <th style="text-align: left;">' . esc_html__('Naam', 'bluem') . '</th>
        <th style="text-align: left;">' . esc_html__('Waarde', 'bluem') . '</th>
    </tr>
</thead>
<tbody>
    <tr>
        <td>' . esc_html__('Formulier ID', 'bluem') . ':</td>
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
    if ($cat === 'mandates') {
        return esc_html__('Incassomachtigen', 'bluem');
    }

    if ($cat === 'ideal') {
        return esc_html__('iDEAL', 'bluem');
    }

    if ($cat === 'creditcard') {
        return esc_html__('Creditcard', 'bluem');
    }

    if ($cat === 'paypal') {
        return esc_html__('PayPal', 'bluem');
    }

    if ($cat === 'cartebancaire') {
        return esc_html__('Carte Bancaire', 'bluem');
    }

    if ($cat === 'sofort') {
        return esc_html__('SOFORT', 'bluem');
    }

    if ($cat === 'identity') {
        return esc_html__('Identiteit', 'bluem');
    }

    if ($cat === 'integrations') {
        return esc_html__('Integraties', 'bluem');
    }
    return esc_html__('Onbekend type', 'bluem') . ': ' . esc_html($cat);
}

function bluem_render_requests_table_title($cat): void
{
    $result = '';
    if ($cat === 'mandates') {
        $result .= '<span class="dashicons dashicons-money"></span>&nbsp; ';
        $result .= esc_html__('Digitaal Incassomachtigen', 'bluem');
    } elseif ($cat === 'ideal') {
        $result .= '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        $result .= esc_html__('iDEAL betalingen', 'bluem');
    } elseif ($cat === 'creditcard') {
        $result .= '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        $result .= esc_html__('Creditcard betalingen', 'bluem');
    } elseif ($cat === 'paypal') {
        $result .= '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        $result .= esc_html__('PayPal betalingen', 'bluem');
    } elseif ($cat === 'cartebancaire') {
        $result .= '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        $result .= esc_html__('Carte Bancaire betalingen', 'bluem');
    } elseif ($cat === 'sofort') {
        $result .= '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        $result .= esc_html__('SOFORT betalingen', 'bluem');
    } elseif ($cat === 'identity') {
        $result .= '<span class="dashicons dashicons-businessperson"></span>&nbsp; ';
        $result .= esc_html__('Identiteit', 'bluem');
    } elseif ($cat === 'integrations') {
        $result .= '<span class="dashicons dashicons-businessperson"></span>&nbsp; ';
        $result .= esc_html__('Integraties', 'bluem');
    }

    echo '<h2>' . wp_kses_post($result) . '</h2>';
}


function bluem_render_nav_header($active_page = '')
{
    ?>
    <nav class="nav-tab-wrapper">
        <a href="<?php echo esc_url(admin_url('admin.php?page=bluem-admin')); ?>"
            <?php
            if ($active_page === 'home') {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>
        >
            <span class="dashicons dashicons-admin-home"></span>
            <?php esc_html_e('Home', 'bluem'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=bluem-activate')); ?>"
            <?php
            if ($active_page === 'activate') {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>
        >
            <span class="dashicons dashicons-yes-alt"></span>
            <?php esc_html_e('Activatie', 'bluem'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=bluem-transactions')); ?>"
            <?php
            if ($active_page === 'transactions') {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>
        >
            <span class="dashicons dashicons-money"></span>
            <?php esc_html_e('Transacties', 'bluem'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=bluem-settings')); ?>"
            <?php
            if ($active_page === 'settings') {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>
        >
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e('Instellingen', 'bluem'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=bluem-importexport')); ?>"
            <?php
            if ($active_page === 'importexport') {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>
        >
            <span class="dashicons dashicons-database"></span>
            <?php esc_html_e('Import / export', 'bluem'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=bluem-status')); ?>"
            <?php
            if ($active_page === 'status') {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>
        >
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e('Status', 'bluem'); ?>
        </a>
        <a href="https://bluem-development.github.io/bluem-docs/wordpress-woo-handleiding.html"
           target="_blank"
           class="nav-tab">
            <span class="dashicons dashicons-media-document"></span>
            <?php esc_html_e('Handleiding', 'bluem'); ?>
            <small>
                <span class="dashicons dashicons-external"></span>
            </small>
        </a>
        <a href="mailto:pluginsupport@bluem.nl?subject=Bluem+WordPress+Plugin" class="nav-tab" target="_blank">
            <span class="dashicons dashicons-editor-help"></span>
            <?php esc_html_e('E-mail support', 'bluem'); ?>
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


