<?php

if (!defined('ABSPATH')) {
	exit;
}

use Bluem\BluemPHP\Integration as BluemCoreIntegration;
use Carbon\Carbon;


if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    // WooCommerce specific code incoming here
}

function bluem_woocommerce_get_idin_option($key) {
	$options = bluem_woocommerce_get_idin_options();
	if(array_key_exists($key,$options))
	{
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
            'description' => "Indien je een volledige identiteitscontrole uitvoert, wil je dan de naam opvragen?",
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_request_address' => [
            'key' => 'idin_request_address',
            'title' => 'bluem_idin_request_address',
            'name' => 'Adres opvragen?',
            'description' => "Indien je een volledige identiteitscontrole uitvoert, wil je dan het woonadres opvragen?",
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_request_birthdate' => [
            'key' => 'idin_request_birthdate',
            'title' => 'bluem_idin_request_birthdate',
            'name' => 'Geboortedatum opvragen?',
            'description' => "Indien je een volledige identiteitscontrole uitvoert, wil je dan de geboortedatum opvragen? Dit gegeven wordt ALTIJD opgevraagd indien je ook op de minimumleeftijd controleert",
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_request_gender' => [
            'key' => 'idin_request_gender',
            'title' => 'bluem_idin_request_gender',
            'name' => 'Geslacht opvragen?',
            'description' => "Indien je een volledige identiteitscontrole uitvoert, wil je dan het geslacht opvragen?",
            'type' => 'bool',
            'default' => '0',
        ],
        'idin_request_telephone' => [
            'key' => 'idin_request_telephone',
            'title' => 'bluem_idin_request_telephone',
            'name' => 'Adres opvragen?',
            'description' => "Indien je een volledige identiteitscontrole uitvoert, wil je dan het telefoonnummer opvragen?",
            'type' => 'bool',
            'default' => '1',
        ],
        'idin_request_email' => [
            'key' => 'idin_request_email',
            'title' => 'bluem_idin_request_email',
            'name' => 'E-mailadres opvragen?',
            'description' => "Indien je een volledige identiteitscontrole uitvoert, wil je dan het e-mailadres opvragen?",
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
        'name' => 'URL van Identificatiepagina',
        'description' => 'van pagina waar het Identificatie proces wordt weergegeven, bijvoorbeeld een accountpagina. De gebruiker komt op deze pagina terug na het proces',
        'default' => 'my-account'
    ],
    // 'IDINCategories' => [
    //     'key' => 'IDINCategories',
    //     'title' => 'bluem_IDINCategories',
    //     'name' => 'Comma separated categories in IDIN shortcode requests',
    //     'description' => 'Opties: CustomerIDRequest, NameRequest, AddressRequest, BirthDateRequest, AgeCheckRequest, GenderRequest, TelephoneRequest, EmailRequest',
    //     'default' => 'AddressRequest,BirthDateRequest'
    // ],
  
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
    ],


   

 

    



    ];
}

function bluem_woocommerce_idin_settings_section()
{
    $options = get_option('bluem_woocommerce_options');

    ?>
    <p><a id="tab_idin"></a>
    Hier kan je alle belangrijke gegevens instellen rondom iDIN (Identificatie). Lees de readme bij de plug-in voor meer informatie.</p>
    <h3>
    Automatische check:
    </h3>
    <p>
    
    <?php switch($options['idin_scenario_active']) {
        
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
       
       </p>   

       <?php if($options['idin_scenario_active'] >= 1) {
           ?>
           <p>
Deze gegevens vraag je op het moment op de volledige identiteitscontrole voor checkout:
<br/>

<code style="display:inline-block;">
<?php foreach(bluem_idin_get_categories() as $cat) {

    echo "&middot; ".str_replace("Request","",$cat)."<br>";
} 
?>
</code>
           </p>
           <?php 
       } ?>
    <!-- <p> Verander de instellingen hieronder</p> -->


    <!-- <p>
        <select class="form-control" id="bluem_woocommerce_settings_IDINShortcodeOnlyAfterLogin" name="bluem_woocommerce_options[IDINShortcodeOnlyAfterLogin]">
                            <option value="0">Voor iedereen</option>
                            <option value="1">Alleen voor ingelogde bezoekers</option>
                    </select>
    
    
        <br><label style="color:ddd;" for="bluem_woocommerce_settings_IDINShortcodeOnlyAfterLogin">Indien je hier ja invult, wordt het </label>
    
    </p>
     -->
    
    
    <h3>
       Zelf op een pagina een IDIN verzoek initiëren
    </h3>
    <p>Het IDIN formulier werkt ook een shortcode, welke je kan plaatsen op een pagina, post of in een template. De shortcode is als volgt: 
    <code>[bluem_identificatieformulier]</code>. 
    </p>
    <p>
        Zodra je deze hebt geplaatst, is op deze pagina een blok zichtbaar waarin de status van de identificatieprocedure staat. Indien geen identificatie is uitgevoerd, zal er een knop verschijnen om deze te starten.
    </p>
    <p>
    Bij succesvol uitvoeren van de identificatie via Bluem, komt men terug op de pagina die hieronder wordt aangemerkt als IDINPageURL (huidige waarde: 
    <code>
    <?php
if (isset($options['IDINPageURL'])) {
    echo($options['IDINPageURL']);
}
    ?></code>).
    </p>
    <h3>
       Waar vind ik de gegevens?
    </h3>
    <p>
        Gegevens worden na een identificatie opgeslagen bij het user profile als metadata. Je kan deze velden zien als je bij een gebruiker kijkt.
    </p>
    <h3>
       Identity configureren
    </h3>
    <?php
}

function bluem_woocommerce_settings_render_IDINSuccessMessage()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_idin_option('IDINSuccessMessage'));
}

