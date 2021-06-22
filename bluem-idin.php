<?php

if (!defined('ABSPATH')) {
    exit;
}

use Bluem\BluemPHP\Bluem;
use Carbon\Carbon;

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // WooCommerce specific code incoming here
}

function bluem_register_session()
{
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'bluem_register_session');

function bluem_woocommerce_get_idin_option($key)
{
    $options = bluem_woocommerce_get_idin_options();
    if (array_key_exists($key, $options)) {
        return $options[$key];
    }
    return false;
}

function bluem_woocommerce_get_idin_options()
{
    $idinDescriptionTags = (
        function_exists('bluem_get_IDINDescription_tags')?
        bluem_get_IDINDescription_tags() : []
    );
    $idinDescriptionReplaces = (
        function_exists('bluem_get_IDINDescription_replaces')?
        bluem_get_IDINDescription_replaces() : []
    );
    $idinDescriptionTable = "<table><thead><tr><th>Invulveld</th><th>Voorbeeld invulling</th></tr></thead><tbody>";
    foreach ($idinDescriptionTags as $ti => $tag) {
        if (!isset($idinDescriptionReplaces[$ti])) {
            continue;
        }
        $idinDescriptionTable.= "<tr><td><code>$tag</code></td><td>".$idinDescriptionReplaces[$ti]."</td></tr>";
    }

    $idinDescriptionTable.="</tbody></table>";
    $options = get_option('bluem_woocommerce_options');

    if ($options !==false
        && isset($options['IDINDescription'])
    ) {
        $idinDescriptionCurrentValue = bluem_parse_IDINDescription(
            $options['IDINDescription']
        );
    } else {
        $idinDescriptionCurrentValue = bluem_parse_IDINDescription(
            "Identificatie {gebruikersnaam}"
        );
    }

    return [
        'IDINBrandID' => [
            'key' => 'IDINBrandID',
            'title' => 'bluem_IDINBrandID',
            'name' => 'IDIN BrandId',
            'description' => '',
            'default' => ''
        ],



        'idin_scenario_active' => [
            'key' => 'idin_scenario_active',
            'title' => 'bluem_idin_scenario_active',
            'name' => 'IDIN Scenario',
            'description' => "Wil je een leeftijd- of volledige adrescontrole uitvoeren bij Checkout?",
            'type' => 'select',
            'default' => '0',
            'options' => [
                '0' => 'Voer geen identiteitscheck uit voor de checkout procedure',
                '1' => 'Check op de minimumleeftijd door middel van een AgeCheckRequest',
                '2' => 'Voer een volledige identiteitscontrole uit en sla dit op, maar blokkeer de checkout NIET indien minimumleeftijd niet bereikt is',
                '3' => 'Voer een volledige identiteitscontrole uit, sla dit op EN  blokkeer de checkout WEL indien minimumleeftijd niet bereikt is',

            ],
        ],

        'idin_check_age_minimum_age' => [
            'key' => 'idin_check_age_minimum_age',
            'title' => 'bluem_idin_check_age_minimum_age',
            'name' => 'Minimumleeftijd',
            'description' => "Wat is de minimumleeftijd, in jaren? Indien de plugin checkt op leeftijd, wordt deze waarde gebruikt om de check uit te voeren.",
            'type' => 'number',
            'default' => '18',
        ],
        'idin_request_name' => [
            'key' => 'idin_request_name',
            'title' => 'bluem_idin_request_name',
            'name' => 'Naam opvragen?',
            'description' => "Indien je een volledige identiteitscontrole uitvoert,
                wil je dan de naam opvragen?",
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_request_address' => [
            'key' => 'idin_request_address',
            'title' => 'bluem_idin_request_address',
            'name' => 'Adres opvragen?',
            'description' => "Indien je een volledige identiteitscontrole uitvoert,
                wil je dan het woonadres opvragen?",
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_request_birthdate' => [
            'key' => 'idin_request_birthdate',
            'title' => 'bluem_idin_request_birthdate',
            'name' => 'Geboortedatum opvragen?',
            'description' => "Indien je een volledige identiteitscontrole uitvoert,
                wil je dan de geboortedatum opvragen? Dit gegeven wordt ALTIJD opgevraagd
                indien je ook op de minimumleeftijd controleert",
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_request_gender' => [
            'key' => 'idin_request_gender',
            'title' => 'bluem_idin_request_gender',
            'name' => 'Geslacht opvragen?',
            'description' => "Indien je een volledige identiteitscontrole uitvoert,
                wil je dan het geslacht opvragen?",
            'type' => 'bool',
            'default' => '0',
        ],
        'idin_request_telephone' => [
            'key' => 'idin_request_telephone',
            'title' => 'bluem_idin_request_telephone',
            'name' => 'Telefoonnummer opvragen?',
            'description' => "Indien je een volledige identiteitscontrole uitvoert,
                wil je dan het telefoonnummer opvragen?",
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_request_email' => [
            'key' => 'idin_request_email',
            'title' => 'bluem_idin_request_email',
            'name' => 'E-mailadres opvragen?',
            'description' => "Indien je een volledige identiteitscontrole uitvoert,
                wil je dan het e-mailadres opvragen?",
            'type' => 'bool',
            'default' => '1',
        ],

        'IDINSuccessMessage' => [
        'key' => 'IDINSuccessMessage',
        'title' => 'bluem_suIDINSuccessMessage',
        'name' => 'Melding bij succesvolle Identificatie via shortcode',
        'description' => 'Een bondige beschrijving volstaat.',
        'default' => 'Uw identificatie is succesvol ontvangen. Hartelijk dank.'
    ],
    'IDINErrorMessage' => [
        'key' => 'IDINErrorMessage',
        'title' => 'bluem_IDINErrorMessage',
        'name' => 'Melding bij gefaalde Identificatie via shortcode',
        'description' => 'Een bondige beschrijving volstaat.',
        'default' => 'Er is een fout opgetreden. De identificatie is geannuleerd.'
    ],


    'IDINPageURL' => [
        'key' => 'IDINPageURL',
        'title' => 'bluem_IDINPageURL',
        'name' => 'URL vanwaar Identificatie gestart wordt',
        'description' => 'van pagina waar het Identificatie proces wordt weergegeven, bijvoorbeeld een accountpagina. De gebruiker komt op deze pagina terug na het proces',
        'default' => 'my-account'
    ],
    // 'IDINCategories' => [
    //     'key' => 'IDINCategories',
    //     'title' => 'bluem_IDINCategories',
    //     'name' => 'Comma separated categories in iDIN shortcode requests',
    //     'description' => 'Opties: CustomerIDRequest, NameRequest, AddressRequest, BirthDateRequest, AgeCheckRequest, GenderRequest, TelephoneRequest, EmailRequest',
    //     'default' => 'AddressRequest,BirthDateRequest'
    // ],

    'IDINShortcodeOnlyAfterLogin' => [
        'key' => 'IDINShortcodeOnlyAfterLogin',
        'title' => 'bluem_IDINShortcodeOnlyAfterLogin',
        'name' => 'Shortcode beperken tot ingelogde gebruikers',
        'description' => "Moet het iDIN formulier via shortcode zichtbaar zijn voor iedereen of alleen ingelogde gebruikers?",
        'type' => 'select',
        'default' => '0',
        'options' => [
            '0' => 'Voor iedereen',
            '1' => 'Alleen voor ingelogde bezoekers'
        ],
    ],
    'IDINDescription' => [
        'key' => 'IDINDescription',
        'title' => 'bluem_IDINDescription',
        'name' => 'Formaat beschrijving request',
        'description' => '

        <div style="width:400px; float:right; margin:10px; font-size: 9pt;
        border: 1px solid #ddd;
        padding: 10pt;
        border-radius: 5pt;">
        Mogelijke invulvelden: '.
        $idinDescriptionTable.
        '<br>Let op: max 128 tekens. Toegestane karakters: <code>-0-9a-zA-ZéëïôóöüúÉËÏÔÓÖÜÚ€ ()+,.@&amp;=%&quot;&apos;/:;?$</code></div>'
        .
        'Geef het format waaraan de beschrijving van
            een identificatie request moet voldoen, met automatisch ingevulde velden.<br>Dit gegeven wordt ook weergegeven in de Bluem portal als de \'Inzake\' tekst.
            <br>Voorbeeld huidige waarde: <code style=\'display:inline-block;\'>'.
            $idinDescriptionCurrentValue.'</code><br>',
        'default' => 'Identificatie {gebruikersnaam}'
    ],

    'idin_add_field_in_order_emails' => [
        'key' => 'idin_add_field_in_order_emails',
        'title' => 'bluem_idin_add_field_in_order_emails',
        'name' => 'Identificatie status in emails',
        'description' => "Moet de status van identificatie worden weergegeven
            in de order notificatie email naar de klant en naar jezelf? <strong>Let op: dit werkt op het moment alleen voor ingelogde klanten</strong>",
        'type' => 'bool',
        'default' => '1',
    ],
    'idin_add_address_in_order_emails' => [
        'key' => 'idin_add_address_in_order_emails',
        'title' => 'bluem_idin_add_address_in_order_emails',
        'name' => 'Identificatie adres in emails',
        'description' => "Moet het adres van identificatie worden weergegeven
            in de order notificatie email naar de klant en naar jezelf? <strong>Let op: dit werkt op het moment alleen voor ingelogde klanten</strong>",
        'type' => 'bool',
        'default' => '1',
    ],
    'idin_add_name_in_order_emails' => [
        'key' => 'idin_add_name_in_order_emails',
        'title' => 'bluem_idin_add_name_in_order_emails',
        'name' => 'Identificatie naam in emails',
        'description' => "Moet de naam van identificatie worden weergegeven
            in de order notificatie email naar de klant en naar jezelf? <strong>Let op: dit werkt op het moment alleen voor ingelogde klanten</strong>",
        'type' => 'bool',
        'default' => '1',
    ],
    'idin_add_birthdate_in_order_emails' => [
        'key' => 'idin_add_birthdate_in_order_emails',
        'title' => 'bluem_idin_add_birthdate_in_order_emails',
        'name' => 'Identificatie geboortedatum in emails',
        'description' => "Moet de geboortedatum van identificatie worden weergegeven
            in de order notificatie email naar de klant en naar jezelf? <strong>Let op: dit werkt op het moment alleen voor ingelogde klanten</strong>",
        'type' => 'bool',
        'default' => '1',
    ],


    'idin_identify_button_inner' => [
        'key' => 'idin_identify_button_inner',
        'title' => 'bluem_idin_identify_button_inner',
        'name' => 'Tekst op Identificeren knop',
        'description' => 'Wat moet er op de knop staan in kaders waar de identificatie wordt vereist.',
        'default' => 'Klik hier om je te identificeren'
    ],

    'idin_identity_dialog_no_verification_text' => [
        'key' => 'idin_identity_dialog_no_verification_text',
        'title' => 'bluem_idin_identity_dialog_no_verification_text',
        'name' => 'Tekst in kader Identificeren (onder checkout) als er nog GEEN geldige identificatie bekend is maar deze wel vereist is',
        'description' => 'Wat moet er op de knop staan in kaders waar de identificatie wordt vereist.',
        'default' => 'Uw leeftijd is niet bekend of niet toereikend. U kan dus niet deze bestelling afronden. Neem bij vragen contact op met de webshop support.'
    ],


    'idin_identity_dialog_no_verification_text' => [
        'key' => 'idin_identity_dialog_no_verification_text',
        'title' => 'bluem_idin_identity_dialog_no_verification_text',
        'name' => 'Tekst in kader Identificeren (onder checkout) als er nog GEEN geldige identificatie bekend is maar deze wel vereist is',
        'description' => 'Wat moet er op de knop staan in kaders waar de identificatie wordt vereist.',
        'default' => 'Uw leeftijd is niet bekend of niet toereikend. U kan dus niet deze bestelling afronden. Neem bij vragen contact op met de webshop support.'
    ],
    'idin_identity_topbar_no_verification_text' => [
        'key' => 'idin_identity_topbar_no_verification_text',
        'title' => 'bluem_idin_identity_topbar_no_verification_text',
        'name' => 'Tekst in Pop-up boven checkout als er nog GEEN geldige identificatie bekend is maar deze wel vereist is',
        'description' => 'Wat moet er op de knop staan in kaders waar de identificatie wordt vereist.',
        'default' => 'We hebben uw leeftijd (nog) niet kunnen opvragen. Voltooi eerst de identificatie procedure.'
    ],

    'idin_identity_topbar_invalid_verification_text' => [
        'key' => 'idin_identity_topbar_invalid_verification_text',
        'title' => 'bluem_idin_identity_topbar_invalid_verification_text',
        'name' => 'Tekst in Pop-up boven checkout als er een ongeldige identificatie terugkomt na opvragen hiervan',
        'description' => 'Wat moet er op de knop staan in kaders waar de identificatie wordt vereist.',
        'default' => "Uw leeftijd is niet toereikend. U kan dus niet deze bestelling afronden."
    ],


    'idin_identity_dialog_thank_you_message' => [
        'key' => 'idin_identity_dialog_thank_you_message',
        'title' => 'bluem_idin_identity_dialog_thank_you_message',
        'name' => 'Tekst in kader onder checkout zodra er een geldige identificatie procedure is voltooid',
        'description' => 'Wat moet er op de knop staan in kaders waar de identificatie wordt vereist.',
        'default' => "Je leeftijd is geverifieerd, bedankt."
    ],

    'idin_identity_popup_thank_you_message' => [
        'key' => 'idin_identity_popup_thank_you_message',
        'title' => 'bluem_idin_identity_popup_thank_you_message',
        'name' => 'Tekst in Pop-up boven checkout zodra er een geldige identificatie procedure is voltooid',
        'description' => 'Wat moet er op de knop staan in kaders waar de identificatie wordt vereist.',
        'default' => "Je leeftijd is geverifieerd."
    ],

    'idin_identity_more_information_popup' => [
        'key' => 'idin_identity_more_information_popup',
        'title' => 'bluem_idin_identity_more_information_popup',
        'name' => 'Uitleg kader over identificeren',
        'type'=>'textarea',
        'description' => 'Schrijf hier een toelichting met eventuele doorklik links om klanten/gebruikers te vertellen over iDIN en het belang hiervan.',
        'default' => '**Identificeren is per 1 juli 2021 verplicht in winkels waar producten verkocht worden met een identiteitsplicht van de klant.**

De methode die hier gebruikt wordt is veilig, snel en makkelijk - net zoals iDeal.   Het duurt hoogstens twee minuten en het resultaat wordt opgeslagen voor vervolgtransacties als je ingelogd bent als terugkerende klant.

[Lees hier meer: https://bluem.nl/blog/2021/04/26/nieuwe-alcoholwet-per-1-juli-online-leeftijdsverificatie-verplicht/](https://bluem.nl/blog/2021/04/26/nieuwe-alcoholwet-per-1-juli-online-leeftijdsverificatie-verplicht/)'
    ],
    ];
}

function bluem_woocommerce_idin_settings_section()
{
    $options = get_option('bluem_woocommerce_options'); ?>
    <p><a id="tab_idin"></a>
    Hier kan je alle belangrijke gegevens instellen rondom iDIN (Identificatie).</p>
    <h3>
    <span class="dashicons dashicons-saved"></span>
    Automatische check:
    </h3>
    <p>
    <strong>
    <?php switch ($options['idin_scenario_active']) {

        case 0:
            {
                echo "Er wordt geen automatische check uitgevoerd";
                break;
            }
            case 1:
                {
                    echo "Er wordt een check gedaan op minimum leeftijd bij checkout";
                    break;
                }
                case 2:
                    {
                        echo "Er wordt een volledige identiteitscheck gedaan voor de checkout beschikbaar wordt
                        ";
                        break;
                    }
                    case 3:
                        {
                            echo "Er wordt een volledige identiteitscheck gedaan en op leeftijd gecontroleerd voor de checkout beschikbaar wordt
                            ";
                            break;
                        }
                    } ?>
                    </strong>

       </p>

       <?php if ($options['idin_scenario_active'] >= 1) {
                        ?>
        <p>
            Deze gegevens vraag je op het moment op de volledige identiteitscontrole voor checkout:<br/>
            <code style="display:inline-block;">
            <?php foreach (bluem_idin_get_categories() as $cat) {
                            echo "&middot; ".str_replace("Request", "", $cat)."<br>";
                        } ?>
            </code>
        </p>
           <?php
                    } ?>

    <h3>
    <span class="dashicons dashicons-welcome-write-blog"></span>
       Zelf op een pagina een iDIN verzoek initiëren
    </h3>
    <p>Het iDIN formulier werkt ook een shortcode, welke je kan plaatsen op een pagina, post of in een template. De shortcode is als volgt:
    <code>[bluem_identificatieformulier]</code>.
    </p>
    <p>
        Zodra je deze hebt geplaatst, is op deze pagina een blok zichtbaar waarin de status van de identificatieprocedure staat. Indien geen identificatie is uitgevoerd, zal er een knop verschijnen om deze te starten.
    </p>
    <p>
    Bij succesvol uitvoeren van de identificatie via Bluem, komt men terug op de pagina die hieronder wordt aangemerkt als iDINPageURL (huidige waarde:
    <code>
    <?php
if (isset($options['IDINPageURL'])) {
                        echo($options['IDINPageURL']);
                    } ?></code>).
    </p>
    <h3>
    <span class="dashicons dashicons-editor-help"></span>
       Waar vind ik de gegevens?
    </h3>
    <p>
        Gegevens worden na een identificatie opgeslagen bij het user profile als metadata. Je kan deze velden zien als je bij een gebruiker kijkt.
        Kijk bijvoorbeeld bij <a href="<?php echo admin_url('profile.php'); ?>" target="_blank">je eigen profiel</a>.
    </p>
    <h3>
    <span class="dashicons dashicons-admin-settings"></span>
       Identity instellingen en voorkeuren
    </h3>
    <?php
}

function bluem_woocommerce_settings_render_IDINSuccessMessage()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINSuccessMessage')
    );
}

