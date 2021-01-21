<?php

if (!defined('ABSPATH')) {
	exit;
}

use Bluem\BluemPHP\Integration as BluemCoreIntegration;
use Carbon\Carbon;


if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // WooCommerce specific code incoming here
}

function _bluem_get_idin_option($key) {
	$options = _bluem_get_idin_options();
	if(array_key_exists($key,$options))
	{
		return $options[$key];
	}
	return false;
}

function _bluem_get_idin_options()
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
        'name' => 'URL van Identificatiepagina',
        'description' => 'van pagina waar het Identificatie proces wordt weergegeven, bijvoorbeeld een accountpagina. De gebruiker komt op deze pagina terug na het proces',
        'default' => 'my-account'
    ],
    'IDINCategories' => [
        'key' => 'IDINCategories',
        'title' => 'bluem_IDINCategories',
        'name' => 'Comma separated categories in IDIN shortcode requests',
        'description' => 'Opties: CustomerIDRequest, NameRequest, AddressRequest, BirthDateRequest, AgeCheckRequest, GenderRequest, TelephoneRequest, EmailRequest',
        'default' => 'AddressRequest,BirthDateRequest'
    ],
    'IDINBrandID' => [
        'key' => 'IDINBrandID',
        'title' => 'bluem_IDINBrandID',
        'name' => 'IDIN BrandId',
        'description' => '',
        'default' => ''
    ],
    'IDINShortcodeOnlyAfterLogin' => [
        'key' => 'IDINShortcodeOnlyAfterLogin',
        'title' => 'bluem_IDINShortcodeOnlyAfterLogin',
        'name' => 'IDINShortcodeOnlyAfterLogin',
        'description' => "Moet het iDIN formulier via shortcode zichtbaar zijn voor iedereen of alleen ingelogde bezoekers?",
        'type' => 'select',
        'default' => '0',
        'options' => ['0' => 'Voor iedereen', '1' => 'Alleen voor ingelogde bezoekers'],
    ],
    'IDINDescription' => [
        'key' => 'IDINDescription',
        'title' => 'bluem_IDINDescription',
        'name' => 'Formaat beschrijving request',
        'description' => 'Geef het format waaraan de beschrijving van 
            een identificatie request moet voldoen, met automatisch ingevulde velden.<br>Dit gegeven wordt ook weergegeven in de Bluem portal als de \'Inzake\' tekst.   
            <br>Voorbeeld Huidige waarde: <code style=\'display:block;\'>'.
            $idinDescriptionCurrentValue.'</code><br>Mogelijke invulvelden '. $idinDescriptionTable.
            "<br>Let op: max 128 tekens. Toegestane karakters: <code>-0-9a-zA-ZéëïôóöüúÉËÏÔÓÖÜÚ€ ()+,.@&amp;=%&quot;&apos;/:;?$</code>",
        'default' => 'Identificatie {gebruikersnaam}'
    ]
    ];
}

function bluem_woocommerce_idin_settings_section()
{
    ?>
    <p>Hier kan je alle belangrijke gegevens instellen rondom iDIN (Identificatie). Lees de readme bij de plug-in voor meer informatie.</p>
    <h4>
        Hoe het werkt
    </h4>
    <p>Het IDIN formulier werkt via een shortcode, welke je kan plaatsen op een pagina, post of in een template. De shortcode is als volgt: 
    <code>[bluem_identificatieformulier]</code>. 
    </p>
    <p>
        Zodra je deze hebt geplaatst, is op deze pagina een blok zichtbaar waarin de status van de identificatieprocedure staat. Indien geen identificatie is uitgevoerd, zal er een knop verschijnen om deze te starten.
    </p>
    <p>
    Bij succesvol uitvoeren van de identificatie via Bluem, komt men terug op de pagina die hieronder wordt aangemerkt als IDINPageURL (huidige waarde: 
    <?php

$options = get_option('bluem_woocommerce_options');
if (isset($options['IDINPageURL'])) {
    echo($options['IDINPageURL']);
}
    ?>).
    </p><p>
    Gegevens worden vervolgens opgeslagen bij het user profile.
    </p>

    <?php
}

function bluem_woocommerce_settings_render_IDINSuccessMessage()
{
    bluem_woocommerce_settings_render_input(_bluem_get_idin_option('IDINSuccessMessage'));
}

function bluem_woocommerce_settings_render_IDINErrorMessage()
{
    bluem_woocommerce_settings_render_input(_bluem_get_idin_option('IDINErrorMessage'));
}

function bluem_woocommerce_settings_render_IDINPageURL()
{
    bluem_woocommerce_settings_render_input(_bluem_get_idin_option('IDINPageURL'));
}

function bluem_woocommerce_settings_render_IDINCategories()
{
    bluem_woocommerce_settings_render_input(_bluem_get_idin_option('IDINCategories'));
}

function bluem_woocommerce_settings_render_IDINBrandID()
{
    bluem_woocommerce_settings_render_input(_bluem_get_idin_option('IDINBrandID'));
}


function bluem_woocommerce_settings_render_IDINShortcodeOnlyAfterLogin()
{
    bluem_woocommerce_settings_render_input(_bluem_get_idin_option('IDINShortcodeOnlyAfterLogin'));
}

function bluem_woocommerce_settings_render_IDINDescription()
{
    bluem_woocommerce_settings_render_input(_bluem_get_idin_option('IDINDescription'));
}