function bluem_woocommerce_settings_render_IDINErrorMessage()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_idin_option('IDINErrorMessage'));
}

function bluem_woocommerce_settings_render_IDINPageURL()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_idin_option('IDINPageURL'));
}

function bluem_woocommerce_settings_render_IDINCategories()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_idin_option('IDINCategories'));
}

function bluem_woocommerce_settings_render_IDINBrandID()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_idin_option('IDINBrandID'));
}


function bluem_woocommerce_settings_render_IDINShortcodeOnlyAfterLogin()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_idin_option('IDINShortcodeOnlyAfterLogin'));
}

function bluem_woocommerce_settings_render_IDINDescription()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_idin_option('IDINDescription'));
}

function bluem_woocommerce_settings_render_idin_scenario_active()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_idin_option('idin_scenario_active'));
}

function bluem_woocommerce_settings_render_idin_check_age_minimum_age()
{
    bluem_woocommerce_settings_render_input(bluem_woocommerce_get_idin_option('idin_check_age_minimum_age'));
}


function bluem_woocommerce_settings_render_idin_request_address() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_address')
    );
}
function bluem_woocommerce_settings_render_idin_request_birthdate() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_birthdate')
    );
}
function bluem_woocommerce_settings_render_idin_request_gender() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_gender')
    );
}
function bluem_woocommerce_settings_render_idin_request_telephone() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_telephone')
    );
}
function bluem_woocommerce_settings_render_idin_request_email() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_email')
    );
}
function bluem_woocommerce_settings_render_idin_request_name() {
    bluem_woocommerce_settings_render_input(
        bluem_woocommerce_get_idin_option('idin_request_name')
    );
}




function bluem_idin_get_categories(int $preset_scenario = null) {
    
    $catListObject = new BluemIdentityCategoryList();
    $options = get_option('bluem_woocommerce_options');
    
    // if you want to infer the scenario from the settings and not override it.
    if(is_null($preset_scenario)) {

        if(isset($options['idin_scenario_active']) && $options['idin_scenario_active']!=="") {
            
            $scenario = (int) $options['idin_scenario_active'];
        } else {
            $scenario = 0;
        }
    } else {
        $scenario = $preset_scenario;
    }


    // always ask for this
    $catListObject->addCat("CustomerIDRequest");
    
    // '0' => 'Voer geen identiteitscheck uit voor de checkout procedure', dus we overriden hier geen cats
    // then we don't have to do anything else here.
    
    // '1' => 'Check op de minimumleeftijd door middel van een AgeCheckRequest',
    if($scenario == 1) {
        $catListObject->addCat("AgeCheckRequest");
        // return prematurely because we don't even consider the rest of the stuffs.
        return $catListObject->getCats();

        
    // '2' => 'Voer een volledige identiteitscontrole uit en sla dit op, maar blokkeer de checkout NIET indien minimumleeftijd niet bereikt is',
    // '3' => 'Voer een volledige identiteitscontrole uit, sla dit op EN  blokkeer de checkout WEL indien minimumleeftijd niet bereikt is',
    } elseif($scenario == 2 || $scenario == 3) {
        
        if($scenario == 3) {
            // deze moet verplicht mee
            $catListObject->addCat("BirthDateRequest");
        }
    } 
    if(isset($options['idin_request_name']) &&  $options['idin_request_name'] == "1") {
        $catListObject->addCat("NameRequest");
    }   
    if(isset($options['idin_request_address']) &&  $options['idin_request_address'] == "1") {
        $catListObject->addCat("AddressRequest");
    }
    if(isset($options['idin_request_address']) &&  $options['idin_request_address'] == "1") {
        $catListObject->addCat("AddressRequest");
    }
    if(isset($options['idin_request_birthdate']) &&  $options['idin_request_birthdate'] == "1") {
        $catListObject->addCat("BirthDateRequest");
    }
    if(isset($options['idin_request_gender']) &&  $options['idin_request_gender'] == "1") {
        $catListObject->addCat("GenderRequest");
    }
    if(isset($options['idin_request_telephone']) &&  $options['idin_request_telephone'] == "1") {
        $catListObject->addCat("TelephoneRequest");
    }
    if(isset($options['idin_request_email']) &&  $options['idin_request_email'] == "1") {
        $catListObject->addCat("EmailRequest");
    }
    
    return $catListObject->getCats();
    //explode(",", str_replace(" ", "", $bluem_config->IDINCategories));
}
