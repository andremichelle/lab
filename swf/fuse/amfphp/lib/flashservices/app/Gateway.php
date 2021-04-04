<?php
/**
 * Gateway is the the class that provides the AMFPHP remoting service.  The Gateway class accepts
 * an amf input stream and builds the additional classes necessary to deserialize, execute, and serialize
 * the results back into amf.
 *
 * @author    Musicman  original design
 * @author    Justin Watkins  Gateway architecture, class structure, datatype io additions
 * @author    John Cowen  Datatype io additions, class structure, 
 * @author    Klaasjan Tukker Modifications, check routiines, and register-framework
 * @version    0.5
 */

define("AMFPHP_BASE", dirname(dirname(__FILE__)) . "/");

require_once(AMFPHP_BASE."io/AMFInputStream.php");
require_once(AMFPHP_BASE."io/AMFDeserializer.php");
require_once(AMFPHP_BASE."app/Executive.php");
require_once(AMFPHP_BASE."io/AMFSerializer.php");
require_once(AMFPHP_BASE."io/AMFOutputStream.php");
require_once(AMFPHP_BASE."exception/Exceptions.php");
require_once(AMFPHP_BASE."util/AMFObject.php");
require_once(AMFPHP_BASE."util/Authenticate.php");

class Gateway
{
    var $exec; // The executive object
    var $debugdir = "../dump/"; // The default location of the debugging dump directory
    var $deserializer; // The deserializer object
    var $amfin; // The input stream object
    var $amfout; // The output stream object
    var $service_browser_header = "DescribeService"; // The amf header used by the service browser
	var $credentials_header = "Credentials"; // The amf header used to set credentials
    var $callback_header = "/onResult"; // The string flash expects from a successful method call
    var $amf_header = "Content-type: application/x-amf"; // The value of the HTTP content header expected by Flash

	/**
	 * Constructor method initializes the Executive class and initializes the global method index property
	 * for proper error reporting
	 *
	 */
	function Gateway()
	{
		$GLOBALS['_lastMethodCall'] = "/1";
		$this->exec = new Executive();
	}
    
    /**
     * The service method runs the gateway application.  It turns the gateway 'on'.  You
     * have to call the service method as the last line of the gateway script after all of the
     * gateway configuration properties have been set.
     *
     * Right now the service method also includes a very primitive debugging mode that
     * just dumps the raw amf input and output to files.  This may change in later versions.
     * The debugging implementation is NOT thread safe so be aware of file corruptions that
     * may occur in concurrent environments.
     *
     */
    function service() 
    {
        global $debug;
        if ($debug) {
            $this->_saveRawDataToFile ($this->debugdir."input.amf", $GLOBALS["HTTP_RAW_POST_DATA"]);
        }
        
		$inputStream = new AMFInputStream($GLOBALS["HTTP_RAW_POST_DATA"]); // wrap the raw data with the input stream
        $deserializer = new AMFDeserializer($inputStream); // deserialize the data
        $amfin = $deserializer->getAMFObject(); // grab the deserialized object
        $amfout = new AMFObject(); // create a new amfobject to store the output
        
		$headercount = $amfin->numHeader(); // count the number of header
        for ($i=0; $i<$headercount; $i++) { // loop over the headers
            $header = $amfin->getHeaderAt($i); // get the current header
            if ($header['key'] == $this->service_browser_header) { // is this the service browser header
                $this->exec->addHeaderFilter($header); // tell the executive that
            } else if($header['key'] == $this->credentials_header){
				$this->exec->addHeaderFilter($header); // tell the executive that
			}
        }
        
        $bodycount = $amfin->numBody(); // get the body count
        for ($i=0; $i<$bodycount; $i++) {
            $body = $amfin->getBodyAt($i);
            $GLOBALS['_lastMethodCall'] = $body["response"]; // update the error call back indicator
            $this->exec->setClassPath($body["target"]); // set the class path
            $results = $this->exec->doMethodCall($body["value"]); // execute the method
            $returnType = $this->exec->getReturnType(); // get the declared return type
            $amfout->addBody($body["response"].$this->callback_header, "null", $results, $returnType); // add the item to the amf out object
        }
        
        $outstream = new AMFOutputStream(); // create an output stream wrapper
        $serializer = new AMFSerializer($outstream); // create the serializer
        $serializer->serialize($amfout); // serialize the amfout object

        if ($debug){
            $this->_saveRawDataToFile($this->debugdir."results.amf", $outstream->flush());
        }
		header($this->amf_header); // define the proper header
        print($outstream->flush()); // flush the binary data
    }
	/**
	 * Setter for the debugging directory property
	 *
	 * @param dir    The directory to store debugging files.
	 */
    function setDebugDirectory($dir)
	{ 
        $this->debugdir = $dir;
    }
    
    /**
     * Set an instance name for this gateway instance
     * Setting an instance name is used for restricted access to a gateway
     * If a gateway has an instance name, only service methods that have a matching instance
     * name can be used with the gateway
     *
     * @param    name    The instance name to bind to the gateway instance
     */
    function setInstanceName($name = "Instance1")
    {
        $this->exec->setInstanceName($name);
    }
    
    /**
     * Sets the base path for loading service methods.
     *
     * Call this method to define the directory to look for service classes in.
     * Relative or full paths are acceptable
     *
     * @param    path        The path the the service class directory
     */
    function setBaseClassPath($path) 
    {
        $this->exec->setBaseClassPath($path);
    }
    
	/**
	 * usePearSOAP is a method to disable the use of the PEAR::SOAP package.
	 * This method should only be called if PEAR::SOAP is installed and the
	 * preference is nuSoap.
	 *
	 * @param boolean Boolean whether to use the pear soap package
	 */
	 function usePearSOAP($bool = true)
	 {
	 	$this->exec->usePearSOAP($bool);
	 }
	
    /**
     * Dumps data to a file
     *
     * @param    filepath        The location of the dump file
     * @param    data            The data to insert into the dump file
     */
    function _saveRawDataToFile($filepath, $data)
    {
        if (!$handle = fopen($filepath, 'w')) {
             exit;
        }
        if (!fwrite($handle, $data)) {
            exit;
        }
        fclose($handle);
    }

    /**
     *    Appends data to a file
     *
     * @param    filepath        The location of the dump file
     * @param    data            The data to append to the dump file
     */
    function _appendRawDataToFile($filepath, $data)
    {
        if (!$handle = fopen($filepath, 'a')) {
             exit;
        }
        if (!fwrite($handle, $data)) {
            exit;
        }
        fclose($handle);
    }
    
    /**
     * Loads raw amf data from a file
     *
     * @param      filepath        The location of the dump file
     * @returns    The contents from the file
     */
    function _loadRawDataFromFile($filepath)
    {
        $handle = fopen($filepath, "r");
        $contents = fread($handle, filesize($filepath));
        fclose($handle);
        return $contents;
    }

    /**
     * Passes the content through to the appendRawDataToFile method
     *
     * @param    content    The content to append to the data file.
     */
    function debug($content) {
        $this->_appendRawDataToFile($this->debugdir."processing.txt",$content."\n");
    }
}
?>