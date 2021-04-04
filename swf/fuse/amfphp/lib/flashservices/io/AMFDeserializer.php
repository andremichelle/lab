<?php
/**
 *	AMFDeserializer takes the raw amf input stream and converts it PHP objects
 * representing the data.
 */
class AMFDeserializer
{
	var $header_count; // the number of headers in the packet
	var $headers; // the content of the headers
	var $body_count; // the number of body elements
	var $body; // the content of the body
	var $amfdata; // the object to store the deserialized data
	var $inputStream; // the input stream
	
	
	/**
	 *	Constructor function
	 *
	 * @param The referenced input stream
	 */
	function AMFDeserializer(&$is)
	{
		$this->amfdata = new AMFObject();
		$this->inputStream = &$is; // save the input stream in this object
		$this->readHeader(); // read the binary header
		$this->readBody(); // read the binary body
	}
	
	/**
	 * returns the built AMFObject from the deserialization operation
	 *
	 * @returns The deserialized AMFObject
	 */
	function getAMFObject()
	{
		return $this->amfdata;
	}
	
	/**
	 *	readHeader converts that header section of the amf message into php obects.
	 * Header information typically contains meta data about the message.
	 */
	function readHeader()
	{
		$this->inputStream->readInt(); // ignore the first two bytes -- version or something
		$this->header_count = $this->inputStream->readInt(); // find the total number of header elements
		while($this->header_count--) { // loop over all of the header elements
			$name = $this->inputStream->readUTF();
			$required = $this->readBoolean(); // find the must understand flag
			$length = $this->inputStream->readLong(); // grab the length of the header element
			$type = $this->inputStream->readByte(); // grab the type of the element
			$content = $this->readData($type); // turn the element into real data
			$this->amfdata->addHeader($name, $required, $content); // save the name/value into the headers array
		}
	}
	
	/**
	 *	readBody converts the payload of the message into php objects.
	 */
	function readBody()
	{
		$this->body_count = $this->inputStream->readInt(); // find the total number of body elements
		while($this->body_count--) { // loop over all of the body elements	
			$target = $this->readString();
			$response = $this->readString(); // the response that the client understands
			$length = $this->inputStream->readLong(); // grab the length of the body element
			$type = $this->inputStream->readByte(); // grab the type of the element
			$data = $this->readData($type); // turn the argument elements into real data
			$this->amfdata->addBody($target, $response, $data); // add the body element to the body object
		}
	}
	
	
	/**
	 *	readObject reads the name/value properties of the amf message and converts them into
	 * their equivilent php representation
	 *
	 * @returns The php array with the object data
	 */
	function readObject()
	{
		$ret = array(); // init the array
		$key = $this->inputStream->readUTF(); // grab the key
		for  ($type = $this->inputStream->readByte(); $type != 9; $type = $this->inputStream->readByte()) {	
			$val = $this->readData($type); // grab the value
			$ret[$key] = $val; // save the name/value pair in the array
			$key = $this->inputStream->readUTF(); // get the next name
		}
		return $ret; // return the array
	}
	
	/**
	 * readArray turns an all numeric keyed actionscript array into a php array.
	 *
	 * @returns The php array
	 */
	function readArray()
	{
		$ret = array(); // init the array object
		$length = $this->inputStream->readLong(); // get the length of the array
		for ($i=0; $i<$length; $i++) { // loop over all of the elements in the data
			$type = $this->inputStream->readByte(); // grab the type for each element
			$ret[] = $this->readData($type); // grab each element
		}
		return $ret; // return the data
		
	}
	
	/**
	 *	readMixedArray turns an array with numeric and string indexes into a php array
	 *
	 * @returns The php array
	 */
	function readMixedArray()
	{
		$length = $this->inputStream->readLong(); // get the length property set by flash
		return $this->readObject(); // return the body of mixed array
	}
	
