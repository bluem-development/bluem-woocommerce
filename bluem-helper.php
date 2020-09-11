<?php

if (!defined('ABSPATH')) {
	exit;
}



class Bluem_Helper  
{
	public function GetBluemCoreOptions()
	{
		return [
			'environment' => [
				'key'=> 'environment',
				'title' => 'bluem_environment',
				'name' => 'Kies de actieve modus',
				'description' => 'Vul hier welke modus je wilt gebruiken: prod, test of acc in voor productie (live), test of acceptance omgeving.',
				'type'=>'select',
				'default' => 'test',
				'options'=>
					['prod'=>"Productie (live)",'test'=>'Test']
					// acceptance eventueel later toevoegen
			],
			'senderID' => [
				'key'=> 'senderID',
				'title' => 'bluem_senderID',
				'name' => 'Bluem Sender ID',
				'description' => 'Het sender ID, uitgegeven door Bluem. Begint met een S, gevolgd door een getal.',
				'default' => ""
			],
			'brandID' => [
				'key'=> 'brandID',
				'title' => 'bluem_brandID',
				'name' => 'Bluem Brand ID',
				'description' => 'Wat is je BrandID? Gegeven door Bluem',
				'default' => ''
			],
			'test_accessToken' => [
				'key'=> 'test_accessToken',
				'title' => 'bluem_test_accessToken',
				'type'=>'password',
				'name' => 'Access Token voor Testen',
				'description' => 'Het access token om met Bluem te kunnen communiceren, voor de test omgeving',
				'default' => ''
			],
			'production_accessToken' => [
				'key'=> 'production_accessToken',
				'title' => 'bluem_production_accessToken',
				'type'=>'password',
				'name' => 'Access Token voor Productie',
				'description' => 'Het access token om met Bluem te kunnen communiceren, voor de productie omgeving',
				'default' => ''
			],
            'expectedReturnStatus' => [
				'key'=> 'expectedReturnStatus',
                'title' => 'bluem_expectedReturnStatus',
                'name' => 'Test modus verwachte return status',
                'description' => 'Welke status wil je terug krijgen voor een TEST transaction of status request? Mogelijke waarden: none, success, cancelled, expired, failure, open, pending',
				'default' => 'success',
				'type'=>'select',
				'options'=>[
					'success'=>'success', 
					'cancelled'=>'cancelled', 
					'expired'=>'expired', 
					'failure'=>'failure', 
					'open'=>'open', 
					'pending'=>'pending',
					'none'=>'none'
				]
            ]
		];
	}
}