function bluem_woocommerce_settings_render_IDINErrorMessage()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINErrorMessage')
    );
}

function bluem_woocommerce_settings_render_IDINPageURL()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINPageURL')
    );
}

function bluem_woocommerce_settings_render_IDINCategories()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINCategories')
    );
}

function bluem_woocommerce_settings_render_IDINBrandID()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINBrandID')
    );
}


function bluem_woocommerce_settings_render_IDINShortcodeOnlyAfterLogin()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINShortcodeOnlyAfterLogin')
    );
}

function bluem_woocommerce_settings_render_IDINDescription()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('IDINDescription')
    );
}

function bluem_woocommerce_settings_render_idin_scenario_active()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_scenario_active')
    );
}

function bluem_woocommerce_settings_render_idin_check_age_minimum_age()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_check_age_minimum_age')
    );
}


function bluem_woocommerce_settings_render_idin_request_address()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_address')
    );
}
function bluem_woocommerce_settings_render_idin_request_birthdate()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_birthdate')
    );
}
function bluem_woocommerce_settings_render_idin_request_gender()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_gender')
    );
}
function bluem_woocommerce_settings_render_idin_request_telephone()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_telephone')
    );
}
function bluem_woocommerce_settings_render_idin_request_email()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_email')
    );
}
function bluem_woocommerce_settings_render_idin_request_name()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_name')
    );
}


