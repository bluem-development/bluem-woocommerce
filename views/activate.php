<div class="wrap">
    <h1>
        <?php echo bluem_get_bluem_logo_html(48);?>
    </h1>

    <?php bluem_render_nav_header('activate');?>

    <h1 style="margin-top: 25px;">Welkom bij Bluem!</h1>

    <p>Bedankt voor het gebruik maken van de Bluem WordPress WooCommerce plug-in.</p>
    <p>Om verder te gaan, vragen wij u vriendelijk onderstaande gegevens in te vullen.</p>

    <?php if ( $bluem_plugin_registration == 1 ) { ?>
        <div class="notice notice-success is-dismissible">
            <p><span class="dashicons dashicons-yes-alt"></span> De plug-in is geactiveerd.</p>

            Volgende stappen:
            <ul>
                <?php if(is_woocommerce_activated()) { ?>

                <li>
                    ✓&nbsp;
                    <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout');?>"
                       title="WooCommerce betaalmethoden"
                    >
                        Activeer en beheer de betaalmethoden in WooCommerce instellingen
                    </a>
                </li>
                <?php } ?>
                <li>
                    ✓&nbsp;
                    <a href="<?php echo admin_url('admin.php?page=bluem-transactions');?>"
                       title="WooCommerce instellingen"
                    >
                        Bekijk de transactieweergave
                    </a>
                </li>
                <li>
                    ✓&nbsp;
                    Ga naar <strong>Instellingen</strong> en beheer de specifieke instellingen per betaalmethode.<br>
                    Hier vind je ook de integratiemogelijkheden met Gravity Forms en ContactForm 7.
                </li>
            </ul>



        </div>
    <?php } ?>

    <?php if ( isset ( $is_valid ) && ! $is_valid ) { ?>
        <div class="notice notice-warning is-dismissible">
            <p><span class="dashicons dashicons-warning"></span> Gelieve alle verplichte velden correct in te vullen.</p>
        </div>
    <?php } ?>

    <h3>Account vereist</h3>
    <p>Voor het gebruik van onze diensten is een account vereist.<br />Kijk voor meer informatie op de <a href="https://bluem.nl/direct-online-betalen/" title="Bluem website bezoeken" target="_blank">Bluem website</a>, bel <a href="tel:+31852220400" title="Bellen naar Bluem">+31(0)85-2220400</a> of e-mail naar <a href="mailto:info@bluem.nl" title="Mailen naar Bluem">info@bluem.nl</a>.</p>

    <form id="activateform" method="POST">
        <h2>Accountgegevens</h2>
        <p>Vul hieronder de accountgegevens in, zoals door Bluem is verstrekt.<br />Neem voor meer informatie contact op met uw accountmanager.<br />Laat velden leeg om dit later op te geven.</p>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">SenderID</th>
                    <td><input type="text" name="acc_senderid" id="acc_senderid" value="<?php echo !empty( $_POST['acc_senderid'] ) ? $_POST['acc_senderid'] : (!empty( $bluem_options['senderID'] ) ? $bluem_options['senderID'] : ''); ?>" class="form-control"></td>
                </tr>
                <tr>
                    <th scope="row">Test token</th>
                    <td><input type="password" name="acc_testtoken" id="acc_testtoken" value="<?php echo !empty( $_POST['acc_testtoken'] ) ? $_POST['acc_testtoken'] : (!empty( $bluem_options['test_accessToken'] ) ? $bluem_options['test_accessToken'] : ''); ?>" class="form-control" style="width: 425px;"></td>
                </tr>
                <tr>
                    <th scope="row">Productie token</th>
                    <td><input type="password" name="acc_prodtoken" id="acc_prodtoken" value="<?php echo !empty( $_POST['acc_prodtoken'] ) ? $_POST['acc_prodtoken'] : (!empty( $bluem_options['production_accessToken'] ) ? $bluem_options['production_accessToken'] : ''); ?>" class="form-control" style="width: 425px;"></td>
                </tr>
            </tbody>
        </table>

        <h2>Bedrijfsgegevens</h2>
        <p>Vul hieronder de gegevens van uw bedrijf in.</p>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Bedrijfsnaam *</th>
                    <td><input type="text" name="company_name" id="company_name" value="<?php echo !empty( $_POST['company_name'] ) ? $_POST['company_name'] : (!empty( $bluem_registration['company'] ) && !empty( $bluem_registration['company']['name'] ) ? $bluem_registration['company']['name'] : ''); ?>" class="form-control" required="required"></td>
                </tr>
                <tr>
                    <th scope="row">Telefoonnummer *</th>
                    <td><input type="tel" name="company_telephone" id="company_telephone" value="<?php echo !empty( $_POST['company_telephone'] ) ? $_POST['company_telephone'] : (!empty( $bluem_registration['company'] ) && !empty( $bluem_registration['company']['telephone'] ) ? $bluem_registration['company']['telephone'] : ''); ?>" class="form-control" required="required"></td>
                </tr>
                <tr>
                    <th scope="row">E-mailadres *</th>
                    <td><input type="email" name="company_email" id="company_email" value="<?php echo !empty( $_POST['company_email'] ) ? $_POST['company_email'] : (!empty( $bluem_registration['company'] ) && !empty( $bluem_registration['company']['email'] ) ? $bluem_registration['company']['email'] : ''); ?>" class="form-control" required="required"></td>
                </tr>
            </tbody>
        </table>

        <h2>Technisch contactpersoon</h2>
        <p>Vul hieronder de gegevens van de technisch contactpersoon in.<br />Ingeval van belangrijke updates brengen wij de contactpersoon op de hoogte.</p>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">Voor- en achternaam *</th>
                    <td><input type="text" name="tech_name" id="tech_name" value="<?php echo !empty( $_POST['tech_name'] ) ? $_POST['tech_name'] : (!empty( $bluem_registration['tech_contact'] ) && !empty( $bluem_registration['tech_contact']['name'] ) ? $bluem_registration['tech_contact']['name'] : ''); ?>" class="form-control" required="required"></td>
                </tr>
                <tr>
                    <th scope="row">Telefoonnummer *</th>
                    <td><input type="tel" name="tech_telephone" id="tech_telephone" value="<?php echo !empty( $_POST['tech_telephone'] ) ? $_POST['tech_telephone'] : (!empty( $bluem_registration['tech_contact'] ) && !empty( $bluem_registration['tech_contact']['telephone'] ) ? $bluem_registration['tech_contact']['telephone'] : ''); ?>" class="form-control" required="required"></td>
                </tr>
                <tr>
                    <th scope="row">E-mailadres *</th>
                    <td><input type="email" name="tech_email" id="tech_email" value="<?php echo !empty( $_POST['tech_email'] ) ? $_POST['tech_email'] : (!empty( $bluem_registration['tech_contact'] ) && !empty( $bluem_registration['tech_contact']['email'] ) ? $bluem_registration['tech_contact']['email'] : ''); ?>" class="form-control" required="required"></td>
                </tr>
            </tbody>
        </table>

        <input type="submit" value="Opslaan" class="button button-primary">
    </form>

    <?php bluem_render_footer(); ?>
</div>

<script type="text/javascript">

    /**
     * Validate the required inputs.
     *
     * @returns {boolean}
     */
    function validateRequiredInputs() {
        let isValid = true;

        // Select all input fields with the 'required' attribute
        jQuery("input[required]").each(function() {
            if (jQuery(this).val() === "") {
                isValid = false;

                jQuery(this).addClass('error'); // Add an 'error' class for visual indication
            } else {
                jQuery(this).removeClass('error');
            }
        });

        return isValid;
    }

    jQuery(document).ready(function() {
        jQuery("#activateform").on('submit', function(e) {
            if ( ! validateRequiredInputs() ) {
                e.preventDefault();

                alert("Gelieve alle verplichte velden correct in te vullen.");
            }
        });
    });

</script>
