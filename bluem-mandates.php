<?php

if (!defined('ABSPATH')) {
    exit;
}

use Bluem\BluemPHP\Bluem;

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'bluem_add_gateway_class_mandates', 11);
function bluem_add_gateway_class_mandates($gateways)
{
    $gateways[] = Bluem_Mandates_Payment_Gateway::class;

    return $gateways;
}


function bluem_woocommerce_get_mandates_option($key)
{
    if (function_exists('bluem_woocommerce_get_mandates_options')) {
        $options = bluem_woocommerce_get_mandates_options();
        if (array_key_exists($key, $options)) {
            return $options[$key];
        }
    }

    return false;
}

function bluem_woocommerce_get_mandates_options()
{
    return array(
        'brandID' => array(
            'key' => 'brandID',
            'title' => 'bluem_brandID',
            'name' => 'Bluem Brand ID',
            'description' => 'Wat is je Bluem eMandates BrandID? Je hebt deze ontvangen door Bluem.',
            'default' => '',
        ),
        'merchantID' => array(
            'key' => 'merchantID',
            'title' => 'bluem_merchantID',
            'name' => 'Incassant merchantID (benodigd voor machtigingen op Productie)',
            'description' => 'Het merchantID, te vinden op het contract dat je hebt met de bank voor ontvangen van incasso machtigingen. <strong>Dit is essentieel: zonder dit gegeven zal een klant geen machtiging kunnen afsluiten op productie</strong>.',
            'default' => '',
        ),
        'merchantSubId' => array(
            'key' => 'merchantSubId',
            'title' => 'bluem_merchantSubId',
            'name' => 'Bluem Merchant Sub ID',
            'default' => '0',
            'description' => 'Hier hoef je waarschijnlijk niks aan te veranderen.',
            'type' => 'select',
            'options' => array('0' => '0'),
        ),

        'thanksPage' => array(
            'key' => 'thanksPage',
            'title' => 'bluem_thanksPage',
            'name' => 'Waar wordt de gebruiker uiteindelijk naar verwezen?',
            'type' => 'select',
            'options' => array(
                'order_page' => 'Detailpagina van de zojuist geplaatste bestelling (standaard)',
            ),
        ),
        'eMandateReason' => array(
            'key' => 'eMandateReason',
            'title' => 'bluem_eMandateReason',
            'name' => 'Reden voor Machtiging',
            'description' => 'Een bondige beschrijving van incasso weergegeven bij afgifte.',
            'default' => 'Incasso machtiging',
        ),
        'localInstrumentCode' => array(
            'key' => 'localInstrumentCode',
            'title' => 'bluem_localInstrumentCode',
            'name' => 'Type incasso machtiging afgifte',
            'description' => 'Kies type incassomachtiging. Neem bij vragen hierover contact op met Bluem.',
            'type' => 'select',
            'default' => 'CORE',
            'options' => array(
                'CORE' => 'CORE machtiging',
                'B2B' => 'B2B machtiging (zakelijk)',
            ),
        ),

        // RequestType = Issuing (altijd)
        'requestType' => array(
            'key' => 'requestType',
            'title' => 'bluem_requestType',
            'name' => 'Bluem Request Type',
            'description' => '',
            'type' => 'select',
            'default' => 'Issuing',
            'options' => array('Issuing' => 'Issuing (standaard)'),
        ),

        'sequenceType' => array(
            'key' => 'sequenceType',
            'title' => 'bluem_sequenceType',
            'name' => 'Type incasso sequentie',
            'description' => '',
            'type' => 'select',
            'default' => 'RCUR',
            'options' => array(
                'RCUR' => 'Doorlopende machtiging (recurring)',
                'OOFF' => 'Eenmalige machtiging (one-time)',
            ),
        ),

        'mandatesUseDebtorWallet' => array(
            'key' => 'mandatesUseDebtorWallet',
            'title' => 'bluem_mandatesUseDebtorWallet',
            'name' => 'Selecteer bank methode',
            'description' => "Wil je dat er in deze website al een bank moet worden geselecteerd bij de Checkout procedure, in plaats van in de Bluem Portal? Indien je 'Gebruik eigen checkout' selecteert, wordt er een veld toegevoegd aan de WooCommerce checkout pagina waar je een van de beschikbare banken kan selecteren.",
            'type' => 'select',
            'default' => '0',
            'options' => array(
                '0' => 'Gebruik Bluem Portal (standaard)',
                '1' => 'Gebruik eigen checkout',
            ),
        ),

        'successMessage' => array(
            'key' => 'successMessage',
            'title' => 'bluem_successMessage',
            'name' => 'Melding bij succesvolle machtiging via shortcode formulier',
            'description' => 'Een bondige beschrijving volstaat.',
            'default' => 'Uw machtiging is succesvol ontvangen. Hartelijk dank.',
        ),
        'errorMessage' => array(
            'key' => 'errorMessage',
            'title' => 'bluem_errorMessage',
            'name' => 'Melding bij gefaalde machtiging via shortcode formulier',
            'description' => 'Een bondige beschrijving volstaat.',
            'default' => 'Er is een fout opgetreden. De incassomachtiging is geannuleerd.',
        ),

        'purchaseIDPrefix' => array(
            'key' => 'purchaseIDPrefix',
            'title' => 'bluem_purchaseIDPrefix',
            'name' => 'Automatisch Voorvoegsel bij klantreferentie',
            'description' => 'Welke korte tekst moet voor de debtorReference weergegeven worden bij een transactie in de Bluem incassomachtiging portaal. Dit kan handig zijn om Bluem transacties makkelijk te kunnen identificeren.',
            'type' => 'text',
            'default' => '',
        ),
        'debtorReferenceFieldName' => array(
            'key' => 'debtorReferenceFieldName',
            'title' => 'bluem_debtorReferenceFieldName',
            'name' => 'Label voor klantreferentie bij invulformulier shortcode',
            'description' => "Indien je de Machtigingen shortcode gebruikt: Welk label moet bij het invulveld in het formulier komen te staan? Dit kan bijvoorbeeld 'volledige naam' of 'klantnummer' zijn. <strong>Laat dit veld leeg om alleen een knop weer te geven</strong>.",
            'type' => 'text',
            'default' => '',
        ),
        'thanksPageURL' => array(
            'key' => 'thanksPageURL',
            'title' => 'bluem_thanksPageURL',
            'name' => 'Slug van de resultaat pagina',
            'description' => 'Indien je de Machtigingen shortcode gebruikt: Op welke pagina wordt de shortcode geplaatst? Dit is een slug, dus als je <code>thanks</code> invult, wordt de gehele URL: ' . site_url('thanks') . '. We geven de querystrings <code>result</code> en indien van toepassing <code>reason</code> mee waarmee je de status kan opvangen.',
            'type' => 'text',
            'default' => '',
        ),
        'instantMandatesResponseURI' => array(
            'key' => 'instantMandatesResponseURI',
            'title' => 'bluem_instantMandatesResponseURI',
            'name' => 'URI voor InstantMandates',
            'description' => 'Indien je InstantMandates gebruikt: De <code>response</code> URI na een request. Dit kan een externe URL of een Deep Link zijn. We geven de querystrings <code>result</code> en indien van toepassing <code>reason</code> mee waarmee je de status kan opvangen.',
            'type' => 'text',
            'default' => '',
        ),
        'mandate_id_counter' => array(
            'key' => 'mandate_id_counter',
            'title' => 'bluem_mandate_id_counter',
            'name' => 'Begingetal mandaat ID\'s',
            'description' => 'Op welk getal wil je mandaat op dit moment nummeren? Dit getal wordt vervolgens automatisch opgehoogd.',
            'type' => 'text',
            'default' => '1',
        ),
        'maxAmountEnabled' => array(
            'key' => 'maxAmountEnabled',
            'title' => 'bluem_maxAmountEnabled',
            'name' => 'Check op maximale bestelwaarde voor incassomachtigingen',
            'description' => "Wil je dat er bij zakelijke incassomachtigingen een check wordt uitgevoerd op de maximale waarde van de incasso, indien er een beperkte bedrag incasso machtiging is afgegeven? Zet dit gegeven dan op 'wel checken'. Er wordt dan een foutmelding gegeven als een klant een bestelling plaatst met een toegestaan bedrag dat lager is dan het orderbedrag (vermenigvuldigd met het volgende gegeven, de factor). Is de machtiging onbeperkt of anders groter dan het orderbedrag, dan wordt de machtiging geaccepteerd.",
            'type' => 'select',
            'default' => '1',
            'options' => array(
                '1' => 'Wel checken op MaxAmount',
                '0' => 'Niet checken op MaxAmount',
            ),
        ),

        // Bij B2B krijgen wij terug of de gebruiker een maximaal mandaatbedrag heeft afgegeven.
        // Dit mandaat bedrag wordt vergeleken met de orderwaarde. De orderwaarde plus
        // onderstaand percentage moet lager zijn dan het maximale mandaatbedrag.
        // Geef hier het percentage aan.
        'maxAmountFactor' => array(
            'key' => 'maxAmountFactor',
            'title' => 'bluem_maxAmountFactor',
            'name' => 'Welke factor van de bestelling mag het maximale bestelbedrag zijn?',
            'description' => 'Als er een max amount wordt meegestuurd, wat is dan het maximale bedrag wat wordt toegestaan? Gebaseerd op de order grootte.',
            'type' => 'number',
            'attrs' => array(
                'step' => '0.01',
                'min' => '0.00',
                'max' => '999.00',
                'placeholder' => '1.00',
            ),
            'default' => '1.00',
        ),
    );
}


