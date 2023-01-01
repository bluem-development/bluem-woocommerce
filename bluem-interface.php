<?php

use Carbon\Carbon;

// @todo create a language file and consistently localize everything

function bluem_get_idin_logo_html() {
    return "<img src='" .
           plugin_dir_url( __FILE__ ) . "assets/bluem/idin.png' class='bluem-idin-logo'
        style='float:left; max-height:64px; margin:10pt 20pt 0 10pt;'/>";
}

// @todo make a stylesheet and include it, move all inline styles there.

function bluem_get_bluem_logo_html( $height = 64 ) {
    return '<img src="' .
           plugin_dir_url( __FILE__ ) . 'assets/bluem/logo.png' .
           '" class="bluem-bluem-logo" style="' .
           "max-height:{$height}px; margin:10pt;  margin-bottom:0; " .
           '"/>';
}


function bluem_render_request_table( $requests, $users_by_id = [] ) {
    if ( count( $requests ) == 0 ) {
        echo "<p>" . __( "Nog geen transacties", 'bluem' ) . "</p>";

        return;
    } ?>

    <div class="bluem-requests-table-container">
        <table class="table widefat bluem-requests-table">

            <thead>
            <tr>
                <th style="width:20%;">Verzoek</th>
                <th style="width:20%;">Gebruiker</th>
                <th style="width:20%;">Datum</th>
                <th style="width:20%;">Extra informatie</th>
                <th style="width:20%;">Status</th>
                <th style="width:20%;"></th>
            </tr>
            </thead>
            <tbody>


            <?php foreach ( $requests as $r ) {
                ?>
                <tr>


                    <td>
                        <a href="<?php echo admin_url( "admin.php?page=bluem-transactions&request_id=" . $r->id ); ?>"
                           target="_self">
                            <?php echo $r->description; ?>
                        </a>
                        <br>
                        <span style="color:#aaa; font-size:9pt;">
                    <?php echo $r->transaction_id; ?>
                </span>
                    </td>
                    <td><?php
                        bluem_render_request_user( $r, $users_by_id ); ?>
                    </td><?php $rdate = Carbon::parse( $r->timestamp, 'UTC' )->setTimeZone( "Europe/Amsterdam" ); ?>
                    <td title="<?php echo $rdate->format( "d-m-Y H:i:s" ); ?>">
                        <?php echo $rdate->format( "d-m-Y H:i:s" ); ?>
                    </td>
                    <td>
                        <?php
                        if ( ! is_null( $r->order_id ) && $r->order_id != "0" ) {
                            try {
                                $order = new WC_Order( $r->order_id );
                            } catch ( Throwable $th ) {
                                $order = false;
                            }
                            if ( $order !== false ) {
                                ?>
                            <a href="<?php echo admin_url( "post.php?post={$r->order_id}&action=edit" ); ?>"
                               target="_blank">
                                Bestelling <?php echo $order->get_order_number() ?> (<?php echo wc_price( $order->get_total() ); ?>)
                                </a><?php
                            } else {
                                echo "&nbsp;";
                            }
                        } ?>
                        <?php if ( isset( $r->debtor_reference ) && $r->debtor_reference !== "" ) {
                            ?>

                            <span style="color:#aaa; font-size:9pt; display:block;">Klantreferentie:
            <?php
            echo $r->debtor_reference; ?>
            </span>
                            <?php
                        } ?>
                    </td>
                    <td>
                        <?php bluem_render_request_status( $r->status ); ?>
                    </td>
                    <td></td>
                </tr>


                <?php
            } ?>

            </tbody>
        </table>
    </div>
    <?php
}


