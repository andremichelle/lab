<?php

/**
 * the RawAMF class reads posted AMF data from flash,
 * based on the executed action (save or load) it sends
 * back the loaded AMF data or an status string.
 * 
 * @see RawAMF.php
 * @author patrick müller aka elias
 */

class RawAMF
{
	function &factory($driver)
	{
		$inc = include_once './RawAMF_'.$driver.'.php';

		if ( ! $inc )
			die('driver does not exists'.__FILE__.'::'.__LINE__);

		if ( ! class_exists('RawAMF_'.$driver) )
			die('class does not exists or has wrong name'.__FILE__.'::'.__LINE__);

		$class = 'RawAMF_'.$driver;

		return new $class();
	}

	var $raw;
	var $out;
	var $pnt;

	var $actions = array('save', 'load');
	var $dir     = array();

	var $delimiter = '->';

	var $base_dir;
	
	function RawAMF()
	{
		$this->base_dir = dirname(__FILE__);
		$this->args     = array();
		$this->pnt      = 0;
	}

	function run()
	{
		$this->pnt = 7;
		$this->raw = $raw = $GLOBALS['HTTP_RAW_POST_DATA'];

		$request_len = ord($raw[$this->pnt++]);
		$request     = utf8_decode(substr($this->raw, $this->pnt, $request_len));
		list($action, $path)     = explode($this->delimiter, $request);

		$dir    = dirname($path);
		$file   = basename($path);

//		echo "action: $action\n";
//		echo "file: $file\n";
//		echo "dir: $dir\n";
//		echo "request_len: $request_len\n";
//		echo "request: $request";

		$this->pnt += $request_len + 1;

		if ( ! in_array($action, $this->actions) && ! method_exists($this, $action))
		{
			$this->buildMessage('/1/onStatus', 'Only save and load actions are available.');
			$this->flush();
		}
		if ( ! in_array($dir, $this->dir))
		{
			$this->buildMessage('/1/onStatus', "Read/Write access to $dir is not permitted.");
			$this->flush();
		}

		$respond_len = ord($this->raw[$this->pnt++]);
		$respond     = utf8_decode(substr($this->raw, $this->pnt, $respond_len));

		if ($action == 'load')
		{
			$ret = $this->load($this->base_dir.'/'.$dir.'/'.$file);
			$this->buildMessage($respond.'/onResult', $ret, true);
			$this->flush();
		}
		else if ($action == 'save')
		{
			$this->pnt += $respond_len + 9;

			$arg  = substr($raw, $this->pnt++);
			$ret  = $this->save($this->base_dir.'/'.$dir.'/'.$file, $arg);

			$this->buildMessage($respond.'/onResult', $ret);
			$this->flush();
		}
		
	}

	function buildMessage($respond_handler, $return, $isBin=false )
	{
		//header, body, class path
		$out  = pack('xxxxnn', 0x01, strlen($respond_handler));
		$out .= utf8_encode($respond_handler);

		//respond
		$out .= pack('n', strlen('null'));
		$out .= utf8_encode('null');

		//body length
		$out .= pack('N', 0xFFFFFFFF);
		
		if ($isBin == false)
		{
			//type
			$out .= pack('h', 0x02); //2 = string

			//return value
			$out .= pack('n', strlen($return));
			$out .= utf8_encode($return);
		}
		else
		{
			$out .= $return;
		}
		
		$this->out = $out;
	}

	function flush()
	{
		if (isset($GLOBALS['debug']) && $GLOBALS['debug'])
			$this->dumpDebugData();

		header("Content-type: application/x-amf");
		echo $this->out;
		exit();
	}

	function dumpDebugData()
	{
		$str = ob_get_clean();
		$h  = fopen('./debug.amf', 'w');
		fwrite($h, "--out--\n\n{$this->out}\n\n--IN--\n\n{$this->raw}\n\n--$str");
		fclose($h);
	}

	function setDelimiter($del)
	{
		$this->delimiter = $del;
	}

	function addDirectorys($arr)
	{
		$this->dir = array_merge($this->dir, $arr);
	}

	function setBaseDir($dir)
	{
		$this->base_dir = $dir;
	}

}

?>