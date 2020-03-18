<?php 

require 'BlueMIntegration.php';

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

		?>
<h2>
Thanks for your request # <?php echo $mandate_id; ?>
</h2>	

<p>Status of your request: pending..</p>
		<?php
	}
}