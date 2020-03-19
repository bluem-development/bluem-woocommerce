<?php 
require_once 'BlueMIntegrationCallback.php';

// $bluem = new BlueMIntegration();
$callback = new BlueMIntegrationCallback();
// echo "waiting..";

// 
// callback.php
// 
// TODO: 
// wait for the response and check it.
// webhook should update the status soon.
// or provide a button to refresh this page


$callback->renderCallbackPage();

