<?php

class DirectoryReader
{
	function readList(&$arr, $dir, $replace=false)
	{
		if ($h = opendir($dir)) 
		{

			while (false !== ($file = readdir($h))) 
			{
				if ($file == '.' || $file == '..') 
					continue;

				if($replace)
				{
					$curDir  = "$dir/$file";
					$arr[] = str_replace(key($replace), $replace[key($replace)], $curDir);
				}
				else
				{
					$curDir = $arr[] = "$dir/$file";
				}
				if(is_dir($curDir))
				{
					DirectoryReader::readList($arr, $curDir, $replace);
				}
			}
		}
		closedir($h);
	}	
	
	function readTree(&$arr, $dir)
	{
		if ($h = opendir($dir)) 
		{
			while (false !== ($file = readdir($h))) 
			{
				if ($file == '.' || $file == '..') 
					continue;

				$curDir = "$dir/$file";

				if(is_dir($curDir))
				{
					$arr[] = array('name' => $file, 'node' => array());
					DirectoryReader::readTree($arr[count($arr)-1]['node'], $curDir);
				}
				else
				{
					$arr[] = array('name' => $file);
				}
			
			}
		}

		closedir($h);
	}		
	
	function readAssocTree(&$arr, $dir)
	{
		if ( !($h = opendir($dir)) )
			return;

		while ( false !== ($value = readdir($h)) )
		{
			if ( $value == '.' || $value == '..' )
				continue;

			if ( is_dir("$dir/$value") )
			{
				$arr[$value] = array();

				DirectoryReader::readAssocTree(&$arr[$value], "$dir/$value");
			}
			else
			{
				$arr[] = $value;
			}
		}
	}		

}

?>