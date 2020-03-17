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


		$r = new EMandateTransactionRequest($this->senderID,$this->merchantID,$this->merchantSubID,$this->brandId,"success");
		// $bluem->PrintRequest($r);
		$this->CreatePaymentTransaction($r);		
	}

	/**
	 * Print a request in XML
	 */
	public function PrintRequest($r)
	{
		
		header('Content-Type: text/xml');
		print($r->Xml());
	}

	protected function getHttpRequestUrl($call)
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

	/**
	 * Create a new payment transaction
	 */
	public function CreatePaymentTransaction(EMandateTransactionRequest $transaction_request)
	{
		$request_url = $this->getHttpRequestUrl("createTransaction"); // 
		
		if(static::$verbose) {
			echo $request_url."<br>";
		}
		
		$transaction_type = "TRX";
		// Opties:
		// EMandate createTransaction (TRX) 
		// EMandate requestStatus (SRX) 
		// IDentity createTransaction (ITX) 
		// IDentity requestStatus (ISX) 
		// IBANCheck createTransaction (INX)
		
		$now = Carbon::now();
		$now->tz = new DateTimeZone('Europe/London');
		
		$xttrs_filename = "{$transaction_type}-{$this->senderID}-BSP1-".$now->format('YmdHis')."000.xml";
		
		$xttrs_date = $now->format("D, d M Y H:i:s")." GMT"; 	// conform Rfc1123 standaard in GMT tijd

		$req = new HTTP_Request2();
		$req->setUrl($request_url);

		$req->setMethod(HTTP_Request2::METHOD_POST);
		// $req->setConfig(array(
		//   'follow_redirects' => TRUE
		// ));

     	$req->setHeader("Content-Type", "application/xml");
     	$req->setHeader("Charset", "UTF-8");

		$req->setHeader('x-ttrs-date', $xttrs_date);
		$req->setHeader('x-ttrs-files-count', '1');
		$req->setHeader('x-ttrs-filename', $xttrs_filename);
		$req->setHeader('type',$transaction_type);

// var_dump($req->getHeaders());
// die();
		// $req->setHeader(array(
		//   'Content-Type' => 'text/xml; charset=utf-8',
		//   // 'charset' => 'utf-8',
		  
		// ));

		$req->setBody($transaction_request->xml());
		
		try {
		  $response = $req->send();
		  if ($response->getStatus() == 200) {
	  		
	  			if(self::$verbose) { 
	  				echo htmlentities($response->getBody());
				}
		  }
		  else {
		    echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
		    $response->getReasonPhrase();
			echo "<hr>";
			echo htmlentities($response->getBody());
			echo "<hr>Original request body: <br><pre>".htmlentities($transaction_request->xml());
			echo "</pre><HR>";

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
	
	function __construct($senderID, $merchantID, $merchantSubID, $brandId, String $expected_return="none")
	{
		parent::__construct($senderID, $merchantID, $merchantSubID, $brandId);



		// for testing:
		switch ($expected_return) {
			case 'none':
			{
				$prefix="";
				break;
			}
			case 'success':
			{
				$prefix = "HIO100OIH";
				break;
			}
			case 'cancelled':
			{
				$prefix = "HIO200OIH";
				break;
			}
			case 'expired':
			{
				$prefix = "HIO300OIH";
				break;
			}
			case 'failure':
			{
				$prefix = "HIO500OIH";
				break;
			}
			case 'open':
			{
				$prefix = "HIO400OIH";
				break;
			}
			case 'pending':
			{
				$prefix = "HIO600OIH";
				break;
			}
			default: {
				throw new Exception("Invalid expected return value given", 1);		
				break;
			}
		}
		
		$now = Carbon::now();
		





		// TODO implementeer test entranceCode substrings voor bepaalde types return responses

		$this->localInstrumentCode = "CORE"; // CORE | B2B

		$this->createDateTime = $now->toDateTimeLocalString().".000Z";

		$this->mandateID = "308201711021106036540002"; // 35 max, no space!
		$this->merchantReturnURL = "https://daanrijpkema.com/bluem/?xxxxx"; // https uniek returnurl voor klant
		$this->sequenceType = "RCUR";
		$this->eMandateReason = "Incasso abonnement"; // reden van de machtiging; configurabel per partij
		$this->debtorReference = "2525"; // Klantreferentie bijv naam of nummer
		$this->purchaseID = "Contract {$this->debtorReference}"; // inkoop/contract/order/klantnummer


		// uniek in de tijd voor emandate; string; niet zichtbaar voor klant; uniek kenmerk van incassant voor deze transactie
		$this->entranceCode = $prefix.$this->debtorReference.$now->format('YmdHis');//"1811D20171102111707"; 


		$this->sendOption = "none"; // als sendoption ='email' dan ook minimaal emailadres meegeven

	
	}
	public function Xml()
	{
		return '<?xml version="1.0"?>
<EMandateInterface xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
type="TransactionRequest" 
mode="direct" 
senderID="'.$this->senderID.'" 
version="1.0" 
createDateTime="'.$this->createDateTime.'" 
messageCount="1">
<EMandateTransactionRequest entranceCode="'.$this->entranceCode.'" 
requestType="Issuing" 
localInstrumentCode="'.$this->localInstrumentCode.'" 
merchantID="'.$this->merchantID.'" 
merchantSubID="'.$this->merchantSubID.'" 
language="nl" 
sendOption="'.$this->sendOption.'">
<MandateID>'.$this->mandateID.'</MandateID>
<MerchantReturnURL automaticRedirect="1">'.$this->merchantReturnURL.'</MerchantReturnURL>
<SequenceType>'.$this->sequenceType.'</SequenceType>
<EMandateReason>'.$this->eMandateReason.'</EMandateReason>
<DebtorReference>'.$this->debtorReference.'</DebtorReference>
<PurchaseID>'.$this->purchaseID.'</PurchaseID>
</EMandateTransactionRequest>
</EMandateInterface>';
	}
}


$run = new BlueMIntegration();
?>