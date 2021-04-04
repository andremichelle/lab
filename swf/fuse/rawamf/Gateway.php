<?php

require_once 'RawAMF.php';

/* uncomment for debugging */
//$debug = true;
//ob_start();

$driver = 'Flat';

$ra =& RawAMF::factory('Flat');

$ra->setBaseDir(dirname(dirname(__FILE__))."/data/tracks");

$dirs = array
(
	'/','\\'
);

$ra->addDirectorys($dirs);

$ra->run();


?>