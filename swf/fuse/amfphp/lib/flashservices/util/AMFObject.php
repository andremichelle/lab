<?php
	
// class used for building and retreiving AMF header and body information	
	
class AMFObject
{
	// the headers
	var $_headers;
	// the body objects
	var $_bodys;

	// constructor
	function AMFObject()
	{
		// init the headers and bodys arrays
		$this->_headers = array();
		$this->_bodys = array();
	}
	
	// adds a header to our object
	// requires three arguments key, required, and value
	function addHeader($k, $r, $v)
	{
		$header = array();
		$header["key"] = $k;
		$header["required"] = $r;
		$header["value"] = $v;
		array_push($this->_headers, $header);
	}
	
	// returns the number of headers
	function numHeader()
	{
		return count($this->_headers);
	}
	
	function getHeaderAt($id = 0)
	{
		return $this->_headers[$id];
	}
	
	// adds a body to our bodys object
	// requires three arguments target, response, and value
	function addBody($t, $r, $v, $ty=-1)
	{
		$body = array();
		$body["target"] = $t;
		$body["response"] = $r;
		$body["value"] = $v;
		$body["type"] = $ty;
		array_push($this->_bodys, $body);
	}
	// returns the number of body elements
	function numBody()
	{
		return count($this->_bodys);
	}
	// returns the body element at a specific index
	function getBodyAt($id = 0)
	{
		return $this->_bodys[$id];
	}
}
?>