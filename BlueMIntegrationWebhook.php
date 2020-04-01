<?php 

require 'BlueMIntegration.php';

libxml_use_internal_errors(true);

class BlueMIntegrationWebhook extends BlueMIntegration
{
	/**
	 * Constructs a new instance.
	 */
	// function __construct($configuraiton)
	// {
	// 	parent::__construct();
	// }


	public function receive()
	{

		/* Senders provide Bluem with a webhook URL. The URL will be checked for consistency and validity and will not be stored if any of the checks fails. The following checks will be performed:
		▪	URL must start with https://
		*/
		// ONLY Accept post requests
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(400);
			exit();
		}
		// An empty POST to the URL (normal HTTP request) always has to respond with HTTP 200 OK
		$postData = file_get_contents('php://input');
		if($postData=="")
		{
			http_response_code(200);
			exit();
		}

		try {
			$xml_response = new SimpleXMLElement($postData);
		} catch (Exception $e) {
			http_response_code(400); 		// could not parse XML
			exit();
		}


		// echo "<hr>Input";
		// var_dump($xml_response);
		$status_update = $xml_response->EMandateInterface->EMandateStatusUpdate;
		// echo "<hr>status_update";
		// var_dump($status_update);
		$status = $status_update->EMandateStatus;
		// echo "<hr>status";
		// var_dump($status);
		

		switch ($status->Status) {
			case 'Success':
			{	
				echo "Success request";
				break;
			}
			case 'Cancelled':
			{	
				echo "Cancelled request";
				break;
			}
			case 'Expired':
			{	
				echo "Expired request";
				break;
			}
			case 'Failure':
			{	
				echo "Failure request";
				break;
			}
			case 'Open':
			{	
				echo "Open request";
				break;
			}
			case 'Pending':
			{	
				echo "Pending request";
				break;	
			}
			default:
			{
				// echo "Unknown status";
				break;
			}
		}


		$signature = $xml_response->Signature;
		echo "<hr>signature";
		var_dump($signature);
		echo "<hr>";
$this->validateSignature($signature->SignatureValue);
		// print_r(getallheaders());
		die();
		// expected header("Content-type: text/xml; charset=UTF-8");


		// https://www.php.net/manual/ro/function.openssl-verify.php
		// https://stackoverflow.com/questions/15490753/php-validate-client-signature-using-client-public-key
	}

	public function validateSignature($signature)
	{
		$public_key_file = "bluem_nl.crt";
		$public_key_file_path = "../keys/".$public_key_file;
		if(file_exists($public_key_file_path)){
echo "File exists!";
// echo file_get_contents($public_key_file_path);
		}
		$public_key = openssl_pkey_get_public(file_get_contents($public_key_file_path));

		if(!$public_key) {
			throw new Exception("pKey not initiated properly", 500);
			exit();		
		}
$keyData = openssl_pkey_get_details($public_key); 
var_dump($keyData);
		var_dump($public_key);
		var_dump($signature);
	}
}