/*
 * The gateway class itself, please note that it is inside plugins_loaded action hook
 */
if (bluem_woocommerce_is_woocommerce_active()) {
    add_action('plugins_loaded', 'bluem_init_mandate_gateway_class');
}

function bluem_init_mandate_gateway_class()
{
    include_once __DIR__ . '/gateways/Bluem_Mandates_Payment_Gateway.php';
}

// Integrations in third party systems:
add_action('bluem_woocommerce_valid_mandate_callback', 'bluem_woocommerce_valid_mandate_callback_function', 10, 2);
function bluem_woocommerce_valid_mandate_callback_function($user_id, $response)
{
    // Implement this method in third-party extensions of this system
}

add_action('show_user_profile', 'bluem_woocommerce_mandates_show_extra_profile_fields', 2);
add_action('edit_user_profile', 'bluem_woocommerce_mandates_show_extra_profile_fields');

function bluem_woocommerce_mandates_show_extra_profile_fields($user)
{
    $bluem_requests = bluem_db_get_requests_by_user_id_and_type($user->ID, 'mandates'); ?>
    <table class="form-table">
        <a id="user_mandates"></a>

        <?php if (isset($bluem_requests) && count($bluem_requests) > 0) { ?>
            <tr>
                <th>
                    Digitale Incassomachtigingen
                </th>
                <td>
                    <?php
                    bluem_render_requests_list($bluem_requests);
                    ?>
                </td>
            </tr>
            <?php
        } else {
            // legacy code
            ?>
            <tr>
                <th><label for="bluem_latest_mandate_id">Meest recente MandateID</label></th>
                <td>
                    <input type="text" name="bluem_latest_mandate_id" id="bluem_latest_mandate_id"
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_latest_mandate_id', true)); ?>"
                           class="regular-text"/><br/>
                    <span class="description">Hier wordt het meest recente mandate ID geplaatst; en gebruikt bij het doen van een volgende checkout.</span>
                </td>
            </tr>
            <tr>
                <th><label for="bluem_latest_mandate_entrance_code">Meest recente EntranceCode</label></th>

                <td>
                    <input type="text" name="bluem_latest_mandate_entrance_code"
                           id="bluem_latest_mandate_entrance_code"
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_latest_mandate_entrance_code', true)); ?>"
                           class="regular-text"/><br/>
                    <span class="description">Hier wordt het meest recente entrance_code geplaatst; en gebruikt bij het doen van een volgende checkout.</span>
                </td>
            </tr>
            <tr>
                <th><label for="bluem_latest_mandate_amount">Omvang laatste machtiging</label></th>
                <td>
                    <input type="text" name="bluem_latest_mandate_amount" id="bluem_latest_mandate_amount"
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'bluem_latest_mandate_amount', true)); ?>"
                           class="regular-text"/><br/>
                    <span class="description">Dit is de omvang van de laatste machtiging</span>
                </td>
            </tr>

            <?php
        }
        ?>
        <tr>
            <th><label for="bluem_mandates_validated">Machtiging via shortcode / InstantMandates valide?</label></th>
            <td>
                <?php
                $curValidatedVal = (int)esc_attr(
                    get_user_meta(
                        $user->ID,
                        'bluem_mandates_validated',
                        true
                    )
                );
                ?>
                <select name="bluem_mandates_validated" id="bluem_mandates_validated">
                    <option value="1"
                        <?php
                        if ($curValidatedVal == 1) {
                            echo 'selected';
                        }
                        ?>
                    >
                        Ja
                    </option>
                    <option value="0"
                        <?php
                        if ($curValidatedVal == 0) {
                            echo 'selected';
                        }
                        ?>
                    >
                        Nee
                    </option>
                </select><br/>
                <span class="description">Is een machtiging via shortcode of InstantMandates doorgekomen? Indien van toepassing kan je dit hier overschrijven</span>
            </td>
        </tr>
    </table>
    <?php
}