	/**
	 *	readCustomClass reads the amf content associated with a class instance which was registered
	 * with Object.registerClass.  In order to preserve the class name an additional property is assigned
	 * to the object "_explicitType".  This property will be overwritten if it existed within the class already.
	 *
	 * @returns The php representation of the object
	 */
	function readCustomClass()
	{
		$typeIdentifier = $this->inputStream->readUTF();
		$value = $this->readObject(); // the rest of the bytes are an object without the 0x03 header
		$value["_explicitType"] = $typeIdentifier; // save that type because we may need it if we can find a way to add debugging features
		return $value; // return the object
	}
	
	/**
	 *	readNumber reads the numeric value and converts it into a useable number
	 *
	 * @returns The number
	 */
	function readNumber()
	{
		return $this->inputStream->readDouble(); // grab the binary representation of the number	
	}
	
	/**
	 *	readBoolean reads the boolean byte and returns true only if the value of the byte is 1
	 *
	 * @returns the Boolean value
	 */
	function readBoolean()
	{
		$int = $this->inputStream->readByte(); // grab the int value of the next byte
		if ($int == 1) {
			return true; // if it's a 0x01 return true else return false
		} else {
			return false;
		}
	}
	
	/**
	 *	readString reads the string from the amf message and returns it.
	 *
	 * @returns The string
	 */
	function readString()
	{
		return $this->inputStream->readUTF();
	}

	/**
	 *	readDate reads a date from the amf message and returns the time in ms.
	 * This method is still under development.
	 *
	 * @returns The date in ms.
	 */
	function readDate()
	{
		$ms = $this->inputStream->readDouble(); // date in milliseconds from 01/01/1970
		$int = $this->inputStream->readInt(); // nasty way to get timezone
		if($int > 720) {
			$int = -(65536 - $int);
		}
		$hr = floor($int / 60);
		$min = $int % 60;
		$timezone = "GMT " . -$hr . ":" . abs($min);
		// end nastiness 
		return $ms; // is there a nice way to return entire date(milliseconds and timezone) in PHP???
	}

	/**
	 *	readXML reads the xml string from the amf message and returns it.
	 *
	 * @returns The XML string
	 */
	function readXML() //XML reading function
	{ 
		$rawXML = $this->inputStream->readLongUTF(); // reads XML
		return $rawXML;
	}
	
	/**
	 *	readFlushedSO is supposed to handle something with SO's, but I can not replicate
	 * the ability to even get this to appear.  ???
	 */
	function readFlushedSO()
	{
		return $this->inputStream->readInt();
	}
	
	/**
	 * object Button, object Textformat, object Sound, object Number, object Boolean, object String, 
	 * SharedObject unflushed, XMLNode, used XMLSocket??, NetConnection,
	 * SharedObject.data, SharedObject containing 'private' properties
	 * 
	 * the final byte seems to be the dataType -> 0D
	 */
	function readASObject()
	{
		return null;
	}
	
	/**
	 * readData is the main switch for mapping a type code to an actual
	 * implementation for deciphering it.
	 *
	 * @param The type integer
	 * @returns The php version of the data in the message block
	 */
	function readData($type)
	{	
		switch ($type) {	
			case 0: // number
				$data = $this->readNumber();
				break;
			case 1: // boolean
				$data = $this->readBoolean();
				break;
			case 2: // string
				$data = $this->readString();
				break;
			case 3: // object Object
				$data = $this->readObject();
				break;
			case 5: // null
				$data = null;
				break;
			case 6: // undefined
				$data = null;
				break;
			case 7: // flushed SharedObject containing 'public' properties
				$data = $this->readFlushedSO(); 
				break;
			case 8: // mixed array with numeric and string keys
				$data = $this->readMixedArray();
				break;
			case 10: // array
				$data = $this->readArray();
				break;
			case 11: // date
				$data = $this->readDate();
				break;
			case 13: // mainly internal AS objects
				$data = $this->readASObject();
				break;
			case 15: // XML
				$data = $this->readXML();
				break;
			case 16: // Custom Class
				$data = $this->readCustomClass();
				break;
			default: // unknown case
				print "xxx $type ";
				break;
		}
			return $data;
	}
}
?>