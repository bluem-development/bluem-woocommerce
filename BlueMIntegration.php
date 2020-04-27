<?php //BlueMIntegration.php
if (!defined("BLUEM_ENVIRONMENT_PRODUCTION")) define("BLUEM_ENVIRONMENT_PRODUCTION", "prod");
if (!defined("BLUEM_ENVIRONMENT_TESTING")) define("BLUEM_ENVIRONMENT_TESTING", "test");
if (!defined("BLUEM_ENVIRONMENT_ACCEPTANCE")) define("BLUEM_ENVIRONMENT_ACCEPTANCE", "acc");

require 'vendor/autoload.php';

require_once 'EMandateRequest.php';
require_once 'EMandateResponse.php';

use Selective\XmlDSig\XmlSignatureValidator;

libxml_use_internal_errors(true);

use Carbon\Carbon;
// use Carbon\CarbonInterval;

/**
 * BlueM Integration main class
 */
class BlueMIntegration
{
	private static $verbose = false;
	// private $accessToken;
	private $configuration;

	public $environment;

	/**
	 * Constructs a new instance.
	 */
	function __construct($configuration = null)
	{
		if (is_null($configuration)) {

			$this->configuration = $this->_getDefaultConfiguration();
		} else {

			$this->configuration = $configuration;

			if ($this->configuration->environment === BLUEM_ENVIRONMENT_PRODUCTION) {
				$this->configuration->accessToken = $configuration->production_accessToken;
			} elseif ($this->configuration->environment === BLUEM_ENVIRONMENT_TESTING) {
				$this->configuration->accessToken = $configuration->test_accessToken;
			}
		}
		$this->environment = $this->configuration->environment;

		$this->configuration->merchantSubID = "0";
		// bank uitgifte (default 0)
	}

	/** 
	 * if no configuration is given, use default configuration for now
	 * @return [type] [description]
	 */
	private function _getDefaultConfiguration()
	{
		$configuration = new Stdclass();
		$configuration->senderID = "S1212";			// bluem uitgifte
		$configuration->merchantID = "0020009469";  	// bank uitgifte, BlueM MerchantID 0020000387

		$configuration->brandID = "NextDeliMandate"; 				// bluem uitgifte

		// parameters worden later toegevoegd achteraan deze URL
		$configuration->merchantReturnURLBase = "http://daanrijpkema.com/bluem/integration/callback.php";
		// TODO: update naar URL in nextdeli omgeving

		// test | prod | acc, gebruikt voor welke calls er worden gemaakt.
		$configuration->environment = BLUEM_ENVIRONMENT_PRODUCTION;

		if ($this->environment === BLUEM_ENVIRONMENT_PRODUCTION) {
			$configuration->accessToken = "170033937f3000f170df000000000107f1b150019333d317";
		} else {
			$configuration->accessToken = "ef552fd4012f008a6fe3000000690107003559eed42f0000";
		}
		return $configuration;
	}

	/**
	 * Request transaction status
	 * 
	 * @param [type] $mandateID [description]
	 */
	public function RequestTransactionStatus($mandateID, $entranceCode)
	{
		$r = new EMandateStatusRequest(
			$this->configuration,
			$mandateID,
			$entranceCode,
			($this->configuration->environment == BLUEM_ENVIRONMENT_TESTING &&
				isset($this->configuration->expected_return) ?
				$this->configuration->expected_return : "")
		);

		$response = $this->PerformRequest($r);

		return $response;
	}

	/**
	 * Creates a new test transaction and in case of success, return the link to redirect to to get to the BlueM eMandate environment.
	 * @param int $customer_id The Customer ID
	 * @param int $order_id    The Order ID
	 */
	public function CreateNewTransaction($customer_id, $order_id): EMandateResponse
	{

		if (is_null($customer_id)) {
			throw new Exception("Customer ID Not set", 1);
		}
		if (is_null($order_id)) {
			throw new Exception("Order ID Not set", 1);
		}

		$r = new EMandateTransactionRequest(
			$this->configuration,
			$customer_id,
			$order_id,
			$this->CreateMandateID($order_id, $customer_id),
			($this->configuration->environment == BLUEM_ENVIRONMENT_TESTING &&
				isset($this->configuration->expected_return) ?
				$this->configuration->expected_return : "")
		);

		return $this->PerformRequest($r);
	}


	/**
	 * Generate an entrance code based on the current date and time.
	 */
	public function CreateEntranceCode(): String
	{
		return Carbon::now()->format("YmdHis") . "000";
	}

	/**
	 * Create a mandate ID in the required structure, based on the order ID, customer ID and the current timestamp.
	 * @param String $order_id    The order ID
	 * @param String $customer_id The customer ID
	 */
	public function CreateMandateID(String $order_id, String $customer_id): String
	{
		return substr($customer_id . Carbon::now()->format('Ymd') . $order_id, 0, 35);
	}

