<?php
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
} ?>
<?php 

require_once 'BlueMIntegration.php';

libxml_use_internal_errors(true);

class BlueMIntegrationCallback extends BlueMIntegration
{
	/**
	 * Constructs a new instance.
	 */
	function __construct()
	{

		parent::__construct();
	}


	public function renderCallbackPage()
	{
		
		if(!isset($_GET['mandateID']) || is_null($_GET['mandateID'])) {
			echo "Er ging iets fout; je hebt geen mandaat ID teruggekregen. Kan je het opnieuw proberen?";
			// TODO: terug naar webshop link toevoegen
			exit;
		}

		$mandate_id = $_GET['mandateID'];

		$this->renderPageHeader();
		?>
		<div class="card">
		  <div class="card-body">
		    
<h2>
Thanks for your request # <?php echo $mandate_id; ?>
</h2>	

<p>Status of your request: pending..</p>
		  </div>
		</div>
		<?php
		$this->renderPageFooter();
	}
}