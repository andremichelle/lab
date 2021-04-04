<?php

require_once 'DirectoryReader.php';

class DirectoryReaderService
{

	function DirectoryReaderService()
	{
		$this->methodTable = array(
			"readAssocTree" => array(
				"description" => "Echos the passed argument back to Flash (no need to set the return type)",
				"access" => "remote", // available values are private, public, remote
				"arguments" => array ("data")
			),
			"readTree" => array(
				"description" => "Echos a Flash Date Object (the returnType needs setting)",
				"access" => "remote", // available values are private, public, remote
				"arguments" => array ("data"),
			),
			"readList" => array(
				"description" => "Echos a Flash XML Object (the returnType needs setting)",
				"access" => "remote", // available values are private, public, remote
				"arguments" => array ("data"),
			)
		);
	}

	function readAssocTree($dir)
	{
		if (strstr('..', $dir))
			return false;
		
		$arr = array();
		DirectoryReader::readAssocTree($arr, $GLOBALS['__base_dir'].$dir);
		
		return $arr;
	}

	function readTree($dir)
	{
		if (strstr('..', $dir))
			return false;
		
		$arr = array();
		DirectoryReader::readTree($arr, $GLOBALS['__base_dir'].$dir);
		
		return $arr;
	}

	function readList($dir)
	{
		if (strstr('..', $dir))
			return false;
		
		$arr = array();
		DirectoryReader::readList($arr, $GLOBALS['__base_dir'].$dir, array($GLOBALS['base_dir'].$dir => ''));
		
		return $arr;
	}
}
?>