function bluem_woocommerce_settings_render_idin_add_field_in_order_emails()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_add_field_in_order_emails')
    );
}


function bluem_woocommerce_settings_render_idin_add_address_in_order_emails()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_add_address_in_order_emails')
    );
}

function bluem_woocommerce_settings_render_idin_add_name_in_order_emails()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_add_name_in_order_emails')
    );
}

function bluem_woocommerce_settings_render_idin_add_birthdate_in_order_emails()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_add_birthdate_in_order_emails')
    );
}

function bluem_woocommerce_settings_render_idin_identify_button_inner()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identify_button_inner')
    );
}


function bluem_woocommerce_settings_render_idin_identity_dialog_no_verification_text()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identity_dialog_no_verification_text')
    );
}


function bluem_woocommerce_settings_render_idin_identity_topbar_no_verification_text()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identity_topbar_no_verification_text')
    );
}


function bluem_woocommerce_settings_render_idin_identity_topbar_invalid_verification_text()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identity_topbar_invalid_verification_text')
    );
}


function bluem_woocommerce_settings_render_idin_identity_dialog_thank_you_message()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identity_dialog_thank_you_message')
    );
}


function bluem_woocommerce_settings_render_idin_identity_popup_thank_you_message()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identity_popup_thank_you_message')
    );
}


function bluem_woocommerce_settings_render_idin_identity_more_information_popup()
{
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_identity_more_information_popup')
    );
}





function bluem_idin_get_categories(int $preset_scenario = null)
{
    $catListObject = new BluemIdentityCategoryList();
    $options = get_option('bluem_woocommerce_options');

    // if you want to infer the scenario from the settings and not override it.
    if (is_null($preset_scenario)) {
        if (isset($options['idin_scenario_active']) && $options['idin_scenario_active']!=="") {
            $scenario = (int) $options['idin_scenario_active'];
        } else {
            $scenario = 0;
        }
    } else {
        $scenario = $preset_scenario;
    }



    // '0' => 'Voer geen identiteitscheck uit voor de checkout procedure', dus we overriden hier geen cats
    // then we don't have to do anything else here.

    // '1' => 'Check op de minimumleeftijd door middel van een AgeCheckRequest',
    if ($scenario == 1) {
        $catListObject->addCat("AgeCheckRequest");
        // return prematurely because we don't even consider the rest of the stuffs.
        return $catListObject->getCats();


    // '2' => 'Voer een volledige identiteitscontrole uit en sla dit op, maar blokkeer de checkout NIET indien minimumleeftijd niet bereikt is',
        // '3' => 'Voer een volledige identiteitscontrole uit, sla dit op EN  blokkeer de checkout WEL indien minimumleeftijd niet bereikt is',
    } elseif ($scenario == 2 || $scenario == 3) {
        // always ask for this
        $catListObject->addCat("CustomerIDRequest");

        if ($scenario == 3) {
            // deze moet verplicht mee
            $catListObject->addCat("BirthDateRequest");
        }
    }
    if (isset($options['idin_request_name']) &&  $options['idin_request_name'] == "1") {
        $catListObject->addCat("NameRequest");
    }
    if (isset($options['idin_request_address']) &&  $options['idin_request_address'] == "1") {
        $catListObject->addCat("AddressRequest");
    }
    if (isset($options['idin_request_address']) &&  $options['idin_request_address'] == "1") {
        $catListObject->addCat("AddressRequest");
    }
    if (isset($options['idin_request_birthdate']) &&  $options['idin_request_birthdate'] == "1") {
        $catListObject->addCat("BirthDateRequest");
    }
    if (isset($options['idin_request_gender']) &&  $options['idin_request_gender'] == "1") {
        $catListObject->addCat("GenderRequest");
    }
    if (isset($options['idin_request_telephone']) &&  $options['idin_request_telephone'] == "1") {
        $catListObject->addCat("TelephoneRequest");
    }
    if (isset($options['idin_request_email']) &&  $options['idin_request_email'] == "1") {
        $catListObject->addCat("EmailRequest");
    }

    return $catListObject->getCats();
    //explode(",", str_replace(" ", "", $bluem_config->IDINCategories));
}




/* ********* RENDERING THE STATIC FORM *********** */
add_shortcode('bluem_identificatieformulier', 'bluem_idin_form');

/**
 * Shortcode: `[bluem_identificatieformulier]`
 *
 * @return void
 */
function bluem_idin_form()
{
    $bluem_config = bluem_woocommerce_get_config();

    if (isset($bluem_config->IDINShortcodeOnlyAfterLogin)
        && $bluem_config->IDINShortcodeOnlyAfterLogin=="1"
        && !is_user_logged_in()
    ) {
        return "";
    }

    // ob_start();

    $r ='';
    $validated = get_user_meta(get_current_user_id(), "bluem_idin_validated", true) == "1";

    if ($validated) {
        if (isset($bluem_config->IDINSuccessMessage)) {
            $r.= "<p>" . $bluem_config->IDINSuccessMessage . "</p>";
        } else {
            $r.= "<p>Uw identificatieverzoek is ontvangen. Hartelijk dank.</p>";
        }

        // $r.= "Je hebt de identificatieprocedure eerder voltooid. Bedankt<br>";
        // $results = bluem_idin_retrieve_results();
        // $r.= "<pre>";
        // foreach ($results as $k => $v) {
        //     if (!is_object($v)) {
        //         $r.= "$k: $v";
        //     } else {
        //         foreach ($v as $vk => $vv) {
        //             $r.= "\t$vk: $vv";
        //             $r.="<BR>";
        //         }
        //     }
        //     $r.="<BR>";
        // }
        // // var_dump($results);
        // $r.= "</pre>";
        // return;
        return $r;
    } else {
        if (isset($_GET['result']) && sanitize_text_field($_GET['result']) == "false") {
            $r.= '<div class="">';

            if (isset($bluem_config->IDINErrorMessage)) {
                $r.= "<p>" . $bluem_config->IDINErrorMessage . "</p>";
            } else {
                $r.= "<p>Er is een fout opgetreden. Uw verzoek is geannuleerd.</p>";
            }

            if (isset($_SESSION['BluemIDINTransactionURL']) && $_SESSION['BluemIDINTransactionURL'] !== "") {
                $retryURL = $_SESSION['BluemIDINTransactionURL'];
                $r.= "<p><a href='{$retryURL}' target='_self' alt='probeer opnieuw' class='button'>Probeer het opnieuw</a></p>";
            } else {
                // $retryURL = home_url($bluem_config->checkoutURL);
            }
            $r.= '</div>';
        } else {
            $r.= "Je hebt de identificatieprocedure nog niet voltooid.<br>";
            $r.= '<form action="' . home_url('bluem-woocommerce/idin_execute') . '" method="post">';
            // @todo add custom fields
            $r.= '<p>';
            $r.= '<p><input type="submit" name="bluem_idin_submitted" class="bluem-woocommerce-button bluem-woocommerce-button-idin" value="Identificeren.."></p>';
            $r.= '</form>';
        }
    }


    return $r;
    //ob_get_clean();
}

add_action('parse_request', 'bluem_idin_shortcode_idin_execute');
/**
 * This function is called POST from the form rendered on a page or post
 *
 * @return void
 */
function bluem_idin_shortcode_idin_execute()
{
    $shortcode_execution_url = "bluem-woocommerce/idin_execute";

    if (strpos($_SERVER["REQUEST_URI"], $shortcode_execution_url) === false) {
        // any other request
        return;
    }

    $goto = false;
    if (array_key_exists('redirect_to_checkout', $_GET)
        && sanitize_text_field($_GET['redirect_to_checkout']) == "true"
    ) {
        // v1.2.6: added cart url instead of static cart as this is front-end language dependent
        // $goto = wc_get_cart_url();
        // v1.2.8: added checkout url instead of cart url :)
        $goto = wc_get_checkout_url();
    }

    bluem_idin_execute(null, true, $goto);
}
/* ******** CALLBACK ****** */
add_action('parse_request', 'bluem_idin_shortcode_callback');
/**
 * This function is executed at a callback GET request with a given mandateId. This is then, together with the entranceCode in Session, sent for a SUD to the Bluem API.
 *
 */
