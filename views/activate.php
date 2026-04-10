<?php if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>
        <?php echo wp_kses_post(bluem_get_bluem_logo_html(48)); ?>
    </h1>

    <?php bluem_render_nav_header('activate'); ?>

    <h1 style="margin-top: 25px;"><?php esc_html_e('Welcome to Bluem!', 'bluem'); ?></h1>

    <p>
        <?php esc_html_e('Thank you for using the Bluem WordPress WooCommerce plugin.', 'bluem'); ?>
        <?php esc_html_e('To continue, please fill in the details below.', 'bluem'); ?>
    </p>
    <p>
        <?php esc_html_e('If you have already filled this in before, you are seeing this screen again because the plugin has been reactivated.', 'bluem'); ?>
    </p>

    <?php if (isset($bluem_plugin_registration) && ((int)$bluem_plugin_registration) === 1) { ?>
        <div class="notice notice-success is-dismissible">
            <p>
                <span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('The plugin has been activated', 'bluem'); ?>
            </p>

            <?php esc_html_e('Next steps:', 'bluem'); ?>
            <ul>
                <?php if (bluem_is_woocommerce_activated()) { ?>

                    <li>
                        ✓&nbsp;
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout')); ?>"
                           title="<?php echo esc_attr__('WooCommerce payment methods', 'bluem'); ?>"
                        >
                            <?php esc_html_e('Activate and manage payment methods in WooCommerce settings', 'bluem'); ?>
                        </a>
                    </li>
                <?php } ?>
                <li>
                    ✓&nbsp;
                    <a href="<?php echo esc_url(admin_url('admin.php?page=bluem-transactions', 'bluem')); ?>"
                       title="<?php echo esc_attr__('WooCommerce settings', 'bluem'); ?>"
                    >
                        <?php esc_html_e('View the transaction overview', 'bluem'); ?>
                    </a>
                </li>
                <li>
                    ✓&nbsp;
                    <?php echo wp_kses_post(__('Go to Settings and manage the specific settings per payment method.<br>You will also find the integration options for Gravity Forms and ContactForm 7 there.', 'bluem')); ?>
                </li>
            </ul>


        </div>
    <?php } ?>

    <?php if (isset ($is_valid) && !$is_valid) { ?>
        <div class="notice notice-warning is-dismissible">
            <p><span class="dashicons dashicons-warning"></span>
                <?php esc_html_e('Please fill in all required fields correctly.', 'bluem'); ?></p>
        </div>
    <?php } ?>


    <form id="activateform" method="POST">
        <h2><?php esc_html_e('Account details', 'bluem'); ?></h2>
        <div class="wizard-flexbox">
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('SenderID', 'bluem'); ?>
                    </th>
                    <td><input type="text" name="acc_senderid" id="acc_senderid"
                               value="<?php echo !empty($_POST['acc_senderid']) ? esc_html(sanitize_text_field(wp_unslash($_POST['acc_senderid']))) : (!empty($bluem_options['senderID']) ? esc_attr($bluem_options['senderID']) : ''); ?>"
                               class="form-control"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Test token', 'bluem'); ?>
                    </th>
                    <td><input type="password" name="acc_testtoken" id="acc_testtoken"
                               value="<?php echo !empty($_POST['acc_testtoken']) ? esc_html(sanitize_text_field(wp_unslash($_POST['acc_testtoken']))) : (!empty($bluem_options['test_accessToken']) ? esc_attr($bluem_options['test_accessToken']) : ''); ?>"
                               class="form-control" style="width: 425px;"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Production token', 'bluem'); ?>
                    </th>
                    <td><input type="password" name="acc_prodtoken" id="acc_prodtoken"
                               value="<?php echo !empty($_POST['acc_prodtoken']) ? esc_html(sanitize_text_field(wp_unslash($_POST['acc_prodtoken']))) : (!empty($bluem_options['production_accessToken']) ? esc_attr($bluem_options['production_accessToken']) : ''); ?>"
                               class="form-control" style="width: 425px;"></td>
                </tr>
                </tbody>
            </table>
            <div style="">
                <h3><?php esc_html_e('Account required', 'bluem'); ?></h3>
                <p>
                    <?php
                    esc_html_e('An account is required to use our services.', 'bluem');
                    ?>
                    <br/>
                    <?php
                    esc_html_e('For more information:', 'bluem');
                    ?><br/>
                    <a href='https://bluem.nl/direct-online-betalen/'
                       title='<?php echo esc_attr__('Bluem website', 'bluem'); ?>' target='_blank'>
                        <?php esc_html_e('bluem.nl', 'bluem'); ?></a>
                    <br/>
                    <a href='tel:+31852220400' target='_blank'
                       title='<?php echo esc_attr__('Call Bluem', 'bluem'); ?>'>
                        +31(0)85-2220400</a><br/>
                    <a href='mailto:info@bluem.nl' target='_blank'
                       title='<?php echo esc_attr__('Email Bluem', 'bluem'); ?>'>info@bluem.nl</a>.
                </p>
                <p>
                    <?php echo wp_kses_post(__('Enter the account details as provided by Bluem.<br />For more information, contact your account manager.<br />Leave fields empty to provide them later.', 'bluem')); ?>
                </p>
            </div>
        </div>

        <h2><?php esc_html_e('Company details', 'bluem'); ?></h2>
        <div class="wizard-flexbox">

            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Company name', 'bluem'); ?> *</th>
                    <td><input type="text" name="company_name" id="company_name"
                               value="<?php echo !empty($_POST['company_name']) ? esc_html(sanitize_text_field(wp_unslash($_POST['company_name']))) : (!empty($bluem_registration['company']) && !empty($bluem_registration['company']['name']) ? esc_html($bluem_registration['company']['name']) : ''); ?>"
                               class="form-control" required="required"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Phone number', 'bluem'); ?> *</th>
                    <td><input type="tel" name="company_telephone" id="company_telephone"
                               value="<?php echo !empty($_POST['company_telephone']) ? esc_html(sanitize_text_field(wp_unslash($_POST['company_telephone']))) : (!empty($bluem_registration['company']) && !empty($bluem_registration['company']['telephone']) ? esc_html($bluem_registration['company']['telephone']) : ''); ?>"
                               class="form-control" required="required"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Email address', 'bluem'); ?> *</th>
                    <td><input type="email" name="company_email" id="company_email"
                               value="<?php echo !empty($_POST['company_email']) ? esc_html(sanitize_text_field(wp_unslash($_POST['company_email']))) : (!empty($bluem_registration['company']) && !empty($bluem_registration['company']['email']) ? esc_html($bluem_registration['company']['email']) : ''); ?>"
                               class="form-control" required="required"></td>
                </tr>
                </tbody>

            </table>
            <div>
                <p><?php esc_html_e('Fill in your company profile.', 'bluem'); ?> </p>
            </div>
        </div>

        <h2><?php esc_html_e('Technical contact', 'bluem'); ?></h2>
        <div class="wizard-flexbox">


            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('First and last name', 'bluem'); ?> *</th>
                    <td><input type="text" name="tech_name" id="tech_name"
                               value="<?php echo !empty($_POST['tech_name']) ? esc_html(sanitize_text_field(wp_unslash($_POST['tech_name']))) : (!empty($bluem_registration['tech_contact']) && !empty($bluem_registration['tech_contact']['name']) ? esc_html($bluem_registration['tech_contact']['name']) : ''); ?>"
                               class="form-control" required="required"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Phone number', 'bluem'); ?> *</th>
                    <td><input type="tel" name="tech_telephone" id="tech_telephone"
                               value="<?php echo !empty($_POST['tech_telephone']) ? esc_html(sanitize_text_field(wp_unslash($_POST['tech_telephone']))) : (!empty($bluem_registration['tech_contact']) && !empty($bluem_registration['tech_contact']['telephone']) ? esc_html($bluem_registration['tech_contact']['telephone']) : ''); ?>"
                               class="form-control" required="required"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Email address', 'bluem'); ?> *</th>
                    <td><input type="email" name="tech_email" id="tech_email"
                               value="<?php echo !empty($_POST['tech_email']) ? esc_html(sanitize_text_field(wp_unslash($_POST['tech_email']))) : (!empty($bluem_registration['tech_contact']) && !empty($bluem_registration['tech_contact']['email']) ? esc_html($bluem_registration['tech_contact']['email']) : ''); ?>"
                               class="form-control" required="required"></td>
                </tr>
                </tbody>
            </table>
            <div>

                <p><?php esc_html_e('Fill in the details of the technical contact. We will notify this person in case of important updates.', 'bluem'); ?></p>
            </div>
        </div>

        <input type="submit" value="<?php esc_html_e('Save', 'bluem'); ?>" class="button button-primary">
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

                alert("<?php echo esc_js(__('Please fill in all required fields correctly.', 'bluem')); ?>");
            }
        });
    });

</script>
