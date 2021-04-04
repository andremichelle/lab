<?php
/**
 * AMFSerializer manages the job of translating PHP objects into
 * the actionscript equivalent via amf.  The main method of the serializer
 * is the serialize method which takes and AMFObject as it's argument
 * and builds the resulting amf body.
 */
class AMFSerializer
{
	var $data; // holder for the data
	var $out; // holder for the output stream
	var $amfout;
	
	/**
	 *AMFSerializer is the constructor function.  You must pass the
	 * method an AMFOutputStream as the single argument.
	 *
	 * @param $stream The AMFOutputStream
	 */
	function AMFSerializer(&$stream)
	{	
		$this->out = &$stream; // save
	}
	
	/**
	 *	serialize is the run method of the class.  When serialize is called
	 * the AMFObject passed in is read and converted into the amf binary
	 * representing the PHP data represented.
	 *
	 * @param $d the AMFObject to serialize
	 */
	function serialize($d)
	{
		$this->amfout = $d;
		$this->out->writeInt(0); // write the version ???
		
		$count = $this->amfout->numHeader(); // get the header count
		$this->out->writeInt($count); // write header count
		for ($i=0; $i<$count; $i++) {
			$this->writeHeader($i);
		}
		
		$count = $this->amfout->numBody();
		$this->out->writeInt($count); // write the body count
		for ($i=0; $i<$count; $i++) {
			$this->writeBody($i); // start writing the body
		}
	}
	
	/**
	 * writeHeader will write all of the header information
	 * that the Flash client can react to.  This method is currently
	 * unimplemented and is only really used for the NetConnection Debugger
	 * and pageable record sets. 
	 */
	function writeHeader($i)
	{
		// for all header values
		// write the header to the output stream
		// ignoring header for now
	}

	/**
	 *	writeBody converts the body part of the AMFObject into the amf binary
	 *
	 * @param $i The numeric index of the body object within the AMFObject
	 */
	function writeBody($i)
	{
		$body = $this->amfout->getBodyAt($i);
		$this->out->writeUTF($body["target"]); // write the responseURI header
		$this->out->writeUTF($body["response"]); // write null, haven't found another use for this
		$this->out->writeLong(-1); // always, always there is four bytes of FF, which is -1 of course
		$this->writeData($body["value"], $body["type"]); // write the data to the output stream
	}
	
	/**
	 *	writeBoolean writes the boolean code (0x01) and the data to the output stream
	 *
	 * @param $d The boolean value
	 */
	function writeBoolean($d)
	{
		$this->out->writeByte(1); // write the boolean flag
		$this->out->writeByte($d); // write the boolean byte
	}
	
	/**
	 *	writeString writes the string code (0x02) and the UTF8 encoded
	 * string to the output stream.
	 *
	 * @param $d The string data
	 */
	function writeString($d)
	{
		$this->out->writeByte(2); // write the string code
		$this->out->writeUTF(utf8_encode($d)); // write the string value
	}
	
	/**
	 *	writeXML writes the xml code (0x0F) and the XML string to the output stream
	 *
	 * @param $d The XML string
	 */
	function writeXML($d)
	{
		$this->out->writeByte(15);
		$this->out->writeLongUTF(utf8_encode($d));
	}
	
	/**
	 *	writeData writes the date code (0x0B) and the date value to the output stream
	 *
	 * @param $d The date value
	 */
	function writeDate($d)
	{
		$this->out->writeByte(11); // write date code
		$this->out->writeDouble($d); // write date (milliseconds from 1970)
		/****************************************************************
		write timezone
		?? this is wierd -- put what you like and it pumps it back into flash at the current GMT ?? 
		have a look at the amf it creates...
		****************************************************************/
		$this->out->writeInt(0); 
	}
	
	/**
	 * writeNumber writes the number code (0x00) and the numeric data to the output stream
	 * All numbers passed through remoting are floats.
	 *
	 * @param $d The numeric data
	 */
	function writeNumber($d)
	{
		$this->out->writeByte(0); // write the number code
		$this->out->writeDouble($d); // write the number as a double
	}
	
	/**
	 * writeNull writes the null code (0x05) to the output stream
	 */
	function writeNull()
	{
		$this->out->writeByte(5); // null is only a 0x05 flag
	}

