<?php 
require 'BlueMIntegration.php';

$bluem = new BlueMIntegration();
$callback = new BlueMIntegrationCallback();

// 
// callback.php
// 

// TODO: 
// wait for the response and check it.
// webhook should update the status soon.
// or provide a button to refresh this page


$callback->renderCallbackPage();

