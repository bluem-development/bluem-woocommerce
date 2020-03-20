<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require '../BlueMIntegration.php';




$bluem = new BlueMIntegration();


if(!isset($_GET['action'])) {
?>
<?php $bluem->renderPageHeader(); ?>


		<div class="card">
			
				<div class="card-header">
					
				Choose an option:
				</div>
					
			<ul class="list-group list-group-flush">
				<!-- <a class="list-group-item list-group-item-action" href="?action=request" target="_self">
				</a> -->
				<li class="list-group-item">
					<div class="d-inline-block">
					Create eMandate request
						
					</div>
					<form class="form-inline" action="index.php?action=request" method="post">
						
					  	<label class="mx-2" for="CustomerIDField">CustomerID</label>
					  	<input type="text" name="CustomerID" class="form-control mb-2 mr-sm-2" id="CustomerIDField" placeholder="12342020" value="12342020" required="required"  autofocus="autofocus" />

					  	<label class="mx-2" for="OrderIDField">OrderID</label>
					  	<input type="text" name="OrderID" class="form-control mb-2 mr-sm-2" id="OrderIDField" placeholder="3186789" value="3186789" required="required"  autofocus="autofocus" />


						<button type="submit" class="btn btn-primary mb-2">Create</button>
					</form>
				</li>
				<a class="list-group-item list-group-item-action" href="?action=callback" target="_self">
					Simulate callback
				</a>
				<li class="list-group-item">
					<div class="d-inline-block">
						Request transaction status based on MandateID
					</div>
					<form class="form-inline" action="index.php?action=statusrequest" method="post">
						
					  	<label class="mx-2" for="inlineFormInputName2">MandateID</label>
					  	<input type="text" name="mandate_id" class="form-control mb-2 mr-sm-2" id="inlineFormInputName2" placeholder="1234202003186789" value="1234202003186789" required="required"  autofocus="autofocus" />
						<button type="submit" class="btn btn-primary mb-2">Request</button>
					</form>
				</li>
			</ul>
		<div class="card-footer small text-right">
			&copy; Daan Rijpkema, 2020 &middot; 
			<a href="https://github.com/DaanRijpkema/bluem-woocommerce">github.com/DaanRijpkema/bluem-woocommerce</a>
		</div>
		</div>

		<?php $bluem->renderPageFooter(); ?>


<?php	
exit;
}

		
switch ($_GET['action']) {
	case 'request':
		if(isset($_POST['CustomerID'])) {

		$CustomerID = $_POST['CustomerID'];
		} else {
			$CustomerID = 1234;
		}
		if(isset($_POST['OrderID'])) {

		$OrderID = $_POST['OrderID'];
		} else {
			$OrderID = 5678;
		}
		// var_dump($CustomerID);
		// var_dump($OrderID);
		// die();
		try {
			$bluem->CreateNewTransaction($CustomerID,$OrderID);
			
		} catch (Exception $e) {
			echo $e->getMessage();
			die();
		}


		break;
	case 'callback':
		$mandate_id = 1234;
		header("Location: http://daanrijpkema.com/bluem/integration/callback.php?mandateID={$mandate_id}");
		break;
	case 'statusrequest':
		if(isset($_POST['mandate_id'])) {
			$mandate_id = $_POST['mandate_id'];	
		} else {
			$mandate_id = "308201711021106036540002";//"1234202003186789";
			// header("Location: index.php");
			// exit;
		}
	 		$bluem->RequestTransactionStatus($mandate_id);
	default: {
		exit;
		break;
	}
}

?>