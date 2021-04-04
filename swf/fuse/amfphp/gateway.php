<?php

require_once './config.php';
require_once "flashservices/app/Gateway.php";

$gateway = new Gateway();
$gateway->setBaseClassPath( APP_PATH."/lib/flashservices/services/" );
$gateway->service();

?>