function bluem_idin_shortcode_callback()
{
    if (strpos($_SERVER["REQUEST_URI"], "bluem-woocommerce/idin_shortcode_callback") === false) {
        // return;
    } else {
        $bluem_config = bluem_woocommerce_get_config();

        // fallback until this is corrected in bluem-php
        $bluem_config->brandID = $bluem_config->IDINBrandID;
        $bluem = new Bluem($bluem_config);



        if (is_user_logged_in()) {
            $entranceCode = get_user_meta(get_current_user_id(), "bluem_idin_entrance_code", true);
            $transactionID = get_user_meta(get_current_user_id(), "bluem_idin_transaction_id", true);
            $transactionURL = get_user_meta(get_current_user_id(), "bluem_idin_transaction_url", true);
        } else {
            if (isset($_SESSION["bluem_idin_entrance_code"]) && !is_null($_SESSION["bluem_idin_entrance_code"])) {
                $entranceCode = $_SESSION["bluem_idin_entrance_code"];
            } else {
                echo "Error: ".$_SESSION["bluem_idin_entrance_code"]." missing";
                exit;
            }
            if (isset($_SESSION["bluem_idin_transaction_id"]) && !is_null($_SESSION["bluem_idin_transaction_id"])) {
                $transactionID = $_SESSION["bluem_idin_transaction_id"];
            } else {
                echo "Error: ".$_SESSION["bluem_idin_transaction_id"]." missing";
                exit;
            }
            if (isset($_SESSION["bluem_idin_transaction_url"]) && !is_null($_SESSION["bluem_idin_transaction_url"])) {
                $transactionURL = $_SESSION["bluem_idin_transaction_url"];
            } else {
                echo "Error: ".$_SESSION["bluem_idin_transaction_url"]." missing";
                exit;
            }
        }

        $statusResponse = $bluem->IdentityStatus(
            $transactionID,
            $entranceCode
        );

        if ($statusResponse->ReceivedResponse()) {
            $statusCode = ($statusResponse->GetStatusCode());


            $request_from_db = bluem_db_get_request_by_transaction_id($transactionID);

            if ($request_from_db->status !== $statusCode) {
                bluem_db_update_request(
                    $request_from_db->id,
                    [
                        'status'=>$statusCode
                    ]
                );
            }


            if (is_user_logged_in()) {
                update_user_meta(
                    get_current_user_id(),
                    "bluem_idin_validated",
                    false
                );
            } else {
                $_SESSION['bluem_idin_validated'] = false;
            }



            switch ($statusCode) {
            case 'Success': // in case of success...
                // ..retrieve a report that contains the information based on the request type:
                $identityReport = $statusResponse->GetIdentityReport();

                if (is_user_logged_in()) {
                    update_user_meta(get_current_user_id(), "bluem_idin_results", json_encode($identityReport));
                    update_user_meta(get_current_user_id(), "bluem_idin_validated", true);
                } else {
                    // As suggested by Joost Oostdyck | HeathenMead - juni 2021
                    $_SESSION['bluem_idin_validated'] = true;
                    $_SESSION['bluem_idin_results'] = json_encode($identityReport);
                    //data van de validatie ook opslaan in de sessie, anders kun je de leeftijd etc niet ophalen
                }

                // update an age check response field if that sccenario is active.
                $verification_scenario = bluem_idin_get_verification_scenario();

                if ($verification_scenario == 1
                    && isset($identityReport->AgeCheckResponse)
                ) {
                    $agecheckresponse = $identityReport->AgeCheckResponse."";
                    if (is_user_logged_in()) {
                        update_user_meta(get_current_user_id(), "bluem_idin_report_agecheckresponse", $agecheckresponse);
                    } else {
                        $_SESSION['bluem_idin_report_agecheckresponse'] = $agecheckresponse;
                    }
                }
                if (isset($identityReport->CustomerIDResponse)) {
                    $customeridresponse = $identityReport->CustomerIDResponse."";
                    if (is_user_logged_in()) {
                        update_user_meta(get_current_user_id(), "bluem_idin_report_customeridresponse", $customeridresponse);
                    } else {
                        $_SESSION['bluem_idin_report_customeridresponse'] = $customeridresponse;
                    }
                }
                if (isset($identityReport->DateTime)) {
                    $datetime = $identityReport->DateTime."";
                    if (is_user_logged_in()) {
                        update_user_meta(get_current_user_id(), "bluem_idin_report_last_verification_timestamp", $datetime);
                    } else {
                        $_SESSION['bluem_idin_report_last_verification_timestamp'] = $datetime;
                    }
                }

                if (isset($identityReport->BirthdateResponse)) {
                    $birthdate = $identityReport->BirthdateResponse."";
                    if (is_user_logged_in()) {
                        update_user_meta(
                            get_current_user_id(),
                            "bluem_idin_report_birthdate",
                            $birthdate
                        );
                    } else {
                        $_SESSION['bluem_idin_report_birthdate'] = $birthdate;
                    }
                }
                if (isset($identityReport->TelephoneResponse)) {
                    $telephone = $identityReport->TelephoneResponse."";
                    if (is_user_logged_in()) {
                        update_user_meta(
                            get_current_user_id(),
                            "bluem_idin_report_telephone",
                            $telephone
                        );
                    }
                }
                if (isset($identityReport->EmailResponse)) {
                    $email = $identityReport->EmailResponse."";
                    if (is_user_logged_in()) {
                        update_user_meta(
                            get_current_user_id(),
                            "bluem_idin_report_email",
                            $email
                        );
                    }
                }


                $min_age = bluem_idin_get_min_age();
                if ($verification_scenario == 3
                    && isset($identityReport->BirthDateResponse)
                ) {
                    $user_age = bluem_idin_get_age_based_on_date(
                        $identityReport->BirthDateResponse
                    );

                    if ($user_age >= $min_age) {
                        if (is_user_logged_in()) {
                            update_user_meta(
                                get_current_user_id(),
                                "bluem_idin_report_agecheckresponse",
                                "true"
                            );
                        } else {
                            $_SESSION['bluem_idin_report_agecheckresponse'] = "true";
                        }
                    }
                }
                // var_dump($request_from_db);
                if (isset($request_from_db) && $request_from_db!==false) {
                    if ($request_from_db->payload!=="") {
                        try {
                            $oldPayload = json_decode($request_from_db->payload);
                        } catch (Throwable $th) {
                            $oldPayload = new Stdclass;
                        }
                    } else {
                        $oldPayload = new Stdclass;
                    }
                    $oldPayload->report = $identityReport;

                    bluem_db_update_request(
                        $request_from_db->id,
                        [
                            'status'=>$statusCode,
                            'payload'=>json_encode($oldPayload)
                        ]
                    );
                }

                bluem_transaction_notification_email(
                    $request_from_db->id
                );

                if (strpos($_SERVER["REQUEST_URI"], "bluem-woocommerce/idin_shortcode_callback/go_to_cart") !== false) {
                    $goto = wc_get_checkout_url();
                } else {
                    $goto = $bluem_config->IDINPageURL;

                    if ($goto == false || $goto == "") {
                        $goto = home_url();
                    } else {
                        $goto = home_url($bluem_config->IDINPageURL);
                    }
                }

                wp_redirect($goto);


                exit;
            break;
            case 'Processing':
                echo "Request has status Processing";

                // @todo: improve this flow
                // no break
            case 'Pending':
                echo "Request has status Pending";

                // @todo: improve this flow
                // do something when the request is still processing (for example tell the user to come back later to this page)
                break;
            case 'Cancelled':
                    echo "Request has status Cancelled";

                    // @todo: improve this flow
                    // do something when the request has been canceled by the user
                break;
            case 'Open':
                    echo "Request has status Open";

                    // @todo: improve this flow
                    // do something when the request has not yet been completed by the user, redirecting to the transactionURL again
                break;
            case 'Expired':
                    echo "Request has status Expired";

                    // @todo: improve this flow
                    // do something when the request has expired
                break;
            // case 'New':
                    //     echo "New request";
                    // break;
            default:
                    // unexpected status returned, show an error
                break;
            }


            bluem_transaction_notification_email(
                $request_from_db->id
            );

            wp_redirect(
                home_url($goto) .
                "?result=false&status={$statusCode}"
            );
        } else {
            // no proper response received, tell the user
            wp_redirect(
                home_url($goto) .
                "?result=false&status=no_response"
            );
        }
    }
}



add_action('show_user_profile', 'bluem_woocommerce_idin_show_extra_profile_fields', 2);
add_action('edit_user_profile', 'bluem_woocommerce_idin_show_extra_profile_fields');

