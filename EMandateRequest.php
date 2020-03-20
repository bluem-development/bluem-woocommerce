<?php 

use Carbon\Carbon;
use Carbon\CarbonInterval;

/**
 * 	EMandateRequest
 */
class EMandateRequest
{
	public $type_identifier;

	protected $senderID;
	protected $entranceCode;
	protected $merchantID;
	protected $merchantSubID;
	protected $createDateTime;

	protected $mandateID;

	function __construct($config,$expected_return="")
	{
		$this->environment = $config->environment;
		
		$this->senderID = $config->senderID;
		
		$this->merchantID = $config->merchantID;

		// override when using test env, according to documentation
		if($this->environment=="test") {
			$this->merchantID = "0020000387"; 
		}

		$this->merchantSubID = $config->merchantSubID;

		$this->accessToken = $config->accessToken;

		$this->createDateTime = Carbon::now()->toDateTimeLocalString().".000Z";

		// uniek in de tijd voor emandate; string; niet zichtbaar voor klant; 
		// uniek kenmerk van incassant voor deze transactie
		// structuur: prefix voor testing + klantnummer + huidige timestamp tot op de seconde
		$this->entranceCode = $this->entranceCode($expected_return);

	}
	
	public function XmlString()
	{
		return "";
	}
	public function Xml()
	{
		return new SimpleXMLElement($this->XmlString());
	}


	
	/**
	 * Prints a request, for testing purposes
	 *
	 * @param      EMandateTransactionRequest  $r      The TransactionRequest Object
	 */
	public function Print() 
	{
		header('Content-Type: text/xml; charset=UTF-8');
		print($this->XmlString());
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
	public function HttpRequestURL() : String
	{
		// var_dump($this);
		switch ($this->environment) {
			case BLUEM_ENVIRONMENT_TESTING:
			{
				$request_url = "https://test.viamijnbank.net/mr/";
				break;
			}
			case BLUEM_ENVIRONMENT_ACCEPTANCE:
			{
				$request_url = "https://acc.viamijnbank.net/mr/";
				break;
			}
			case BLUEM_ENVIRONMENT_PRODUCTION:
			{
				$request_url = "https://viamijnbank.net/mr/";
				break;
			}
			default:
				// var_dump($this->environment);
				throw new Exception("Invalid environment setting", 1);
				break;
		}

		switch ($this->type_identifier) {
			case 'createTransaction':
			{
				$request_url .= "createTransactionWithToken";
				break;
			}
			case 'requestStatus':
			{
				$request_url .= "requestTransactionStatusWithToken";
				break;
			}
			default:
				throw new Exception("Invalid call called for", 1);
				break;
		}
		$request_url.= "?token={$this->accessToken}";
		return $request_url;
	}

	public function TransactionType() : String
	{
		switch ($this->type_identifier) {
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
	// test entranceCode substrings voor bepaalde types return responses
	private function entranceCode($expected_return)
	{
		$entranceCode = "";
		// only allow this in testing mode
		if($this->environment === BLUEM_ENVIRONMENT_TESTING) {

			switch ($expected_return) {
				case 'none':
				{
					$entranceCode = "";
				}
				case 'success':
				{
					$entranceCode = "HIO100OIH";
				}
				case 'cancelled':
				{
					$entranceCode = "HIO200OIH";
				}
				case 'expired':
				{
					$entranceCode = "HIO300OIH";
				}
				case 'failure':
				{
					$entranceCode = "HIO500OIH";
				}
				case 'open':
				{
					$entranceCode = "HIO400OIH";
				}
				case 'pending':
				{
					$entranceCode = "HIO600OIH";
				}
				default: {
					$entranceCode = "";
				}
			}
		}
		$entranceCode .= Carbon::now()->format('YmdHisu');
		return $entranceCode;
	}
}


/**
 * 	EMandateStatusRequest
 */
class EMandateStatusRequest extends EMandateRequest
{
	
	function __construct($config,$mandateID,$expected_return="")
	{
		parent::__construct($config,$expected_return);
		$this->type_identifier = "requestStatus";
		
		$this->mandateID = $mandateID;
	}

	public function XmlString()
	{
		return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<EMandateInterface xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" type="StatusRequest"
    mode="direct" senderID="'.$this->senderID.'" version="1.0" createDateTime="'.$this->createDateTime.'"
    messageCount="1">
    <EMandateStatusRequest entranceCode="'.$this->entranceCode.'">
        <MandateID>'.$this->mandateID.'</MandateID>
    </EMandateStatusRequest>
</EMandateInterface>';
	}
	
}

/**
 * TransactionRequest
 */
class EMandateTransactionRequest extends EMandateRequest
{
	
	
	private $localInstrumentCode;
	private $merchantReturnURLBase;
	private $merchantReturnURL;
	private $sequenceType;
	private $eMandateReason;
	private $debtorReference;
	private $purchaseID;
	private $sendOption;
	
	function __construct($config, Int $customer_id, String $order_id, String $expected_return="none")
	{
		
		parent::__construct($config,$expected_return);
		
		$this->type_identifier = "createTransaction";
		
		$this->merchantReturnURLBase = $config->merchantReturnURLBase;
		

		$now = Carbon::now();
		
		$this->localInstrumentCode = "B2B"; // CORE | B2B ,  conform gegeven standaard

		
		$this->mandateID = substr($customer_id.$now->format('Ymd').$order_id, 0,35);
		// BlueM MandateID example "308201711021106036540002";  // 35 max, no space! 

		// https uniek returnurl voor klant
		$this->merchantReturnURL = $this->merchantReturnURLBase."?mandateID={$this->mandateID}"; 
		$this->sequenceType = "RCUR";
		
		// reden van de machtiging; configurabel per partij
		$this->eMandateReason = "Incasso abonnement"; 
		
		// TODO: Deze gegevens variabel maken via input parameters
		// Klantreferentie bijv naam of nummer
		$this->debtorReference = $customer_id; // KLANTNUMMER
		
		// inkoop/contract/order/klantnummer
		$this->purchaseID = "NextDeli-{$this->debtorReference}-{$order_id}";  // INKOOPNUMMER
		/* PurchaseID is verplichtveld van de banken. Dit vertalen het naar de klant als ‘inkoopnummer’ of ‘ordernummer’ (afh. Bank). Wij presenteren het niet op de checkout, omdat wij zien dat veel partijen echt niet weten wat ze er in moeten zetten. Wij adviseren dan altijd klantnummer. En dat doet dan ook veel partijen */

	
	}
	public function XmlString()
	{
		$raw = '<?xml version="1.0"?>
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
sendOption="none">
<MandateID>'.$this->mandateID.'</MandateID>
<MerchantReturnURL automaticRedirect="1">'.$this->merchantReturnURL.'</MerchantReturnURL>
<SequenceType>'.$this->sequenceType.'</SequenceType>
<EMandateReason>'.$this->eMandateReason.'</EMandateReason>
<DebtorReference>'.$this->debtorReference.'</DebtorReference>
<PurchaseID>'.$this->purchaseID.'</PurchaseID>
</EMandateTransactionRequest>
</EMandateInterface>';
		return $raw;
	}
	

	
}