	/**
	 * Perform a request to the BlueM API given a request object and return its response
	 * @param EMandateRequest $transaction_request The Request Object
	 */
	public function PerformRequest(EMandateRequest $transaction_request)
	{

		$now = Carbon::now();

		$xttrs_filename = $transaction_request->TransactionType() . "-{$this->configuration->senderID}-BSP1-" . $now->format('YmdHis') . "000.xml";

		$xttrs_date = $now->format("D, d M Y H:i:s") . " GMT";
		// conform Rfc1123 standard in GMT time

		$req = new HTTP_Request2();
		$req->setUrl($transaction_request->HttpRequestUrl());

		$req->setMethod(HTTP_Request2::METHOD_POST);

		$req->setHeader("Content-Type", "application/xml; type=" . $transaction_request->TransactionType() . "; charset=UTF-8");
		$req->setHeader('x-ttrs-date', $xttrs_date);
		$req->setHeader('x-ttrs-files-count', '1');
		$req->setHeader('x-ttrs-filename', $xttrs_filename);

		$req->setBody($transaction_request->XmlString());

		try {
			$http_response = $req->send();
			if ($http_response->getStatus() == 200) {
				// echo $http_response->getBody();
				$response = new EMandateResponse($http_response->getBody());
				if (!$response->Status()) {

					return new EMandateErrorResponse("Error: " . ($response->Error()->ErrorMessage));
					
				}
				return $response;
			} else {
				// if ($this->configuration->environment === BLUEM_ENVIRONMENT_TESTING) {	
				// 	var_dump($response);
				// }
				$error = new EMandateErrorResponse(
					'Unexpected HTTP status: ' .
					$response->getStatus() . ' ' .
					$response->getReasonPhrase()
				);
				return $error;
			}
		} catch (HTTP_Request2_Exception $e) {
			$error = new EMandateErrorResponse('Error: ' . $e->getMessage());
			return $error;
		}
	}

	/** Old version: get it from XML data */
	/*
	$xml_string = $response->EMandateStatusUpdate->EMandateStatus->OriginalReport;
		$xml_string = "<?xml version=\"1.0\"?>" . str_replace(['awvsp12:','doc:'], ['',''], substr($xml_string, 8, strlen($xml_string) - 10));

		// $xml_string = substr($xml_string,-2); 
		var_dump($xml_string);
		echo "<hr>";
		try {
			
			$xml_array = new SimpleXMLElement($xml_string);
		} catch (\Throwable $th) {
			var_dump($th);
			//throw $th;
		}

		echo "<hr>";
			var_dump($xml_array->asXML);
			echo "<hr>";
		die();
		// echo $xml_array->asXML();
		if (isset($xml_array->MndtAccptncRpt->UndrlygAccptncDtls->OrgnlMndt->OrgnlMndt->MaxAmt)) {
			$maxAmountObj = $xml_array->MndtAccptncRpt->UndrlygAccptncDtls->OrgnlMndt->OrgnlMndt->MaxAmt;
var_dump($maxAmountObj);

			$maxAmount = new Stdclass;
			$maxAmount->amount = (float) ($maxAmountObj . "");
			$maxAmount->currency = $maxAmountObj->attributes()['Ccy'] . "";
			return $maxAmount;
		} else {
			return (object) ['amount' => (float) 0.0, 'currency' => 'EUR'];
		}
	 */
	public function GetMaximumAmountFromTransactionResponse($response)
	{
		if (isset($response->EMandateStatusUpdate->AcceptanceReport->MaxAmount)) {
			
			return (object) [
				'amount' => (float) ($response->EMandateStatusUpdate->AcceptanceReport->MaxAmount.""), 
				'currency' => 'EUR'
			];

			// $maxAmount = new Stdclass;
			// $maxAmount->amount = (float) ($response->EMandateStatusUpdate->AcceptanceReport->MaxAmount."");
			// $maxAmount->currency = "EUR"; 
			// return $maxAmount;
			// var_dump($maxAmountObj);
			// $maxAmount->amount = (float) ($maxAmountObj . "");
		} 
		
		return (object) ['amount' => (float) 0.0, 'currency' => 'EUR'];
		
	}




	/**
	 * Webhook for BlueM Mandate signature verification procedure
	 * @return [type] [description]
	 */
	public function Webhook()
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
		if ($postData === "") {
			http_response_code(200);
			exit();
		}

		// check content type; it has to be: "Content-type", "text/xml; charset=UTF-8"


		// Parsing XML data from POST body
		try {
			$xml_input = new SimpleXMLElement($postData);
		} catch (Exception $e) {
			http_response_code(400); 		// could not parse XML
			exit();
		}

		// check if signature is valid in postdata
		if (!$this->validateWebhookSignature($postData)) {
			http_response_code(400);
			// echo 'The XML signature is not valid.';
			// echo PHP_EOL;
			exit;
		}

		// valid!
		// echo $postData;
		// echo "<hr>Input";
		// var_dump($xml_input);
		// die();
		if (!isset($xml_input->EMandateInterface->EMandateStatusUpdate)) {
			http_response_code(400);
			exit;
		}

		$status_update = $xml_input->EMandateInterface->EMandateStatusUpdate;
		return $status_update;
	}


	public function validateWebhookSignature($xml_input)
	{
		$temp_file = tmpfile();
		fwrite($temp_file, $xml_input);
		$temp_file_path = stream_get_meta_data($temp_file)['uri'];

		$signatureValidator = new XmlSignatureValidator();

		// @todo Check if keyfile has to be chosen according to env
		// if ($this->configuration->environment === BLUEM_ENVIRONMENT_TESTING) {
		// $public_key_file = "webhook.bluem.nl_pub_cert_test.crt";
		// } else {
		// $public_key_file = "webhook.bluem.nl_pub_key_production.crt";
		// }

		$public_key_file = "bluem_nl.crt";
		$public_key_file_path = ABSPATH . "wp-content/plugins/bluem-woocommerce/keys/" . $public_key_file;

		try {
			$signatureValidator->loadPublicKeyFile($public_key_file_path);
		} catch (\Throwable $th) {
			return false;
			// echo "Fout: " . $th->getMessage();
			// exit;
		}

		$isValid = $signatureValidator->verifyXmlFile($temp_file_path);
		fclose($temp_file);

		if ($isValid) {
			return true;
		}
		return false;
	}
}
