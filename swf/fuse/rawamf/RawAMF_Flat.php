<?php

/**
 * this is an RawAMF driver which writes AMF data to
 * harddisk.
 * 
 * @author patrick müller aka elias
 */

class RawAMF_Flat extends RawAMF
{

	function RawAMF_Flat()
	{
		parent::RawAMF();
	}

	function save($file, $data)
	{
		$h = fopen($file, 'w');
		fwrite($h, $data);
		fclose($h);

		return 'saved'; 
	}

	function load($file)
	{
		return implode(null, file($file));
	}

}

?>