	/**
	 * writeArray first deterines if the PHP array contains all numeric indexes
	 * or a mix of keys.  Then it either writes the array code (0x0A) or the
	 * object code (0x03) and then the associated data.
	 *
	 * @param $d The php array
	 */
	function writeArray($d)
	{
		if (is_array($d)) { // make damn certain the input is an array
			$numeric = array(); // holder to store the numeric keys
			$string = array(); // holder to store the string keys
			$len = count($d); // get the total number of entries for the array
			foreach($d as $key => $data) { // loop over each element	
				if(is_int($key) && ($key >= 0)) { // make sure the keys are numeric
					$numeric[$key] = $data; // The key is an index in an array
				} else {
					$string[$key] = $data; // The key is a property of an object
				}
			}
			$num_count = count($numeric); // get the number of numeric keys
			$str_count = count($string); // get the number of string keys
			
			if ($num_count > 0) { // if there are numeric keys
				$this->array_empty_fill($numeric); // null fill the empty slots to preseve sparsity
				$num_count = count($numeric); // get the new count
			}
			
			if ($num_count > 0 && $str_count > 0) { // this is a mixed array
				$this->out->writeByte(8); // write the mixed array code
				$this->out->writeLong($num_count); // write the count of items in the array
				$this->writeObject($numeric + $string); // write the numeric and string keys in the mixed array
			} else if ($num_count > 0) { // this is just an array
				$this->out->writeByte(10); // write the mixed array code
				$this->out->writeLong($num_count); // write the count of items in the array
				for($i=0 ; $i < $num_count ; $i++) { // write all of the array elements
					$this->writeData($numeric[$i]);
				}
			} else { // this is an object
				$this->out->writeByte(3); // this is an object so write the object code
				$this->writeObject($string); // write the object name/value pairs
			}
		}
	}

	/**
	 *	array_empty_fill fills in all of the empty numeric slots with null to preserve the
	 * indexes of a sparse array.
	 */
	function array_empty_fill(&$array, $fill = NULL)
	{ 
		$indexmax = -1;
		for (end($array); $key = key($array); prev($array)) { // loop over the array
			if (is_int($key)) { // if the key is an integer
				if ($key > $indexmax) { // is this key greater than the previous max
					$indexmax = $key; // save this key as the high
				}
			}
		} 
		for ($i = 0; $i <= $indexmax; $i++) { // loop over all possible indexes from 0 to max
			if (!isset($array[$i])) { // is it set already
				$array[$i] = $fill; // fill it with the $fill value
			}
		} 
		ksort($array); // resort the keys
		reset($array); // reset the pointer to the beginning
	}
	
	/**
	 *	writeObject handles writing a php array with string or mixed keys.  It does
	 * not write the object code as that is handled by the writeArray and this method
	 * is shared with the CustomClass writer which doesn't use the object code.
	 *
	 * @param $d The php array with string keys
	 */
	function writeObject($d)
	{
		foreach($d as $key => $data) { // loop over each element	
			$this->out->writeUTF($key); // write the name of the object
			$this->writeData($data); // write the value of the object
		}
		$this->out->writeInt(0); // write the end object flag 0x00, 0x00, 0x09
		$this->out->writeByte(9);
	}
	
	/**
	 *	writePHPObject takes an instance of a class and writes the variables defined
	 * in it to the output stream.
	 * To accomplish this we just blanket grab all of the object vars with get_object_vars
	 *
	 * @param object The object to serialize the properties
	 */
	 function writePHPObject($d)
	 {
	 	$this->writeCustomClass(get_class($d), get_object_vars($d));
	 }
	
	/**
	 * writeRecordSet is the abstracted method to write a custom class recordset object.
	 * Any recordset from any datasource can be written here, it just needs to be properly formatted
	 * beforehand.
	 *
	 * @param $rs The formatted RecordSet object
	 */

	function writeRecordSet($rs)
	{	
		$RecordSet = array(); // create the RecordSet object
		$RecordSet["serverInfo"] = array(); // create the serverInfo array
		$RecordSet["serverInfo"]["id"] = "PHPRemoting"; // create the id field --> i think this is used for pageable recordsets
		$RecordSet["serverInfo"]["totalCount"] = $rs->numRows; // get the total number of records
		$RecordSet["serverInfo"]["initialData"] = $rs->initialData; // save the initial data into the RecordSet object
		$RecordSet["serverInfo"]["cursor"] = 1; // maybe the current record ????
		$RecordSet["serverInfo"]["serviceName"] = "doStuff"; // in CF this is PageAbleResult not here
		$RecordSet["serverInfo"]["columnNames"] = $rs->columnNames;
		$RecordSet["serverInfo"]["version"] = 1; // versioning
		$this->writeCustomClass("RecordSet", $RecordSet);	
	}
	
	/**
	 * writeCustomClass promotes the writing of the class name and data for CustomClasses
	 *
	 * @param string The class name
	 * @param object The class instance object
	 */
	function writeCustomClass ($name, $d)
	{
		$this->out->writeByte(16); // write the custom class code
		$this->out->writeUTF($name); // write the class name
		$this->writeObject($d); // write the classes data
	}
	
	/**
	 *	throwWrongDataTypeError sends the message back to the user that the 
	 * manual data type passed doesn't match the actual data type returned by
	 * the service method.
	 *
	 * @param $dt The data type that was expected but not encountered
	 */
	function throwWrongDataTypeError($dt)
	{
		trigger_error("The returned data was not of the type " . $dt);
	}

