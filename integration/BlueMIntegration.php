<?php //BlueMIntegration.php

require '../vendor/autoload.php';

require_once './EMandateRequest.php';
require_once './EMandateResponse.php';

use Carbon\Carbon;
use Carbon\CarbonInterval;

// TODO: define environment as constants

// Report all PHP errors, for now
error_reporting(-1);

class BlueMIntegration 
{
	private static $verbose = true;

	private $accessToken;
	public $environment;
	
	/**
	 * Constructs a new instance.
	 */
	function __construct()
	{
		$this->configuration = new Stdclass();

		$this->configuration->accessToken = "ef552fd4012f008a6fe3000000690107003559eed42f0000";

		$this->configuration->senderID = "S1212";			// bluem uitgifte
		$this->configuration->merchantID = "0020000387";  	// bank uitgifte
		$this->configuration->merchantSubID = "0";			// bank uitgifte (default 0)
		$this->configuration->brandId = ""; 				// bluem uitgifte
		
		// parameters worden later toegevoegd achteraan deze URL
		$this->configuration->merchantReturnURLBase = "http://daanrijpkema.com/bluem/integration/callback.php"; 
		// TODO: update naar URL in nextdeli omgeving
		
		$this->configuration->environment = "test"; // test | prod | acc, gebruikt voor welke calls er worden gemaakt.

	}


	public function RequestTransactionStatus($mandateID)
	{
		$r = new EMandateStatusRequest($this->configuration,$mandateID);
		
		$response = $this->PerformRequest($r);
		
		if(!$response->Status()) {
			echo "Error: ".($response->Error()->ErrorMessage);
			exit;
		} else {
			var_dump($response);

			// TODO: continue handling when a proper transaction status has been requested
		}
	}

	/**
	 * Creates a new test transaction and in case of success, redirect to the BlueM eMandate environment
	 */
	public function CreateNewTransaction($customer_id,$order_id) : void
	{
		$r = new EMandateTransactionRequest($this->configuration,$customer_id,$order_id,"success");
		$response = $this->PerformRequest($r);	

		header("Location: {$response->EMandateTransactionResponse->TransactionURL}");	
	}

	public function PerformRequest(EMandateRequest $transaction_request) : ?EMandateResponse
	{

		$now = Carbon::now();
		$now->tz = new DateTimeZone('Europe/London');
		
		$xttrs_filename = $transaction_request->TransactionType()."-{$this->configuration->senderID}-BSP1-".$now->format('YmdHis')."000.xml";
		
		$xttrs_date = $now->format("D, d M Y H:i:s")." GMT"; 	// conform Rfc1123 standaard in GMT tijd

		$req = new HTTP_Request2();
		$req->setUrl($transaction_request->HttpRequestUrl());

		$req->setMethod(HTTP_Request2::METHOD_POST);

     	$req->setHeader("Content-Type", "application/xml; type=".$transaction_request->TransactionType()."; charset=UTF-8");
		$req->setHeader('x-ttrs-date', $xttrs_date);
		$req->setHeader('x-ttrs-files-count', '1');
		$req->setHeader('x-ttrs-filename', $xttrs_filename);

		$req->setBody($transaction_request->XmlString());

		try {
		  $response = $req->send();
		  if ($response->getStatus() == 200) {
	  		
			$response = new EMandateResponse($response->getBody());
			return $response;
		  }
		  else {
		    echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
		    $response->getReasonPhrase();
			echo "<hr>";
			echo htmlentities($response->getBody());
			echo "<hr>Original request body: <br><pre>".htmlentities($transaction_request->xml());
			echo "</pre><HR>";
			return null;
		  }
		}
		catch(HTTP_Request2_Exception $e) {
		  echo 'Error: ' . $e->getMessage();
		  return null;
		}
	}
	
}