function bluem_woocommerce_idin_show_extra_profile_fields($user)
{
    // var_dump($user->ID);
    // var_dump($bluem_requests);
    $bluem_requests = bluem_db_get_requests_by_user_id_and_type($user->ID."", "identity"); ?>

          <table class="form-table" style="max-height:800px; overflow-y:auto;">
          <a id="user_identity"></a>
    <?php

        ?>

    <?php if (isset($bluem_requests) && count($bluem_requests)>0) { ?>
        <tr>
    <th>
    Identiteit
    </th>
    <td>
    <?php
        bluem_render_requests_list($bluem_requests);?>
    </td>
        </tr>
    <?php } else {
            ?>

<tr>
<th>
Identiteit
    </th>
        <td>
Nog geen verzoeken uitgevoerd.
    </td>
</tr>
<tr>
<th>
<label for="bluem_idin_entrance_code">
    Bluem iDIN transactiegegevens
</label>
</th>
<td>
<input type="text" name="bluem_idin_entrance_code" id="bluem_idin_entrance_code" value="<?php echo get_user_meta($user->ID, 'bluem_idin_entrance_code', true); ?>" class="regular-text" /><br />
<span class="description">Recentste Entrance code voor Bluem iDIN requests</span>
<br>
<input type="text" name="bluem_idin_transaction_id" id="bluem_idin_transaction_id" value="<?php echo get_user_meta($user->ID, 'bluem_idin_transaction_id', true); ?>" class="regular-text" /><br />
<span class="description">DE meest recente transaction ID: deze wordt gebruikt bij het doen van een volgende identificatie.</span>
<br>
<input type="text" name="bluem_idin_transaction_url" id="bluem_idin_transaction_url" value="<?php echo get_user_meta($user->ID, 'bluem_idin_transaction_url', true); ?>" class="regular-text" /><br />
<span class="description">De meest recente transactie URL</span>
<br>
<input type="text" name="bluem_idin_report_last_verification_timestamp"
id="bluem_idin_report_last_verification_timestamp"
value="<?php echo get_user_meta($user->ID, 'bluem_idin_report_last_verification_timestamp', true); ?>"
class="regular-text" /><br />
<span class="description">Laatste keer dat verificatie is uitgevoerd</span>
</td>
</tr>

<tr>
<?php
        } ?>


<th>
<label for="bluem_idin_report_agecheckresponse">
Respons van bank op leeftijdscontrole, indien van toepassing
</label>
</th>


<td>
<?php $ageCheckResponse = get_user_meta($user->ID, 'bluem_idin_report_agecheckresponse', true); ?>
<select class="form-control" name="bluem_idin_report_agecheckresponse" id="bluem_idin_report_agecheckresponse">
<option value="" <?php if ($ageCheckResponse == "") {
            echo "selected='selected'";
        } ?>>Leeftijdcheck nog niet uitgevoerd</option>
<option value="false" <?php if ($ageCheckResponse == "false") {
            echo "selected='selected'";
        } ?>>Leeftijd niet toereikend bevonden</option>
<option value="true" <?php if ($ageCheckResponse == "true") {
            echo "selected='selected'";
        } ?>>Leeftijd toereikend bevonden</option>
</select>

<br>
<span class="description"></span>
</td>

</tr>
<tr>
<th>
    <label for="bluem_idin_report_customeridresponse">
    CustomerID dat terugkomt van de Bank
    </label>
</th>

<td>


<input type="text" name="bluem_idin_report_customeridresponse"
id="bluem_idin_report_customeridresponse"
value="<?php echo get_user_meta($user->ID, 'bluem_idin_report_customeridresponse', true); ?>"
class="regular-text" /><br />
<span class="description"></span>
</td>

</tr>

<tr>
<th>
<label for="bluem_idin_transaction_url">iDIN responses</label>
</th>

<td>

    <select class="form-control" name="bluem_idin_validated" id="bluem_idin_validated">
        <option value="0" <?php if (get_user_meta($user->ID, 'bluem_idin_validated', true)== "0") {
            echo "selected='selected'";
        } ?>>Identificatie nog niet uitgevoerd</option>
<option value="1" <?php if (get_user_meta($user->ID, 'bluem_idin_validated', true)== "1") {
            echo "selected='selected'";
        } ?>>Identificatie succesvol uitgevoerd</option>
</select>
<span class="description" style="display:block;">
Status en Resultaten van iDIN requests
</span>
</div>
</td>
</tr>
</table>
    <?php
}
add_action('personal_options_update', 'bluem_woocommerce_idin_save_extra_profile_fields');
add_action('edit_user_profile_update', 'bluem_woocommerce_idin_save_extra_profile_fields');

function bluem_woocommerce_idin_save_extra_profile_fields($user_id)
{
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    update_user_meta(
        $user_id,
        'bluem_idin_entrance_code',
        sanitize_text_field($_POST['bluem_idin_entrance_code'])
    );
    update_user_meta(
        $user_id,
        'bluem_idin_transaction_id',
        sanitize_text_field($_POST['bluem_idin_transaction_id'])
    );
    update_user_meta(
        $user_id,
        'bluem_idin_transaction_url',
        sanitize_text_field($_POST['bluem_idin_transaction_url'])
    );

    update_user_meta(
        $user_id,
        'bluem_idin_validated',
        sanitize_text_field($_POST['bluem_idin_validated'])
    );

    update_user_meta(
        $user_id,
        'bluem_idin_report_last_verification_timestamp',
        sanitize_text_field($_POST['bluem_idin_report_last_verification_timestamp'])
    );


    update_user_meta(
        $user_id,
        'bluem_idin_report_customeridresponse',
        sanitize_text_field($_POST['bluem_idin_report_customeridresponse'])
    );

    update_user_meta(
        $user_id,
        'bluem_idin_report_agecheckresponse',
        sanitize_text_field($_POST['bluem_idin_report_agecheckresponse'])
    );
}

function bluem_idin_retrieve_results()
{
    if (is_user_logged_in()) {
        $raw = get_user_meta(get_current_user_id(), "bluem_idin_results", true);
    } else {
        // As suggested by Joost Oostdyck | HeathenMead - juni 2021
        $raw = $_SESSION['bluem_idin_results'];
    }

    $obj = json_decode($raw);
    return $obj;
}

function bluem_idin_user_validated()
{
    global $current_user;
    if (is_user_logged_in()) {
        return get_user_meta(get_current_user_id(), "bluem_idin_validated", true) == "1";
    } else {
        if (isset($_SESSION['bluem_idin_validated']) && $_SESSION['bluem_idin_validated'] === true) {
            return true;
        } else {
            return false;
        }
    }
}

function bluem_get_IDINDescription_tags()
{
    return [
        '{gebruikersnaam}',
        '{email}',
        '{klantnummer}',
        '{datum}',
        '{datumtijd}'
    ];
}

function bluem_get_IDINDescription_replaces()
{
    global $current_user;

    // @todo: add fallbacks if user is not logged in

    return [
        $current_user->display_name,    //'{gebruikersnaam}',
        $current_user->user_email,      //'{email}',
        $current_user->ID,              // {klantnummer}
        date("d-m-Y"),                  //'{datum}',
        date("d-m-Y H:i")               //'{datumtijd}',
    ];
}
function bluem_parse_IDINDescription($input)
{
    $tags = bluem_get_IDINDescription_tags();
    $replaces = bluem_get_IDINDescription_replaces();


    $result = str_replace($tags, $replaces, $input);
    $invalid_chars = ['[',']','{','}','!','#'];
    // @todo Add full list of invalid chars for description based on XSD
    $result = str_replace($invalid_chars, '', $result);

    $result = substr($result, 0, 128);
    return $result;
}




function bluem_idin_execute($callback=null, $redirect=true, $redirect_page = false)
{
    global $current_user;
    $bluem_config = bluem_woocommerce_get_config();
    if (isset($bluem_config->IDINDescription)) {
        $description = bluem_parse_IDINDescription($bluem_config->IDINDescription);
    } else {
        $description =  "Identificatie " . $current_user->display_name ;
    }

    $debtorReference = $current_user->ID;

    // fallback until this is corrected in bluem-php
    $bluem_config->brandID = $bluem_config->IDINBrandID;
    $bluem = new Bluem($bluem_config);

    $cats = bluem_idin_get_categories();
    if (count($cats)==0) {
        exit("Geen juiste iDIN categories ingesteld");
    }

    if (is_null($callback)) {
        $callback = home_url("bluem-woocommerce/idin_shortcode_callback");
    }

    if ($redirect_page!==false) {
        $callback .= "/go_to_cart";
    }

    // To create AND perform a request:
    $request = $bluem->CreateIdentityRequest(
        $cats,
        $description,
        $debtorReference,
        $callback
    );

    $response = $bluem->PerformRequest($request);

    bluem_register_session();

    if ($response->ReceivedResponse()) {
        $entranceCode = $response->GetEntranceCode();
        $transactionID = $response->GetTransactionID();
        $transactionURL = $response->GetTransactionURL();

        bluem_db_create_request(
            [
                'entrance_code'=>$entranceCode,
                'transaction_id'=>$transactionID,
                'transaction_url'=>$transactionURL,
                'user_id'=> is_user_logged_in() ? $current_user->ID : 0,
                'timestamp'=> date("Y-m-d H:i:s"),
                'description'=>$description,
                'debtor_reference'=>$debtorReference,
                'type'=>"identity",
                'order_id'=>null,
                'payload'=>json_encode([
                    'environment' => $bluem->environment
                ])
            ]
        );


        // save this in our user meta data store
        if (is_user_logged_in()) {
            update_user_meta(
                get_current_user_id(),
                "bluem_idin_entrance_code",
                $entranceCode
            );
            update_user_meta(
                get_current_user_id(),
                "bluem_idin_transaction_id",
                $transactionID
            );
            update_user_meta(
                get_current_user_id(),
                "bluem_idin_transaction_url",
                $transactionURL
            );
        } else {
            $_SESSION["bluem_idin_entrance_code"] = $entranceCode;
            $_SESSION["bluem_idin_transaction_id"] = $transactionID;
            $_SESSION["bluem_idin_transaction_url"] = $transactionURL;
        }

        if ($redirect) {
            if (ob_get_length()!==false && ob_get_length()>0) {
                ob_clean();
            }
            ob_start();
            wp_redirect($transactionURL);
            exit;
        } else {
            return ['result'=>true,'url'=>$transactionURL];
        }
    } else {
        $msg = "Er ging iets mis bij het aanmaken van de transactie.<br>
        Vermeld onderstaande informatie aan het websitebeheer:";
        if ($response->Error() !=="") {
            $msg.= "<br>Response: " .
            $response->Error();
        } else {
            $msg .= "<br>Algemene fout";
        }


        bluem_woocommerce_prompt($msg);
        exit;
    }
    exit;
}

