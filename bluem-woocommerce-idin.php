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
    ]
    ];
}

function bluem_woocommerce_idin_settings_section()
{
    echo '<p>Hier kan je alle belangrijke gegevens instellen rondom iDIN (Identificatie). Lees de readme bij de plug-in voor meer informatie.</p>';
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


	
	