add_action(
    'personal_options_update',
    'bluem_woocommerce_mandates_save_extra_profile_fields'
);
add_action(
    'edit_user_profile_update',
    'bluem_woocommerce_mandates_save_extra_profile_fields'
);

function bluem_woocommerce_mandates_save_extra_profile_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['bluem_latest_mandate_id'])) {
        update_user_meta(
            $user_id,
            'bluem_latest_mandate_id',
            esc_attr(sanitize_text_field(wp_unslash($_POST['bluem_latest_mandate_id'])))
        );
    }

    if (isset($_POST['bluem_latest_mandate_entrance_code'])) {
        update_user_meta(
            $user_id,
            'bluem_latest_mandate_entrance_code',
            esc_attr(sanitize_text_field(wp_unslash($_POST['bluem_latest_mandate_entrance_code'])))
        );
    }
    if (isset($_POST['bluem_latest_mandate_amount'])) {
        update_user_meta(
            $user_id,
            'bluem_latest_mandate_amount',
            esc_attr(sanitize_text_field(wp_unslash($_POST['bluem_latest_mandate_amount'])))
        );
    }
    if (isset($_POST['bluem_mandates_validated'])) {
        update_user_meta(
            $user_id,
            'bluem_mandates_validated',
            esc_attr(sanitize_text_field(wp_unslash($_POST['bluem_mandates_validated'])))
        );
    }
}

