<?php 
require 'BlueMIntegration.php';

if(!isset($_GET['action'])) {
?>
<!DOCTYPE html>
<html>
<head>
	<title></title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
</head>
<body class="p-5">
	<div class="container">
		
	
	<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-header">
<h4>eMandate testing</h4>
				<p>Kies een actie:</p>
			</div>
			<ul class="list-group list-group-flush">
				<a class="list-group-item" href="?action=request" target="_self">
					Create eMandate request
				</a>
				<a class="list-group-item" href="?action=callback" target="_self">
					Simulate callback
				</a>
				<li class="list-group-item">
					<div class="d-inline-block">
						Request transaction status based on MandateID
					</div>
					<form class="form-inline" action="index.php?action=statusrequest" method="post">
						<input type="hidden" name="action" value="statusrequest">
					  	<label class="sr-only" for="inlineFormInputName2">MandateID</label>
					  	<input type="text" name="mandate_id" class="form-control mb-2 mr-sm-2" id="inlineFormInputName2" placeholder="1234202003186789" value="1234202003186789" required="required"  autofocus="autofocus" />
						<button type="submit" class="btn btn-primary mb-2">Go</button>
					</form>
				</li>
			</ul>
		</div>
</div>
	</div>
	</div>
</body>
</html>
<?php	
exit;
}

		$bluem = new BlueMIntegration();
switch ($_GET['action']) {
	case 'request':
		$customer_id = 1234;
		$order_id = 6789;
		$bluem->CreateNewTransaction($customer_id,$order_id);
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