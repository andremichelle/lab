<?php
/**
 * Executive loads the service class and executes the targeted method from the amfphp call
 * 
 *
 * @author    Musicman original design
 * @author    Justin Watkins Gateway architecture, class structure, datatype io additions
 * @author    John Cowen Datatype io additions, class structure
 * @author    Klaasjan Tukker Modifications, check routines
 * @version   0.5
 */
class Executive
{
    var $_basecp = "services/"; // This is the directory "services/" relative to the gateway
    var $_classpath; // the classpath which is the path of the file from $_basecp
    var $_classname; // the string name of the class derived from the classpath
    var $_classConstruct; // the object we build from the class
    var $_methodname; // the method to execute in the construct
    var $_returnType=-1; // the defined return type
    var $_instanceName; // the instance name to use for this gateway executive
    var $services = array(); // the list with registered service-classes
    var $_incomingcp; // The original incoming classpath
    var $_origClassPath; // The original classpath
	var $_usePear = true; // Boolean determining if we default to use the pear soap module
    var $_headerFilters = array(); // switch to take different actions based on the headers
	var $_isWebServiceURI = false; // was the uri a web service?
	var $_webServiceURI;
	var $_webServiceMethod;
	var $_nusoapInstalled;
	var $_pearInstalled;
    
    /**
     * Executive constructor function
     */
    function Executive()
    {
		//nothing here
    }
    
    /**
     * Sets the header current amfheader name. 
     * The executive will perform different actions depending on various
     * Headers passed by the remoting client
     *
     * @param header    A header string
     */
    function addHeaderFilter($header)
    {
        $this->_headerFilters[$header['key']] = $header;
    }
    
	/**
	 * Sets the boolean usePear switch
	 */
	function usePearSOAP($bool=true)
	{
		$this->_usePear = $bool;
	}
	
    /**
     * Sets the class path so the Executive will know where to find service class files
     *
     * @param basecp    The base class path directory
     */
    function setBaseClassPath($basecp) 
	{
        $this->_basecp = $basecp; 
    }
    
    /**
     * setInstanceName binds this gateway to a string
     * When this happens only services with a matching instance name
     * may be used with this gateway instance
     *
     * @ param    name    The instance name to bind to
     */
    function setInstanceName($name)
    {
        $this->_instanceName = $name;
    }
    
    /**
     * Through remoting you pass a . delimited path to the service class
     * without the extension.  This method translates that string into a real path
     *
     * @param cp    The full . delimited class path from the flash client
     */
    function setClassPath($cp)
    {
		if (strpos($cp, "http://") === false && strpos($cp, "https://") === false) { // check for a http link which means web service
			$this->_incomingcp = $cp;
			$lpos = strrpos($cp, ".");
			if ($lpos === false) {
				// throw an error because there has to be atleast 1
			} else {
				$this->_methodname = substr($cp, $lpos+1);
			}
			$trunced = substr($cp, 0, $lpos);
			$this->_origClassPath = $trunced;
			$lpos = strrpos($trunced, ".");
			if ($lpos === false) {
				$this->_classname = $trunced;
				$this->_classpath = $this->_basecp . $trunced.".php";
			} else {
				$this->_classname = substr($trunced, $lpos+1);
				$this->_classpath = $this->_basecp .str_replace(".", "/", $trunced) . ".php"; // removed to strip the basecp out of the equation here
				
			}
		} else { // launch a web service and not a php service
			$this->_isWebServiceURI = true;
			$rdot = strrpos($cp, ".");
			$this->_webServiceURI = substr($cp, 0, $rdot);
			$this->_webServiceMethod = substr($cp, $rdot + 1);
		}
    }

	/**
	 *	negotiate whether the PEAR SOAP is installed, if not then if nuSOAP is installed.
	 */
	function consumeWebService($a)
	{
		if ($this->_usePear) { // don't load PEAR::SOAP if it's not wanted.
									   	   // There are also name space conflicts between the 2 packages.
			return $this->pearSoapImpl($a); // run the pear implementation	
		} else {
			return $this->nuSoapImpl($a); // run the nuSoap implementation
		}
	}
	