function bluem_render_request_status( $status ) {
    switch ( strtolower( $status ) ) {
        case 'created':
        {
            echo "<span style='color:#1a94c0;'>
                <span class='dashicons dashicons-plus-alt'></span>
                    Aangemaakt
                </span>";

            break;
        }

        case 'success':
        {
            echo "<span style='color:#2e9801'>

                <span class='dashicons dashicons-yes-alt'></span>
                        Succesvol afgerond
                    </span>";

            break;
        }

        case 'cancelled':
        {
            echo "<span style='color:#bd1818'>

                        <span class='dashicons dashicons-dismiss'></span>
                        Geannuleerd</span>";
            break;
        }
        case 'expired':
        {
            echo "<span style='color:#bd1818'>

                        <span class='dashicons dashicons-dismiss'></span>
                        Verlopen</span>";
            break;
        }
        case 'open':
        case 'new':
        {
            echo "<span style='color:#6a4285'>

                        <span class='dashicons dashicons-editor-help'></span>
                        Openstaand</span>";
            break;
        }
        case 'pending':
        {
            echo "<span style='color:#6a4285' title='mogelijk moet dit verzoek nog worden ondertekend door een tweede ondertekenaar'>

                        <span class='dashicons dashicons-editor-help'></span>
                        in afwachting van verwerking</span>";
            break;
        }
        case 'processing':
        {
            echo "<span style='color:#6a4285'>

                        <span class='dashicons dashicons-marker'></span>
                        In verwerking</span>";
            break;
        }

        case 'insufficient':
        {

            echo "<span style='color:#ac1111'>

                            <span class='dashicons dashicons-dismiss'></span>
                            Ontoereikend</span>";
            break;
        }
        case 'failure':
        {

            echo "<span style='color:#ac1111'>

                    <span class='dashicons dashicons-dismiss'></span>
                    Gefaald</span>";
            break;
        }


        default:
        {
            echo $status;
            break;
        }
    }
}

function bluem_render_request_user( $r, $users_by_id ) {
    if ( isset( $users_by_id[ (int) $r->user_id ] ) ) {
        ?>
        <a href="<?php echo admin_url( "user-edit.php?user_id=" . $r->user_id . "#user_" . $r->type ); ?>"
           target="_blank">
            <?php
            echo $users_by_id[ (int) $r->user_id ]->user_nicename; ?>
        </a>

        <?php
    } else {
        echo "Gast/onbekend";
    }
}

function bluem_render_footer( $align_right = true ) {
    ?>

    <p style="display:block;
    <?php
    if ( $align_right ) {
        echo 'text-align:right;';
    } ?>
        ">
        Hulp nodig?
        <br>
        <a href="https://www.notion.so/codexology/Bluem-voor-WordPress-WooCommerce-Handleiding-9e2df5c5254a4b8f9cbd272fae641f5e"
           target="_blank" style="text-decoration:none;">
            <span class="dashicons dashicons-media-document"></span>
            Handleiding</a>
        &middot;
        <a href="mailto:pluginsupport@bluem.nl?subject=Bluem+Wordpress+Plugin" target="_blank"
           style="text-decoration:none;">
            <span class="dashicons dashicons-editor-help"></span>
            E-mail support</a>

    </p>
    <?php
}