// https://www.businessbloomer.com/woocommerce-visual-hook-guide-checkout-page/
// add_action( 'woocommerce_review_order_before_payment', 'bluem_checkout_check_idin_validated' );

// CHECKOUT review message
add_action('woocommerce_review_order_before_payment', 'bluem_checkout_idin_notice');
function bluem_checkout_idin_notice()
{
    global $current_user;

    // if no woo
    if (!function_exists("is_checkout") || !function_exists('is_wc_endpoint_url')) {
        return;
    }

    // use a setting if this check has to be incurred
    if (function_exists("is_checkout") && !is_checkout()) {
        return;
    }

    if (!function_exists('bluem_idin_user_validated')) {
        return;
    }

    // don't show this notice on the my-account page (DrankStunter Request, 22-04-2021)
    if (is_page('my-account')
        || is_page('mijn-account')
    ) {
        return;
    }


    if (home_url() === "https://drankstunter.nl") {
        if (!is_user_logged_in()) {
            return;
        }
    }

    $options = get_option('bluem_woocommerce_options');

    if (isset($options['idin_scenario_active']) && $options['idin_scenario_active']!=="") {
        $scenario = (int) $options['idin_scenario_active'];
    }


    if (isset($options['idin_identity_dialog_no_verification_text']) && $options['idin_identity_dialog_no_verification_text']!=="") {
        $identity_dialog_no_verification_text = $options['idin_identity_dialog_no_verification_text'];
    } else {
        $identity_dialog_no_verification_text = "Uw leeftijd is niet bekend of niet toereikend. U kan dus niet deze bestelling afronden. Neem bij vragen contact op met de webshop support.";
    }



    if (isset($options['idin_identity_dialog_thank_you_message']) && $options['idin_identity_dialog_thank_you_message']!=="") {
        $idin_identity_dialog_thank_you_message = $options['idin_identity_dialog_thank_you_message'];
    } else {
        $idin_identity_dialog_thank_you_message = "Je leeftijd is geverifieerd, bedankt.";
    }

    if (isset($options['idin_identity_topbar_no_verification_text']) && $options['idin_identity_topbar_no_verification_text']!=="") {
        $idin_identity_topbar_no_verification_text = $options['idin_identity_topbar_no_verification_text'];
    } else {
        $idin_identity_topbar_no_verification_text = "We hebben uw leeftijd (nog) niet kunnen opvragen. Voltooi eerst de identificatie procedure.";
    }


    if (isset($options['idin_identity_more_information_popup']) && $options['idin_identity_more_information_popup']!=="") {
        $idin_identity_more_information_popup = $options['idin_identity_more_information_popup'];
    } else {
        $idin_identity_more_information_popup = "**Identificeren is per 1 juli 2021 verplicht in winkels waar producten verkocht worden met een identiteitsplicht van de klant.**

De methode die hier gebruikt wordt is veilig, snel en makkelijk - net zoals iDeal.   Het duurt hoogstens twee minuten en het resultaat wordt opgeslagen voor vervolgtransacties als je ingelogd bent als terugkerende klant.

Lees hier meer: [https://bluem.nl/blog/2021/04/26/nieuwe-alcoholwet-per-1-juli-online-leeftijdsverificatie-verplicht/](https://bluem.nl/blog/2021/04/26/nieuwe-alcoholwet-per-1-juli-online-leeftijdsverificatie-verplicht/)";
    }
    // todo: remove these obsolete defaults

    // var_dump($_SESSION);
    // BROODJE



    if ($scenario > 0) {
        echo "<h3>Identificatie</h3>";

        $validated = bluem_idin_user_validated();
        $validation_message = $idin_identity_topbar_no_verification_text;
        // $validation_message = "Let op: Graag eerst eenmalig identificeren.";
        //"Identificatie is vereist alvorens de bestelling kan worden afgerond.";
        $idin_logo_html = bluem_get_idin_logo_html();
        // above 0: any form of verification is required
        if (!$validated) {
            echo bluem_idin_generate_notice($validation_message, true);
            return;
        }

        // get report from user metadata
        // $results = bluem_idin_retrieve_results();
        // identified? but is this person OK of age?
        if ($scenario == 1 || $scenario == 3) {
            // we gaan er standaard vanuit dat de leeftijd NIET toereikend is
            $age_valid = false;

            if (is_user_logged_in()) {
                $ageCheckResponse = get_user_meta(
                    $current_user->ID,
                    'bluem_idin_report_agecheckresponse',
                    true
                );
            } else {
                // for debugging
                // $_SESSION['bluem_idin_report_agecheckresponse'] = "true";

                $ageCheckResponse = $_SESSION['bluem_idin_report_agecheckresponse'];
            }
            // var_dump($_SESSION['bluem_idin_report_agecheckresponse']);

            // var_dump($ageCheckResponse);
            // check on age based on response of AgeCheckRequest in user meta
            // if ($scenario == 1)
            // {
            if (isset($ageCheckResponse)) {
                if ($ageCheckResponse == "true") {

                    // TRUE Teruggekregen van de bank
                    $age_valid = true;
                } else {
                    // ERROR KON BIRTHDAY NIET INLEZEN, WEL INGEVULD BIJ DE BANK? nIET VALIDE DUS
                    $validation_message = $identity_dialog_no_verification_text;
                    // /"Uw leeftijd is niet bekend of niet toereikend. U kan dus niet deze bestelling afronden. Neem bij vragen contact op met de webshop support.";

                    $age_valid = false;
                }
            } else {
                // ERROR KON BIRTHDAY NIET INLEZEN, WEL INGEVULD BIJ DE BANK? nIET VALIDE DUS
                $validation_message = $identity_dialog_no_verification_text;
                //"We hebben uw leeftijd nog niet kunnen opvragen bij de identificatie.<BR>  Neem contact op met de webshop support.";

                $age_valid = false;
            }
            // }

            // check on age based on response of BirthDateRequest in user meta
            // if ($scenario == 3)
            // {
            //     $min_age = bluem_idin_get_min_age();


            //     // echo $results->BirthDateResponse; // prints 1975-07-25
            //     if (isset($results->BirthDateResponse) && $results->BirthDateResponse!=="") {

            //         $user_age = bluem_idin_get_age_based_on_date($results->BirthDateResponse);
            //         if ($user_age < $min_age) {
            //             $validation_message = "Je leeftijd, $user_age, is niet toereikend. De minimumleeftijd is {$min_age} jaar.
            //             <br>Identificeer jezelf opnieuw of neem contact op.";
            //             $age_valid = false;
            //         } else {
            //             $age_valid = true;
            //         }
            //     } else {

            //         // ERROR KON BIRTHDAY NIET INLEZEN, WEL INGEVULD BIJ DE BANK? nIET VALIDE DUS
            //         $validation_message = "We hebben je leeftijd niet kunnen opvragen bij de identificatie.<BR>
            //         Neem contact op met de webshop support.";
            //         $age_valid =false;
            //     }
            // }


            if (!$age_valid) {
                echo bluem_idin_generate_notice($validation_message, true);
                return;
            } else {
                echo bluem_idin_generate_notice($idin_identity_dialog_thank_you_message);
                return;
            }
        }
    }

    // <p>Identificatie is vereist alvorens je deze bestelling kan plaatsen</p>";

    if (bluem_checkout_check_idin_validated_filter()==false) {
        echo bluem_idin_generate_notice("Verifieer eerst je identiteit.", true);
        // echo __(
        //     "Verifieer eerst je identiteit via de mijn account pagina",
        //     "woocommerce"
        // );
        return;
    }
}


// add_action('woocommerce_check_cart_items', 'bluem_checkout_check_idin_validated'); // Cart and Checkout



add_action('woocommerce_after_checkout_validation', 'bluem_validate_idin_at_checkout', 10, 2);

function bluem_validate_idin_at_checkout($fields, $errors)
{
    bluem_checkout_check_idin_validated();
    // $errors->add( 'validation', 'Your first or last name contains a number. Really?' );
}



/**
 * Show notice!
 */
add_action('template_redirect', 'bluem_checkout_check_idin_validated');
function bluem_checkout_check_idin_validated()
{
    global $current_user;
    // ! is_user_logged_in() &&

    if (!function_exists("is_checkout") || !function_exists('is_wc_endpoint_url')) {
        return;
    }

    // only run this check in Woo
    if (is_checkout() && ! is_wc_endpoint_url()) {
        // wc_add_notice( sprintf( __('This is my <strong>"custom message"</strong> and I can even add a button to the right… <a href="%s" class="button alt">My account</a>'), get_permalink( get_option('woocommerce_myaccount_page_id') ) ), 'notice' );
    } else {
        return;
    }

    // don't show this notice on the my-account page (DrankStunter Request, 22-04-2021)
    if (is_page('my-account')
        || is_page('mijn-account')
    ) {
        return;
    }

    if (home_url() === "https://drankstunter.nl") {
        if (!is_user_logged_in()) {
            return;
        }
    }

    if (!function_exists('bluem_idin_user_validated')) {
        return;
    }



    $options = get_option('bluem_woocommerce_options');
    if (isset($options['idin_identify_button_inner']) && $options['idin_identify_button_inner']!=="") {
        $identify_button_inner = $options['idin_identify_button_inner'];
    } else {
        $identify_button_inner = "Klik hier om je te identificeren";
    }

    if (isset($options['idin_identity_topbar_invalid_verification_text']) && $options['idin_identity_topbar_invalid_verification_text']!=="") {
        $idin_identity_topbar_invalid_verification_text = $options['idin_identity_topbar_invalid_verification_text'];
    } else {
        $idin_identity_topbar_invalid_verification_text = "Uw leeftijd is niet toereikend. U kan dus niet deze bestelling afronden.";
    }
    if (isset($options['idin_identity_topbar_no_verification_text']) && $options['idin_identity_topbar_no_verification_text']!=="") {
        $idin_identity_topbar_no_verification_text = $options['idin_identity_topbar_no_verification_text'];
    } else {
        $idin_identity_topbar_no_verification_text = "We hebben uw leeftijd (nog) niet kunnen opvragen. Voltooi eerst de identificatie procedure.";
    }


    if (isset($options['idin_identity_popup_thank_you_message']) && $options['idin_identity_popup_thank_you_message']!=="") {
        $idin_identity_popup_thank_you_message = $options['idin_identity_popup_thank_you_message'];
    } else {
        $idin_identity_popup_thank_you_message = "Je leeftijd is geverifieerd.";
    }
    // todo: remove these obsolete defaults


    $identify_button_html = "<br><a href='".
        home_url('bluem-woocommerce/idin_execute?redirect_to_checkout=true')."'
        target='_self' class='button bluem-identify-button'>{$identify_button_inner}</a>";

    if (isset($options['idin_scenario_active']) && $options['idin_scenario_active']!=="") {
        $scenario = (int) $options['idin_scenario_active'];
    }

    if ($scenario > 0) {
        $validated = bluem_idin_user_validated();
        $idin_logo_html = bluem_get_idin_logo_html();
        $validation_message = $idin_identity_topbar_no_verification_text;
        ///Identificatie is vereist alvorens de bestelling kan worden afgerond.

        // above 0: any form of verification is required
        if (!$validated) {
            wc_add_notice(
                bluem_idin_generate_notice($validation_message, true, false, false),
                'error'
            );
        } else {

            // get report from user metadata
            // $results = bluem_idin_retrieve_results();
            // identified? but is this person OK of age?
            if ($scenario == 1 || $scenario == 3) {
                // we gaan er standaard vanuit dat de leeftijd NIET toereikend is
                $age_valid = false;

                if (is_user_logged_in()) {
                    $ageCheckResponse = get_user_meta(
                        $current_user->ID,
                        'bluem_idin_report_agecheckresponse',
                        true
                    );
                } else {
                    // for debugging
                    // $_SESSION['bluem_idin_report_agecheckresponse'] = "true";

                    $ageCheckResponse = $_SESSION['bluem_idin_report_agecheckresponse'];
                }
                // var_dump($_SESSION['bluem_idin_report_agecheckresponse']);

                // check on age based on response of AgeCheckRequest in user meta
                // if ($scenario == 1)
                // {
                $age_valid = false;
                if (isset($ageCheckResponse) && $ageCheckResponse !="") {
                    if ($ageCheckResponse == "true") {

                    // TRUE Teruggekregen van de bank
                        $age_valid = true;
                    } else {
                        // ERROR KON BIRTHDAY NIET INLEZEN, WEL INGEVULD BIJ DE BANK? nIET VALIDE DUS
                        $validation_message = $idin_identity_topbar_invalid_verification_text;
                        //"Uw leeftijd is niet toereikend. U kan dus niet deze bestelling afronden.";

                        $age_valid = false;
                    }
                } else {
                    // ERROR KON BIRTHDAY NIET INLEZEN, WEL INGEVULD BIJ DE BANK? nIET VALIDE DUS
                    $validation_message = $idin_identity_topbar_no_verification_text;
                    // "We hebben uw leeftijd (nog) niet kunnen opvragen. Voltooi eerst de identificatie procedure";

                    $age_valid = false;
                }
                // }

                // check on age based on response of BirthDateRequest in user meta
                // if ($scenario == 3)
                // {
                //     $min_age = bluem_idin_get_min_age();


                //     // echo $results->BirthDateResponse; // prints 1975-07-25
                //     if (isset($results->BirthDateResponse) && $results->BirthDateResponse!=="") {

                //         $user_age = bluem_idin_get_age_based_on_date($results->BirthDateResponse);
                //         if ($user_age < $min_age) {
                //             $validation_message = "Je leeftijd, $user_age, is niet toereikend. De minimumleeftijd is {$min_age} jaar.
                //             <br>Identificeer jezelf opnieuw of neem contact op.";
                //             $age_valid = false;
                //         } else {
                //             $age_valid = true;
                //         }
                //     } else {

                //         // ERROR KON BIRTHDAY NIET INLEZEN, WEL INGEVULD BIJ DE BANK? nIET VALIDE DUS
                //         $validation_message = "We hebben je leeftijd niet kunnen opvragen bij de identificatie.<BR>
                //         Neem contact op met de webshop support.";
                //         $age_valid =false;
                //     }
                // }
                if (!$age_valid) {
                    wc_add_notice(
                        bluem_idin_generate_notice($validation_message, true, false, false),
                        'error'
                    );
                } else {
                    wc_add_notice(
                        $idin_identity_popup_thank_you_message,
                        'success'
                    );
                }
            }
        }
    }

    // custom user-based checks:
    if (bluem_checkout_check_idin_validated_filter()==false) {
        wc_add_notice(
            bluem_idin_generate_notice($validation_message, true, false, false),
            'error'
        );
        // wc_add_notice(
        //     $idin_logo_html . __(
        //         "Verifieer eerst je identiteit via de mijn account pagina",
        //         "woocommerce"
        //     ),
        //     'error'
        // );
    }
    return;
}

// @todo: simplify and merge above two functions bluem_checkout_check_idin_validated and bluem_checkout_idin_notice

add_filter(
    'bluem_checkout_check_idin_validated_filter',
    'bluem_checkout_check_idin_validated_filter_function',
    10,
    1
);
function bluem_checkout_check_idin_validated_filter()
{

    // override this function if you want to add a filter to block the checkout procedure based on the iDIN validation procedure being completed.
    // if you return true, the checkout is enabled. If you return false, the checkout is blocked and a notice is shown.

    // example:
    // if (!bluem_idin_user_validated()) {
    //   return false;
    // }

    return true;
}


function bluem_idin_get_age_based_on_date($birthday_string)
{
    $birthdate_seconds = strtotime($birthday_string);
    $now_seconds = strtotime("now");
    return (int)floor(($now_seconds - $birthdate_seconds) / 60 / 60 / 24 / 365);
}


function bluem_idin_get_verification_scenario()
{
    $options = get_option('bluem_woocommerce_options');
    $scenario = 0;
    if (isset($options['idin_scenario_active']) && $options['idin_scenario_active']!=="") {
        $scenario = (int) $options['idin_scenario_active'];
    }
    return $scenario;
}

function bluem_idin_get_min_age()
{
    $options = get_option('bluem_woocommerce_options');
    if (isset($options['idin_check_age_minimum_age']) && $options['idin_check_age_minimum_age']!=="") {
        $min_age = $options['idin_check_age_minimum_age'];
    } else {
        $min_age = 18;
    }
    return $min_age;
}
class BluemIdentityCategoryList
{
    public $_cats = [];

    public function getCats()
    {
        return $this->_cats;
    }
    public function addCat($cat)
    {
        if (!in_array($cat, $this->_cats)) {
            $this->_cats[] = $cat;
        }
    }
}


// https://wordpress.stackexchange.com/questions/314955/add-custom-order-meta-to-order-completed-email
add_filter('woocommerce_email_order_meta_fields', 'bluem_order_email_identity_meta_data', 10, 3);

function bluem_order_email_identity_meta_data($fields, $sent_to_admin, $order)
{
    global $current_user;

    // Note: only works for logged in users at the moment

    $options = get_option('bluem_woocommerce_options');

    if (!array_key_exists('idin_add_field_in_order_emails', $options)
        || (array_key_exists('idin_add_field_in_order_emails', $options)
        && $options['idin_add_field_in_order_emails'] == "1")
    ) {
        if (is_user_logged_in()) {
            $validation_text = "";
            if (get_user_meta($current_user->ID, 'bluem_idin_validated', true)) {
                $validation_text = __('ja', 'bluem');
            // $validation_text .= " (Transactie ". get_user_meta($current_user->ID, 'bluem_idin_transaction_id', true).")";
            } else {
                $validation_text = __('nee', 'bluem') ;
            }

            $fields['bluem_idin_validated'] = [
                'label'=>__('Identiteit geverifieerd', 'bluem'),
                'value'=> $validation_text
            ];
        }
    }


    $request = bluem_db_get_most_recent_request($current_user->ID, "identity");
    $pl = json_decode($request->payload);

    if (!array_key_exists('idin_add_address_in_order_emails', $options)
        || (array_key_exists('idin_add_address_in_order_emails', $options)
        && $options['idin_add_address_in_order_emails'] == "1")
    ) {
        if (is_user_logged_in()) {
            if ($request !== false) {
                $address_text = "";
                if (isset($pl->report->AddressResponse->Street)) {
                    $address_text.= $pl->report->AddressResponse->Street." ";
                }
                if (isset($pl->report->AddressResponse->HouseNumber)) {
                    $address_text.= $pl->report->AddressResponse->HouseNumber." ";
                }
                $address_text.="<br>";
                if (isset($pl->report->AddressResponse->PostalCode)) {
                    $address_text.= $pl->report->AddressResponse->PostalCode." ";
                }
                if (isset($pl->report->AddressResponse->City)) {
                    $address_text.= $pl->report->AddressResponse->City." ";
                }
                if (isset($pl->report->AddressResponse->CountryCode)) {
                    $address_text.= $pl->report->AddressResponse->CountryCode."";
                }

                $fields['bluem_idin_address'] = [
                'label'=> __('Adres uit verificatie', 'bluem'),
                'value'=> $address_text
            ];
            // var_dump($address_text);
            // die();
            } else {
                $fields['bluem_idin_address'] = [
                'label'=> __('Adres uit verificatie', 'bluem'),
                'value'=> "Onbekend"
            ];
            }
        }
    }


    if (!array_key_exists('idin_add_name_in_order_emails', $options)
        || (array_key_exists('idin_add_name_in_order_emails', $options)
        && $options['idin_add_name_in_order_emails'] == "1")
    ) {
        if ($request !== false) {
            $name_text = "";
            if (isset($pl->report->NameResponse->Initials)
                && $pl->report->NameResponse->Initials!==""
            ) {
                $name_text.= $pl->report->NameResponse->Initials." ";
            }
            if (isset($pl->report->NameResponse->LegalLastNamePrefix)
                && $pl->report->NameResponse->LegalLastNamePrefix!==""
            ) {
                $name_text.= $pl->report->NameResponse->LegalLastNamePrefix." ";
            }
            if (isset($pl->report->NameResponse->LegalLastName)
                && $pl->report->NameResponse->LegalLastName!==""
            ) {
                $name_text.= $pl->report->NameResponse->LegalLastName." ";
            }

            $fields['bluem_idin_name'] = [
                'label'=> __('Naam uit verificatie', 'bluem'),
                'value'=> $name_text
            ];
        } else {
            $fields['bluem_idin_name'] = [
                'label'=> __('Naam uit verificatie', 'bluem'),
                'value'=> "Onbekend"
            ];
        }
    }

    if (!array_key_exists('idin_add_birthdate_in_order_emails', $options)
        || (array_key_exists('idin_add_birthdate_in_order_emails', $options)
        && $options['idin_add_birthdate_in_order_emails'] == "1")
    ) {
        if ($request !== false) {
            $birthdate_text = "";
            if (isset($pl->report->BirthDateResponse)
                && $pl->report->BirthDateResponse!==""
            ) {
                $birthdate_text.= $pl->report->BirthDateResponse." ";
            }

            $fields['bluem_idin_birthdate'] = [
                'label'=> __('Geboortedatum uit verificatie', 'bluem'),
                'value'=> $birthdate_text
            ];
        } else {
            $fields['bluem_idin_birthdate'] = [
                'label'=> __('Geboortedatum uit verificatie', 'bluem'),
                'value'=> "Onbekend"
            ];
        }
    }
    return $fields;
}


// add_action('woocommerce_review_order_before_payment', 'bluem_idin_before_payment_notice');
// function bluem_idin_before_payment_notice()
// {
// }

/**
 * Generate the necessary HTML to show a notice concerning iDIN status
 *
 * @param String $message
 * @param boolean $button
 * @return String
 */
function bluem_idin_generate_notice(String $message ="", bool $button = false, bool $logo = true, bool $border = true) : String
{
    $idin_logo_html = "<img src='".
    plugin_dir_url(__FILE__)."assets/bluem/idin.png' class='bluem-idin-logo'
    style='position:absolute; top:15pt; left:15pt; max-height:64px; '/>";


    $options = get_option('bluem_woocommerce_options');
    if (isset($options['idin_identify_button_inner']) && $options['idin_identify_button_inner']!=="") {
        $identify_button_inner = $options['idin_identify_button_inner'];
    } else {
        $identify_button_inner = "Klik hier om je te identificeren";
    }
    if (isset($options['idin_identify_button_inner']) && $options['idin_identify_button_inner']!=="") {
        $identify_button_inner = $options['idin_identify_button_inner'];
    } else {
        $identify_button_inner = "Klik hier om je te identificeren";
    }

    if (isset($options['idin_identity_more_information_popup']) && $options['idin_identity_more_information_popup']!=="") {
        $more_information_popup = $options['idin_identity_more_information_popup'];
    } else {
        $more_information_popup = "Toelicthing op IDIN als essentieel onderdeel van het winkelproces";
    }
    $Parsedown = new Parsedown();

    $more_information_popup_parsed = $Parsedown->text($more_information_popup);


    $idin_button_html = "<a href='".
        home_url('bluem-woocommerce/idin_execute?redirect_to_checkout=true').
        "' target='_self' class='button bluem-identify-button' style='display:inline-block' title='{$identify_button_inner}'>
            {$identify_button_inner}
        </a><br>";

    $html = "<div style='position:relative;".
    ($border?"border-radius:4px;
    width:auto;
    min-height:110px;
    display:block;
    padding:15pt;
    margin-top:10px;
    margin-bottom:10px;
    border:1px solid #50afed;":"")."'>";
    if ($logo) {
        $html .= "{$idin_logo_html}";
    }

    $html .= "<div style='
    ".($border?"margin-left:100px; display:block; width:auto; height:auto;":"")."'>
        {$message}";
    if ($button) {
        $html .= "<div style='' class='bluem-idin-button'>";
        $html .= "{$idin_button_html}";
        $html .= "</div>";
    }
    $html .= "
    </div>";
    $html .= "</div>";
    $html .= '
    <div class="bluem-idin-box">
	<a class="bluem-idin-info-button" href="#idin_info_popup">
        <span class="dashicons dashicons-editor-help"></span>
        Wat is dit?
    </a>

    </div>
    <div id="idin_info_popup" class="bluem-idin-overlay">
	<div class="bluem-idin-popup">
    <h4>Toelichting op vereiste identificatie</h4>
    <a class="bluem-idin-popup-close bluem-idin-popup-close-icon" href="#">&times;</a>
    <div class="bluem-idin-popup-content">
    '.$more_information_popup_parsed.'

    <hr>
    <a class="bluem-idin-popup-close" href="#">Klik hier om dit kader te sluiten</a>
    </div>
	</div>
</div> ';
    return $html;
}






 /** Add identity request if it was already created but first decoupled form a user, based on sesh
  * https://wordpress.stackexchange.com/questions/161574/wp-create-user-hook

  **/
 add_filter('pre_user_login', function ($user) {
     // KAASS
     if (isset($_SESSION['bluem_idin_transaction_id'])) {
         $tid = $_SESSION['bluem_idin_transaction_id'];
         $req = bluem_db_get_request_by_transaction_id($tid);
         // var_dump($req);

         if (is_user_logged_in()) {
             // $user_id = $current_user->ID;
             $user_id = $user->ID;
         }

         if ($req->user_id == "0") {
             bluem_db_update_request(
                 $req->id,
                 ['user_id'=>$user_id]
             );
             bluem_db_request_log($req->id, "Linked identity to user by logging in");
         }
     }



     // ["bluem_idin_entrance_code"]=>
     // string(26) "HIO100OIH20210622164952435"
     // ["bluem_idin_transaction_id"]=>
     // string(16) "144e875e5053d779"
     // ["bluem_idin_transaction_url"]=>
     // string(79) "https://test.viamijnbank.net/i/00020a00362f000007da140000150107d0002f0100f40260"
     // ["bluem_idin_validated"]=>
     // bool(true)
     // ["bluem_idin_results"]=>
     // string(110) "{"DateTime":"2021-06-22T16:49:59.497Z","CustomerIDResponse":"FANTASYBANK1234567890","AgeCheckResponse":"true"}"
     // ["bluem_idin_report_agecheckresponse"]=>
     // string(4) "true"
     // ["bluem_idin_report_customeridresponse"]=>
     // string(21) "FANTASYBANK1234567890"
     // ["bluem_idin_report_last_verification_timestamp"]=>
     // string(24) "2021-06-22T16:49:59.497Z"
     //  var_dump( current_filter()." works fine" );
     return $user;
 });
