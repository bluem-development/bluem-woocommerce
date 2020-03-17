<?php 
require '../vendor/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonInterval;



class BlueMIntegration 
{
	private static $verbose = true;

	private $senderID;
	private $merchantID;
	private $merchantSubID;
	private $brandId;
	private $accessToken;

	public $environment;
	

	function __construct()
	{
		$this->senderID = "S1212";			// bluem uitgifte
		$this->merchantID = "0020000387";  	// bank uitgifte
		$this->merchantSubID = "0";			// bank uitgifte (99,99% van gevallen is er maar 1 subID en die is dan default 0)
		$this->brandId = ""; 				// bluem uitgifte

		$this->accessToken = "ef552fd4012f008a6fe3000000690107003559eed42f0000";

		$this->environment = "test"; // test | prod | acc
	}

	// public function CreateRequest()
	// {
	// 	$r = new EMandateTransactionRequest();
	// }

	public function PrintRequest()
	{
		$r = new EMandateTransactionRequest($this->senderID,$this->merchantID,$this->merchantSubID,$this->brandId);
		header('Content-Type: text/xml');
		print($r->Xml());
	}

	public function getBaseRequestUrl($call)
	{
		//'https://test.viamijnbank.net/pr/createTransactionWithToken?token=ef552fd4012f008a6fe3000000690107003559eed42f0000');

		switch ($this->environment) {
			case 'test':
			{
				$request_url = "https://test.viamijnbank.net/pr/";
				break;
			}
			case 'acc':
			{
				$request_url = "https://acc.viamijnbank.net/pr/";
				break;
			}
			case 'prod':
			{
				$request_url = "https://viamijnbank.net/pr/";
				break;
			}
			default:
				throw new Exception("Invalid environment setting", 1);
				break;
		}
		// $request_url = $pre;
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

	public function post()
	{
		$r = new EMandateTransactionRequest($this->senderID,$this->merchantID,$this->merchantSubID,$this->brandId);

		$request_url = $this->getBaseRequestUrl("createTransaction"); // 
		

		if(self::$verbose) {
			echo $request_url."<br>";
		}
		$transaction_type = "TRX"; // or PTX, ITX, INX, SRX, PSX, ISX)
		
		$now = Carbon::now();
		
		$xttrs_filename = "{$transaction_type}-{$this->senderID}-BSP1-".$now->format('YmdHis')."000.xml";
	
	

		$request = new HTTP_Request2();
		$request->setUrl($request_url);

		$request->setMethod(HTTP_Request2::METHOD_POST);
		$request->setConfig(array(
		  'follow_redirects' => TRUE
		));
		$request->setHeader(array(
		  'Content-Type' => 'application/xml',
		  'type' => $transaction_type,
		  'charset' => 'utf8',
		  'x-ttrs-date' => $now->toRfc7231String(),
		  'x-ttrs-files-count' => '1',
		  'x-ttrs-filename' => $xttrs_filename
		));

		$request->setBody($r->xml());
		
		try {
		  $response = $request->send();
		  if ($response->getStatus() == 200) {
		  			if(self::$verbose) {

		    echo $response->getBody();

		}
		  }
		  else {
		    echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
		    $response->getReasonPhrase();
		    echo "<hr>".($response->getBody());
		  }
		}
		catch(HTTP_Request2_Exception $e) {
		  echo 'Error: ' . $e->getMessage();
		}	
	
	}

}




/**
 * TransactionRequest
 */
class TransactionRequest
{
	protected $senderID;
	protected $merchantID;
	protected $merchantSubID;
	
	function __construct($senderID, $merchantID, $merchantSubID, $brandId)
	{
		$this->senderID = $senderID; 
		$this->merchantID = $merchantID; 
		$this->merchantSubID = $merchantSubID; 
		$this->brandId = $brandId; 
		
	}
}


/**
 * TransactionRequest
 */
class EMandateTransactionRequest extends TransactionRequest
{
	private $entranceCode;
	private $localInstrumentCode;
	private $createDateTime;
	private $mandateID;
	private $merchantReturnURL;
	private $sequenceType;
	private $eMandateReason;
	private $debtorReference;
	private $purchaseID;
	private $sendOption;
	
	function __construct($senderID, $merchantID, $merchantSubID, $brandId)
	{
		parent::__construct($senderID, $merchantID, $merchantSubID, $brandId);


		$this->entranceCode = "1811D20171102111707"; // uniek in de tijd voor emandate; string; niet zichtbaar voor klant; uniek kenmerk van incassant voor deze transactie

		// TODO implementeer test entranceCode substrings voor bepaalde types return responses

		$this->localInstrumentCode = "CORE"; // CORE | B2B

		$this->createDateTime = Carbon::now()->toISOString();//"2017-11-02T11:17:09.000Z"; 

		$this->mandateID = "308201711021106036540002"; // 35 max, no space!
		$this->merchantReturnURL = "https://daanrijpkema.com/bluem/?xxxxx"; // https uniek returnurl voor klant
		$this->sequenceType = "RCUR";
		$this->eMandateReason = "Incasso abonnement"; // reden van de machtiging; configurabel per partij
		$this->debtorReference = "2525"; // Klantreferentie bijv naam of nummer
		$this->purchaseID = "Contract {$this->debtorReference}"; // inkoop/contract/order/klantnummer

		$this->sendOption = "none"; // als sendoption ='email' dan ook minimaal emailadres meegeven
	
	}
	public function Xml()
	{
		return "<?xml version='1.0'?>
			<EMandateInterface xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' 
			type='TransactionRequest' 
			mode='direct' 
			senderID='{$this->senderID}' 
			version='1.0' 
			createDateTime='{$this->createDateTime}' 
			messageCount='1'>
			  <EMandateTransactionRequest entranceCode='{$this->entranceCode}' 
			  requestType='Issuing' 
			  localInstrumentCode='{$this->localInstrumentCode}' 
			  merchantID='{$this->merchantID}' 
			  merchantSubID='{$this->merchantSubID}' 
			  language='nl' 
			  sendOption='none'>
			    <MandateID>{$this->mandateID}</MandateID>
			    <MerchantReturnURL automaticRedirect='1'>{$this->merchantReturnURL}</MerchantReturnURL>
			    <SequenceType>{$this->sequenceType}</SequenceType>
			    <EMandateReason>{$this->eMandateReason}</EMandateReason>
			    <DebtorReference>{$this->debtorReference}</DebtorReference>
			    <PurchaseID>{$this->purchaseID}</PurchaseID>
			  </EMandateTransactionRequest>
			</EMandateInterface>";
	}
}

$bluem = new BlueMIntegration();

// $bluem->PrintRequest();

$bluem->post();
?>