function bluem_woocommerce_mandates_settings_section()
{
    $mandate_id_counter = get_option('bluem_woocommerce_mandate_id_counter');

    // The below code is useful when you want the mandate_id to start counting at a fixed minimum.
    // This is what had to be implemented for H2OPro; one of the first clients.
    // @todo: convert to action so it can be overriden by third-party developers such as H2OPro.
    if (home_url() == 'https://www.h2opro.nl' && (int)($mandate_id_counter . '') < 111100) {
        $mandate_id_counter += 111000;
        update_option('bluem_woocommerce_mandate_id_counter', $mandate_id_counter);
    }
    echo '<p><a id="tab_mandates"></a> Hier kan je alle belangrijke gegevens instellen rondom Digitale Incassomachtigingen.</p>';
}

// ********************** Mandate specific

function bluem_woocommerce_settings_render_brandID()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('brandID'));
}

function bluem_woocommerce_settings_render_merchantID()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('merchantID'));
}

function bluem_woocommerce_settings_render_merchantSubId()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('merchantSubId'));
}

function bluem_woocommerce_settings_render_thanksPage()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('thanksPage'));
}

function bluem_woocommerce_settings_render_eMandateReason()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('eMandateReason'));
}

function bluem_woocommerce_settings_render_localInstrumentCode()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('localInstrumentCode'));
}

function bluem_woocommerce_settings_render_requestType()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('requestType'));
}

function bluem_woocommerce_settings_render_sequenceType()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('sequenceType'));
}

function bluem_woocommerce_settings_render_successMessage()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('successMessage'));
}

function bluem_woocommerce_settings_render_errorMessage()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('errorMessage'));
}

function bluem_woocommerce_settings_render_purchaseIDPrefix()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('purchaseIDPrefix'));
}

function bluem_woocommerce_settings_render_debtorReferenceFieldName()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('debtorReferenceFieldName'));
}

function bluem_woocommerce_settings_render_thanksPageURL()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('thanksPageURL'));
}

function bluem_woocommerce_settings_render_instantMandatesResponseURI()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('instantMandatesResponseURI'));
}

function bluem_woocommerce_settings_render_mandate_id_counter()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('mandate_id_counter'));
}

function bluem_woocommerce_settings_render_maxAmountEnabled()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('maxAmountEnabled'));
}

function bluem_woocommerce_settings_render_maxAmountFactor()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('maxAmountFactor'));
}

function bluem_woocommerce_settings_render_useMandatesDebtorWallet()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('useMandatesDebtorWallet'));
}

function bluem_woocommerce_settings_render_mandatesUseDebtorWallet()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_mandates_option('mandatesUseDebtorWallet'));
}

add_filter('bluem_woocommerce_enhance_mandate_request', 'bluem_woocommerce_enhance_mandate_request_function', 10, 1);

/**
 * allow third parties to add additional data to the request object through this additional action
 *
 * @param [type] $request
 *
 * @return void
 */
function bluem_woocommerce_enhance_mandate_request_function($request)
{
    // do something with the Bluem Mandate request, use this in third-party extensions of this system
    return $request;
}