function bluem_render_requests_list( $requests ) {
    ?>
    <div class="bluem-request-list">
        <?php foreach ( $requests as $r ) {
            $pl = json_decode( $r->payload ); ?>
            <div class="bluem-request-list-item">


                <?php
                if ( $r->type == "payments" || $r->type == "mandates" ) {
                    if ( ! is_null( $pl ) ) {
                        ?>
                        <div class="bluem-request-list-item-floater">
                        <?php
                        foreach ( $pl as $k => $v ) {
                            bluem_render_obj_row_recursive( $k, $v );
                        } ?>
                        </div><?php
                    }
                } elseif ( $r->type == "identity" ) {
                    ?>
                    <div class="bluem-request-list-item-floater">
                        <?php
                        if ( ! is_null( $pl ) ) {
                            ?>

                            <div>
                                <?php if ( isset( $pl->report->CustomerIDResponse )
                                           && $pl->report->CustomerIDResponse . "" != ""
                                ) { ?>
                                    <span class="bluem-request-label">
                    CustomerID:
                </span>
                                    <?php echo $pl->report->CustomerIDResponse; ?>
                                    <?php
                                } ?>
                            </div>

                            <?php if ( isset( $pl->report->AddressResponse ) ) { ?>
                                <div>
                <span class="bluem-request-label">
                Adres
                </span>
                                    <?php foreach ( $pl->report->AddressResponse as $k => $v ) {
                                        echo "{$v} ";
                                    } ?>
                                </div>
                                <?php
                            } ?>

                            <?php if ( isset( $pl->report->BirthDateResponse ) ) { ?>
                                <div>
                <span class="bluem-request-label">
                    Geb.datum
                </span>
                                    <?php echo $pl->report->BirthDateResponse; ?>


                                </div>
                            <?php } ?>
                            <?php if ( isset( $pl->report->EmailResponse ) ) { ?>
                                <div>
                <span class="bluem-request-label">
                    E-mail
                </span>
                                    <?php echo $pl->report->EmailResponse; ?>

                                </div>
                            <?php } ?>
                            <?php if ( isset( $pl->report->TelephoneResponse1 ) ) { ?>
                                <div>
                <span class="bluem-request-label">
                    Telefoonnr.
                </span>
                                    <?php echo $pl->report->TelephoneResponse1; ?>

                                </div>

                            <?php } ?>
                            <?php
                            if ( isset( $pl->environment ) ) { ?>
                                <div>
                <span class="bluem-request-label">
                Bluem modus
                </span>
                                    <?php echo ucfirst( $pl->environment ); ?>
                                </div>
                                <?php
                            } ?>
                            <?php
                        } ?>
                    </div>
                    <?php
                } ?>

                <div class="bluem-request-list-item-row bluem-request-list-item-row-title">
                    <a href="<?php echo admin_url( "admin.php?page=bluem-transactions&request_id=" . $r->id ); ?>"
                       target="_self">
                        <?php echo $r->description; ?>
                    </a>
                </div>
                <div class="bluem-request-list-item-row">

            <span class="bluem-request-label">
                Transactienummer:
            </span>
                    <?php echo $r->transaction_id; ?>

                </div>
                <?php if ( isset( $r->debtor_reference ) && $r->debtor_reference !== "" ) {
                    ?>
                    <div class="bluem-request-list-item-row">
            <span class="bluem-request-label">
                Klantreferentie
            </span>
                        <?php echo $r->debtor_reference; ?>
                    </div>
                    <?php
                } ?>
                <div class="bluem-request-list-item-row">

            <span class="bluem-request-label">
                Tijdstip
            </span>
                    <?php $rdate = strtotime( $r->timestamp ); ?>
                    <?php echo date( "d-m-Y H:i:s", $rdate ); ?>
                </div>


                <div class="bluem-request-list-item-row">

            <span class="bluem-request-label">
                Status:
            </span>
                    <?php bluem_render_request_status( $r->status ); ?>
                </div>
            </div>
            <?php
        } ?>
    </div>
    <?php
}


function bluem_render_obj_row_recursive( $key, $value, $level = 0 ) {
    if ( $key == "linked_orders" ) {
        return;
    }
    if ( is_numeric( $key ) ) {
        $key     = "";
        $nicekey = "";
    } else {
        $nicekey = ucfirst( str_replace( [ '_', 'Response1', 'Response', 'id' ], [ ' ', '', '', 'ID' ], $key ) );
        if ( $level > 1 ) {
            $nicekey = str_repeat( "&nbsp;&nbsp;", $level - 1 ) . $nicekey;
        }
    }
    if ( is_string( $value ) ) {
        if ( $nicekey !== "" ) {
            echo "<span class='bluem-request-label'>
                {$nicekey}:
                </span> ";
        }

        if ($nicekey === 'Contactform7') {
            $contactform_details = json_decode($value);

            if (!empty($contactform_details))
            {
                $form_details = '';

                if (!empty($contactform_details->id)) {
                    $form_details = '<table style="display: inline-block; vertical-align: inherit;"><thead><tr><th style="text-align: left;">Naam</th><th style="text-align: left;">Waarde</th></tr></thead><tbody><tr><td>Formulier:</td><td>' . $contactform_details->id . '</td></tr>';
                }

                if (!empty($contactform_details->payload)) {
                    foreach ($contactform_details->payload as $key => $value) {
                        $form_details .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
                    }
                }

                if (!empty($form_details)) {
                    echo $form_details . '</tbody></table>';
                } else {
                    echo "{$value}";
                }
            }
        } else {
            echo "{$value}";
        }
    } else {
        if ( $nicekey !== "" ) {
            echo "<span class='bluem-request-label'>
        {$nicekey}:
        </span>";
        }
        if ( is_iterable( $value ) || is_object( $value ) ) {
            echo "<br>";
            foreach ( $value as $valuekey => $valuevalue ) {
                if ( $key == "linked_orders" ) {
                    continue;
                    // $valuevalue = "<a href='". admin_url("post.php?post={$valuevalue}&action=edit")."' target='_blank'>$valuevalue</a>";
                }
                bluem_render_obj_row_recursive( $valuekey, $valuevalue, $level + 1 );
            }
        } else {
            if ( is_bool( $value ) ) {
                echo " " . ( $value ? "Ja" : "Nee" );
            } else {
                var_dump( $value );
            }
        }
    }
    echo "<br>";
}