	/**
	 * The nuSoap client implementation
	 */
	 function nuSoapImpl($a)
	 {
		$this->_nusoapInstalled = @include_once(AMFPHP_BASE."lib/nusoap.php");
		if ($this->_nusoapInstalled) {
			$soapclient = new soapclient($this->_webServiceURI, true); // create a instance of the SOAP client object
			if (count($a) == 1 && is_array($a)) {
				$result = $soapclient->call($this->_webServiceMethod, $a[0]); // execute without the proxy
			} else {
				$proxy = $soapclient->getProxy();
				$result = call_user_func_array(array($proxy, $this->_webServiceMethod), $a);
			}
			return $result;
		} else {
			trigger_error("You must install a soap package, both PEAR::SOAP and nuSOAP are supported", E_USER_ERROR);
		}
	 }
	
	/**	
	* The PEAR::SOAP client implementation
	*/
	function pearSOAPImpl($a)
	{
		$this->_pearInstalled = @include_once "SOAP/Client.php"; // load the PEAR::SOAP implementation
		if ($this->_pearInstalled) {
			$client = new SOAP_Client($this->_webServiceURI);
			$response = $client->call($this->_webServiceMethod, $a);
			return $response;
		} else {
			$this->_usePear = false;
			$this->nuSoapImpl($a);
		}
	}
	
    /**
     * Returns the return type for the called remote method
     * 
     * @returns        The defined return type from the service class's method table
     */
    function getReturnType()
    {
        return $this->_returnType;
    }

	
	/**
	 *	filterCustomClassArguments takes the reference to the arguments property and
	 * checks each argument to see if it has an _explicitType property, which means it was created
	 * by the deserializer and should be passed through as an instance that custom class defined by the service
	 *
	 * @param object Reference to the passed arguments
	 */
	function filterCustomClassArguments (&$a)
	{
		foreach($a as $argument => $obj) {
			if(isset($obj['_explicitType'])) {// get the name of the flash registered class and remove the label from the object
				$customclassname = $obj['_explicitType'];
				unset($obj['_explicitType']);
				if(class_exists($customclassname)) {
					// create instance of custom class in php
					// and add its properties from flash
					$customclass = new $customclassname();
					foreach($obj as $prop => $value) {
						$customclass->$prop = $value;
					}
					// reset the argument as the new instance
					$a[$argument] = &$customclass;
				} else {
					// Probably better to pass anyway, and maybe send warning to NC Debug, if enabled
					// trigger_error("Custom Class " . $_customclassname . " does not exist", E_USER_ERROR);
				}
			}
		}
	}
	
	/**
	 * loadServiceClass grabs the service class file
	 */
	 function loadServiceClass ()
	 {
		$this->_calledMethod = $this->_methodname;
		$fileExists = @include_once($this->_classpath); // include the class file
		if($fileExists && class_exists($this->_classname)) { // Just make sure the class name is the same as the file name
			chdir(dirname($this->_classpath)); // change the cwd to the to the dir with the class
		} else { // Class not found exception
			trigger_error("no class named " . $this->_classname . " is known to the gateway", E_USER_ERROR);
		}
	 }
	
	/**
	 * extendServiceClass's job is to prepend the RemotingService name to the class name and actually package
	 * the remoting service class so it's ready to be built
	 */
	function extendServiceClass ()
	{
		$this->_finalclassname = "RemotingService_" . $this->_classname;  // append the class name to our RemotingService class
		if (!class_exists($this->_finalclassname)) { // only do this once
			$SuperClass = $this->_classname;
			include(AMFPHP_BASE."util/RemotingService.php");
			eval($RemotingSubClass); // evaluate the string as code
		}	
	}
	
