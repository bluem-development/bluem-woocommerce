<?php

if (!defined('ABSPATH')) {
	exit;
}



class Bluem_Helper  
{
	public function help()
	{
		return "HALPT";
	}

	public function GetBluemCoreOptions()
	{
		return [
			'environment' => [
				'title' => 'bluem_environment',
				'name' => 'environment',
				'description' => 'Vul hier prod, test of acc in voor productie, test of acceptance omgeving.',
				'default' => 'test'
			],
			'senderID' => [
				'title' => 'bluem_senderID',
				'name' => 'senderID',
				'description' => 'Het sender ID, uitgegeven door BlueM. Begint met een S, gevolgd door een getal.',
				'default' => "S1212"
			],
			'test_accessToken' => [
				'title' => 'bluem_test_accessToken',
				'name' => 'test_accessToken',
				'description' => 'Het access token om met BlueM te kunnen communiceren, voor de test omgeving',
				'default' => 'ef552fd4012f008a6fe3000000690107003559eed42f0000'
			],
			'production_accessToken' => [
				'title' => 'bluem_production_accessToken',
				'name' => 'production_accessToken',
				'description' => 'Het access token om met BlueM te kunnen communiceren, voor de productie omgeving',
				'default' => '170033937f3000f170df000000000107f1b150019333d317'
            ],
            'expectedReturnStatus' => [
                'title' => 'bluem_expectedReturnStatus',
                'name' => 'expectedReturnStatus',
                'description' => 'Welke status wil je terug krijgen voor een TEST transaction of status request? Mogelijke waarden: none, success, cancelled, expired, failure, open, pending',
                'default' => 'success'
            ],
            'brandID' => [
                'title' => 'bluem_brandID',
                'name' => 'brandID',
                'description' => 'Wat is je BrandID? Ingesteld bij BlueM',
                'default' => 'NextDeliMandate'
            ]
		];
	}
}