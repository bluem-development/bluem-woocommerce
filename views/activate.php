<?php if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap">
    <h1>
        <?php echo bluem_get_bluem_logo_html(48); ?>
    </h1>

    <?php bluem_render_nav_header('activate'); ?>

    <h1 style="margin-top: 25px;"><?php _e('Welkom bij Bluem!', 'bluem'); ?></h1>

    <p><?php _e('Bedankt voor het gebruik maken van de Bluem WordPress WooCommerce plug-in', 'bluem'); ?></p>
    <p><?php _e('Om verder te gaan, vragen wij u vriendelijk onderstaande gegevens in te vullen', 'bluem'); ?></p>

    <?php if (isset($bluem_plugin_registration) && $bluem_plugin_registration == 1) { ?>
        <div class="notice notice-success is-dismissible">
            <p><span class="dashicons dashicons-yes-alt"></span> <?php _e('De plug-in is geactiveerd', 'bluem'); ?>
            </p>

            <?php _e('Volgende stappen:', 'bluem'); ?>
            <ul>
                <?php if (is_woocommerce_activated()) { ?>

                    <li>
                        ✓&nbsp;
                        <a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout'); ?>"
                           title="<?php _e('WooCommerce betaalmethoden', 'bluem'); ?>"
                        >
                            <?php _e('Activeer en beheer de betaalmethoden in WooCommerce instellingen', 'bluem'); ?>
                        </a>
                    </li>
                <?php } ?>
                <li>
                    ✓&nbsp;
                    <a href="<?php echo admin_url('admin.php?page=bluem-transactions', 'bluem'); ?>"
                       title="<?php _e('WooCommerce instellingen', 'bluem'); ?>"
                    >
                        <?php _e('Bekijk de transactieweergave', 'bluem'); ?>
                    </a>
                </li>
                <li>
                    ✓&nbsp;
                    <?php _e('Ga naar <strong>Instellingen</strong> en beheer de specifieke instellingen per betaalmethode.<br>
                    Hier vind je ook de integratiemogelijkheden met Gravity Forms en ContactForm 7.', 'bluem'); ?>
                </li>
            </ul>


        </div>
    <?php } ?>

    <?php if (isset ($is_valid) && !$is_valid) { ?>
        <div class="notice notice-warning is-dismissible">
            <p><span class="dashicons dashicons-warning"></span>
                <?php _e('Gelieve alle verplichte velden correct in te vullen.', 'bluem'); ?></p>
        </div>
    <?php } ?>


    <form id="activateform" method="POST">
        <h2><?php _e('Accountgegevens', 'bluem'); ?></h2>
        <div class="wizard-flexbox">
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><?php _e('SenderID', 'bluem'); ?>
                    </th>
                    <td><input type="text" name="acc_senderid" id="acc_senderid"
                               value="<?php echo !empty($_POST['acc_senderid']) ? sanitize_text_field($_POST['acc_senderid']) : (!empty($bluem_options['senderID']) ? $bluem_options['senderID'] : ''); ?>"
                               class="form-control"></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Test token', 'bluem'); ?>
                    </th>
                    <td><input type="password" name="acc_testtoken" id="acc_testtoken"
                               value="<?php echo !empty($_POST['acc_testtoken']) ? sanitize_text_field($_POST['acc_testtoken']) : (!empty($bluem_options['test_accessToken']) ? $bluem_options['test_accessToken'] : ''); ?>"
                               class="form-control" style="width: 425px;"></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Productie token', 'bluem'); ?>
                    </th>
                    <td><input type="password" name="acc_prodtoken" id="acc_prodtoken"
                               value="<?php echo !empty($_POST['acc_prodtoken']) ? sanitize_text_field($_POST['acc_prodtoken']) : (!empty($bluem_options['production_accessToken']) ? $bluem_options['production_accessToken'] : ''); ?>"
                               class="form-control" style="width: 425px;"></td>
                </tr>
                </tbody>
            </table>
            <div style="">
                <h3><?php _e('Account vereist', 'bluem'); ?></h3>
                <p>
                    <?php _e("Voor het gebruik van onze diensten is een account vereist.<br />
                Kijk voor meer informatie op de <a href='https://bluem.nl/direct-online-betalen/' title='Bluem website bezoeken' target='_blank'>Bluem website</a>, bel <a href='tel:+31852220400' target='_blank' title='Bellen naar Bluem'>+31(0)85-2220400</a> of e-mail naar <a href='mailto:info@bluem.nl' target='_blank' title='Mailen naar Bluem'>info@bluem.nl</a>.", 'bluem'); ?>
                </p>
                <p>
                    <?php _e('Vul hier de accountgegevens in, zoals door Bluem is verstrekt.<br />Neem voor meer informatie contact op met uw accountmanager.<br />Laat velden leeg om dit later op te geven.', 'bluem'); ?>
                </p>
            </div>
        </div>

        <h2><?php _e('Bedrijfsgegevens', 'bluem'); ?></h2>
        <div class="wizard-flexbox">

            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><?php _e('Bedrijfsnaam', 'bluem'); ?> *</th>
                    <td><input type="text" name="company_name" id="company_name"
                               value="<?php echo !empty($_POST['company_name']) ? sanitize_text_field($_POST['company_name']) : (!empty($bluem_registration['company']) && !empty($bluem_registration['company']['name']) ? $bluem_registration['company']['name'] : ''); ?>"
                               class="form-control" required="required"></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Telefoonnummer', 'bluem'); ?> *</th>
                    <td><input type="tel" name="company_telephone" id="company_telephone"
                               value="<?php echo !empty($_POST['company_telephone']) ? sanitize_text_field($_POST['company_telephone']) : (!empty($bluem_registration['company']) && !empty($bluem_registration['company']['telephone']) ? $bluem_registration['company']['telephone'] : ''); ?>"
                               class="form-control" required="required"></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('E-mailadres', 'bluem'); ?> *</th>
                    <td><input type="email" name="company_email" id="company_email"
                               value="<?php echo !empty($_POST['company_email']) ? sanitize_text_field($_POST['company_email']) : (!empty($bluem_registration['company']) && !empty($bluem_registration['company']['email']) ? $bluem_registration['company']['email'] : ''); ?>"
                               class="form-control" required="required"></td>
                </tr>
                </tbody>

            </table>
            <div>
                <p><?php _e('Vul het profiel van uw bedrijf in.', 'bluem'); ?> </p>
            </div>
        </div>

        <h2><?php _e('Technisch contactpersoon', 'bluem'); ?></h2>
        <div class="wizard-flexbox">


            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><?php _e('Voor- en achternaam', 'bluem'); ?> *</th>
                    <td><input type="text" name="tech_name" id="tech_name"
                               value="<?php echo !empty($_POST['tech_name']) ? sanitize_text_field($_POST['tech_name']) : (!empty($bluem_registration['tech_contact']) && !empty($bluem_registration['tech_contact']['name']) ? $bluem_registration['tech_contact']['name'] : ''); ?>"
                               class="form-control" required="required"></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Telefoonnummer', 'bluem'); ?> *</th>
                    <td><input type="tel" name="tech_telephone" id="tech_telephone"
                               value="<?php echo !empty($_POST['tech_telephone']) ? sanitize_text_field($_POST['tech_telephone']) : (!empty($bluem_registration['tech_contact']) && !empty($bluem_registration['tech_contact']['telephone']) ? $bluem_registration['tech_contact']['telephone'] : ''); ?>"
                               class="form-control" required="required"></td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('E-mailadres', 'bluem'); ?> *</th>
                    <td><input type="email" name="tech_email" id="tech_email"
                               value="<?php echo !empty($_POST['tech_email']) ? sanitize_text_field($_POST['tech_email']) : (!empty($bluem_registration['tech_contact']) && !empty($bluem_registration['tech_contact']['email']) ? $bluem_registration['tech_contact']['email'] : ''); ?>"
                               class="form-control" required="required"></td>
                </tr>
                </tbody>
            </table>
            <div>

                <p><?php _e('Vul de gegevens van de technisch contactpersoon in.<br />In geval van belangrijke updates brengen wij dit persoon op de hoogte.', 'bluem'); ?></p>
            </div>
        </div>

        <input type="submit" value="<?php _e('Opslaan', 'bluem'); ?>" class="button button-primary">
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
        jQuery("input[required]").each(function () {
            if (jQuery(this).val() === "") {
                isValid = false;

                jQuery(this).addClass('error'); // Add an 'error' class for visual indication
            } else {
                jQuery(this).removeClass('error');
            }
        });

        return isValid;
    }

    jQuery(document).ready(function () {
        jQuery("#activateform").on('submit', function (e) {
            if (!validateRequiredInputs()) {
                e.preventDefault();

                alert("<?php _e('Gelieve alle verplichte velden correct in te vullen.', 'bluem');?>");
            }
        });
    });

</script>
