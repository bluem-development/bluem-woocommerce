<?php 
require '../vendor/autoload.php';

use Carbon\Carbon;
use Carbon\CarbonInterval;




/**
 * TransactionRequest
 */
class EMandateTransactionRequest
{
	private $senderID;
	private $merchantID;
	private $merchantSubID;
	
	private $entranceCode;
	private $localInstrumentCode;
	private $createDateTime;
	private $mandateID;
	private $merchantReturnURLBase;
	private $merchantReturnURL;
	private $sequenceType;
	private $eMandateReason;
	private $debtorReference;
	private $purchaseID;
	private $sendOption;
	
	function __construct($config, String $expected_return="none")
	{
		$this->senderID = $config->senderID;
		$this->merchantID = $config->merchantID;
		$this->merchantSubID = $config->merchantSubID;
		$this->merchantReturnURLBase = $config->merchantReturnURLBase;
		
		// test entranceCode substrings voor bepaalde types return responses
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
		
		$this->localInstrumentCode = "B2B"; // CORE | B2B

		$this->createDateTime = $now->toDateTimeLocalString().".000Z"; // conform gegeven standaard

		// 35 max, no space!
		$this->mandateID = "308201711021106036540002"; 

		// https uniek returnurl voor klant
		$this->merchantReturnURL = $this->merchantReturnURLBase."?mandateID={$this->mandateID}"; 
		$this->sequenceType = "RCUR";
		
		// reden van de machtiging; configurabel per partij
		$this->eMandateReason = "Incasso abonnement"; 
		
		// TODO: Deze gegevens variabel maken via input parameters
		// Klantreferentie bijv naam of nummer
		$this->debtorReference = "2525"; 
		// inkoop/contract/order/klantnummer
		$this->purchaseID = "Contract {$this->debtorReference}"; 

		// uniek in de tijd voor emandate; string; niet zichtbaar voor klant; uniek kenmerk van incassant voor deze transactie
		$this->entranceCode = $prefix.$this->debtorReference.$now->format('YmdHis');


		// als sendoption ='email' dan ook minimaal emailadres meegeven. Voor nu niet verder aan de orde
		$this->sendOption = "none"; 

	
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
sendOption="'.$this->sendOption.'">
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
	public function Xml()
	{
		$xml = new SimpleXMLElement($this->XmlString());
		return $xml;
	}

}
