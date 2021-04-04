<?php

// ----- user settings ----- \\

$base_dir = dirname(dirname(__FILE__))."/data";

$GLOBALS['__base_dir'] = $base_dir;

define('APP_PATH', dirname(__FILE__));

$delimiter    = (stristr(getenv('OS'), 'win')) ? ';' : ':';
$include_path = '.'.$delimiter.APP_PATH.'/lib';
$include_path = str_replace('\\', '/', $include_path);

ini_set('include_path', $include_path);
error_reporting(E_ALL);
?>