function bluem_render_requests_table_title( $cat ) {
    echo "<h2>";
    if ( $cat == "mandates" ) {
        echo '<span class="dashicons dashicons-money"></span>&nbsp; ';
        echo "Digitaal Incassomachtigen";
    } elseif ( $cat == "ideal" ) {
        echo '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        echo "iDEAL betalingen";
    } elseif ( $cat == "creditcard" ) {
        echo '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        echo "Creditcard betalingen";
    } elseif ( $cat == "paypal" ) {
        echo '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        echo "PayPal betalingen";
    } elseif ( $cat == "cartebancaire" ) {
        echo '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        echo "Carte Bancaire betalingen";
    } elseif ( $cat == "sofort" ) {
        echo '<span class="dashicons dashicons-money-alt"></span>&nbsp; ';
        echo "SOFORT betalingen";
    } elseif ( $cat == "identity" ) {
        echo '<span class="dashicons dashicons-businessperson"></span>&nbsp; ';
        echo "Identiteit";
    } elseif ( $cat == "integrations" ) {
        echo '<span class="dashicons dashicons-businessperson"></span>&nbsp; ';
        echo "Integraties";
    }
    echo "</h2>";
}


function bluem_render_nav_header( $active_page = '' ) {


    ?>
    <nav class="nav-tab-wrapper">
        <a href="<?php echo admin_url( 'admin.php?page=bluem-admin' ); ?>"
            <?php if ( $active_page == "home" ) {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>>
            <span class="dashicons dashicons-admin-home"></span>
            Home
        </a>
        <a href="<?php echo admin_url( 'admin.php?page=bluem-transactions' ); ?>"
            <?php if ( $active_page == "transactions" ) {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>>
            <span class="dashicons dashicons-money"></span>
            Transacties
        </a>
        <a href="<?php echo admin_url( 'admin.php?page=bluem-settings' ); ?>"
            <?php if ( $active_page == "settings" ) {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>>
            <span class="dashicons dashicons-admin-settings"></span>
            Instellingen
        </a>
        <a href="<?php echo admin_url( 'admin.php?page=bluem-importexport' ); ?>"
            <?php if ( $active_page == "importexport" ) {
                echo 'class="nav-tab nav-active tab-active active"  style="background-color: #fff;"';
            } else {
                echo 'class="nav-tab"';
            }
            ?>>
            <span class="dashicons dashicons-database"></span>
            Import / export
        </a>
        <a href="https://www.notion.so/codexology/Bluem-voor-WordPress-WooCommerce-Handleiding-9e2df5c5254a4b8f9cbd272fae641f5e"
           target="_blank"
           class="nav-tab">
            <span class="dashicons dashicons-media-document"></span>
            Handleiding
        </a>
        <a href="mailto:pluginsupport@bluem.nl?subject=Bluem+Wordpress+Plugin" class="nav-tab" target="_blank">
            <span class="dashicons dashicons-editor-help"></span>
            E-mail support
        </a>
    </nav>

    <?php
}
