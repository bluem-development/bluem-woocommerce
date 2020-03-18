<?php //BlueMIntegration.php

require '../vendor/autoload.php';

require_once './TransactionRequest.php';

use Carbon\Carbon;
use Carbon\CarbonInterval;

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

		$this->accessToken = "ef552fd4012f008a6fe3000000690107003559eed42f0000";

		$this->configuration->senderID = "S1212";			// bluem uitgifte
		$this->configuration->merchantID = "0020000387";  	// bank uitgifte
		$this->configuration->merchantSubID = "0";			// bank uitgifte (default 0)
		$this->configuration->brandId = ""; 				// bluem uitgifte
		$this->configuration->merchantReturnURLBase = "http://daanrijpkema.com/bluem/integration/callback.php"; // parameters worden automatisch toegevoegd aan deze URL

		$this->environment = "test"; // test | prod | acc, gebruikt voor welke calls er worden gemaakt.

	}


	/**
	 * Prints a request, for texting purposes
	 *
	 * @param      EMandateTransactionRequest  $r      The TransactionRequest Object
	 */
	public function PrintRequest(EMandateTransactionRequest $r) 
	{
		header('Content-Type: text/xml');
		print($r->Xml());
	}

	/**
	 * Gets the http request url.
	 *
	 * @param      string     $call   The call identifier as a string
	 *
	 * @throws     Exception  (description)
	 *
	 * @return     string     The http request url.
	 */
	protected function _getHttpRequestUrl(String $call) : String
	{
		switch ($this->environment) {
			case 'test':
			{
				$request_url = "https://test.viamijnbank.net/mr/";
				break;
			}
			case 'acc':
			{
				$request_url = "https://acc.viamijnbank.net/mr/";
				break;
			}
			case 'prod':
			{
				$request_url = "https://viamijnbank.net/mr/";
				break;
			}
			default:
				throw new Exception("Invalid environment setting", 1);
				break;
		}

		switch ($call) {
			case 'createTransaction':
				$request_url .= "createTransactionWithToken";
				break;
			default:
				throw new Exception("Invalid call called for", 1);
				break;
		}
		$request_url.= "?token={$this->accessToken}";
		return $request_url;
	}

	protected function _getTransactionType(String $type_identifier) : String
	{
		switch ($type_identifier) {
			case 'createTransaction':  	// EMandate createTransaction (TRX) 
			{ 
				return "TRX";
			}
			case 'requestStatus':  		// EMandate requestStatus (SRX) 
			{ 
				return "SRX";
			}
			case 'createTransaction':  	// IDentity createTransaction (ITX) 
			{ 
				return "ITX";
			}
			case 'requestStatus':  		// IDentity requestStatus (ISX) 
			{ 
				return "ISX";
			}
			case 'createTransaction': 	// IBANCheck createTransaction (INX)
			{ 
				return "INX";
			}
			default:
			{
				throw new Exception("Invalid call called for",1);
				break;
			}
		}
	}

	/**
	 * Creates a new test transaction and in case of success, redirect to the BlueM eMandate environment
	 */
	public function CreateNewTransaction() : void
	{
		$r = new EMandateTransactionRequest($this->configuration,"success");
		$response = $this->PerformPaymentTransactionRequest($r);
			// var_dump($response);
			// die();
			echo "<hr><h1>Succes</h1>
			<p> Ga naar BlueM: <a href='{$response->TransactionURL}' target='_blank'>{$response->TransactionURL}</a></p>";
		
			header("Location: $response->TransactionURL");	// Automatic redirect
	}

	/**
	 * Create a new payment transaction request via HTTP and return its response
	 *
	 * @param      EMandateTransactionRequest                    $transaction_request  The transaction request
	 *
	 * @return     EMandateTransactionResponse|SimpleXMLElement  ( description_of_the_return_value )
	 */
	public function PerformPaymentTransactionRequest(EMandateTransactionRequest $transaction_request) : ?SimpleXMLElement
	{
		$type_identifier = "createTransaction";
		$request_url = $this->_getHttpRequestUrl($type_identifier); // 
		echo $request_url;
		$transaction_type = $this->_getTransactionType($type_identifier);
		
		$now = Carbon::now();
		$now->tz = new DateTimeZone('Europe/London');
		
		$xttrs_filename = "{$transaction_type}-{$this->configuration->senderID}-BSP1-".$now->format('YmdHis')."000.xml";
		
		$xttrs_date = $now->format("D, d M Y H:i:s")." GMT"; 	// conform Rfc1123 standaard in GMT tijd

		$req = new HTTP_Request2();
		$req->setUrl($request_url);

		$req->setMethod(HTTP_Request2::METHOD_POST);

     	$req->setHeader("Content-Type", "application/xml; type=TRX; charset=UTF-8");
		$req->setHeader('x-ttrs-date', $xttrs_date);
		$req->setHeader('x-ttrs-files-count', '1');
		$req->setHeader('x-ttrs-filename', $xttrs_filename);


		$req->setBody($transaction_request->XmlString());

		try {
		  $response = $req->send();
		  if ($response->getStatus() == 200) {
	  		
	  	// 		if(self::$verbose) { 
	  	// 			echo "RESPONSE";
	  	// 			echo htmlentities($response->getBody());
				// }

			$xml_response = new SimpleXMLElement($response->getBody());
			$xml_response = $xml_response->EMandateTransactionResponse;
			// var_dump($xml_response);
			// die();
			return $xml_response;
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