	/**
	 *	autoNegotiateType tries to determine the data type of the passed data with
	 * php's recommended is_ class of methods.
	 *
	 * @param $d the data to negotiate
	 */
	function autoNegotiateType($d)
	{
		if (is_bool($d)) { // boolean
			$this->writeBoolean($d);
			return true;
		} else if (is_string($d)) { // string
			$this->writeString($d);
			return true;	
		} else if (is_double($d)) { // double
			$this->writeNumber($d);
			return true;
		} else if (is_int($d)) { // int
			$this->writeNumber($d);
			return true;
		} else if (is_object($d)) { // object
			$this->writePHPObject($d);
			return true;
		} else if (is_array($d)) { // array
			$this->writeArray($d);
			return true;
		} else if (is_null($d)) { // null
			$this->writeNull();
			return true;
		} else if (is_resource($d)) { // resource
			$this->writeData($d, get_resource_type($d));
			return true;
		}
		return false;		
	}
	
	/**
	 * manualType allows the developer to explicitly set the type of
	 * the returned data.  The returned data is validated for most of the
	 * cases when possible.  Some datatypes like xml and date have to
	 * be returned this way in order for the Flash client to correctly serialize them
	 *
	 * mysql result appears top on the list because that will probably be the most
	 * common hit in this method.  Then the other datasource types, followed by the
	 * datatypes that have to be manually set.  Then the auto negotiatable types last.
	 * The order may be changed for optimization.
	 *
	 * @param $d The data
	 * @param $type The type of $d
	 */
	function manualType($d, $type)
	{
		$type = strtolower($type);
		$dbtype = $type; // save this here incase we need it in a few lines
		$result = strpos($type , " result"); // first check to see if this is a recordset do this here for efficiency
		if ($result !== FALSE) {
			$type = "__RECORDSET__";
		}
		switch ($type) {
			case "__RECORDSET__" :
				$resultType = substr($dbtype, 0, $result);
				$classname = $resultType . "RecordSet"; // full class name
				$includeFile = @include_once(AMFPHP_BASE . "sql/" . $classname . ".php"); // try to load the recordset library from the sql folder
				if(!$includeFile) {
					if (!@include_once($classname . ".php")) {// try from the same folder as the service
						trigger_error("The recordset filter class " . $classname . " was not found");
					}
				}
				$recordSet = new $classname($d); // returns formatted recordset
				$this->writeRecordSet($recordSet); // writes the recordset formatted for Flash
				break;		
			case "xml" :
				if (is_string($d)) {
					$this->writeXML($d);
				} else {
					$this->throwWrongDataTypeError("xml");
				}
				break;
			case "date" :
				if (is_float($d)) {
					$this->writeDate($d);
				} else {
					$this->throwWrongDataTypeError("date");
				}
				break;
			case "boolean" :
				if (is_bool($d)) {
					$this->writeBoolean($d);
				} else {
					$this->throwWrongDataTypeError("boolean");
				}
				break;
			case "string" :
				if (is_string($d)) {
					$this->writeString($d);
				} else {
					$this->throwWrongDataTypeError("string");
				}
				break;
			case "double" :
				if (is_double($d)) {
					$this->writeNumber($d);
				} else {
					$this->throwWrongDataTypeError("double");
				}
				break;
			case "integer" :
				if (is_int($d)) {
					$this->writeNumber($d);
				} else {
					$this->throwWrongDataTypeError("integer");
				}
				break;
			case "object" :
				if (is_object($d)) {
					$this->writePHPObject($d);
				} else {
					$this->throwWrongDataTypeError("object");
				}
				break;
			case "array" :
				if (is_array($d)) {
					$this->writeArray($d);
				} else {
					$this->throwWrongDataTypeError("array");
				}
				break;
			case "null" :
				if (is_null($d)) {
					$this->writeNull();
				} else {
					$this->throwWrongDataError("null");
				}
				break;
			case "resource" :
				if (is_resource($d)) {
					$this->writeData($d, get_resource_type($d));
				} else {
					$this->throwWrongDataTypeError("resource");
				}
				break;
			default:
				// non of the above so lets assume its a Custom Class thats defined in the client
				$this->writeCustomClass($type, $d);
				//trigger_error("Unsupported Datatype");
				break;
				
		}
	}
	

	/**
	 *	writeData checks to see if the type was declared and then either
	 * auto negotiates the type or relies on the user defined type to 
	 * serialize the data into amf
	 *
	 * @param $d The data
	 * @param $type The optional type
	 */
    function writeData($d, $type=-1)
	{
		if ($type==-1) {
			if ($this->autoNegotiateType($d)) {
				return; // we successfully found the type so quit
			} else {
				$type = gettype($d); // if the auto negotiate failed, try the old way
			}
		}
		$this->manualType($d, $type); // try the manual route if we didn't return already
	}
}
?>