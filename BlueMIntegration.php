<?php //BlueMIntegration.php
if(!defined("BLUEM_ENVIRONMENT_PRODUCTION")) define("BLUEM_ENVIRONMENT_PRODUCTION","prod");
if(!defined("BLUEM_ENVIRONMENT_TESTING")) define("BLUEM_ENVIRONMENT_TESTING","test");
if(!defined("BLUEM_ENVIRONMENT_ACCEPTANCE")) define("BLUEM_ENVIRONMENT_ACCEPTANCE","acc");

require 'vendor/autoload.php';

require_once 'EMandateRequest.php';
require_once 'EMandateResponse.php';

use Carbon\Carbon;
use Carbon\CarbonInterval;


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
	 * Creates a new test transaction and in case of success, redirect to the BlueM eMandate environment
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


	public function CreateEntranceCode($order)
	{
		$now = Carbon::now();
		// $now->tz = new DateTimeZone('Europe/Amsterdam');
		return $now->format("YmdHis")."000";
	}
	public function CreateMandateID($order_id,$customer_id)
	{
		$now = Carbon::now();
		return substr($customer_id.$now->format('Ymd').$order_id, 0,35);
	}

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




	/** page rendering */

	public function renderPageHeader()
{
	?>

		<!DOCTYPE html>
<html>
<head>
	<title></title>
	<link rel="stylesheet" href="css/bootstrap.min.css">
</head>
<body style="background: url(img/bg.jpg) repeat top center; background-size:  contain;">
		<nav class="navbar navbar-expand-lg navbar-dark text-light bg-dark">
		  <a class="navbar-brand" href="index.php" target="_self">BlueM Integration</a>
		  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
		    <span class="navbar-toggler-icon"></span>
		  </button>
		
		  <div class="collapse navbar-collapse" id="navbarSupportedContent">
		    <ul class="navbar-nav ml-auto">
		      <li class="nav-item">
		      	<span class="nav-text">
		      		Environment: 
<span class="badge badge-info badge-pill">	
<?php echo $this->environment ?>
</span>
		      	</span>
		      </li>
		    </ul>
		  </div>
		</nav>
	<div class="container p-5">
	
	<div class="row">
	<div class="col-12">
	<?php
} 

 public function renderPageFooter()
{
	?>


</div>
	</div>
	</div>
</body>
</html>
	<?php
} 
	
}
