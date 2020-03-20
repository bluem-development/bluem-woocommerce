<?php //BlueMIntegration.php
if(!defined("BLUEM_ENVIRONMENT_PRODUCTION")) define("BLUEM_ENVIRONMENT_PRODUCTION","prod");
if(!defined("BLUEM_ENVIRONMENT_TESTING")) define("BLUEM_ENVIRONMENT_TESTING","test");
if(!defined("BLUEM_ENVIRONMENT_ACCEPTANCE")) define("BLUEM_ENVIRONMENT_ACCEPTANCE","acc");


require 'vendor/autoload.php';

require_once 'EMandateRequest.php';
require_once 'EMandateResponse.php';

use Carbon\Carbon;
use Carbon\CarbonInterval;

// TODO: define environment as constants
// Report all PHP errors, for now
error_reporting(-1);

class BlueMIntegration 
{
	private static $verbose = true;

	private $accessToken;
	
	private $configuration;

	public $environment;
	
	/**
	 * Constructs a new instance.
	 */
	function __construct()
	{

		$this->configuration = new Stdclass();

		

		$this->configuration->senderID = "S1212";			// bluem uitgifte
		$this->configuration->merchantID = "0020009469";  	// bank uitgifte, BlueM MerchantID 0020000387
		$this->configuration->merchantSubID = "0";			// bank uitgifte (default 0)
		$this->configuration->brandId = ""; 				// bluem uitgifte
		
		// parameters worden later toegevoegd achteraan deze URL
		$this->configuration->merchantReturnURLBase = "http://daanrijpkema.com/bluem/integration/callback.php"; 
		// TODO: update naar URL in nextdeli omgeving
		
		// test | prod | acc, gebruikt voor welke calls er worden gemaakt.
		$this->configuration->environment = BLUEM_ENVIRONMENT_PRODUCTION; 
		$this->environment = $this->configuration->environment;

		if($this->environment === BLUEM_ENVIRONMENT_PRODUCTION)
		{
			$this->configuration->accessToken = "170033937f3000f170df000000000107f1b150019333d317";
		} elseif($this->environment === BLUEM_ENVIRONMENT_TESTING) {
			$this->configuration->accessToken = "ef552fd4012f008a6fe3000000690107003559eed42f0000";
		} else {
			throw new Exception("Environment access Token not set yet",1);
		}
	}


	public function RequestTransactionStatus($mandateID)
	{
		$r = new EMandateStatusRequest($this->configuration,$mandateID);
		
		$response = $this->PerformRequest($r);
		
		var_dump($response);
		// TODO: continue handling when a proper transaction status has been requested
		
	}

	/**
	 * Creates a new test transaction and in case of success, redirect to the BlueM eMandate environment
	 */
	public function CreateNewTransaction($customer_id,$order_id) : void
	{

		if(is_null($customer_id)) {
			throw new Exception("Customer ID Not set", 1);
			
		}
		if(is_null($order_id))
		{
			throw new Exception("Order ID Not set", 1);
		}

		$r = new EMandateTransactionRequest($this->configuration,$customer_id,$order_id,"success");
		// echo "kaas";
		// var_dump($r);
		// echo $r->HttpRequestUrl();
		// die();
		// die();
		$response = $this->PerformRequest($r);	
// var_dump($response);
// die();
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
// var_dump($req);
// die();
		try {
		  $response = $req->send();
		  if ($response->getStatus() == 200) {
	  		
			$response = new EMandateResponse($response->getBody());
			if(!$response->Status()) {
				$this->renderPageHeader();
				?>
					<div class="card">
					  <div class="card-body">
								<?php
								echo "Error: ".($response->Error()->ErrorMessage);?>
					    
					  </div>
					</div>

				<?php
				$this->renderPageFooter();
				exit;
			} else {
				return $response;
			}
		  }
		  else {
		  	$this->renderPageHeader();
				?>
					<div class="card">
					  <div class="card-body">
					  	<?php
		    echo 'Unexpected HTTP status: ' . 
		    $response->getStatus() . ' ' .
		    $response->getReasonPhrase();
			// echo "<hr>";
			// echo htmlentities($response->getBody());
			// echo "<hr>Original request body: <br><pre>".htmlentities($transaction_request->xml());
			// echo "</pre>";?>

					  </div>
					</div>

				<?php
				$this->renderPageFooter();
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
