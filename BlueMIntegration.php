<?php //BlueMIntegration.php
if(!defined("BLUEM_ENVIRONMENT_PRODUCTION")) define("BLUEM_ENVIRONMENT_PRODUCTION","prod");
if(!defined("BLUEM_ENVIRONMENT_TESTING")) define("BLUEM_ENVIRONMENT_TESTING","test");
if(!defined("BLUEM_ENVIRONMENT_ACCEPTANCE")) define("BLUEM_ENVIRONMENT_ACCEPTANCE","acc");

require 'vendor/autoload.php';

require_once 'EMandateRequest.php';
require_once 'EMandateResponse.php';

use Carbon\Carbon;
use Carbon\CarbonInterval;

/**
 * BlueM Integration main class
 */
class BlueMIntegration 
{
	private static $verbose = true;
	private $accessToken;
	private $configuration;
	
	public $environment;

	/**
	 * Constructs a new instance.
	 */
	function __construct($configuration=null)
	{
		if(is_null($configuration))
		{

			$this->configuration = $this->_getDefaultConfiguration();
		} else {

			$this->configuration = $configuration;

			if($this->configuration->environment === BLUEM_ENVIRONMENT_PRODUCTION)
			{
				$this->configuration->accessToken = $configuration->production_accessToken;
			} elseif($this->configuration->environment === BLUEM_ENVIRONMENT_TESTING) {
				$this->configuration->accessToken = $configuration->test_accessToken;
			} 
			// var_dump($this->configuration->accessToken);
			// die();

		}	
		$this->environment = $this->configuration->environment;

		$this->configuration->merchantSubID = "0";			// bank uitgifte (default 0)
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
		
		if($this->environment === BLUEM_ENVIRONMENT_PRODUCTION)
		{
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
	public function RequestTransactionStatus($mandateID,$entranceCode)
	{
		$r = new EMandateStatusRequest($this->configuration,
			$mandateID,
			$entranceCode,
			(
				$this->configuration->environment==BLUEM_ENVIRONMENT_TESTING && 
				isset($this->configuration->expected_return)?
				$this->configuration->expected_return : ""

			)
		);
		
		$response = $this->PerformRequest($r);
		return $response;	
	}

	/**
	 * Creates a new test transaction and in case of success, return the link to redirect to to get to the BlueM eMandate environment.
	 * @param int $customer_id The Customer ID
	 * @param int $order_id    The Order ID
	 */
	public function CreateNewTransaction($customer_id,$order_id) : EMandateResponse
	{

		if(is_null($customer_id)) {
			throw new Exception("Customer ID Not set", 1);
			
		}
		if(is_null($order_id))
		{
			throw new Exception("Order ID Not set", 1);
		}

		$r = new EMandateTransactionRequest(
			$this->configuration,
			$customer_id,
			$order_id,
			$this->CreateMandateID($order_id,$customer_id),
			(
				$this->configuration->environment==BLUEM_ENVIRONMENT_TESTING && 
				isset($this->configuration->expected_return)?
				$this->configuration->expected_return : ""
			)
		);
		// var_dump($r);

		return $this->PerformRequest($r);	
		// header("Location: {$response->EMandateTransactionResponse->TransactionURL}");	
	}


	/**
	 * Generate an entrance code based on the current date and time.
	 */
	public function CreateEntranceCode() : String
	{
		return Carbon::now()->format("YmdHis")."000";
	}

	/**
	 * Create a mandate ID in the required structure, based on the order ID, customer ID and the current timestamp.
	 * @param [type] $order_id    [description]
	 * @param [type] $customer_id [description]
	 */
	public function CreateMandateID($order_id,$customer_id) : String
	{
		return substr($customer_id.Carbon::now()->format('Ymd').$order_id, 0,35);
	}

	/**
	 * Perform a request to the BlueM API given a request object and return its response
	 * @param EMandateRequest $transaction_request The Request Object
	 */
	public function PerformRequest(EMandateRequest $transaction_request) : ?EMandateResponse
	{

		$now = Carbon::now();
		// $now->tz = new DateTimeZone('Europe/London');
		
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
			if(!$response->Status()) {

				?>
				<div>
					<?php
					echo "Error: ".($response->Error()->ErrorMessage);?>
		    	</div>
				<?php
				exit;
			} else {
				return $response;
			}
		  }
		  else {
		  	?>
					<div>
						
					
					  	<?php
		    echo 'Unexpected HTTP status: ' . 
		    $response->getStatus() . ' ' .
		    $response->getReasonPhrase();
		    if($this->configuration->environment === BLUEM_ENVIRONMENT_TESTING)
		    {

			echo "<HR>";
			var_dump($response);
		    }
			?>

				</div>
				<?php
				exit;
				
			return null;
		  }
		}
		catch(HTTP_Request2_Exception $e) {
		  echo 'Error: ' . $e->getMessage();
		  return null;
		}
	}


	public function GetMaximumAmountFromTransactionResponse($response)
	{
		
		$xml_string= $response->EMandateStatusUpdate->EMandateStatus->OriginalReport;
		$xml_string = "<?xml version=\"1.0\"?>".str_replace('awvsp12:','',substr($xml_string,8,strlen($xml_string)-10)); 

	// $xml_string = substr($xml_string,-2); 
	// var_dump($xml_string);
	// echo "<hr>";
		$xml_array = new SimpleXMLElement($xml_string);
	// var_dump($xml_array);
	// echo $xml_array->asXML();
		if(isset($xml_array->MndtAccptncRpt->UndrlygAccptncDtls->OrgnlMndt->OrgnlMndt->MaxAmt)) {
			$maxAmountObj = $xml_array->MndtAccptncRpt->UndrlygAccptncDtls->OrgnlMndt->OrgnlMndt->MaxAmt;

			
			$maxAmount = new Stdclass;
			$maxAmount->amount = (float)($maxAmountObj."");
			$maxAmount->currency = $maxAmountObj->attributes()['Ccy']."";
			return $maxAmount;

		} else {
			return (object)['amount'=>(float)0.0,'currency'=>'EUR'];
		}

	}
	
}
