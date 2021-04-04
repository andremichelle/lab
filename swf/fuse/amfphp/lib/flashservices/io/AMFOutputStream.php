<?php
/*
	Class used to convert the php stuff into binary
*/
class AMFOutputStream
{
	// the output buffer
	var $outBuffer;
	var $byteorder;

	// constructor
	function AMFOutputStream()
	{
		// the buffer
		$this->outBuffer = "";
		// determine the multi-byte ordering of this machine
		// temporarily pack 1
		$tmp = pack("d", 1);
		// if the bytes are not reversed
		if ($tmp=="\0\0\0\0\0\0\360\77") {
			$this->byteorder = 'big-endian';
		} else if ($tmp == "\77\360\0\0\0\0\0\0") {
			$this->byteorder = 'little-endian';
		}
	}
	// write a single byte
	function writeByte($b)
	{
		// use pack with the c flag
		$this->outBuffer .= pack("c", $b);
	}	
	// write 2 bytes
	function writeInt($n)
	{
		// use pack with the n flag
		$this->outBuffer .= pack("n", $n);
	}
	// write 4 bytes
	function writeLong($l)
	{
		// use pack with the N flag
		$this->outBuffer .= pack("N", $l);
	}
	// write a string
	function writeUTF($s)
	{
		// write the string length - max 65536
		$this->writeInt(strlen($s));
		// write the string chars
		$this->outBuffer .= $s;
	}
	//write a long string
	function writeLongUTF($s)
	{
		// write the string length - max 65536
		$this->writeLong(strlen($s));
		// write the string chars
		$this->outBuffer .= $s;
	}
	// write a double
	function writeDouble($d)
	{
		// pack the bytes
		$b = pack("d", $d);
		
		if ($this->byteorder == 'big-endian') {
			$r = "";
			// reverse the bytes
			for($byte = 7 ; $byte >= 0 ; $byte--) {
				$r .= $b[$byte];
			}
			// add the bytes to the output
		} else {
			$r = $b;
		}
		$this->outBuffer .= $r;	
	}
	// send the output buffer
	function flush()
	{
		// flush typically empties the buffer
		// but this is not a persistent pipe so it's not needed really here
		// plus it's useful to be able to flush to a file and to the client simultaneously
		// with out have to create another method just to peek at the buffer contents.
		return $this->outBuffer;
	}
}
?>