	function handleHeaders ()
	{
		if (isset($this->_headerFilters['DescribeService'])) { // catch the Describe service header
			$this->_methodname = "__describeService"; // override the method name if one was sent
			$this->_classConstruct->methodTable[$this->_methodname]['instance'] = $this->_instanceName;
		}
		if(isset($this->_headerFilters['Credentials'])) // catch credentials service header
		{
			$credentials = &$this->_headerFilters['Credentials']['value'];
			if (method_exists($this->_classConstruct, "_authenticate")) {
				$roles = $this->_classConstruct->_authenticate($credentials['userid'], $credentials['password']);
				if ($roles !== false) {
					Authenticate::login($credentials['userid'], $roles);
				} else {
					Authenticate::logout();
				}
			} else {
				trigger_error("The _authenticate method was not found", E_USER_ERROR);
			}
		}	
	}
	
	function checkIfClassExists ()
	{
		if (!isset($this->_classConstruct->methodTable[$this->_methodname])) { // check to see if the methodTable exists
			trigger_error("Function " . $this->_calledMethod . " does not exist in class ".$this->_classConstruct.".",E_USER_ERROR);			
		}
	}
	
	function checkAccess ()
	{
		if (!isset($this->methodrecord['access']) || (strtolower($this->methodrecord['access']) != "remote")) { // make sure we can remotely call it
			trigger_error("Access Denied to " . $this->_calledMethod ,E_USER_ERROR);
		}
	}
	
	function handleAlias ()
	{
		if (isset($this->methodrecord['alias'])) { // see if it's an alias
			$this->_methodname = $this->methodrecord['alias'];
		}
	}
	
	function checkInstanceRestriction ()
	{
		if (isset($this->_instanceName)) { // see if we have an instance defined
			if ($this->_instanceName != $this->methodrecord['instance']) { // if the names don't match die   
				trigger_error("The method is not allowed through this restricted gateway!" ,E_USER_ERROR);
			}
		} else if (isset($this->methodrecord['instance'])) { // see if the method has an instance defined
			if ($this->_instanceName != $this->methodrecord['instance']) { // if the names don't match die   
				trigger_error("The restricted method is not allowed through a non-restricted gateway" ,E_USER_ERROR);
			}
		}
	}
	
	function checkRoles ()
	{
		if(isset($this->methodrecord['roles'])) {
			if(!Authenticate::isUserInRole($this->methodrecord['roles'])) {
				trigger_error("This user is not does not have access to " . $this->_methodname, E_USER_ERROR);
			}
		}
	}
	
	function handleReturnType ()
	{
		if (isset($this->methodrecord['returns'])) { // check to see if it specifies a return type
			$this->_returnType = $this->methodrecord['returns'];
		} else {
			$this->_returnType = -1;
		}
	}	
	
	function checkMethodDefinition ()
	{
		if (!method_exists($this->_classConstruct, $this->_methodname)) { // finally see if it is actually defined
			trigger_error("Function " . $this->_calledMethod . " does not exist in class ".$this->_classConstruct.".",E_USER_ERROR);
		}
	}
	
    /**
     * The main method of the executive class. 
     * 
     * @param    a    Arguments to pass to the method
     */
    function doMethodCall($a)
    {
		if ($this->_isWebServiceURI) {
			return $this->consumeWebService($a); // run the web service
		} else {
			$this->loadServiceClass(); // load the service class file
			$this->extendServiceClass(); // extend the service class
			$this->filterCustomClassArguments($a); // check for custom class arguments
			$this->_classConstruct = new $this->_finalclassname($this->_classname); // build the extended service class
			$this->_classConstruct->setClassPath($this->_origClassPath); // pass the class path
			$this->handleHeaders();
			$this->checkIfClassExists();
			$this->methodrecord =& $this->_classConstruct->methodTable[$this->_methodname]; // create a shortcut for the ugly path
			$this->checkAccess();
			$this->handleAlias();
			$this->checkInstanceRestriction();
			$this->checkRoles();
			$this->handleReturnType();
			$this->checkMethodDefinition();
			return call_user_func_array ( array(&$this->_classConstruct, $this->_methodname), $a); // do the magic
		}
    }
}
?>