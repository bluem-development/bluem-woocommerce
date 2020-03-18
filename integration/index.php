<?php 
require 'BlueMIntegration.php';


switch ($_GET['action']) {
	case 'request':
		$bluem = new BlueMIntegration();
		$bluem->CreateNewTransaction();
		break;
	case 'callback':
		header("Location: http://daanrijpkema.com/bluem/integration/callback.php?mandateID=1234");
		break;
	default: {
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
			</ul>
		</div>
</div>
	</div>
	</div>
</body>
</html>
		<?php
		break;
	}
}

?>